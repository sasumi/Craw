<?php

namespace LFPhp\Craw\Http;

use LFPhp\Logger\Logger;
use function curl_init;
use function curl_setopt_array;
use function LFPhp\Func\curl_merge_options;

abstract class Curl {
	public static $default_timeout = 10;

	/**
	 * 并发获取内容
	 * @param $urls
	 * @param array $control_option
	 * @param array $extra_common_curl_option
	 * @return Result[]
	 * @throws \Exception
	 */
	public static function getContents($urls, $control_option = [], $extra_common_curl_option = []){
		$logger = Logger::instance(__CLASS__);
		$rolling_count = $control_option['rolling_count'] ?: 10;
		$on_item_finish = $control_option['on_item_finish'];
		$batch_interval_time = $control_option['batch_interval_time']; //每批请求之间间隔时间

		//公用CURL选项
		$common_option = curl_merge_options([
			CURLOPT_RETURNTRANSFER => true, //返回内容部分
			CURLOPT_HEADER         => true,
			CURLOPT_ENCODING       => 'gzip',
			CURLOPT_TIMEOUT        => self::$default_timeout,
		], $extra_common_curl_option);

		set_time_limit($common_option[CURLOPT_TIMEOUT]*count($urls));

		$results = [];
		$check_index = 0;
		$done_index = 0;
		$total = count($urls);
		$mh = curl_multi_init();
		$logger('Batch fetch contents:', $urls);
		while($urls){
			$current_tasks = array_slice($urls, 0, $rolling_count);
			$urls = array_slice($urls, $rolling_count);
			$curl_handlers = [];
			foreach($current_tasks as $k => $url){
				$check_index++;
				$ch = curl_init();
				$curl_options = curl_merge_options($common_option, [
					CURLOPT_URL => $url,
				]);
				if(stripos($url, 'https://') !== false){
					$curl_options[CURLOPT_SSL_VERIFYPEER] = 0;
					$curl_options[CURLOPT_SSL_VERIFYHOST] = 1;
				}
				curl_setopt_array($ch, $curl_options);
				curl_multi_add_handle($mh, $ch);
				$curl_handlers[$k] = [$ch, $url];
				$logger("Start Fetching [$check_index/$total] $url ...");
			}

			// execute the handles
			$running = null;
			do{
				curl_multi_exec($mh, $running);
			} while($running > 0);

			// get content and remove handles
			foreach($curl_handlers as $k => list($ch, $url)){
				$done_index++;
				$rst = new Result($url, curl_multi_getcontent($ch), $ch);
				$results[] = $rst;
				curl_multi_remove_handle($mh, $ch);
				if($on_item_finish){
					call_user_func($on_item_finish, $rst);
				}
				$logger("Content Fetched [$done_index/$total] $url ".$rst->getResultMessage());
			}
			if($urls && $batch_interval_time){
				sleep($batch_interval_time);
			}
		}
		$logger('ALL TASK DONE');
		curl_multi_close($mh);
		return $results;
	}
}