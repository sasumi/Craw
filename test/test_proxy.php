<?php

use Craw\Logger\ScreenLogger;
use Craw\ProxyHelper;
require_once "../autoload.php";

$max_page_size = 10;
$file = __DIR__.'/proxy.list';
echo "Testing proxy list", PHP_EOL;
$tmp = file_get_contents($file);
$addr_list = explode("\n", $tmp);
$addr_list = array_map(function($item){
	$row = explode(',', $item);
	return array_map('trim', $row);
}, $addr_list);
$addr_list = array_filter($addr_list, function($item){
	return $item;
});

echo "Task start count:".count($addr_list), PHP_EOL;
$results = ProxyHelper::instance(new ScreenLogger())
	->addProxies($addr_list)
	->testConcurrent(null, 5, 200);

usort($results, function($item1, $item2){
	$time1 = $item1[1]->total_time;
	$time2 = $item2[1]->total_time;
	return $time1 > $time2;
});

/**
 * @var array $task
 * @var \Craw\CurlResult $rst
 */
foreach($results as list($task, $rst)){
	list($url, $psw) = $task;
	$tm = $rst->isSuccess() ? $rst->total_time : 999;
}
echo "All proxies tested.", PHP_EOL;