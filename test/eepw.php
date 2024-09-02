<?php

use LFPhp\Logger\Logger;
use function LFPhp\Craw\curl_concurrent;
use function LFPhp\Craw\curl_default_option;

include "test.inc.php";

Logger::info("START");

const URLS = [
	['https://www.eepw.com/articles/1697_trends_%p.html', '考试动态1'],
	['https://www.eepw.com/articles/1399_1_%p.html', '考试动态2'],
	['https://www.eepw.com/articles/1399_trends_%p.html', '考试资讯'],
	['https://www.eepw.com/articles/1399_6_%p.html', '报考指南'],
];

foreach(URLS as list($url, $cat)){
	$page = 1;
	$page_total = 50;
	Logger::warning('start cate:', $cat);
	$ret = curl_concurrent(function() use (&$page, $page_total, $url){
		if($page > $page_total){
			return null;
		}
		$url = str_replace('%p', $page, $url);
		$page++;
		return curl_default_option($url, [
			CURLOPT_USERAGENT       => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/128.0.0.0 Safari/537.36',
			CURLOPT_ACCEPT_ENCODING => 'gzip, deflate, br, zstd',
			CURLOPT_HTTPHEADER      => [
				'Accept'          => 'text/html,application/xhtml+xml,application/xml;q=0.9,image/avif,image/webp,image/apng,*/*;q=0.8,application/signed-exchange;v=b3;q=0.7',
				'Accept-Language' => 'zh-CN,zh;q=0.9,en;q=0.8,zh-TW;q=0.7,en-US;q=0.6',
			],
			CURLOPT_VERBOSE         => false,
		]);
	}, [
		'rolling_window' => 1,
		'on_item_start'  => function($curl_opt){
			Logger::debug('[START]', $curl_opt[CURLOPT_URL]);
		},
		'on_item_finish' => function($info, $error){
			$filename = basename($info['url']);
			file_put_contents(__DIR__.'/eepw/'.$filename, $info['body']);
			Logger::info('[TASK RESPONSE] ', $info['http_code'], $error, 'Body Length:'.strlen($info['body']));
		},
	]);
}

Logger::info("DONE");
