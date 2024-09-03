<?php

use LFPhp\Logger\Logger;
use LFPhp\Logger\LoggerLevel;
use LFPhp\Logger\Output\ConsoleOutput;
use function LFPhp\Craw\curl_concurrent;
use function LFPhp\Craw\craw_default_option;

include "test.inc.php";

Logger::info("START");
$page = 1;

ConsoleOutput::$COLOR_MAP[LoggerLevel::INFO] = ['green'];

$ret = curl_concurrent(function() use (&$page){
	if($page > 10){
		Logger::debug('page limit');
		return null;
	}
	return craw_default_option('http://127.0.0.1/Craw/test/response.php?page='.($page++));
}, function($curl_opt){
	Logger::info('[TASK START]', $curl_opt[CURLOPT_URL]);
}, function($info, $error){
	Logger::info('[TASK RSP]', $info['http_code'], $error, json_encode($info['body']));
}, 5);

Logger::info("DONE", $ret);
