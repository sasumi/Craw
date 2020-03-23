<?php

namespace Craw\IO;

use LFPhp\Logger\Logger;

class CacheFile {
	private $cache_dir;
	private $cache_in_process = true;
	private static $process_cache = [];

	//temp目录缓存文件夹名称
	public static $temp_fold_name = 'craw_cache';

	public function __construct($cache_dir){
		if(!is_dir($cache_dir)){
			mkdir($cache_dir, 0777, true);
		}
		$this->cache_dir = $cache_dir;
	}

	/**
	 * @param $cache_dir
	 * @return self
	 */
	public static function instance($cache_dir){
		return new self($cache_dir);
	}

	/**
	 * cache in system temporary directory
	 * @return self
	 */
	public static function inTemp(){
		return new self(sys_get_temp_dir().'/'.self::$temp_fold_name);
	}

	/**
	 * 获取缓存文件名
	 * @param $cache_key
	 * @return string
	 */
	public function getFileName($cache_key){
		return $this->cache_dir.'/'.md5($cache_key).'.log';
	}

	/**
	 * @param $cache_key
	 * @return mixed|null
	 */
	public function get($cache_key){
		$cache_key = self::keyAdapter($cache_key);
		if($this->cache_in_process && isset(self::$process_cache[$cache_key])){
			return self::$process_cache[$cache_key];
		}
		$file = $this->getFileName($cache_key);
		if(file_exists($file)){
			$string = file_get_contents($file);
			if($string){
				$data = unserialize($string);
				if($data && strtotime($data['expired']) > time()){
					if($this->cache_in_process){
						self::$process_cache[$cache_key] = unserialize($data['data']);
					}
					return unserialize($data['data']);
				}
			}
			//清空无效缓存，防止缓存文件膨胀
			$this->delete($cache_key);
		}
		return null;
	}

	/**
	 * @param mixed $key
	 * @return mixed
	 */
	private static function keyAdapter($key){
		if(is_string($key)){
			return $key;
		}
		static $md_caches = [];
		$jsk = json_encode($key);
		if(!$md_caches[$jsk]){
			$md_caches[$jsk] = md5($jsk);
		}
		return $md_caches[$jsk];
	}

	/**
	 * 设置缓存
	 * @param $cache_key
	 * @param $data
	 * @param int $expired
	 * @return bool|int|mixed
	 */
	public function set($cache_key, $data, $expired = 60){
		$cache_key = self::keyAdapter($cache_key);
		$file = $this->getFileName($cache_key);
		$string = serialize(array(
			'cache_key' => $cache_key,
			'expired'   => date('Y-m-d H:i:s', time() + $expired),
			'data'      => serialize($data),
		));
		if($handle = fopen($file, 'w')){
			$result = fwrite($handle, $string);
			fclose($handle);
			if($result && $this->cache_in_process){
				self::$process_cache[$cache_key] = $data;
			}
			return $result;
		}
		return false;
	}

	/**
	 * 删除缓存
	 * @param $cache_key
	 * @return bool|mixed
	 */
	public function delete($cache_key){
		$cache_key = self::keyAdapter($cache_key);
		if(isset(self::$process_cache[$cache_key])){
			unset(self::$process_cache[$cache_key]);
		}
		$file = $this->getFileName($cache_key);
		if(file_exists($file)){
			return unlink($file);
		}
		return false;
	}

	/**
	 * 清空缓存
	 * flush cache dir
	 */
	public function flush(){
		self::$process_cache = [];
		$dir = $this->cache_dir;
		if(is_dir($dir)){
			array_map('unlink', glob($dir.'/*'));
		}
	}

	public function cache($cache_key, callable $fetcher, $expired_seconds = 60, $refresh_cache = false){
		$logger = Logger::instance(__CLASS__);
		if(!$expired_seconds && !$refresh_cache){
			$logger->debug('No expired seconds & no refresh cache, call fetcher directly.');
			return call_user_func($fetcher);
		}
		if($refresh_cache){
			$data = call_user_func($fetcher);
			$this->set($cache_key, $data, $expired_seconds);
			return $data;
		}

		$data = $this->get($cache_key);
		if(!isset($data)){
			$logger->debug('Cache expired or no exists, fetch again.');
			$data = call_user_func($fetcher);
			$this->set($cache_key, $data, $expired_seconds);
		} else {
			$logger->debug('Cache hits:', $cache_key);
		}
		return $data;
	}

	public function __invoke($cache_key, callable $fetcher, $expired_seconds = 60, $refresh_cache = false){
		return $this->cache($cache_key, $fetcher, $expired_seconds, $refresh_cache);
	}
}