<?php

namespace LFPhp\Craw\Http;

class HttpAuth implements CurlOption {
	public $user;
	public $password;

	/**
	 * @param array $headers
	 * @return array
	 */
	public function getCurlOption(&$headers = []){
		return [
			CURLOPT_USERPWD  => $this->user.':'.$this->password,
			CURLOPT_HTTPAUTH => CURLAUTH_BASIC,
		];
	}
}