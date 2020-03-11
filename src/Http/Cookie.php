<?php

namespace Craw\Http;

class Cookie {
	private $key;
	private $value;
	private $expired;
	private $path;

	public function __construct($key, $value, $expired = null, $path = null){
		$this->key = $key;
		$this->value = $value;
		$this->expired = $expired;
		$this->path = $path;
	}

	/**
	 * @param $header
	 * @return array
	 */
	public static function parseCookieStr($header){
		preg_match_all('/^Set-Cookie:\s*([^;]*)/mi', $header, $matches);
		$cookies = array();
		foreach($matches[1] as $item){
			parse_str($item, $cookie);
			$cookies = array_merge($cookies, $cookie);
		}
		$ret = [];
		foreach($cookies as $k => $v){
			$ret[] = new self($k, $v);
		}
		return $ret;
	}

	public function __toString(){
		return $this->key.'='.$this->value;
	}
}