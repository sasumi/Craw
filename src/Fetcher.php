<?php

namespace Craw;

use Craw\Logger\NullLogger;

abstract class Fetcher {
	public static $logger;

	/**
	 * @param $url
	 * @param \Craw\Policy|null $policy
	 * @return \Craw\CurlResult
	 */
	public static function getContent($url, Policy $policy = null){
		$policy = $policy ?: new Policy();
		$options = $policy->toCurlOptionsPatch($url, [
			CURLOPT_URL            => $url,
			CURLOPT_RETURNTRANSFER => true,
			CURLOPT_HEADER         => true,
		]);
		if(stripos($url, 'https://') !== false){
			$options[CURLOPT_SSL_VERIFYPEER] = 0;
			$options[CURLOPT_SSL_VERIFYHOST] = 1;
		}
		$policy->waiting();
		$logger = self::$logger ?: new NullLogger();
		$logger->log("Start Fetching $url ...");
		$ch = \curl_init();
		\curl_setopt_array($ch, $options);
		$content = \curl_exec($ch);
		$result = new CurlResult($url, $content, curl_getinfo($ch));
		$result->setTimeoutThreshold($policy->timeout);
		curl_close($ch);
		$logger->log("Content Fetched $url ".$result->getResultMessage());
		return $result;
	}

	/**
	 * 并发获取内容
	 * @param $urls
	 * @param \Craw\Policy|null $policy
	 * @param callable|null $on_item_finish
	 * @param int $item_timeout
	 * @param int $rolling_count
	 * @return CurlResult[]
	 * @throws \Exception
	 */
	public static function getContents($urls, Policy $policy = null, callable $on_item_finish = null, $item_timeout = 5, $rolling_count = 10){
		set_time_limit(0);
		$logger = self::$logger ?: new NullLogger();
		$results = [];
		$check_index = 0;
		$done_index = 0;
		$total = count($urls);
		$mh = curl_multi_init();
		$policy = $policy ?: new Policy();
		while($urls){
			$current_tasks = array_slice($urls, 0, $rolling_count);
			$urls = array_slice($urls, $rolling_count);
			$curl_handlers = [];
			foreach($current_tasks as $k => $url){
				$check_index++;
				$ch = curl_init();
				$curl_options = $policy->toCurlOptionsPatch($url, [
					CURLOPT_URL            => $url,
					CURLOPT_RETURNTRANSFER => true,
					CURLOPT_HEADER         => true,
				]);
				if(stripos($url, 'https://') !== false){
					$curl_options[CURLOPT_SSL_VERIFYPEER] = 0;
					$curl_options[CURLOPT_SSL_VERIFYHOST] = 1;
				}
				curl_setopt_array($ch, $curl_options);
				curl_multi_add_handle($mh, $ch);
				$curl_handlers[$k] = [$ch, $url];
				$logger->log("Start Fetching [$check_index/$total] $url ...");
			}

			// execute the handles
			$running = null;
			do{
				curl_multi_exec($mh, $running);
			} while($running > 0);

			// get content and remove handles
			foreach($curl_handlers as $k => list($ch, $url)){
				$done_index++;
				$rst = new CurlResult($url, curl_multi_getcontent($ch), curl_getinfo($ch));
				$rst->setTimeoutThreshold($item_timeout);
				$results[] = $rst;
				curl_multi_remove_handle($mh, $ch);
				if($on_item_finish){
					call_user_func($on_item_finish, $rst);
				}
				$logger->log("Content Fetched [$done_index/$total] $url ".$rst->getResultMessage());
			}
			if($urls){
				$policy->waiting();
			}
		}
		$logger->log('ALL TASK DONE');
		curl_multi_close($mh);
		return $results;
	}
}