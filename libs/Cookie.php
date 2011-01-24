<?php
	namespace Frawst;
	
	/**
	 * Wrapper class for PHP cookies.
	 */
	class Cookie {
		
		public static function get($name) {
			return Matrix::pathGet($_COOKIE, $name);
		}
		
		public static function set($name, $value, $expires = 0, $path = null, $domain = null) {
			if($path === null) {
				$path = \Frawst\WEB_ROOT;
			}
			
			if($domain === null) {
				$domain = \Frawst\DOMAIN;
			}
			
			setcookie(Matrix::dotToBracket($name), $value, $expires, $path, $domain);
			
			if($expires > time() || $expires == 0) {
				Matrix::pathSet($_COOKIE, $name, $value);
			} else {
				Matrix::pathUnset($_COOKIE, $name);
			}
		}
		
		public static function delete($name, $path = null, $domain = null) {
			self::set($name, '', time()-3600, $path, $domain);
		}
		
		public static function exists($name) {
			return Matrix::pathExists($_COOKIE, $name);
		}
		
	}