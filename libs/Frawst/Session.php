<?php
	namespace Frawst;
	
	class Session extends Object {
		const COOKIE_NAME = 'Session';
		private static $__id;
		
		public static function start() {
			if(Cookie::exists(self::COOKIE_NAME.'.SESSID')) {
				self::$__id = Cookie::get(self::COOKIE_NAME.'.SESSID');
			} else {
				self::$__id = Security::hash(microtime(true));
				Cookie::set(self::COOKIE_NAME.'.SESSID', self::$__id);
			}
		}
		
		public static function destroy() {
			Cookie::delete(self::COOKIE_NAME);
			self::start();
		}
		
		public static function id() {
			if(!isset(self::$__id)) {
				self::start();
			}
			
			return self::$__id;
		}
		
		public static function set($name, $value) {
			if(!isset(self::$__id)) {
				self::start();
			}
			
			Cookie::set(self::COOKIE_NAME.'.'.$name, $value);
		}
		
		public static function get($name) {
			return Cookie::get(self::COOKIE_NAME.'.'.$name);
		}
		
		public static function delete($name) {
			if(!isset(self::$__id)) {
				self::start();
			}
			
			Cookie::delete(self::COOKIE_NAME.'.'.$name);
		}
		
		public static function exists($name) {
			return Cookie::exists(self::COOKIE_NAME.'.'.$name);
		}
	}