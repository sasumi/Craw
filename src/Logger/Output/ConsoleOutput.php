<?php

namespace Craw\Logger\Output;

use Craw\Logger\Logger;

class ConsoleOutput extends CommonAbstract {
	public function output($messages, $level, $logger_logger_id = null){
		echo date('H:i:s m/d'), " $logger_logger_id", ' - '.Logger::LEVEL_TEXT_MAP[$level].' - ', Logger::combineMessages($messages), PHP_EOL;
	}
}