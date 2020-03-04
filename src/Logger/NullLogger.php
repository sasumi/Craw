<?php

namespace craw\Logger;

use craw\Logger\LoggerAbstract;

class NullLogger extends LoggerAbstract {
	protected function doLog($msg, $level){}
}