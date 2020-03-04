<?php

namespace Craw\Logger;

abstract class LoggerAbstract {
	const DEBUG = 'DEBUG';
	const INFO = 'INFO';
	const LOG = 'LOG';
	const WARN = 'WARN';
	const ERROR = 'ERROR';

	/**
	 * @param $msg
	 * @param string $level
	 * @return mixed
	 */
	abstract protected function doLog($msg, $level);

	/**
	 * @param $msg
	 * @return mixed|null
	 */
	public function debug($msg){
		return $this->doLog($msg, self::DEBUG);
	}

	/**
	 * @param $msg
	 * @return mixed|null
	 */
	public function info($msg){
		return $this->doLog($msg, self::INFO);
	}

	/**
	 * log输出
	 * @param $msg
	 * @return mixed|null
	 */
	public function log($msg){
		return $this->doLog($msg, self::LOG);
	}

	/**
	 * @param $msg
	 * @return mixed|null
	 */
	public function warn($msg){
		return $this->doLog($msg, self::WARN);
	}

	/**
	 * @param $msg
	 * @return mixed|null
	 */
	public function error($msg){
		return $this->doLog($msg, self::ERROR);
	}
}