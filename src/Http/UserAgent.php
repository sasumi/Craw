<?php
namespace LFPhp\Craw\Http;

class UserAgent {
	public static function chrome($version = '80.0.3987.132'){
		return "Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/$version Safari/537.36";
	}
}