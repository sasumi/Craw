<?php

namespace LFPhp\Craw\IO;

use function LFPhp\Craw\array_clear_empty;
use function LFPhp\Craw\is_assoc_array;

abstract class CSV {
	/**
	 * 读取csv内容
	 * @param string $content
	 * @param array $config
	 * @return array
	 */
	public static function readCsv($content, array $config = array()){
		$config = array_merge(array(
			'start_offset'     => 1,        //数据开始行（如果首行为下标，start_offset必须大于0）
			'first_row_as_key' => true,     //是否首行作为数据下标返回（如果是，start_offset必须大于0）
			'fields'           => [],       //指定返数据下标（按顺序对应）
			'from_encoding'    => 'gb2312',
			'to_encoding'      => 'utf-8',
			'delimiter'        => ',',
		), $config);

		$data = [];
		$header = [];
		$content = iconv($config['from_encoding'], $config['to_encoding'], $content) ?: $content;
		$raws = explode("\n", $content);
		foreach($raws as $row_idx => $row_str){
			//由于str_getcsv针对编码在不同系统环境中存在较大差异化，因此这里简单实用delimiter进行切割。
			//切割过程未考虑转移字符问题。
			$row = explode($config['delimiter'], $row_str);
			$row = array_map('trim', $row);
			if($row_idx == 0){
				if($config['first_row_as_key']){
					$header = $row;
					continue;
				}
				if($config['fields']){
					$header = $config['fields'];
				}
			}
			if($row_idx >= $config['start_offset']){
				self::dataSyncLen($header, $row);
				$tmp = $config['first_row_as_key'] ? array_combine($header, $row) : $row;
				$tmp = array_clear_empty($tmp);
				if($tmp){
					$data[] = $tmp;
				}
			}
		}
		return $data;
	}

	/**
	 * 同步头部与数据长度
	 * @param $header
	 * @param $row
	 */
	private static function dataSyncLen(&$header, &$row){
		$head_len = count($header);
		$row_len = count($row);
		if($head_len > $row_len){
			$row = array_pad($row, $head_len, '');
		}else if($head_len < $row_len){
			for($i = 0; $i < ($row_len - $head_len); $i++){
				$header[] = 'Row'.($head_len + $i);
			}
		}
	}

	/**
	 * 读取CSV格式文件
	 * @param $file
	 * @param array $config
	 * @return array
	 */
	public static function readCsvFile($file, array $config = []){
		$config = array_merge(array(
			'start_offset'     => 1,        //数据开始行（如果首行为下标，start_offset必须大于0）
			'first_row_as_key' => true,     //是否首行作为数据下标返回（如果是，start_offset必须大于0）
			'fields'           => [],       //指定返数据下标（按顺序对应）
			'delimiter'        => ',',      //分隔符
			'from_encoding'    => 'gbk',    //来源编码
			'to_encoding'      => 'utf-8',  //目标编码
		), $config);

		$data = [];
		$header = [];
		self::readCsvFileChunk($file, function($row, $row_idx) use (&$data, &$header, $config){
			if($row_idx == 0){
				if($config['first_row_as_key']){
					$header = $row;
					return;
				}
				if($config['fields']){
					$header = $config['fields'];
				}
			}
			if($row_idx >= $config['start_offset']){
				self::dataSyncLen($header, $row);
				$data[] = $config['first_row_as_key'] ? array_combine($header, $row) : $row;
			}
		}, $config);
		return $data;
	}

	/**
	 * 分块读取CSV文件
	 * @param string $file CSV文件名
	 * @param callable $row_handler 行处理器，传参为：(array $row, int row_index)
	 * @param array $config 选项
	 * @return array
	 */
	public static function readCsvFileChunk($file, callable $row_handler, $config = []){
		$config = array_merge(array(
			'delimiter'     => ',',      //分隔符
			'from_encoding' => 'gbk',    //来源编码
			'to_encoding'   => 'utf-8',  //目标编码
		), $config);

		$data = [];
		$row_idx = 0;
		$fp = fopen($file, 'r');
		while(($row = fgetcsv($fp, 0, $config['delimiter'])) !== false){
			$row = array_map('utf8_encode', $row);
			$row = array_map(function($str) use ($config){
				$str = trim($str);
				return $str ? (iconv($config['from_encoding'], $config['to_encoding'], $str) ?: $str) : $str;
			}, $row);
			$row_handler($row, $row_idx);
			$row_idx++;
		}
		return $data;
	}


	/**
	 * 分块输出CSV文件
	 * 该方法会记录上次调用文件句柄，因此仅允许单个进程执行单个输出。
	 * @param $data
	 * @param array $fields 字段列表，格式如：['id','name'] 或  ['id'=>'编号', 'name'=>'名称'] 暂不支持其他方式
	 * @param $file_name
	 * @return bool
	 * @see self::exportCSVPlainChunk
	 */
	public static function exportCSVChunk($data, $fields, $file_name){
		static $csv_file_fp;
		$fields = is_assoc_array($fields) ? $fields : array_combine($fields, $fields);
		if(!isset($csv_file_fp)){
			self::headerDownloadFile($file_name);
			$csv_file_fp = fopen('php://output', 'a');
			$head = [];
			foreach($fields as $i => $v){
				$head[$i] = iconv('utf-8', 'gbk', $v);
			}
			fputcsv($csv_file_fp, $head);
		}

		$cnt = 0;   // 计数器
		$limit = 1000;  // 每隔$limit行，刷新一下输出buffer，不要太大，也不要太小
		$count = count($data);  // 逐行取出数据，不浪费内存

		for($t = 0; $t < $count; $t++){
			$cnt++;
			if($limit == $cnt){ //刷新一下输出buffer，防止由于数据过多造成问题
				ob_flush();
				flush();
				$cnt = 0;
			}
			$row = [];
			foreach($fields as $f => $n){
				$row[] = mb_convert_encoding($data[$t][$f], 'gbk', 'utf-8');
			}
			fputcsv($csv_file_fp, $row);
			unset($row);
		}
		return true;
	}

	/**
	 * 动态平铺输出CSV文件，动态列，表头与数据不需要一一对应
	 * @param array $data 二维数据
	 * @param array $headers 头部列名，格式如：['姓名','性别','年龄','编号',...]
	 * @param $file_name
	 * @return bool
	 * @see self::exportCSVChunk
	 */
	public static function exportCSVPlainChunk($data, $headers = [], $file_name = ''){
		$file_name = $file_name ?: date('YmdHi').'.csv';
		static $csv_file_fp;
		if(!isset($csv_file_fp)){
			self::headerDownloadFile($file_name);
			$csv_file_fp = fopen('php://output', 'a');
			if($headers){
				fputcsv($csv_file_fp, $headers);
			}
		}

		$cnt = 0;               //计数器
		$limit = 1000;          //每隔$limit行，刷新一下输出buffer，不要太大，也不要太小
		$count = count($data);  // 逐行取出数据，不浪费内存

		for($t = 0; $t < $count; $t++){
			$cnt++;
			if($limit == $cnt){ //刷新一下输出buffer，防止由于数据过多造成问题
				ob_flush();
				flush();
				$cnt = 0;
			}
			$row = [];
			foreach($data[$t] as $val){
				$row[] = mb_convert_encoding($val, 'gbk', 'utf-8');
			}
			fputcsv($csv_file_fp, $row);
			unset($row);
		}
		return true;
	}

	/**
	 * 输出csv格式数据
	 * @param array $data 二维数组数据
	 * @param array $headers 指定显示字段以及转换后标题，格式如：['id'=>'编号','name'=>'名称']，缺省为数据所有字段
	 * @param array $config 其他控制配置
	 */
	public static function exportCsv(array $data, array $headers = array(), array $config = array()){
		$config = array_merge(array(
			'separator'     => ',',                    //分隔符
			'filename'      => date('YmdHis').'.csv',    //输出文件名
			'from_encoding' => 'utf-8',                    //输入字符编码
			'to_encoding'   => 'gb2312'                    //输入字符编码（默认为gb2312，中文windows Excel使用）
		), $config);

		if(empty($headers)){
			$tmp = array_slice($data, 0, 1);
			$values = array_keys(array_pop($tmp));
			foreach($values as $val){
				$headers[$val] = $val;
			}
		}
		$str = implode($config['separator'], $headers)."\r\n";
		foreach($data as $item){
			$com = '';
			foreach($headers as $idx => $hd){
				$str .= $com.$item[$idx];
				$com = $config['separator'];
			}
			$str .= "\r\n";
		}
		self::headerDownloadFile($config['filename']);
		echo mb_convert_encoding($str, $config['to_encoding'], $config['from_encoding']);
		exit;
	}

	/**
	 * 输出下载文件Header
	 * @param $file_name
	 */
	private static function headerDownloadFile($file_name){
		header("Pragma: public");
		header("Expires: 0");
		header("Cache-Control: must-revalidate, post-check=0, pre-check=0");
		header("Content-Type: application/force-download");
		header("Content-Type: application/octet-stream");
		header("Content-Type: application/download");
		header("Content-Disposition: attachment;filename=".$file_name);
		header("Content-Transfer-Encoding: binary");
	}
}