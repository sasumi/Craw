<?php

namespace Craw\Logger\Output;

use Craw\Logger\Logger;

abstract class CommonAbstract {
	abstract public function output($messages, $level, $logger_logger_id);

	/**
	 * call as function
	 * @param $messages
	 * @param int $level
	 * @param string|null $logger_id
	 * @return mixed
	 */
	public function __invoke($messages, $level = Logger::LOG, $logger_id = null){
		return $this->output($messages, $level, $logger_id);
	}
}