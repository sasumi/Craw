<?php

use function LFPhp\Func\curl_default_option;
use function LFPhp\Func\curl_get_proxy_option;
use function LFPhp\Func\curl_instance;
use function LFPhp\Func\curl_print_option;
use function LFPhp\Func\dump;

require_once "test.inc.php";

$proxy_host = 'http://127.0.0.1:8080';
$url = 'http://www.baidu.com';

$opt = curl_get_proxy_option($proxy_host);
$curl_opt = curl_default_option($url, $opt);
$curl_opt[CURLOPT_VERBOSE] = true;

var_export(curl_print_option($curl_opt, true));

$ch = curl_instance($url, $curl_opt);
$ret = curl_exec($ch);
$err = curl_error($ch);
dump($ret, $err, 1);
