<?php

namespace LFPhp\Craw;

use DOMDocument;
use DOMElement;
use tidy;
use function LFPhp\Func\html_abstract;

/**
 * @param string $html 注意，这里提交过来的html会额外追加utf8识别html片段。请提交utf8编码的html字符串
 * @param string $selector
 * @return \DOMElement[]
 */
function html_find_all($html, $selector){
	static $xpath_cache = [];
	if(!$html){
		return [];
	}
	if(!isset($xpath_cache[$html])){
		$dom = new DOMDocument();
		$contentType = '<meta http-equiv="Content-Type" content="text/html; charset=utf-8">';
		@$dom->loadHTML($contentType.$html);
		$xpath_cache[$html] = new \DOMXPath($dom);
	}
	$xpath = $xpath_cache[$html];
	$elements = $xpath->evaluate(selector_to_xpath($selector));
	$returns = [];
	for($i = 0, $length = $elements->length; $i < $length; ++$i){
		if($elements->item($i)->nodeType == XML_ELEMENT_NODE){
			$returns[] = $elements->item($i);
		}
	}
	return $returns;
}

function html_find_one($html, $selector){
	return html_find_all($html, $selector)[0];
}

/**
 * 获取链接
 * @param string $html
 * @param string $a_selector
 * @return string[]
 */
function html_get_links($html, $a_selector = 'a'){
	$links = html_find_all($html, $a_selector);
	$urls = [];
	foreach($links as $link){
		$urls[] = $link->getAttribute('href');
	}
	return $urls;
}

/**
 * 获取一个节点文本
 * @param string $html
 * @param string $selector
 * @return string
 */
function html_get_inner_text($html, $selector){
	$node = html_find_one($html, $selector);
	return $node ? node_get_inner_text($node) : '';
}

/**
 * 获取一个节点html内容
 * @param string $source_html
 * @param string $selector
 * @return string
 */
function html_get_inner_html($source_html, $selector){
	$node = html_find_one($source_html, $selector);
	return $node ? trim(node_get_inner_html($node)) : '';
}

function node_get_inner_html(DOMElement $node){
	$innerHTML = '';
	$children = $node->childNodes;
	foreach($children as $child){
		$innerHTML .= $child->ownerDocument->saveXML($child);
	}
	return trim($innerHTML);
}

function node_get_inner_text(DOMElement $node){
	$html = node_get_inner_html($node);
	return trim(html_abstract($html, 2000000));
}

function element_to_array($element) {
	$array = array(
		'name' => $element->nodeName,
		'attributes' => array(),
		'text' => $element->textContent,
		'children' =>elements_to_array($element->childNodes)
	);
	if ($element->attributes->length)
		foreach($element->attributes as $key => $attr)
			$array['attributes'][$key] = $attr->value;
	return $array;
}

function elements_to_array($elements) {
	$array = array();
	for ($i = 0, $length = $elements->length; $i < $length; ++$i)
		if ($elements->item($i)->nodeType == XML_ELEMENT_NODE)
			array_push($array, elements_to_array($elements->item($i)));
	return $array;
}

function selector_to_xpath($selector){
	// remove spaces around operators
	$selector = preg_replace('/\s*>\s*/', '>', $selector);
	$selector = preg_replace('/\s*~\s*/', '~', $selector);
	$selector = preg_replace('/\s*\+\s*/', '+', $selector);
	$selector = preg_replace('/\s*,\s*/', ',', $selector);
	$selectors = preg_split('/\s+(?![^\[]+\])/', $selector);

	foreach($selectors as &$selector){
		// ,
		$selector = preg_replace('/,/', '|descendant-or-self::', $selector);
		// input:checked, :disabled, etc.
		$selector = preg_replace('/(.+)?:(checked|disabled|required|autofocus)/', '\1[@\2="\2"]', $selector);
		// input:autocomplete, :autocomplete
		$selector = preg_replace('/(.+)?:(autocomplete)/', '\1[@\2="on"]', $selector);
		// input:button, input:submit, etc.
		$selector = preg_replace('/:(text|password|checkbox|radio|button|submit|reset|file|hidden|image|datetime|datetime-local|date|month|time|week|number|range|email|url|search|tel|color)/', 'input[@type="\1"]', $selector);
		// foo[id]
		$selector = preg_replace('/(\w+)\[([_\w-]+[_\w\d-]*)\]/', '\1[@\2]', $selector);
		// [id]
		$selector = preg_replace('/\[([_\w-]+[_\w\d-]*)\]/', '*[@\1]', $selector);
		// foo[id=foo]
		$selector = preg_replace('/\[([_\w-]+[_\w\d-]*)=[\'"]?(.*?)[\'"]?\]/', '[@\1="\2"]', $selector);
		// [id=foo]
		$selector = preg_replace('/^\[/', '*[', $selector);
		// div#foo
		$selector = preg_replace('/([_\w-]+[_\w\d-]*)\#([_\w-]+[_\w\d-]*)/', '\1[@id="\2"]', $selector);
		// #foo
		$selector = preg_replace('/\#([_\w-]+[_\w\d-]*)/', '*[@id="\1"]', $selector);
		// div.foo
		$selector = preg_replace('/([_\w-]+[_\w\d-]*)\.([_\w-]+[_\w\d-]*)/', '\1[contains(concat(" ",@class," ")," \2 ")]', $selector);
		// .foo
		$selector = preg_replace('/\.([_\w-]+[_\w\d-]*)/', '*[contains(concat(" ",@class," ")," \1 ")]', $selector);
		// div:first-child
		$selector = preg_replace('/([_\w-]+[_\w\d-]*):first-child/', '*/\1[position()=1]', $selector);
		// div:last-child
		$selector = preg_replace('/([_\w-]+[_\w\d-]*):last-child/', '*/\1[position()=last()]', $selector);
		// :first-child
		$selector = str_replace(':first-child', '*/*[position()=1]', $selector);
		// :last-child
		$selector = str_replace(':last-child', '*/*[position()=last()]', $selector);
		// :nth-last-child
		$selector = preg_replace('/:nth-last-child\((\d+)\)/', '[position()=(last() - (\1 - 1))]', $selector);
		// div:nth-child
		$selector = preg_replace('/([_\w-]+[_\w\d-]*):nth-child\((\d+)\)/', '*/*[position()=\2 and self::\1]', $selector);
		// :nth-child
		$selector = preg_replace('/:nth-child\((\d+)\)/', '*/*[position()=\1]', $selector);
		// :contains(Foo)
		$selector = preg_replace('/([_\w-]+[_\w\d-]*):contains\((.*?)\)/', '\1[contains(string(.),"\2")]', $selector);
		// >
		$selector = preg_replace('/>/', '/', $selector);
		// ~
		$selector = preg_replace('/~/', '/following-sibling::', $selector);
		// +
		$selector = preg_replace('/\+([_\w-]+[_\w\d-]*)/', '/following-sibling::\1[position()=1]', $selector);
		$selector = str_replace(']*', ']', $selector);
		$selector = str_replace(']/*', ']', $selector);
	}

	// ' '
	$selector = implode('/descendant::', $selectors);
	$selector = 'descendant-or-self::'.$selector;
	// :scope
	$selector = preg_replace('/(((\|)?descendant-or-self::):scope)/', '.\3', $selector);
	// $element
	$sub_selectors = explode(',', $selector);

	foreach($sub_selectors as $key => $sub_selector){
		$parts = explode('$', $sub_selector);
		$sub_selector = array_shift($parts);

		if(count($parts) && preg_match_all('/((?:[^\/]*\/?\/?)|$)/', $parts[0], $matches)){
			$results = $matches[0];
			$results[] = str_repeat('/..', count($results) - 2);
			$sub_selector .= implode('', $results);
		}

		$sub_selectors[$key] = $sub_selector;
	}

	return implode(',', $sub_selectors);
}

/**
 * 修复HTML标签
 * @param $html
 * @return string
 */
function html_repair($html){
	$tidy = new tidy();
	return $tidy->repairString($html);
}

/**
 * 修复html标签，去除body外框架
 * @param string $html
 * @return string
 */
function html_repair_and_trim_body($html){
	$html = html_repair($html);
	$html = preg_replace('/^[\s\S]+<body\s*>/i', '', $html);
	return preg_replace('/<\/body\s*>[\s\S]+/i', '', $html);
}

function html_resolve_images($html){
	$img_src_list = [];
	if(preg_match_all("/<img(\s*[^>]+)>/", $html, $matches)){
		foreach($matches[1] as $attr_str){
			if(preg_match('/\ssrc=[\'"](.*?)[\'"]/', $attr_str, $src_ms)){
				if(filter_var($src_ms[1], FILTER_VALIDATE_URL)){
					$img_src_list[] = $src_ms[1];
				}
			}
		}
	}
	return $img_src_list;
}
