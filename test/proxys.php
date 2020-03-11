<?php

use Craw\Http\Proxy;

require_once "../autoload.php";

$list = [
	['127.0.0.1', '8888'],
	['127.0.0.1', '8888'],
	['127.0.0.1', '8888'],
	['127.0.0.1', '8888'],
	['127.0.0.1', '8888'],
	['127.0.0.1', '8888'],
	['127.0.0.1', '8888'],
];
$proxy_list = [];
foreach($list as list($host, $port)){
	$proxy_list[] = new Proxy($host, $port);
}
echo "Task start count:".count($proxy_list), PHP_EOL;
Proxy::testConcurrent($proxy_list, 'http://www.baidu.com', 10);

echo "All proxies tested.", PHP_EOL;