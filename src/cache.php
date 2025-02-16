<?php

namespace LFPhp\Craw;

use Exception;
use LFPhp\Logger\Logger;
use function LFPhp\Func\array_merge_assoc;
use function LFPhp\Func\mkdir_by_file;
use const LFPhp\Func\ONE_DAY;

const CACHE_OPTION_KEY = __NAMESPACE__.'CRAW_CACHE_OPTION';
$GLOBALS[CACHE_OPTION_KEY] = [
	'PATH'            => sys_get_temp_dir().'/craw_cache', //cache directory
	'CACHE_GET'       => true, //set cache for GET request
	'CACHE_POST'      => false, //set cache for POST request
	'DEFAULT_TIMEOUT' => ONE_DAY, //default expired timeout
];

/**
 * set or get cache option
 * @param array $option
 * @return array
 */
function craw_cache_option($option = []){
	return array_merge_assoc($GLOBALS[CACHE_OPTION_KEY], $option);
}

/**
 * @param $mix_keys
 * @param $payload
 * @param int $expire_secs
 * @return mixed|null
 * @throws \Exception
 */
function craw_cache($mix_keys, $payload, $expire_secs = 0){
	$data = craw_cache_get($mix_keys);
	if(!isset($data)){
		$data = $payload();
		if(!isset($data)){
			return null;
		}
		$expire_secs = $expire_secs ?: craw_cache_option()['DEFAULT_TIMEOUT'];
		craw_cache_set($mix_keys, $data, $expire_secs);
		return $data;
	}
	return $data;
}

/**
 * cache hits
 * @param $mix_keys
 * @return bool
 */
function craw_cache_hit($mix_keys){
	$data = craw_cache_get($mix_keys);
	return isset($data);
}

function craw_cache_file($mix_keys){
	$opt = craw_cache_option();
	$dir = $opt['PATH'];
	$file = $dir.DIRECTORY_SEPARATOR.craw_cache_key($mix_keys).'.json';
	mkdir_by_file($file);
	return $file;
}

function craw_cache_key($mix_keys){
	return md5(json_encode($mix_keys));
}

function craw_cache_get($mix_keys){
	$file = craw_cache_file($mix_keys);
	if(!is_file($file) || filesize($file) === 0){
		return null;
	}
	$data = json_decode(file_get_contents($file), true);
	if(strtotime($data['expired']) > time()){
		return $data['data'];
	}
	return null;
}

/**
 * 设置缓存
 * @param mixed $mix_keys
 * @param mixed $rsp
 * @param int $expire_secs
 * @return bool
 * @throws \Exception
 */
function craw_cache_set($mix_keys, $rsp, $expire_secs = 0){
	if($rsp === null){
		throw new Exception('Cache Data no null allowed');
	}
	if($rsp['error']){
		Logger::warning('response error, no caching', $rsp['error']);
		return false;
	}
	$expire_secs = $expire_secs ?: craw_cache_option()['DEFAULT_TIMEOUT'];
	$file = craw_cache_file($mix_keys);
	mkdir_by_file($file);
	$str = json_encode([
		'key'     => json_encode($mix_keys, JSON_INVALID_UTF8_IGNORE),
		'expired' => date('Y-m-d H:i:s', time() + $expire_secs),
		'data'    => $rsp,
	], JSON_INVALID_UTF8_IGNORE|JSON_PRETTY_PRINT); //>= PHP7.2
	return file_put_contents($file, $str);
}
