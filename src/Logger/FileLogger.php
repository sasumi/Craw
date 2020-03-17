<?php

namespace Craw\Logger;

use function Craw\dump;

class FileLogger extends Logger {
	private $file;
	private $file_fp;
	public $format = '%H:%i:%s %m/%d [{level}] {message}';

	/**
	 * @param string $id
	 * @param null $log_file
	 * @return \Craw\Logger\Logger|void
	 */
	public static function instance($id = '', $log_file = null){
		$instance = parent::instance($id);
		$log_file = $log_file ?: sys_get_temp_dir().'/craw_logger.'.date('Ymd').'.log';
		$instance->setFile($log_file);
		return $instance;
	}

	/**
	 * set log file
	 * @param string $log_file log file path
	 * @return \Craw\Logger\FileLogger
	 */
	public function setFile($log_file){
		if(is_callable($log_file)){
			$log_file = call_user_func($log_file);
		}
		$dir = dirname($log_file);
		if(!is_dir($dir)){
			mkdir($dir, 0777, true);
		}
		$this->file = $log_file;
		return $this;
	}

	/**
	 * @param $format
	 * @return $this
	 */
	public function setFormat($format){
		$this->format = $format;
		return $this;
	}

	/**
	 * do log
	 * @param $messages
	 * @param string $level
	 * @return mixed|void
	 */
	protected function doLog($messages, $level){
		$str = str_replace(['{level}', '{message}'], [self::LEVEL_TEXT_MAP[$level], self::combineMessages($messages)], $this->format);
		$str = preg_replace_callback('/(%\w)/', function($matches){
			return date(str_replace('%', '', $matches[1]));
		}, $str);
		if(!$this->file_fp){
			$this->file_fp = fopen($this->file, 'a');
		}
		fwrite($this->file_fp, $str.PHP_EOL);
	}
}