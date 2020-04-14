<?php

use LFPhp\Logger\LoggerLevel;
use LFPhp\Logger\Output\ConsoleOutput;
use LFPhp\Logger\Output\FileOutput;
use LFPhp\Logger\Logger;
use function LFPhp\Func\curl_get;
use function LFPhp\Func\dump;

require_once "../autoload.php";
require_once "../vendor/autoload.php";

$url = 'http://www.baidu.com';
Logger::registerGlobal(new FileOutput(__DIR__.'/log/craw.log'), LoggerLevel::INFO);
Logger::registerGlobal(new ConsoleOutput, LoggerLevel::INFO);

$result = curl_get($url);
$a = $result->decodeAsPage()->find('a');
dump($a->getAttribute('href'), $a->html(), 1);