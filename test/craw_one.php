<?php

use Craw\Fetcher;
use Craw\Logger\ScreenLogger;

require_once "../autoload.php";

$url = 'http://www.baidu.com';
echo "Fetching $url", PHP_EOL;

Fetcher::$logger = new ScreenLogger();
$result = Fetcher::getContent($url);
echo $result->getResultMessage(),PHP_EOL;
echo "Content got:", $result;