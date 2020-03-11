<?php

use Craw\Context;

require_once "../autoload.php";

$url = 'http://www.baidu.com';
echo "Fetching $url", PHP_EOL;
$result = Context::create()->get('http://www.baidu.com');;
echo "Content got, content size:", strlen($result);