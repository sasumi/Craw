<?php

use Craw\Http\Proxy;

require_once "../autoload.php";
require_once "../vendor/autoload.php";

$proxy_host = '127.0.0.1';
$proxy_port = '8888';
$url = 'http://www.baidu.com';

$proxy = new Proxy($proxy_host, $proxy_port);
echo "Testing proxy $proxy_host:$proxy_port", PHP_EOL;
echo "Request $url ...", PHP_EOL;
$rst = $proxy->test($url, 5);

echo "Response code:", $rst->http_code, PHP_EOL;
echo "Response content size: ", strlen($rst->body), '  ', ($rst->total_time*1000).'ms', PHP_EOL;