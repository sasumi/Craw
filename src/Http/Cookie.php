<?php

namespace LFPhp\Craw\Http;

class Cookie {
	/** @var string */
	public $domain;

	/** @var string */
	public $key;

	/** @var string */
	public $value;

	/** @var integer timestamp */
	public $expires;

	/** @var string */
	public $path;

	/** @var bool */
	public $http_only;

	public function __construct($key, $value, $expires = null, $domain = null, $path = null, $http_only = null){
		$this->key = $key;
		$this->value = $value;
		$this->expires = $expires;
		$this->path = $path;
		$this->http_only = $http_only;
		$this->domain = $domain;

		//@todo
		$this->expires = time()+86400*7;
	}

	/**
	 * 清理过期cookie
	 * @param self[] $cookies
	 * @return \LFPhp\Craw\Http\Cookie[]
	 */
	public static function cleanExpiredCookies(array $cookies){
		$ret = [];
		foreach($cookies as $cookie){
			if(!$cookie->isExpired()){
				$ret[] = $cookie;
			}
		}
		return $ret;
	}

	/**
	 * 是否与另一个cookie同名
	 * @param self $another_cookie
	 * @return bool
	 */
	public function sameVar(self $another_cookie){
		return $this->domain == $another_cookie->domain && $this->path == $another_cookie->path && $this->key == $another_cookie->key;
	}

	/**
	 * merge remote cookies to local
	 * @param self[] $locals
	 * @param self[] $remotes
	 * @return self[]
	 */
	public static function updateLocalCookies($locals, $remotes){
		foreach($remotes as $remote){
			foreach($locals as $k=>$local){
				if($local->sameVar($remote)){
					unset($locals[$k]);
				}
			}
		}
		$ret = array_filter($locals+$remotes, function(Cookie $cookie){
			return !$cookie->isExpired();
		});
		return $ret;
	}

	/**
	 * 是否过期
	 * @return bool
	 */
	public function isExpired(){
		return $this->expires < time();
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