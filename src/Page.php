<?php
namespace Craw;

use DOMDocument;

class Page extends Parser {
	public function title(){
		return $this->query('title');
	}

	public function body(){
		return $this->query('body');
	}

	public function plainText(){

	}
}