<?php

namespace LFPhp\Craw;

use LFPhp\Logger\Logger;
use function LFPhp\Func\array_merge_assoc;

/**
 * 获取CURL选项
 * @param $url
 * @param $custom_option
 * @return array
 */
function curl_default_option($url = '', $custom_option = []){
	$curl_options = array_merge_assoc([
		CURLOPT_RETURNTRANSFER => true, //返回内容部分
		CURLOPT_HEADER         => true,
		CURLOPT_ENCODING       => 'gzip',
		CURLOPT_TIMEOUT        => 10,
	], $custom_option);

	if($url){
		$curl_options[CURLOPT_URL] = $url;
		if(stripos($url, 'https://') !== false){
			$curl_options[CURLOPT_SSL_VERIFYPEER] = 0;
			$curl_options[CURLOPT_SSL_VERIFYHOST] = 1;
		}
	}
	return $curl_options;
}

function curl_concurrent($curl_option_fetcher, $option = []){
	$option = array_merge_assoc([
		'rolling_window' => 10,
		'on_item_start'  => null,
		'on_item_finish' => null,
	], $option);

	$mh = curl_multi_init();
	$rolling_window = $option['rolling_window'];
	$running_count = 0;
	do{
		$curl_opt = $curl_option_fetcher();
		if(!$curl_opt){
			Logger::warning('no task fetched');
			break;
		}
		$option['on_item_start']($curl_opt);
		$ch = curl_init();
		curl_setopt_array($ch, $curl_opt);
		curl_multi_add_handle($mh, $ch);
		$running_count++;

		$still_running = 0;
		do{
			$status = curl_multi_exec_full($mh, $still_running);
		} while($still_running && $status == CURLM_OK);

		//把所有已完成的任务都处理掉, curl_multi_info_read执行一次读取一条
		while($curl_result = curl_multi_info_read($mh)){
			$info = curl_getinfo($curl_result['handle']);
			$info['head'] = $info['body'] = null;
			$error = curl_error($curl_result['handle']) ?: null;
			if($info['http_code'] == 200){
				$raw_string = curl_multi_getcontent($curl_result['handle']); //获取结果
				$header_size = curl_getinfo($ch, CURLINFO_HEADER_SIZE);
				$info['head'] = substr($raw_string, 0, $header_size);
				$info['body'] = substr($raw_string, $header_size);
			}
			$option['on_item_finish']($info, $error);
			curl_multi_remove_handle($mh, $curl_result['handle']);
			curl_close($curl_result['handle']);
			$running_count--;
		}
		//用间隔时间来控制启动发送任务的频率
//		usleep(100000);
	} while($running_count < $rolling_window);
	curl_multi_close($mh);
	return true;
}

function curl_multi_exec_full($mh, &$still_running){
	do{
		$state = curl_multi_exec($mh, $still_running);
	} while($still_running > 0 && $state === CURLM_CALL_MULTI_PERFORM && curl_multi_select($mh, 0.1));
	return $state;
}

function curl_multi_wait($mh, $minTime = 0.001, $maxTime = 1){
	$umin = $minTime*1000000;

	$start_time = microtime(true);
	$num_descriptors = curl_multi_select($mh, $maxTime);
	if($num_descriptors === -1){
		usleep($umin);
	}

	$timespan = (microtime(true) - $start_time);
	if($timespan < $umin){
		usleep($umin - $timespan);
	}
}
