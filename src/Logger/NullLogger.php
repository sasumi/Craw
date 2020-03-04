<?php

namespace Craw\Logger;

use Craw\Logger\LoggerAbstract;

class NullLogger extends LoggerAbstract {
	protected function doLog($msg, $level){}
}