<?php

namespace LFPhp\Craw;

use Exception;
use LFPhp\Logger\Logger;
use function LFPhp\Func\csv_format;
use function LFPhp\Func\file_lines;
use function LFPhp\Func\file_put_contents_safe;
use function LFPhp\Func\file_read_by_line;
use function LFPhp\Func\mkdir_by_file;

/***********************************************************
 * LIST FILE FORMAT：every line is a json string
 ***********************************************************/

/**
 * @param $arr
 * @param $field_map
 * @return array|mixed
 */
function filter_fields($arr, $field_map = []){
	if(!$field_map){
		return $arr;
	}
	$tmp = [];
	foreach($field_map as $field => $name){
		$tmp[] = $arr[$field] ?: '';
	}
	return $tmp;
}

/**
 * create & save list file
 * @param $list_file
 * @param array $rows init rows
 * @return void
 * @throws \Exception
 */
function list_file_save_rows($list_file, $rows = []){
	mkdir_by_file($list_file);
	$tmp = [];
	foreach($rows as $row){
		$tmp[] = json_encode($row, JSON_UNESCAPED_UNICODE);
	}
	Logger::debug('List file created:'.$list_file);
	file_put_contents_safe($list_file, $tmp ? join("\n", $tmp).PHP_EOL : '');
}

/**
 * convert list format file to csv format file
 * @param string $list_file
 * @param string $csv_file
 * @param array $field_map
 * @return void
 * @throws \Exception
 */
function list_file_to_csv($list_file, $csv_file, $field_map = []){
	if(!($hd = fopen($list_file, 'r'))){
		throw new Exception('file open fail');
	}

	mkdir_by_file($csv_file);
	$lines_handle = function($lines, $line_no) use ($csv_file, $field_map){
		$write_header = $line_no == 1;
		$csv_str = '';
		foreach($lines as $line){
			$data = json_decode($line, true);
			if($write_header){
				$fs = $field_map ? array_values($field_map) : array_keys($data);
				$csv_str .= join(",", csv_format($fs)).PHP_EOL;
				$write_header = false;
			}
			$csv_str .= join(",", csv_format(filter_fields($data, $field_map))).PHP_EOL;
		}
		file_put_contents_safe($csv_file, $csv_str, FILE_APPEND);
	};

	$last_line_buff = '';
	$line_no = 1;
	while(!feof($hd)){
		$buff = $last_line_buff.fgets($hd, 4096);
		$buff = str_replace("\r", "", $buff);
		$lines = explode("\n", $buff);
		$last_line_buff = array_pop($lines);
		if($lines){
			$lines_handle($lines, $line_no);
			$line_no += count($lines);
		}
	}
	fclose($hd);
	if($last_line_buff){
		$lines_handle([$last_line_buff], $line_no++);
	}
}

/**
 * append json to list file
 * @param string $list_file
 * @param mixed $row
 * @return void
 * @throws \Exception
 */
function list_file_append_row($list_file, $row){
	list_file_append_rows($list_file, [$row]);
}

/**
 * append json list to list file
 * @param string $list_file
 * @param array $rows
 * @return false|void
 * @throws \Exception
 */
function list_file_append_rows($list_file, $rows){
	if(!$rows){
		return false;
	}
	$tmp = [];
	foreach($rows as $row){
		$tmp[] = json_encode($row, JSON_UNESCAPED_UNICODE);
	}
	file_put_contents_safe($list_file, join("\n", $tmp).PHP_EOL, FILE_APPEND);
}

/**
 * convert list file to array
 * @param string $list_file
 * @return array
 * @throws \Exception
 */
function list_file_to_array($list_file){
	$tmp = [];
	file_read_by_line($list_file, function($line)use(&$tmp){
		if($line){
			$tmp[] = json_decode($line, true);
		}
	});
	return $tmp;
}

/**
 * read list file chunk
 * @param string $list_file
 * @param callable $payload
 * @return void
 * @throws \Exception
 */
function list_file_read_line_chunk($list_file, $payload){
	$line_total = file_lines($list_file);
	file_read_by_line($list_file, function($text, $line_no)use($payload, $line_total){
		if($text){
			return $payload(json_decode($text, true), $line_no, $line_total);
		}
		return true;
	});
}

/**
 * read json file chunks
 * @param string $list_file
 * @param callable $payload
 * @param int $line_num line count
 * @return void
 * @throws \Exception
 */
function list_file_read_lines_chunk($list_file, $payload, $line_num = 50){
	$list = [];
	$break = false;
	$_line_total = 0;
	$_line_no = 0;
	list_file_read_line_chunk($list_file, function($row, $line_no, $line_total) use ($payload, $line_num, &$list, &$break, &$_line_total, &$_line_no){
		$_line_total = $line_total;
		$_line_no = $line_no;
		$list[] = $row;
		if(count($list) >= $line_num){
			if($payload($list, $line_no - count($list), $line_total) === false){
				$break = true;
				return false;
			}
			$list = [];
		}
		return true;
	});
	if(!$break && $list){
		$payload($list, $_line_no - count($list), $_line_total);
	}
}
