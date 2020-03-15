<?php

namespace Craw\Logger;
use function Craw\dump;

class FileLogger extends LoggerAbstract {
	private $file;
	private $file_fp;
	public $format = '%Y-%m-%d %H:%i:%s [{level}] {message}';

	public function __construct($log_file = null){
		$log_file = $log_file ?: sys_get_temp_dir().'/craw_logger.'.date('Ymd').'.log';
		if(is_callable($log_file)){
			$log_file = call_user_func($log_file);
		}
		$dir = dir($log_file);
		if(!is_dir($dir)){
			mkdir($dir, 0777, true);
		}
		$this->file = $log_file;
	}

	/**
	 * do log
	 * @param $messages
	 * @param string $level
	 * @return mixed|void
	 */
	protected function doLog($messages, $level){
		$str = str_replace(['{level}', '{message}'], [$level, self::combineMessages($messages)], $this->format);
		$str = preg_replace_callback('/(%\w)/', function($date_format){
			dump($date_format, 1);
			return date($date_format);
		}, $str);
		if(!$this->file_fp){
			$this->file_fp = fopen($this->file, 'a');
		}
		fwrite($this->file_fp, $str);
	}

	public function __destruct(){
		fclose($this->file_fp);
		$this->file_fp = null;
	}
}