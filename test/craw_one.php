<?php

use Craw\Http\Curl;
use LFPhp\Logger\LoggerLevel;
use LFPhp\Logger\Output\ConsoleOutput;
use LFPhp\Logger\Output\FileOutput;
use LFPhp\Logger\Logger;
use function Craw\dump;

require_once "../autoload.php";
require_once "../vendor/autoload.php";

$url = 'http://www.baidu.com';
Logger::register(new FileOutput(__DIR__.'/craw.log'), LoggerLevel::INFO);
Logger::register(new ConsoleOutput, LoggerLevel::INFO);

$result = Curl::getContent($url);
$a = $result->decodeAsPage()->findOne('a');
dump($a->getAttribute('href'), $a->outerHtml(), $a->getAllAttributes(), 1);