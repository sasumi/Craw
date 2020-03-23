<?php

namespace Craw\Html;
/**
 * Website: http://sourceforge.net/projects/simplehtmldom/
 * Additional projects: http://sourceforge.net/projects/debugobject/
 * Acknowledge: Jose Solorzano (https://sourceforge.net/projects/php-html/)
 * Licensed under The MIT License
 * See the LICENSE file in the project root for more information.
 * Authors:
 *   S.C. Chen
 *   John Schlick
 *   Rus Carroll
 *   logmanoriginal
 * Contributors:
 *   Yousuke Kumakura
 *   Vadim Voituk
 *   Antcs
 * Version Rev. 1.9.1 (291)
 */
function file_get_html($url, $use_include_path = false, $context = null, $offset = 0, $maxLen = -1, $lowercase = true, $forceTagsClosed = true, $target_charset = DEFAULT_TARGET_CHARSET, $stripRN = true, $defaultBRText = DEFAULT_BR_TEXT, $defaultSpanText = DEFAULT_SPAN_TEXT){
	if($maxLen <= 0){
		$maxLen = MAX_FILE_SIZE;
	}

	$dom = new DOM(null, $lowercase, $forceTagsClosed, $target_charset, $stripRN, $defaultBRText, $defaultSpanText);

	/**
	 * For sourceforge users: uncomment the next line and comment the
	 * retrieve_url_contents line 2 lines down if it is not already done.
	 */
	$contents = file_get_contents($url, $use_include_path, $context, $offset, $maxLen);
	// $contents = retrieve_url_contents($url);

	if(empty($contents) || strlen($contents) > $maxLen){
		$dom->clear();
		return false;
	}

	return $dom->load($contents, $lowercase, $stripRN);
}
