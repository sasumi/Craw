<?php
namespace Craw\Html;

class Page extends Parser {
	public function title(){
		return $this->query('title');
	}

	public function body(){
		return $this->query('body');
	}
}