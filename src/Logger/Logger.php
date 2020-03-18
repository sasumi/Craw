<?php

namespace Craw\Logger;

use function Craw\var_export_min;

class Logger {
	const VERBOSE = 0; //冗余信息
	const INFO = 1; //普通信息
	const LOG = 2; //重要日志信息
	const WARN = 3; //告警
	const ERROR = 4; //错误

	const LEVEL_TEXT_MAP = [
		self::VERBOSE => 'VERBOSE',
		self::INFO    => 'INFO',
		self::LOG     => 'LOG',
		self::WARN    => 'WARN',
		self::ERROR   => 'ERROR',
	];

	const DEFAULT_ID = 'default';

	/**
	 * 收集事件
	 * @var array 处理器，格式：[[processor, collecting_level],...]
	 */
	private static $handlers = [];

	private static $log_dumps = [];
	private static $while_handlers = [];

	private $id;

	/**
	 * Logger constructor.
	 * @param $id
	 */
	public function __construct($id){
		$this->id = $id;
	}

	/**
	 * get instance
	 * @param string|null $id
	 * @return static
	 */
	public static function instance($id = ''){
		$id = $id ?: self::DEFAULT_ID;
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
		return null;
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
	 * @param callable $handler
	 * @param int $collecting_level
	 * @param string|null $filter_id
	 */
	public static function register($handler, $collecting_level = self::LOG, $filter_id = null){
		self::$handlers[] = [$handler, $collecting_level, $filter_id];
	}

	/**
	 * register while log happens on specified trigger level
	 * @param $trigger_level
	 * @param callable $handler
	 * @param int $collecting_level
	 * @param string|null $filter_id
	 */
	public static function registerWhile($trigger_level, $handler, $collecting_level = self::LOG, $filter_id = null){
		self::$while_handlers[] = [$trigger_level, $handler, $collecting_level, $filter_id];
	}

	/**
	 * @param $messages
	 * @param $level
	 * @return mixed
	 */
	private function trigger($messages, $level){
		foreach(self::$handlers as list($handler, $collecting_level, $filter_id)){
			if((!$filter_id || $filter_id == $this->id) && $level >= $collecting_level){
				//break up
				if(call_user_func($handler, $messages, $level, $this->id) === false){
					return false;
				}
			}
		}

		//trigger while handlers
		if(self::$while_handlers){
			self::$log_dumps[] = [$messages, $level];
			foreach(self::$while_handlers as list($trigger_level, $handler, $collecting_level, $filter_id)){
				if((!$filter_id || $filter_id == $this->id) && $level >= $trigger_level){
					array_walk(self::$log_dumps, function($data) use ($collecting_level, $handler){
						if($data[1] >= $collecting_level){
							call_user_func($handler, $data[0], $data[1], $this->id);
						}
					});
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