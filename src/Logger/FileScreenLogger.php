<?php

namespace Craw\Logger;
class FileScreenLogger extends FileLogger {
	/**
	 * do log
	 * @param $messages
	 * @param string $level
	 * @return mixed|void
	 */
	protected function doLog($messages, $level){
		echo date('H:i:s m/d '),self::combineMessages($messages), PHP_EOL;
		return parent::doLog($messages, $level);
	}
}