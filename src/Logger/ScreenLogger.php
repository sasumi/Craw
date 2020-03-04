<?php

namespace craw\Logger;

use craw\Logger\LoggerAbstract;

class ScreenLogger extends LoggerAbstract {
	protected function doLog($msg, $level){
		echo "[$level] ", $msg, PHP_EOL;
	}
}