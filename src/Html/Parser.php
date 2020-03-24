<?php
namespace LFPhp\Craw\Html;

use DOMDocument;
use DOMXPath;

class Parser {
	private $html;
	private $dom;
	private $xpath;

	public function __construct($html){
		$this->html = $html;
		$this->dom = new DOMDocument();
		@$this->dom->loadHTML($html);
		$this->dom->normalize();
		$this->xpath = new DOMXPath($this->dom);
	}

	/**
	 * get instance
	 * @param $html
	 * @return static
	 */
	public static function instance($html){
		static $instance_list;
		$k = md5($html);
		if(!$instance_list[$k]){
			$instance_list[$k] = new self($html);
		}
		return $instance_list[$k];
	}

	/**
	 * @param string $selector
	 * @return string
	 */
	public function query($selector){
		$result = $this->xpath->query($selector);
		return $result ? $result->item(0)->textContent : null;
	}

	/**
	 * @param $selector
	 * @return array
	 */
	public function queryAll($selector){
		$result = $this->xpath->query($selector);
		if(!$result){
			return [];
		}
		$len = $result->length;
		$contents = [];
		for($i=0; $i<$len; $i++){
			$contents[] = $result->item($i)->textContent;
		}
		return $contents;
	}
}