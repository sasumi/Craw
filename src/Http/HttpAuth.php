<?php

namespace Craw\Http;

class HttpAuth implements CurlOption {
	private $user;
	private $password;

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