<?php
	namespace Frawst\Core;
	
	class Security  {
		const CSRF_COOKIE_NAME = 'CSRF';
		private static $requireCookie = true;
		
		/**
		 * @return a unique identifier
		 */
		public static function unique() {
			return uniqid('', true);
		}
		
		/**
		 * Creates a SHA1 hash of a given string, automatically appending the configured
		 * security salt.
		 * @param string $string The string to be hashed
		 * @param string $salt The salt to be used. If omitted, the salt in Frawst config will
		 *                     be used.
		 * @return string
		 */
		public static function hash($string, $salt = null) {
			if (!isset($salt)) {
				$salt = Config::read('frawst', 'salt');
			}
			
			return sha1($salt.$string);			
		}
		
		public static function randomAscii($length, $printable = true) {
			$str = '';
			for($i = 0; $i < $length; $i++) {
				$str .= chr($printable ? mt_rand(0,128) : mt_rand(32,126));
			}
			return $str;
		}
		
		/**
		 * Creates a general-purpose security token associated with the client's session
		 * ID and the current microtime. This token can be used to protect against CSRF,
		 * ensuring malicious unauthorized requests do not succeed.
		 * @param string $salt A salt to be used (in addition to the configured salt) to
		 *                     differentiate tokens with different origins. For example, if
		 *                     you include a form name as the salt when creating and checking
		 *                     a token, it ensures the token was not from a different form.
		 * @return string
		 */
		public static function makeToken($xid = '') {
			$time = microtime(true);
			$token = substr(self::hash($time, $xid), 0, 16);
			Cookie::set(self::CSRF_COOKIE_NAME.'.'.$token, $time);
			return $token.'-'.$time;
		}
		
		/**
		 * Checks a security token for validity.
		 * @param string $token
		 * @param string $xid The salt string that was used to make the token
		 * @param int $timeframe The timeframe for which the token is valid after being
		 *                       created. If 0 (default), tokens will last forever or
		 *                       until cookies are deleted by the user.
		 * @return bool True if the token is valid, false if not
		 */
		public static function checkToken($token, $xid = '', $timeframe = 0) {
			$parts = explode('-', $token);
			if(count($parts) == 2 && is_numeric($parts[1])) {
				if($timeframe <= 0 || microtime(true) <= $parts[1]+$timeframe) {
					$reconstruct = substr(self::hash($parts[1], $xid), 0, 16);
					if($reconstruct == $parts[0]) {
						if(!self::$requireCookie) {
							return true;
						} elseif(Cookie::exists($c = self::CSRF_COOKIE_NAME.'.'.$reconstruct)) {
							Cookie::delete($c);
							return true;
						}
					}
				}
			}
			
			return false;
		}
	}