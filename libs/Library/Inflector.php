<?php
	namespace Frawst\Library;
	
	/**
	 * Provides various tools for manipulating strings.
	 */
	class Inflector {
		protected static $_camelBack = array();
		protected static $_underscore = array();
		
		/**
		 * @return bool True if the first character is not lower-case
		 */
		public static function upperFirst($str) {
			$str = substr($str, 0, 1);
			return (bool) (strtolower($str) !== $str);
		}
		
		/**
		 * Converts lower-case underscored words to PascalCase.
		 * @param string $str String to be PascalCased.
		 * @return string The PascalCased string.
		 */
		public static function pascalCase($str) {
			return ucfirst(self::camelBack($str));
		}
		
		/**
		 * Converts CamelCase words to underscore format.
		 * @param string $str String to be converted to underscore.
		 * @return string The underscore formatted string.
		 */
		public static function underscore($string) {
			if(!isset(self::$_underscore[$string])) {
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
				self::$_underscore[$string] = $str;
			}
			
			return self::$_underscore[$string];
		}
		
		/**
		 * Converts under_score formatted words to camelBack format.
		 * @param string $str The underscore string to be formatted
		 * @return string The formatted string
		 */
		public static function camelBack($string) {
			if(!isset(self::$_camelBack[$string])) {
				$str = $string;
				while(false !== $pos = strpos($str, '_')) {
					$char = strtoupper(substr($str, $pos+1, 1));
					$str = substr($str, 0, $pos).$char.substr($str, $pos+2);
				}
				self::$_camelBack[$string] = $str;
			}
			
			return self::$_camelBack[$str];
		}
		
		/**
		 * @param string A string to be checked
		 * @return bool True if the string contains no lower-case characters
		 */
		public static function isUpper($str) {
			return (bool) (strtolower($str) !== $str);
		}
		
		/**
		 * @param string A string to be checked
		 * @return bool True if the string contains no upper-case characters
		 */
		public static function isLower($str) {
			return (bool) (strtoupper($str) !== $str);
		}
		
		/**
		 * Generates a URL-friendly slug of a string. This cannot necessarily
		 * be converted back to the original string.
		 * @param string $str The original string with non-alphanumeric characters
		 *                    removed, and spaces replaced with $spaceReplacement
		 * @param string $spaceReplace The character(s) to replace a space with
		 * @return string The url-friendly slug
		 */
		public static function slug($str, $spaceReplace = '_') {
			$slug = '';
			$spaceLast = true;
			for($i = 0; $i < strlen($str); $i++) {
				$ch = substr($str, $i, 1);
				if($ch === ' ' && !$spaceLast) {
					$slug .= $spaceReplace;
					$spaceLast = true;
				} elseif(ctype_alnum($ch)) {
					$spaceLast = false;
					$slug .= $ch;
				}
			}
			return $slug;
		}
	}