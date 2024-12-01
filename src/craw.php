<?php

namespace LFPhp\Craw;

use Exception;
use LFPhp\Logger\Logger;
use function LFPhp\Func\curl_concurrent;
use function LFPhp\Func\curl_get;
use function LFPhp\Func\curl_post;
use function LFPhp\Func\curl_query_success;
use function LFPhp\Func\format_size;

/**
 * make page url list
 * @param string $url_pattern
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
 * replace string via regexp
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
 * curl_get cache
 * @param string $url
 * @param array|null $data
 * @param array $curl_option
 * @return array|null
 */
function craw_curl_get_cache($url, $data = null, $curl_option = []){
	return craw_curl_request_cache($url, $data, $curl_option, false);
}

/**
 * curl_post cache
 * @param string $url
 * @param array|null $data
 * @param array $curl_option
 * @return array|null
 */
function craw_curl_post_cache($url, $data, $curl_option = []){
	return craw_curl_request_cache($url, $data, $curl_option, false);
}

/**
 * craw request with caching
 * @param string $url
 * @param array $data
 * @param array $curl_option
 * @param bool $is_post
 * @return array|null
 */
function craw_curl_request_cache($url, $data, $curl_option, $is_post){
	$cache_key = func_get_args();
	$cache_hit = craw_cache_hit($cache_key);
	$cache_file = craw_cache_file($cache_key);
	$query_ret = null;

	$ret = craw_cache($cache_key, function() use ($url, $data, $curl_option, $is_post, &$query_ret){
		Logger::debug('[Req]', $url, $data);
		Logger::debug('[Curl OPT]', $curl_option);
		$query_ret = $is_post ? curl_post($url, $data, $curl_option) : curl_get($url, $data, $curl_option);

		//don't cache on error happens
		return curl_query_success($query_ret) ? $query_ret : null;
	});
	if($cache_hit){
		Logger::debug('Cache Hit: '.$url.' <<< '.$cache_file.' ('.format_size(filesize($cache_file)).')');
	}else{
		Logger::debug('[Cache Set]', $url, $cache_file);
	}

	//while error happens, return error result.
	return $ret ?: $query_ret;
}

/**
 * curl_concurrent cache.
 * cache data use url as cache key
 * @param callable|array $curl_option_fetcher: array Returns the CURL option mapping array. Even if there is only one url, [CURLOPT_URL=>$url] needs to be returned.
 * @param callable|null $on_item_start ($curl_option) Start executing the callback. If false is returned, the task is ignored.
 * @param callable|null $on_item_finish ($curl_ret, $curl_option) Request end callback, parameter 1: return result array, parameter 2: CURL option
 * @param int $rolling_window Number of rolling requests
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
		//$ret['info']['url'] It may be a URL that has been redirected and parsed, rather than the URL of the original option. For caching purposes, the original URL is used here.
		$origin_url = $curl_option[CURLOPT_URL];
		craw_cache_set($origin_url, $ret);
		Logger::debug('[RSP]', $origin_url, strlen($ret['body']).' bytes');
		$on_item_finish && $on_item_finish($ret, $curl_option);
	}, $rolling_window);
}

