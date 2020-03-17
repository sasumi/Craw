<?php

namespace Craw\Logger;

use function Craw\dump;
use function Craw\var_export_min;

class Logger {
	const VERBOSE = 0;
	const DEBUG = 1;
	const INFO = 2;
	const LOG = 3;
	const WARN = 4;
	const ERROR = 5;

	const LEVEL_TEXT_MAP = [
		self::DEBUG => 'DEBUG',
		self::INFO  => 'INFO',
		self::LOG   => 'LOG',
		self::WARN  => 'WARN',
		self::ERROR => 'ERROR',
	];

	const DEFAULT_ID = 'default';

	protected static $handlers = [];
	private $id;

	/**
	 * Logger constructor.
	 * limit to singleton
	 * @param $id
	 */
	private function __construct($id){
		$this->id = $id;
	}

	private function __clone(){
		//limit to singleton
	}

	/**
	 * get instance
	 * @param string|null $id
	 * @return static
	 */
	public static function instance($id = ''){
		$id = $id ?: self::DEFAULT_ID;
		$id = static::class.'-'.$id;
		static $instances = [];
		if(!$instances[$id]){
			$instances[$id] = new static($id);
		}
		return $instances[$id];
	}

	/**
	 * @param array $messages
	 * @param string $level
	 * @return mixed|null
	 */
	protected function doLog($messages, $level){
	}

	/**
	 * call as function
	 * @param mixed ...$messages
	 * @return mixed
	 */
	public function __invoke(...$messages){
		return call_user_func_array([$this, 'log'], $messages);
	}

	/**
	 * Logger call static
	 * @param $method
	 * @param $args
	 * @return mixed
	 */
	public static function __callStatic($method, $args){
		return call_user_func_array([self::instance(), $method], $args);
	}

	/**
	 * @param $messages
	 * @return string
	 */
	public static function combineMessages($messages){
		foreach($messages as $k => $msg){
			$messages[$k] = is_scalar($msg) ? $msg : var_export_min($msg, true);
		}
		return join(' ', $messages);
	}

	/**
	 * register handler
	 * @param $handler
	 * @param int $min_level
	 */
	public static function register($handler, $min_level = self::LOG){
		self::$handlers[] = [$handler, $min_level];
	}

	/**
	 * @param $messages
	 * @param $level
	 * @return mixed
	 */
	private function trigger($messages, $level){
		foreach(self::$handlers as list($handler, $min_level)){
			if($level >= $min_level){
				$ret = null;
				if($handler instanceof Logger){
					$ret = $handler->doLog($messages, $level);
				}else if(is_callable($handler)){
					$ret = call_user_func($handler, $messages, $level);
				}else{
					throw new \Exception('Handler no execute able');
				}
				//break up
				if($ret === false){
					return false;
				}
			}
		}
		return $this->doLog($messages, $level);
	}

	/**
	 * @param array $messages
	 * @return mixed|null
	 */
	public function verbose(...$messages){
		return $this->trigger($messages, self::VERBOSE);
	}

	/**
	 * @param array $messages
	 * @return mixed|null
	 */
	public function debug(...$messages){
		return $this->trigger($messages, self::DEBUG);
	}

	/**
	 * @param array $messages
	 * @return mixed|null
	 */
	public function info(...$messages){
		return $this->trigger($messages, self::INFO);
	}

	/**
	 * log输出
	 * @param array $messages
	 * @return mixed|null
	 */
	public function log(...$messages){
		return $this->trigger($messages, self::LOG);
	}

	/**
	 * @param array $messages
	 * @return mixed|null
	 */
	public function warn(...$messages){
		return $this->trigger($messages, self::WARN);
	}

	/**
	 * @param array $messages
	 * @return mixed|null
	 */
	public function error(...$messages){
		return $this->trigger($messages, self::ERROR);
	}
}