<?php

namespace Craw\Logger\Output;

use Craw\Logger\Logger;

class FileOutput extends CommonAbstract {
	private $file;
	private $file_fp;
	private $format = '%H:%i:%s %m/%d {id} - {level} - {message}';

	/**
	 * @param null $log_file
	 * @return \Craw\Logger\Logger|void
	 */
	public function __construct($log_file = null){
		$log_file = $log_file ?: sys_get_temp_dir().'/craw_logger.'.date('Ymd').'.log';
		$this->setFile($log_file);
	}

	/**
	 * set log file
	 * @param string $log_file log file path
	 * @return \Craw\Logger\Output\FileOutput
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
	 * @param null $logger_id
	 * @return mixed|void
	 */
	public function output($messages, $level, $logger_id = null){
		$str = str_replace(['{id}', '{level}', '{message}'], [
			$logger_id,
			Logger::LEVEL_TEXT_MAP[$level],
			Logger::combineMessages($messages),
		], $this->format);
		$str = preg_replace_callback('/(%\w)/', function($matches){
			return date(str_replace('%', '', $matches[1]));
		}, $str);
		if(!$this->file_fp){
			$this->file_fp = fopen($this->file, 'a');
		}
		fwrite($this->file_fp, $str.PHP_EOL);
	}
}