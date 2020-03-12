<?php

namespace Craw\Http;

use Craw\Logger\NullLogger;
use function Craw\data_to_string;
use function Craw\dump;
use const http\Client\Curl\Versions\CURL;

abstract class Curl {
	public static $default_timeout = 10;
	public static $logger;

	const REQUEST_METHOD_GET = 'GET';
	const REQUEST_METHOD_POST = 'POST';
	const REQUEST_METHOD_DELETE = 'DELETE';
	const REQUEST_METHOD_PUT = 'PUT';

	/**
	 * @param $url
	 * @param array|string|null $data
	 * @param $extra_curl_option
	 * @return \Craw\Http\Result
	 */
	public static function getContent($url, $data = null, $extra_curl_option = null){
		return self::request($url, $data, self::REQUEST_METHOD_GET, $extra_curl_option);
	}

	/**
	 * @param $url
	 * @param array|string|null $data
	 * @param null $extra_curl_option
	 * @return \Craw\Http\Result
	 */
	public static function postContent($url, $data = null, $extra_curl_option = null){
		return self::request($url, $data, self::REQUEST_METHOD_POST, $extra_curl_option);
	}

	/**
	 * CURL请求
	 * @param $url
	 * @param array|string|null $data
	 * @param string $request_method
	 * @param array|null|callable $extra_curl_option 额外CURL选项，如果是闭包函数，传入第一个参数为ch
	 * @return \Craw\Http\Result
	 */
	public static function request($url, $data = null, $request_method = self::REQUEST_METHOD_GET, $extra_curl_option = null){
		$logger = self::$logger ?: new NullLogger();
		$curl_option = [
			CURLOPT_RETURNTRANSFER => true, //返回内容部分
			CURLOPT_TIMEOUT        => self::$default_timeout,
			CURLOPT_ENCODING       => 'gzip',
		];

		//处理request method
		switch($request_method){
			case self::REQUEST_METHOD_POST:
				$curl_option[CURLOPT_POST] = true;
				$curl_option[CURLOPT_POSTFIELDS] = data_to_string($data);
				break;
			case self::REQUEST_METHOD_GET:
				if($data){
					$url .= (strpos($url, '?') !== false ? '&' : '?').data_to_string($data);
				}
				break;
			case self::REQUEST_METHOD_DELETE:
			case self::REQUEST_METHOD_PUT:
			default:
				$curl_option[CURLOPT_POSTFIELDS] = data_to_string($data);
				$curl_option[CURLOPT_CUSTOMREQUEST] = $request_method;
				break;
		}

		$curl_option[CURLOPT_URL] = $url;
		if(stripos($url, 'https://') !== false){
			$curl_option[CURLOPT_SSL_VERIFYPEER] = 0;
			$curl_option[CURLOPT_SSL_VERIFYHOST] = 1;
		}

		$ch = \curl_init();

		//额外CURL选项
		if($extra_curl_option && is_callable($extra_curl_option)){
			$extra_curl_option = $extra_curl_option($ch);
		}

		$curl_option = self::mergeCurlOptions($curl_option, $extra_curl_option);
		$logger("Start Fetching $url ...");

		\curl_setopt_array($ch, $curl_option);

		$content = \curl_exec($ch);
		$result = new Result($url, $content, $ch);
		curl_close($ch);

		$logger($result->getResultMessage());
		return $result;
	}

	/**
	 * 并发获取内容
	 * @param $urls
	 * @param array $control_option
	 * @param array $extra_common_curl_option
	 * @return Result[]
	 * @throws \Exception
	 */
	public static function getContents($urls, $control_option = [], $extra_common_curl_option = []){
		$logger = self::$logger ?: new NullLogger();
		$rolling_count = $control_option['rolling_count'] ?: 10;
		$on_item_finish = $control_option['on_item_finish'];
		$batch_interval_time = $control_option['batch_interval_time']; //每批请求之间间隔时间

		//公用CURL选项
		$common_option = self::mergeCurlOptions([
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
		while($urls){
			$current_tasks = array_slice($urls, 0, $rolling_count);
			$urls = array_slice($urls, $rolling_count);
			$curl_handlers = [];
			foreach($current_tasks as $k => $url){
				$check_index++;
				$ch = curl_init();
				$curl_options = self::mergeCurlOptions($common_option, [
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
		$logger->log('ALL TASK DONE');
		curl_multi_close($mh);
		return $results;
	}

	/**
	 * 合并CURL选项
	 * @param mixed ...$options
	 * @return array
	 */
	public static function mergeCurlOptions(...$options){
		$ret = [];
		$options = array_reverse($options);
		foreach($options as $opt){
			if($opt instanceof CurlOption){
				$option = $opt->getCurlOption();
			}else{
				$option = $opt;
			}
			foreach($option ?: [] as $k => $v){
				$ret[$k] = $v;
			}
		}
		return $ret;
	}

	/**
	 * 打印CURL选项
	 * @param $options
	 * @return array
	 */
	public static function getCurlOptionPrints($options){
		static $all_const_list;
		if(!$all_const_list){
			$all_const_list = get_defined_constants();
		}
		$prints = [];
		foreach($all_const_list as $text => $v){
			if(stripos($text, 'CURLOPT_') === 0 && isset($options[$v])){
				$prints[$text] = $options[$v];
			}
		}
		return $prints;
	}
}