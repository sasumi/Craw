<?php

use LFPhp\Logger\Logger;
use function LFPhp\Func\curl_concurrent;
use function LFPhp\Func\curl_default_option;

include "test.inc.php";

Logger::info("START");

const URLS = [
	['https://www.eepw.com/articles/1697_trends_%p.html', 44, '考试动态1'],
	['https://www.eepw.com/articles/1399_1_%p.html', 93, '考试动态2'],
	['https://www.eepw.com/articles/1399_trends_%p.html', 91, '考试资讯'],
	['https://www.eepw.com/articles/1399_6_%p.html', 100, '报考指南'],
];

$curl_option = [
	CURLOPT_USERAGENT       => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/128.0.0.0 Safari/537.36',
	CURLOPT_ACCEPT_ENCODING => 'gzip, deflate, br, zstd',
	CURLOPT_HTTPHEADER      => [
		'Accept'          => 'text/html,application/xhtml+xml,application/xml;q=0.9,image/avif,image/webp,image/apng,*/*;q=0.8,application/signed-exchange;v=b3;q=0.7',
		'Accept-Language' => 'zh-CN,zh;q=0.9,en;q=0.8,zh-TW;q=0.7,en-US;q=0.6',
	],
	CURLOPT_VERBOSE         => false,
];

$rolling_window = 10;

$time_start = time();
$total_count = 0;

foreach(URLS as list($url, $page_total, $cat)){
	$page = 1;
	$total_count += $page_total;
	Logger::warning('start cat:', $cat);

	$task_fetcher = function() use (&$page, $page_total, $url, $curl_option){
		if($page > $page_total){
			return null;
		}
		$url = str_replace('%p', $page, $url);
		$page++;
		return curl_default_option($url, $curl_option);
	};

	$ret = curl_concurrent($task_fetcher, function($curl_opt){
		Logger::info('[START]', $curl_opt[CURLOPT_URL]);
	}, function($info, $error){
		$filename = basename($info['url']);
		file_put_contents(__DIR__.'/eepw_list/'.$filename, $info['body']);
		Logger::info('[RESPONSE] ', $info['http_code'], $error, basename($info['url']), 'Len:'.strlen($info['body']));
	}, 10);
}

Logger::info("DONE, time cost:", time()-$time_start, 'seconds', 'pages: '.$total_count);
