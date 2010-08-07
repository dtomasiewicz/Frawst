<?php
	namespace Frawst\Library;
	
	/**
	 * Provides various tools for manipulating strings.
	 */
	class Inflector {
		protected static $_camelBack = array();
		protected static $_underscore = array();
		
		public static function upperFirst($str) {
			$str = substr($str, 0, 1);
			return (strtolower($str) !== $str);
		}
		
		/**
		 * Converts lower-case underscored words to CamelCase.
		 * 
		 * @param	string $str String to be CamelCased.
		 * @return string			The CamelCased string.
		 */
		public static function camelCase($str) {
			return ucfirst(self::camelBack($str));
		}
		
		/**
		 * Converts CamelCase words to underscore format. This might be faster
		 * as a regex... don't feel like benchmarking right now.
		 *
		 * @param	string $str String to be converted to underscore.
		 * @return string			The underscore formatted string.
		 */
		public static function underscore($string) {
			if (isset(self::$_underscore[$string])) {
				return self::$_underscore[$string];
			}
			
			$str = lcfirst($string);
			for ($i = 1; $i < strlen($str); $i++) {
				if (ctype_upper($str[$i])) {
					if (ctype_lower($str[$i-1])) {
						$str = substr($str, 0, $i).'_'.lcfirst(substr($str, $i));
						$i++;
					} else {
						$str[$i] = lcfirst($str[$i]);
					}
				}
			}
			
			return self::$_underscore[$string] = $str;
		}
		
		/**
		 * Same as camelCase, only leaves the first letter in lower-case (most often
		 * used for model properties).
		 *
		 * @param	string $str The underscore string to be formatted.
		 * @return string			The formatted string.
		 */
		public static function camelBack($str) {
			if (isset(self::$_camelBack[$str])) {
				return self::$_camelBack[$str];
			}
			
			while (false !== ($pos = strpos($str, '_'))) {
				$char = strtoupper(substr($str, $pos+1, 1));
				$str = substr($str, 0, $pos).$char.substr($str, $pos+2);
			}
			
			return self::$_camelBack[$str] = $str;
		}
		
		public static function isUpper($str) {
			return (strtolower($str) !== $str);
		}
		
		public static function isLower($str) {
			return (strtoupper($str) !== $str);
		}
		
		public static function slug($str) {
			return urlencode($str);
		}
	}