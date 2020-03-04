<?php

namespace craw;

class CurlResult {
	public $url;
	public $response_content = '';
	public $timeout_threshold;
	public $content_type;
	public $http_code;
	public $header_size;
	public $request_size;
	public $filetime;
	public $ssl_verify_result;
	public $redirect_count;
	public $total_time;
	public $namelookup_time;
	public $connect_time;
	public $pretransfer_time;
	public $size_upload;
	public $size_download;
	public $speed_download;
	public $speed_upload;
	public $download_content_length;
	public $upload_content_length;
	public $starttransfer_time;
	public $redirect_time;
	public $redirect_url;
	public $primary_ip;
	public $certinfo;
	public $primary_port;
	public $local_ip;
	public $local_port;
	public $http_version;
	public $protocol;
	public $ssl_verifyresult;
	public $scheme;
	public $appconnect_time_us;
	public $connect_time_us;
	public $namelookup_time_us;
	public $pretransfer_time_us;
	public $redirect_time_us;
	public $starttransfer_time_us;
	public $total_time_us;

	public function __construct($url, $content = '', array $data = []){
		$this->url = $url;
		foreach($data as $k => $v){
			$this->$k = $v;
		}
		if(!isset($this->total_time)){
			$this->total_time = $this->total_time_us/1000000;
		}
		$this->total_time = round($this->total_time, 2);
		$this->response_content = $content;
	}

	public function isSuccess(){
		return $this->http_code == 200;
	}

	public function setTimeoutThreshold($timeout){
		$this->timeout_threshold = $timeout;
	}

	/**
	 * 获取失败信息
	 * @return string|null
	 */
	public function getResultMessage(){
		if($this->isSuccess()){
			return 'success, content length:'.strlen($this->response_content);
		}
		if(isset($this->timeout_threshold) && $this->total_time >= $this->timeout_threshold){
			return 'timeout.';
		}
		if($this->http_code !== 200){
			return 'http code error('.$this->http_code.').';
		}
		return 'unknown error.';
	}

	public function __toString(){
		return $this->response_content ?: '';
	}
}