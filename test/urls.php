<?php

use LFPhp\Logger\Logger;
use function LFPhp\Craw\curl_concurrent;
use function LFPhp\Craw\curl_default_option;
use function LFPhp\Func\curl_print_option;

include "test.inc.php";

Logger::info("START");
$page = 1;
$page_total = null;

$ret = curl_concurrent(function() use (&$page, &$page_total){
	if(!$page_total){
		return curl_default_option('https://pixabay.com/bootstrap/3f0e1ac23bd985706deb2efccc1e91e18d30e5a586636c47b942c0f39660b9f7.json', [
			CURLOPT_USERAGENT       => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/128.0.0.0 Safari/537.36',
			CURLOPT_COOKIE          => 'is_human=1; lang=zh; anonymous_user_id=a02fc49a75844217b6a672132224117c; csrftoken=dbjz3drqAfnlsAbLzvbBdDSJARHipNbb; sessionid=eyJ0ZXN0Y29va2llIjoid29ya2VkIn0:1skgEu:uG4jIK4n6UMiCF0i5UCARZ8O40lcdEVZIWrc4QS6VeQ; _sp_ses.aded=*; _sp_id.aded=1b0a3cbe-4360-4170-a4e3-462d38996c33.1725160111.4.1725199017.1725180435.f2408c88-ebde-4d4f-8c3c-8c5a7ea43bd1.a7b9851c-b213-4ef9-8401-50fb60e3820d.f561e6da-b57d-41a7-8bb3-c6c3a1ac2344.1725198997463.4; __cf_bm=qiVUg7WcTnLyH.lUHNmul8matVBV5qcw8IZF8E02m.s-1725199537-1.0.1.1-IvqweiLorvmJLwy_tbHSG9U_i.EaG1lfTT5iYuoyomTaHR5DywO0LjuGoJzKoW8en4b_SAEa4p5GW2bwLvqnfg',
			CURLOPT_ACCEPT_ENCODING => 'gzip, deflate, br, zstd',
			CURLOPT_HTTPHEADER      => [
				'Accept'          => 'text/html,application/xhtml+xml,application/xml;q=0.9,image/avif,image/webp,image/apng,*/*;q=0.8,application/signed-exchange;v=b3;q=0.7',
				'Accept-Language' => 'zh-CN,zh;q=0.9,en;q=0.8,zh-TW;q=0.7,en-US;q=0.6',
			],
			CURLOPT_VERBOSE         => false,
		]);
	}
	return null;
}, [
	'on_item_start'  => function($curl_opt){
		Logger::debug('[TASK START]', curl_print_option($curl_opt, true));
	},
	'on_item_finish' => function($info, $error){
		Logger::info('[TASK RESPONSE] ', $info['http_code'], $error, json_encode($info['body']));
	},
]);

Logger::info("DONE", $ret);
