<?php
namespace craw;

/**
 * 请求策略
 * Class Policy
 * @package Craw
 */
class Policy {
	//代理列表，随机其中一个
	public $proxies;

	//UA
	public $useragent = '';

	//Cookie
	public $cookie = '';

	//引用来源
	public $referer = '';

	//追加头部信息
	public $headers = [];

	//超时时间
	public $timeout = 10;

	//请求前等待时间(秒)，如是数组[min, max]，则取随机数
	public $waiting = 0;

	//最大重定向次数
	public $max_redirect = 0;

	/**
	 * 转换规则为CURL选项数组
	 * @param string $url
	 * @return array
	 * @throws \Exception
	 */
	public function toCurlOptions($url = ''){
		$options = [];
		if($this->proxies){
			$k = array_rand($this->proxies, 1);
			list($proxy_addr, $proxy_psw) = $this->proxies[$k];
			list($proxy_protocol, $proxy_host, $proxy_port) = ProxyHelper::resolveProtocol($proxy_addr);
			$options[CURLOPT_PROXY] = "$proxy_host:$proxy_port";
			$options[CURLOPT_PROXYTYPE] = ProxyHelper::PROTOCOL_CURL_MAP[$proxy_protocol];
			if(strlen($proxy_psw)){
				$options[CURLOPT_PROXYAUTH] = CURLAUTH_BASIC;
				$options[CURLOPT_PROXYUSERPWD] = $proxy_psw;
			}
		}
		if($this->max_redirect){
			$options[CURLOPT_FOLLOWLOCATION] = true;
			$options[CURLOPT_MAXREDIRS] = $this->max_redirect;
		}
		if($this->headers){
			$options[CURLOPT_HTTPHEADER] = $this->headers;
		}
		if($this->useragent){
			$options[CURLOPT_USERAGENT] = $this->useragent;
		}
		if($this->cookie){
			$options[CURLOPT_COOKIE] = $this->cookie;
		}
		if($this->referer){
			$options[CURLOPT_REFERER] = $this->referer;
		}
		if($this->timeout){
			$options[CURLOPT_TIMEOUT] = $this->timeout;
			$options[CURLOPT_CONNECTTIMEOUT] = $this->timeout;
		}
		return $options;
	}

	/**
	 * @param $url
	 * @param array $patch_options
	 * @return array
	 * @throws \Exception
	 */
	public function toCurlOptionsPatch($url, array $patch_options){
		$options = $this->toCurlOptions($url);
		foreach($options as $k => $v){
			$patch_options[$k] = $v;
		}
		return $patch_options;
	}

	/**
	 * 等待
	 */
	public function waiting(){
		if($this->waiting){
			$t = is_array($this->waiting) ? rand($this->waiting[0],$this->waiting[1]) : $this->waiting;
			sleep($t);
		}
	}
}