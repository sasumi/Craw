<?php

use Craw\Http\Curl;
use Craw\Logger\ScreenLogger;

require_once "../autoload.php";

$url = 'http://www.baidu.com';
echo "Fetching $url", PHP_EOL;

Curl::$logger = new ScreenLogger();
$result = Curl::getContent($url);
echo $result->getResultMessage(),PHP_EOL;
echo "Content got, content size:", strlen($result);