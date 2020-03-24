<?php

namespace LFPhp\Craw\Http;

use function curl_setopt_array;

class Proxy implements CurlOption {
	private $type;
	private $host;
	private $port;
	private $user;
	private $password;

	public function __construct($host, $port, $user = '', $password = '', $type = CURLPROXY_HTTP){
		$this->host = $host;
		$this->port = $port;
		$this->user = $user;
		$this->password = $password;
		$this->type = $type;
	}

	/**
	 * @return array
	 */
	public function getCurlOption(){
		$options = [];
		$options[CURLOPT_PROXY] = $this->host.':'.$this->port;
		$options[CURLOPT_PROXYTYPE] = $this->type;
		if($this->user){
			$options[CURLOPT_PROXYAUTH] = CURLAUTH_BASIC;
			$options[CURLOPT_PROXYUSERPWD] = $this->user.':'.$this->password;
		}
		return $options;
	}

	/**
	 * 测试
	 * @param $test_url
	 * @param int $timeout
	 * @return \LFPhp\Craw\Http\Result
	 */
	public function test($test_url, $timeout = 10){
		$opt = $this->getCurlOption();
		$opt[CURLOPT_TIMEOUT] = $timeout;
		$rst = Curl::getContent($test_url, null, $opt);
		return $rst;
	}

	/**
	 * 批量并发测试
	 * @param \LFPhp\Craw\Http\Proxy[] $proxies
	 * @param $test_url
	 * @param int $rolling_count
	 * @param int $timeout
	 */
	public static function testConcurrent(array $proxies, $test_url, $rolling_count = 10, $timeout = 10){
		set_time_limit(0);
		$tasks = $proxies;
		$check_index = 0;
		$done_index = 0;
		$total = count($tasks);
		$mh = curl_multi_init();

		while($tasks){
			$current_tasks = array_slice($tasks, 0, $rolling_count);
			$tasks = array_slice($tasks, $rolling_count);
			$curl_handlers = [];
			foreach($current_tasks as $k => $proxy){
				$check_index++;
				$ch = curl_init();
				$opt = $proxy->getCurlOption();
				$opt[CURLOPT_URL] = $test_url;
				$opt[CURLOPT_HEADER] = true;
				$opt[CURLOPT_RETURNTRANSFER] = true; //返回内容部分
				$opt[CURLOPT_TIMEOUT] = $timeout;
				if(stripos($test_url, 'https://') !== false){
					$opt[CURLOPT_SSL_VERIFYPEER] = 0;
					$opt[CURLOPT_SSL_VERIFYHOST] = 1;
				}
				curl_setopt_array($ch, $opt);
				curl_multi_add_handle($mh, $ch);
				$curl_handlers[$k] = [$ch, $current_tasks[$k]];
				echo "Start Check [$check_index/$total] $proxy ......", PHP_EOL;
			}

			// execute the handles
			$running = null;
			do{
				curl_multi_exec($mh, $running);
			} while($running > 0);

			// get content and remove handles
			foreach($curl_handlers as $k => list($ch, $proxy)){
				$done_index++;
				$rst = new Result($test_url, curl_multi_getcontent($ch), $ch);
				curl_multi_remove_handle($mh, $ch);
				$msg = $rst->getResultMessage();
				echo "Check Done  [$done_index/$total] {$proxy} {$msg}  ", ($rst->total_time*1000).'ms',PHP_EOL;
			}
		}
		echo 'ALL TASK DONE', PHP_EOL;
		curl_multi_close($mh);
	}

	public function __toString(){
		$addr = $this->type.' '.$this->host.':'.$this->port;
		if($this->user || $this->password){
			$addr .= ' '.$this->user.'@'.$this->password;
		}
		return $addr;
	}
}