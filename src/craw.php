<?php

namespace LFPhp\Craw;

use Exception;
use LFPhp\Logger\Logger;
use function LFPhp\Func\curl_concurrent;
use function LFPhp\Func\curl_get;
use function LFPhp\Func\curl_post;
use function LFPhp\Func\format_size;

const CRAW_CACHE_TIMEOUT = 86400;

/**
 * 生成多页URL链接
 * @param string $url_pattern url模式
 * @param int $page_total
 * @param int $page_start
 * @return array
 * @throws \Exception
 */
function craw_make_page_urls($url_pattern, $page_total, $page_start = 1){
	$urls = [];
	if(strpos($url_pattern, '%p') === false){
		throw new Exception('%p pattern required');
	}
	for($i = $page_start; $i <= $page_total; $i++){
		$urls[] = str_replace('%p', $i, $url_pattern);
	}
	return $urls;
}

/**
 * 命中正则时替换，否则返回原字符串
 * @param string $string
 * @param string $pattern
 * @param string $replacement
 * @param int $replace_count
 * @return string
 */
function craw_replace_on_matched($string, $pattern, $replacement = '$1', &$replace_count = 0){
	$str = preg_replace($pattern, $replacement, $string, -1, $replace_count);
	return $replace_count ? $str : $string;
}

/**
 * curl_get 缓存版，使用所有参数作为key
 * @param string $url
 * @param array|null $data
 * @param array $curl_option
 * @return mixed|null
 */
function craw_curl_get_cache($url, $data = null, $curl_option = []){
	return craw_curl_request_cache($url, $data, $curl_option, false);
}

/**
 * curl_post 缓存版，使用所有参数作为key
 * @param string $url
 * @param array|null $data
 * @param array $curl_option
 * @return mixed|null
 */
function craw_curl_post_cache($url, $data, $curl_option = []){
	return craw_curl_request_cache($url, $data, $curl_option, false);
}

/**
 * @param $url
 * @param $data
 * @param $curl_option
 * @param $is_post
 * @return mixed|null
 */
function craw_curl_request_cache($url, $data, $curl_option, $is_post){
	$cache_key = func_get_args();
	$cache_hit = craw_cache_hit($cache_key);
	$cache_file = craw_cache_file($cache_key);
	$ret = craw_cache($cache_key, function() use ($url, $data, $curl_option, $is_post){
		Logger::debug('[Req]', $url, $data);
		Logger::debug('[Curl OPT]', $curl_option);
		$ret = $is_post ? curl_post($url, $data, $curl_option) : curl_get($url, $data, $curl_option);
		return $ret ?: null;
	});
	if($cache_hit){
		Logger::debug('Cache Hit: '.$url.' <<< '.$cache_file.' ('.format_size(filesize($cache_file)).')');
	}else{
		Logger::debug('[Cache Set]', $url, $cache_file);
	}
	return $ret;
}

/**
 * curl_concurrent 缓存版，
 * 当前版本仅支持使用url作为缓存key
 * @param callable|array $curl_option_fetcher : array 返回CURL选项映射数组，即使只有一个url，也需要返回 [CURLOPT_URL=>$url]
 * @param callable|null $on_item_start ($curl_option) 开始执行回调，如果返回false，忽略该任务
 * @param callable|null $on_item_finish ($curl_ret, $curl_option) 请求结束回调，参数1：返回结果数组，参数2：CURL选项
 * @param int $rolling_window 滚动请求数量
 * @return bool
 * @throws \Exception
 */
function craw_curl_concurrent_cache($curl_option_fetcher, $on_item_start = null, $on_item_finish = null, $rolling_window = 10){
	return curl_concurrent($curl_option_fetcher, function($curl_option) use ($on_item_start, $on_item_finish){
		$url = $curl_option[CURLOPT_URL];
		if($on_item_start && $on_item_start($curl_option) === false){
			Logger::warning('[REQ BRK]', $url);
			return false;
		}
		$data = craw_cache_get($url);
		if(isset($data)){
			Logger::debug('[Cache Hit]', $url);
			$on_item_finish && $on_item_finish($data, $curl_option);
			return false;
		}else{
			Logger::debug('[REQ]', $url);
		}
		return true;
	}, function($ret, $curl_option) use ($on_item_finish){
		//$ret['info']['url'] 可能是经过跳转解析的URL，而不是原始选项的URL，这里为了缓存，使用原始URL
		$origin_url = $curl_option[CURLOPT_URL];
		craw_cache_set($origin_url, $ret);
		Logger::debug('[RSP]', $origin_url, strlen($ret['body']).' bytes');
		$on_item_finish && $on_item_finish($ret, $curl_option);
	}, $rolling_window);
}

