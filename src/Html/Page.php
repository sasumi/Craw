<?php

namespace Craw\Html;

use phpQuery;

class Page extends \DOMDocument {
	/** @var \phpQueryObject */
	public $html;
	public $charset = 'utf-8';

	public function __construct($version = '', $encoding = ''){
		parent::__construct($version, $encoding);
	}


	/**
	 * query
	 * @param $selector
	 * @param null $context
	 * @return false|\phpQueryObject
	 */
	public function query($selector, $context = null){
		return phpQuery::pq($selector, $context);
	}
}