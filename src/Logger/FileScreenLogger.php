<?php

namespace Craw\Logger;
use function Craw\dump;

class FileScreenLogger extends FileLogger {
	/**
	 * do log
	 * @param $messages
	 * @param string $level
	 * @return mixed|void
	 */
	protected function doLog($messages, $level){
		$text = self::combineMessages($messages).PHP_EOL;
		echo $text;
		return parent::doLog([$text], $level);
	}
}