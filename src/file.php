<?php

namespace LFPhp\Craw;

use Exception;
use LFPhp\Logger\Logger;
use function LFPhp\Func\csv_format;
use function LFPhp\Func\file_lines;
use function LFPhp\Func\file_put_contents_safe;
use function LFPhp\Func\file_read_by_line;
use function LFPhp\Func\mkdir_by_file;

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
 * 列表文件追加一行数据
 * @param string $list_file
 * @param mixed $row
 * @return void
 * @throws \Exception
 */
function list_file_append_row($list_file, $row){
	list_file_append_rows($list_file, [$row]);
}

/**
 * 列表文件追加多行数据
 * @param string $list_file
 * @param array[] $rows 数据，如果为空不做操作
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
 * 逐行读取列表文件
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
 * 多行读取列表文件
 * @param string $list_file
 * @param callable $payload
 * @param int $line_num 一次读取行数
 * @return void
 * @throws \Exception
 */
function list_file_read_lines_chunk($list_file, $payload, $line_num = 50){
	$list = [];
	list_file_read_line_chunk($list_file, function($row)use($payload, $line_num, &$list){
		$list[] = $row;
		if(count($list) >= $line_num){
			$payload($list);
			$list = [];
		}
	});
	if($list){
		$payload($list);
	}
}
