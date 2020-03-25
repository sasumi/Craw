<?php

namespace LFPhp\Craw;

use LFPhp\Cache\CacheFile;
use LFPhp\Craw\Http\Cookie;
use LFPhp\Craw\Http\Curl;
use LFPhp\Craw\Http\HttpAuth;
use LFPhp\Craw\Http\Proxy;
use LFPhp\Craw\Http\Result;
use LFPhp\Logger\Logger;
use function LFPhp\Func\range_slice;

class Context {
	public $timeout = 10;

	/** @var int cache time in seconds */
	public $cache_time = 0;

	public $extra_http_headers = [];
	public $auto_update_referer = true;
	public $auto_update_cookie = true;
	public $max_redirect = 3;

	/** @var string UserAgent */
	public $user_agent;

	/** @var Cookie[] */
	public $cookies = [];

	/** @var string language */
	public $lang;

	/** @var Proxy|null */
	public $proxy;
	public $proxy_always_on_set;

	/** @var HttpAuth|null */
	public $auth;
	public $auth_always_on_set;

	/** @var Result 最后一次请求结果 */
	private $last_result;

	public function __construct(){
		$this->extra_http_headers = [
			'Cache-Control'             => 'max-age=0',
			'Upgrade-Insecure-Requests' => '1',
			'Accept'                    => 'text/html,application/xhtml+xml,application/xml;q=0.9,image/webp,image/apng,*/*;q=0.8,application/signed-exchange;v=b3;q=0.9',
			'Accept-Encoding'           => 'gzip, deflate',
		];
	}

	public static function create(){
		return new static();
	}

	/**
	 * 暂停随机时间
	 * @param int $min
	 * @param int $max
	 * @return $this
	 */
	public function sleepRandom($min = 3, $max = 10){
		return $this->sleep((int)rand($min, $max));
	}

	/**
	 * 暂停
	 * @param $sec
	 * @return $this
	 */
	public function sleep($sec){
		sleep($sec);
		return $this;
	}

	/**
	 * 设定HTTP授权登录用户名密码
	 * @param HttpAuth $auth
	 * @param bool $always_on_set 是否全程保持使用
	 * @return \LFPhp\Craw\Context
	 */
	public function setAuth(HttpAuth $auth, $always_on_set = false){
		$this->auth = $auth;
		$this->auth_always_on_set = $always_on_set;
		return $this;
	}

	/**
	 * 设定代理
	 * @param Proxy $proxy
	 * @param bool $always_on_set 是否全程保持使用
	 * @return $this
	 */
	public function setProxy(Proxy $proxy, $always_on_set = true){
		$this->proxy = $proxy;
		$this->proxy_always_on_set = $always_on_set;
		return $this;
	}

	/**
	 * 设定UA
	 * @param string $user_agent
	 * @return \LFPhp\Craw\Context
	 */
	public function setUserAgent($user_agent){
		$this->user_agent = $user_agent;
		return $this;
	}

	/**
	 * 设定cookie
	 * @param Cookie[] $cookies
	 * @return $this
	 */
	public function setCookies(array $cookies){
		$this->cookies = $cookies;
		return $this;
	}

	/**
	 * 设定语言
	 * @param string $lang
	 * @return $this
	 */
	public function setLang($lang){
		$this->lang = $lang;
		return $this;
	}

	/**
	 * 设置超时时间
	 * @param $timeout
	 * @return $this
	 */
	public function setTimeout($timeout){
		$this->timeout = $timeout;
		return $this;
	}

	/**
	 * 设置自动更新Referer
	 * @return $this
	 */
	public function autoUpdateRefererOn(){
		$this->auto_update_referer = true;
		return $this;
	}

	/**
	 * 关闭自动更新Referer
	 * @return $this
	 */
	public function autoUpdateRefererOff(){
		$this->auto_update_referer = false;
		return $this;
	}

	/**
	 * 设置自动更新Cookie
	 * @return $this
	 */
	public function autoUpdateCookieOn(){
		$this->auto_update_cookie = true;
		return $this;
	}

	/**
	 * 关闭自动更新Cookie
	 * @return $this
	 */
	public function autoUpdateCookieOff(){
		$this->auto_update_cookie = false;
		return $this;
	}

	/**
	 * 打开缓存
	 * @param int $expired 缓存时长
	 * @return $this
	 */
	public function cacheOn($expired = 3600){
		$this->cache_time = $expired;
		return $this;
	}

	/**
	 * 关闭缓存
	 * @return $this
	 */
	public function cacheOff(){
		$this->cache_time = 0;
		return $this;
	}

	/**
	 * get request
	 * @param callable|string $url 请求URL，可使用闭包函数动态返回url，传入参数为（上次请求结果last_result, 上下文环境context)
	 * @param null $param
	 * @param array $extra_curl_option
	 * @return \LFPhp\Craw\Http\Result
	 */
	public function get($url, $param = null, $extra_curl_option = []){
		if(is_callable($url)){
			$url = call_user_func($url, $this->last_result, $this);
		}
		$result = CacheFile::instance()->cache(serialize([$url, $param]), function() use ($url, $param, $extra_curl_option){
			return Curl::getContent($url, $param, Curl::mergeCurlOptions($this->getCurlOption(), $extra_curl_option));
		}, $this->cache_time);
		$this->afterRequest($result);
		return $result;
	}

	/**
	 * 并发获取列表信息
	 * @param string|callable $list_url 列表地址，使用 %i%占位符表示页码，或使用函数动态获取（传入参数为：当前页码）
	 * @param array $page_offset
	 * @param array $option
	 * @param array $extra_curl_option 其他CURL控制参数
	 * @return Result[]
	 */
	public function getList($list_url, $page_offset = [], $option = [], $extra_curl_option = []){
		$rolling_count = $option['rolling_count'];
		$break_on_error = $option['break_on_error'];
		$batch_interval_time = $option['batch_interval_time'];
		list($start, $end) = $page_offset;

		$all_results = [];
		$curl_option = Curl::mergeCurlOptions($this->getCurlOption(), $extra_curl_option);

		$logger = Logger::instance(__CLASS__);
		$logger->info('Batch get list', $list_url);
		$logger->debug('Option:', $option, 'Curl Option:', Curl::printCurlOption($extra_curl_option, true));

		foreach(range_slice($start, $end, $rolling_count) as list($item_start, $item_end)){
			$task_list = [];
			while($item_start++ < $item_end){
				if(is_callable($list_url)){
					$task_list[] = $list_url($item_end);
				}else{
					$task_list[] = str_replace('%i%', $item_start, $list_url);
				}
			}
			$results = Curl::getContents($task_list, [
				'rolling_count'       => $rolling_count,
				'batch_interval_time' => $batch_interval_time,
			], $curl_option);
			if($break_on_error){
				$on_error = false;
				array_filter($results, function(Result $result) use (&$on_error){
					if(!$result->isSuccess()){
						$on_error = true;
					}else{
						$all_results[] = $result;
					}
				});
				if($on_error){
					return $all_results;
				}
			}else{
				$all_results = array_merge($all_results, $results);
			}
		}
		return $all_results;
	}

	/**
	 * post请求
	 * @param callable|string $url 请求URL，可使用闭包函数动态返回url，传入参数为（上次请求结果last_result, 上下文环境context)
	 * @param null $param
	 * @param array $extra_curl_option
	 * @return Result
	 */
	public function post($url, $param = null, $extra_curl_option = []){
		if(is_callable($url)){
			$url = call_user_func($url, $this->last_result, $this);
		}
		$result = CacheFile::instance()->cache(serialize([$url, $param]), function() use ($url, $param, $extra_curl_option){
			return Curl::postContent($url, $param, Curl::mergeCurlOptions($this->getCurlOption(), $extra_curl_option));
		}, $this->cache_time);
		$this->afterRequest($result);
		return $result;
	}

	/**
	 * 请求后处理逻辑
	 * @param Result $result
	 */
	protected function afterRequest(Result $result){
		if(!$this->proxy_always_on_set){
			$this->proxy = null;
		}
		if(!$this->auth_always_on_set){
			$this->auth = null;
		}
		if($this->auto_update_cookie && $result->cookies){
			$this->cookies = Cookie::updateLocalCookies($this->cookies, $result->cookies);
		}
		if($this->auto_update_referer){
			$this->extra_http_headers['Referer'] = $this->auto_update_referer ? $result->url : null;
		}
		$this->last_result = $result;
		$this->last_result->setContext($this);
	}

	/**
	 * 获取当前上下文CURL选项
	 * @return array
	 */
	public function getCurlOption(){
		$options = [
			CURLOPT_RETURNTRANSFER => true,
			CURLOPT_HEADER         => true,
		];

		if($this->proxy){
			$options = Curl::mergeCurlOptions($options, $this->proxy->getCurlOption());
		}

		if($this->auth){
			$options = Curl::mergeCurlOptions($options, $this->auth);
		}

		if($this->cookies){
			$cookies = Cookie::cleanExpiredCookies($this->cookies);
			if($cookies){
				$options[CURLOPT_HTTPHEADER]['Cookie'] = join('; ', $cookies);
			}
		}

		if($this->max_redirect){
			$options[CURLOPT_FOLLOWLOCATION] = true;
			$options[CURLOPT_MAXREDIRS] = $this->max_redirect;
		}

		foreach($this->extra_http_headers as $k => $v){
			$options[CURLOPT_HTTPHEADER][$k] = "$v";
		}

		if($this->user_agent){
			$options[CURLOPT_HTTPHEADER]['User-Agent'] = $this->user_agent;
		}

		if($this->lang){
			$options[CURLOPT_HTTPHEADER]['Accept-Language'] = $this->lang;
		}

		//rebuild http headers
		if($options[CURLOPT_HTTPHEADER]){
			$headers = [];
			foreach($options[CURLOPT_HTTPHEADER] as $k => $v){
				$headers[] = "$k: $v";
			}
			$options[CURLOPT_HTTPHEADER] = $headers;
		}

		if($this->timeout){
			$options[CURLOPT_TIMEOUT] = $this->timeout;
			$options[CURLOPT_CONNECTTIMEOUT] = $this->timeout;
		}
		return $options;
	}
}