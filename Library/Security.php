<?php
	namespace Frawst\Library;
	use \Frawst\Config;
	
	class Security {
		public static function hash($string) {
			if(!isset($salt)) {
				$salt = Config::read('General.salt');
			}
			
			return sha1($salt . md5($string));			
		}
		/**
		 * //@TODO implement encryption algorithm
		 */
		public static function encrypt($str) {
			return base64_encode($str);
		}
		public static function decrypt($str) {
			return base64_decode($str);
		}
	}