<?php
	namespace Frawst\Library;
	
	class Session {
		const COOKIE_NAME = 'Session';
		protected static $_id;
		
		public static function start() {
			if(Cookie::exists(self::COOKIE_NAME.'.SESSID')) {
				self::$_id = Cookie::get(self::COOKIE_NAME.'.SESSID');
			} else {
				self::$_id = Security::hash(microtime(true));
				Cookie::set(self::COOKIE_NAME.'.SESSID', self::$_id);
			}
		}
		
		public static function destroy() {
			Cookie::delete(self::COOKIE_NAME);
			self::start();
		}
		
		public static function id() {
			if(!isset(self::$_id)) {
				self::start();
			}
			
			return self::$_id;
		}
		
		public static function set($name, $value) {
			if(!isset(self::$_id)) {
				self::start();
			}
			
			Cookie::set(self::COOKIE_NAME.'.'.$name, $value);
		}
		
		public static function get($name) {
			return Cookie::get(self::COOKIE_NAME.'.'.$name);
		}
		
		public static function delete($name) {
			if(!isset(self::$_id)) {
				self::start();
			}
			
			Cookie::delete(self::COOKIE_NAME.'.'.$name);
		}
		
		public static function exists($name) {
			return Cookie::exists(self::COOKIE_NAME.'.'.$name);
		}
	}