<?php

namespace Craw;

use DateTime;
use DateTimeZone;

/**
 * check array is an assoc array
 * @param $arr
 * @return bool
 */
function is_assoc($arr){
	if(array() === $arr)
		return false;
	return array_keys($arr) !== range(0, count($arr) - 1);
}

/**
 * 划分范围
 * @param $start
 * @param $end
 * @param $size
 * @return \Generator
 */
function range_slice($start, $end, $size){
	$page_count = ceil(($end - $start)/$size);
	for($i = 0; $i < $page_count; $i++){
		yield [$start + $i*$size, min($start + ($i + 1)*$size, $end)];
	}
}

/**
 * @param $timezone_title
 * @return float|int
 * @throws \Exception
 */
function get_timezone_offset_min_between_gmt($timezone_title) {
	$dtz = new DateTimeZone($timezone_title);
	$dt = new DateTime("now", $dtz);
	return $dtz->getOffset($dt)/60;
}

/**
 * convert data to request string
 * @param $data
 * @return string
 * @throws \Exception
 */
function data_to_string($data){
	if(is_scalar($data)){
		return (string)$data;
	}
	if(is_array($data)){
		$d = [];
		if(is_assoc($data)){
			foreach($data as $k => $v){
				if(is_null($v)){
					continue;
				}
				if(is_scalar($v)){
					$d[] = urlencode($k).'='.urlencode($v);
				}else{
					throw new \Exception('Data type no support(more than 3 dimension array no supported)');
				}
			}
		}else{
			$d += $data;
		}
		return join('&', $d);
	}
	throw new \Exception('Data type no supported');
}

function dump(){
	$params = func_get_args();
	$cli = PHP_SAPI === 'cli';
	$exit = false;
	echo !$cli ? PHP_EOL.'<pre style="color:green;">'.PHP_EOL : PHP_EOL;

	if(count($params)){
		$tmp = $params;
		$exit = array_pop($tmp) === 1;
		$params = $exit ? array_slice($params, 0, -1) : $params;
		$comma = '';
		foreach($params as $var){
			echo $comma;
			var_dump($var);
			$comma = str_repeat('-', 80).PHP_EOL;
		}
	}

	//remove closure calling & print out location.
	$trace = debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS);
	if($GLOBALS['DUMP_WITH_TRACE']){
		echo "[trace]", PHP_EOL;
		print_trace($trace, true, true);
	}else{
		print_trace([$trace[0]]);
	}
	echo str_repeat('=', 80), PHP_EOL, (!$cli ? '</pre>' : '');
	$exit && exit();
}

/**
 * 打印trace信息
 * @param $trace
 * @param bool $with_callee
 * @param bool $with_index
 */
function print_trace($trace, $with_callee = false, $with_index = false){
	$ct = count($trace);
	foreach($trace as $k => $item){
		$callee = '';
		if($with_callee){
			$vs = [];
			foreach($item['args'] as $arg){
				$vs[] = var_export_min($arg, true);
			}
			$arg_statement = join(',', $vs);
			$arg_statement = substr(str_replace("\n", '', $arg_statement), 0, 50);
			$callee = $item['class'] ? "\t{$item['class']}{$item['type']}{$item['function']}($arg_statement)" : "\t{$item['function']}($arg_statement)";
		}
		if($with_index){
			echo "[", ($ct - $k), "] ";
		}
		$loc = $item['file'] ? "{$item['file']} #{$item['line']} " : '';
		echo "{$loc}{$callee}", PHP_EOL;
	}
}

/**
 * 检测数组是否为关联数组
 * @param array $array
 * @return boolean
 */
function is_assoc_array($array){
	return is_array($array) && array_values($array) != $array;
}


/**
 * 清理数组中empty的元素
 * @param $data
 * @param bool $recursive
 * @return array
 */
function array_clear_empty($data, $recursive = true){
	if(empty($data) || !is_array($data)){
		return $data;
	}
	foreach($data as $k => $item){
		if(empty($item)){
			unset($data[$k]);
		}
		if($recursive && is_array($item)){
			$data[$k] = array_clear_empty($item);
			if(empty($data[$k])){
				unset($data[$k]);
			}
		}
	}
	return $data;
}

/**
 * preg match one
 * @param $regex
 * @param $str
 * @param bool $throw_exception
 * @return string|null
 * @throws \Exception
 */
function preg_match_one($regex, $str, $throw_exception = true){
	$hit = preg_match($regex, $str, $matches);
	if(!$hit && $throw_exception){
		throw new \Exception("No match regexp: $regex, for string:$str");
	}
	if($hit){
		return trim($matches[1]);
	}
	return null;
}

/**
 * var_export in minimal format
 * @param $var
 * @param bool $return
 * @return mixed|string
 */
function var_export_min($var, $return = false){
	if(is_array($var)){
		$toImplode = array();
		foreach($var as $key => $value){
			$toImplode[] = var_export($key, true).'=>'.var_export_min($value, true);
		}
		$code = 'array('.implode(',', $toImplode).')';
		if($return){
			return $code;
		}else echo $code;
	}else{
		return var_export($var, $return);
	}
}