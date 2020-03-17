<?php

namespace Craw\Logger;

class ConsoleLogger extends Logger {
	protected function doLog($messages, $level){
		echo date('H:i:s m/d '), self::combineMessages($messages), PHP_EOL;
	}
}