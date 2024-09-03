<?php

use LFPhp\Logger\Logger;
use function LFPhp\Craw\curl_concurrent;
use function LFPhp\Craw\craw_urls_to_fetcher;
use function LFPhp\Func\glob_recursive;

include "test.inc.php";

Logger::info("START");

$files = glob_recursive(__DIR__.'/eepw_list/*.html');

$urls = [];
foreach($files as $file){
	$link_list = node_find_all(file_get_contents($file), 'a.list_item');
	foreach($link_list as $link){
		$href = $link->getAttribute('href');
		$urls[] = $href;
	}
}
$urls = array_unique($urls);
Logger::info('Fetch URLS', count($urls));

$task_fetcher = craw_urls_to_fetcher($urls, [
	CURLOPT_USERAGENT       => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/128.0.0.0 Safari/537.36',
	CURLOPT_ACCEPT_ENCODING => 'gzip, deflate, br, zstd',
	CURLOPT_HTTPHEADER      => [
		'Accept'          => 'text/html,application/xhtml+xml,application/xml;q=0.9,image/avif,image/webp,image/apng,*/*;q=0.8,application/signed-exchange;v=b3;q=0.7',
		'Accept-Language' => 'zh-CN,zh;q=0.9,en;q=0.8,zh-TW;q=0.7,en-US;q=0.6',
	],
	CURLOPT_VERBOSE         => false,
]);

$time_start = time();
$total_count = 0;
$ret = curl_concurrent($task_fetcher, function($curl_opt){
	Logger::info('[START]', $curl_opt[CURLOPT_URL]);
}, function($info, $error){
	$filename = md5($info['url']).'_'.basename($info['url']);
	file_put_contents(__DIR__.'/eepw_detail/'.$filename, $info['body']);
	Logger::info('[RESPONSE] ', $info['http_code'], $error, basename($info['url']), 'Len:'.strlen($info['body']));
}, 10);

Logger::info("DONE, time cost:", time() - $time_start, 'seconds', 'pages: '.$total_count);
