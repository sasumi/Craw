<?php

use LFPhp\Logger\Logger;
use LFPhp\Logger\LoggerLevel;
use LFPhp\Logger\Output\ConsoleOutput;

include __DIR__.'/../vendor/autoload.php';
Logger::registerGlobal(new ConsoleOutput(), LoggerLevel::DEBUG);
