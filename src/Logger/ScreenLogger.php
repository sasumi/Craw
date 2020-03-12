<?php

namespace Craw\Logger;

class ScreenLogger extends LoggerAbstract {
	protected function doLog($messages, $level){
		echo date('H:i:s m/d '),self::combineMessages($messages), PHP_EOL;
	}
}