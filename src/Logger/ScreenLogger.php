<?php

namespace Craw\Logger;

class ScreenLogger extends LoggerAbstract {
	protected function doLog($messages, $level){
		echo "[$level] ", self::combineMessages($messages), PHP_EOL;
	}
}