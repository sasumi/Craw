<?php

use Craw\Http\Curl;
use Craw\Logger\Output\ConsoleOutput;
use Craw\Logger\Output\FileOutput;
use Craw\Logger\Logger;

require_once "../autoload.php";

$url = 'http://www.baidu.com';
Logger::register(new FileOutput(__DIR__.'/craw.log'), Logger::LOG);
Logger::register(new ConsoleOutput, Logger::INFO);

$result = Curl::getContent($url);