<?php

namespace Craw\Logger;

use function Craw\var_export_min;

abstract class LoggerAbstract {
	const DEBUG = 'DEBUG';
	const INFO = 'INFO';
	const LOG = 'LOG';
	const WARN = 'WARN';
	const ERROR = 'ERROR';

	public function __invoke(...$messages){
		return call_user_func_array([$this, 'log'], $messages);
	}

	protected static function combineMessages($messages){
		foreach($messages as $k=>$msg){
			$messages[$k] = is_scalar($msg) ? $msg : var_export_min($msg, true);
		}
		return join(' ',$messages);
	}

	/**
	 * @param array $messages
	 * @param string $level
	 * @return mixed
	 */
	abstract protected function doLog($messages, $level);

	/**
	 * @param array $messages
	 * @return mixed|null
	 */
	public function debug(...$messages){
		return $this->doLog($messages, self::DEBUG);
	}

	/**
	 * @param array $messages
	 * @return mixed|null
	 */
	public function info(...$messages){
		return $this->doLog($messages, self::INFO);
	}

	/**
	 * log输出
	 * @param array $messages
	 * @return mixed|null
	 */
	public function log(...$messages){
		return $this->doLog($messages, self::LOG);
	}

	/**
	 * @param array $messages
	 * @return mixed|null
	 */
	public function warn(...$messages){
		return $this->doLog($messages, self::WARN);
	}

	/**
	 * @param array $messages
	 * @return mixed|null
	 */
	public function error(...$messages){
		return $this->doLog($messages, self::ERROR);
	}
}