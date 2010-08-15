<?php
	namespace Frawst\Library;
	use \Frawst\Config;
	
	class Security {
		public static function hash($string) {
			if (!isset($salt)) {
				$salt = Config::read('Frawst.salt');
			}
			
			return sha1($salt.$string);			
		}
	}