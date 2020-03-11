<?php

namespace Craw\Logger;
class NullLogger extends LoggerAbstract {
	protected function doLog($messages, $level){}
}