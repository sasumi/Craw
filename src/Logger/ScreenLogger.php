<?php

namespace Craw\Logger;

use Craw\Logger\LoggerAbstract;

class ScreenLogger extends LoggerAbstract {
	protected function doLog($msg, $level){
		echo "[$level] ", $msg, PHP_EOL;
	}
}