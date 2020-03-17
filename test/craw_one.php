<?php

use Craw\Http\Curl;
use Craw\Logger\ConsoleLogger;
use Craw\Logger\FileLogger;
use Craw\Logger\Logger;

require_once "../autoload.php";

$url = 'http://www.baidu.com';
Logger::register(FileLogger::instance(null, __DIR__.'/craw.log'), Logger::DEBUG);
Logger::register(ConsoleLogger::instance(), Logger::VERBOSE);

$result = Curl::getContent($url);