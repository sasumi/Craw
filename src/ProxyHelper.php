<?php
namespace Craw;

use Craw\Logger\LoggerAbstract;
use Craw\Logger\NullLogger;
use Craw\Logger\ScreenLogger;

class ProxyHelper {
	private $proxies = [];

	/** @var LoggerAbstract $logger*/
	public $logger;
	public static $test_url = 'http://www.baidu.com';

	/**
	 * 协议映射CURL代理类型
	 */
	const PROTOCOL_CURL_MAP = [
		'http'   => CURLPROXY_HTTP,
		'socks4' => CURLPROXY_SOCKS4,
		'socks5' => CURLPROXY_SOCKS5,
	];

	/**
	 * 默认协议端口
	 */
	const PROTOCOL_DEFAULT_PORT = [
		'http'   => 80,
		'socks4' => 1080,
		'socks5' => 1080,
	];

	private function __construct(){}
	private function __clone(){}

	/**
	 * @param \Craw\Logger\LoggerAbstract|null $logger
	 * @return \Craw\ProxyHelper
	 */
	public static function instance(LoggerAbstract $logger = null){
		static $instance;
		if(!$instance){
			$instance = new self();
			$instance->logger = $logger ?: new NullLogger();
		}
		return $instance;
	}

	/**
	 * 添加代理
	 * @param string $addr 代理地址
	 * @param string $psw 密码
	 * @return \Craw\ProxyHelper
	 */
	public function addProxy($addr, $psw = ''){
		$this->proxies[] = [$addr, $psw];
		return $this;
	}

	/**
	 * 添加代理列表
	 * @param array $proxies [[address, password],...]
	 * @return $this
	 */
	public function addProxies(array $proxies){
		$this->proxies = $this->proxies + $proxies;
		return $this;
	}

	/**
	 * 获取代理列表
	 * @return array
	 */
	public function getProxies(){
		return $this->proxies;
	}

	/**
	 * 并发测试
	 * @param null|callable $item_finish_callback ($task, ProxyResult)
	 * @param int $item_timeout
	 * @param int $rolling_count
	 * @return array [[$task, CurlResult $result],...]
	 */
	public function testConcurrent($item_finish_callback = null, $item_timeout = 5, $rolling_count = 10){
		set_time_limit(0);
		$results = [];
		$tasks = $this->proxies;
		$check_index = 0;
		$done_index = 0;
		$total = count($tasks);
		$mh = curl_multi_init();

		$common_policy = new Policy();
		$common_policy->timeout = $item_timeout;

		while($tasks){
			$current_tasks = array_slice($tasks, 0, $rolling_count);
			$tasks = array_slice($tasks, $rolling_count);
			$curl_handlers = [];

			foreach($current_tasks as $k => list($addr, $psw)){
				$check_index++;
				$ch = curl_init();
				$common_policy->proxies = [[$addr, $psw]];
				\curl_setopt_array($ch, $common_policy->toCurlOptionsPatch(self::$test_url, [
					CURLOPT_URL            => self::$test_url,
					CURLOPT_RETURNTRANSFER => true,
					CURLOPT_HEADER         => true,
				]));

				curl_multi_add_handle($mh, $ch);
				$curl_handlers[$k] = [$ch, $current_tasks[$k]];
				$this->logger->log("Start Check [$check_index/$total] $addr ......");
			}

			// execute the handles
			$running = null;
			do{
				curl_multi_exec($mh, $running);
			} while($running > 0);

			// get content and remove handles
			foreach($curl_handlers as $k => list($ch, list($addr, $psw))){
				$done_index++;
				$rst = new CurlResult(self::$test_url, curl_multi_getcontent($ch), curl_getinfo($ch));
				$rst->setTimeoutThreshold($item_timeout);
				$results[] = [[$addr, $psw], $rst];
				curl_multi_remove_handle($mh, $ch);
				if($item_finish_callback){
					call_user_func($item_finish_callback, [$addr, $psw], $rst);
				}
				$msg = $rst->getResultMessage();
				$this->logger->log("Check Done [$done_index/$total] {$addr} {$msg}");
			}
		}
		$this->logger->log('ALL TASK DONE');
		curl_multi_close($mh);
		return $results;
	}

	/**
	 * 单条测试
	 * @param string $addr 地址 <pre>
	 * 格式为：{协议}://{ip}:{端口} 或者 {ip}（缺省为HTTP协议）
	 * 例：http://192.160.1.1:80，socks4://192.168.1.2:1080，socks5://192.168.1.3:8888
	 * </pre>
	 * @param string $psw 密码
	 * @param int $timeout
	 * @return \Craw\CurlResult
	 */
	public function test($addr, $psw = '', $timeout = 5){
		$ch = \curl_init();
		$policy = new Policy();
		$policy->timeout = 5;
		$policy->proxies = [[$addr, $psw]];

		\curl_setopt_array($ch, $policy->toCurlOptionsPatch(self::$test_url, [
			CURLOPT_URL            => self::$test_url,
			CURLOPT_RETURNTRANSFER => true,
			CURLOPT_HEADER         => true,
		]));

		$content = \curl_exec($ch);
		$result = new CurlResult(self::$test_url, $content, curl_getinfo($ch));
		$result->setTimeoutThreshold($timeout);
		curl_close($ch);
		return $result;
	}

	public function getRandomProxy(){
		$proxies = $this->getProxies();
		$k = array_rand($proxies, 1);
		list($proxy_addr, $proxy_psw) = $proxies[$k];
		list($proxy_protocol, $proxy_host, $proxy_port) = static::resolveProtocol($proxy_addr);
		return [$proxy_protocol, $proxy_host, $proxy_port, $proxy_psw];
	}

	/**
	 * 获取地址协议
	 * @param string $addr 地址格式为：协议://主机地址:端口，如socks4://192.168.0.1:1080
	 * @return array [protocol, host, port]
	 * @throws \Exception
	 */
	public static function resolveProtocol($addr){
		$protocol = 'http';
		$host = $addr;

		//resolve protocol
		$pos = strpos($addr, '://');
		if($pos){
			$protocol = strtolower(substr($addr, 0, $pos));
			$host = substr($addr, $pos + 3);
			$p = static::PROTOCOL_CURL_MAP[$protocol];
			if(!isset($p)){
				throw new \Exception('Proxy protocol no supported:'.$addr);
			}
		}

		//resolve port
		list($host, $port) = explode(':', $host);
		$port = $port ?: static::PROTOCOL_DEFAULT_PORT[$protocol];
		return [$protocol, $host, $port];
	}
}