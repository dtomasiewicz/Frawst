<?php
	namespace Frawst;
	
	class Security extends Object {
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
				$salt = self::callDefaultImplementation('configRead', 'Frawst', 'salt');
			}
			
			return sha1($salt.$string);			
		}
		
		public static function randomAscii($length) {
			$str = '';
			for($i = 0; $i < $length; $i++) {
				$str .= chr(mt_rand(0,255));
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
		public static function makeToken($salt = '') {
			$time = microtime(true);
			return substr(self::hash(Session::id().$salt.$time), 0, 20).'-'.$time;
		}
		
		/**
		 * Checks a security token for validity.
		 * @param string $token
		 * @param string $salt The salt string that was used to make the token
		 * @param int $timeframe The timeframe for which the token is valid after being
		 *                       created. If 0 (default), tokens will not expire until the
		 *                       session does. 
		 * @return bool True if the token is valid, false if not
		 */
		public static function checkToken($token, $salt = '', $timeframe = null) {
			$parts = explode('-', $token);
			if(count($parts) != 2 || !is_numeric($parts[1])) {
				return false;
			} else {
				if($timeframe && microtime(true) > $parts[1]+$timeframe) {
					// token is expired
					return false;
				}
				return (bool) (substr(self::hash(Session::id().$salt.$parts[1]), 0, 20) == $parts[0]);
			}
		}
	}