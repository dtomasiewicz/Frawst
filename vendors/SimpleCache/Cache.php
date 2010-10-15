<?php
	namespace SimpleCache;
	use \Frawst\Library\Matrix;
	
	abstract class Cache {
		protected static $_Engine;
		protected static $_config;
		
		public static function init($config = array()) {
			// defaults
			self::$_config = $config + array(
				'engine' => 'File',
				'enable' => false,
				'noCache' => array()
			);
			
			if (self::$_config['enable']) {
				$class = '\\SimpleCache\\Engine\\'.self::$_config['engine'];
				self::$_Engine = new $class(self::$_config);
			}
		}
		
		public static function read($name) {
			if (self::enabled() && self::cacheable($name)) {
				return self::$_Engine->get($name);
			} else {
				return null;
			}
		}
		
		public static function write($name, $value, $life = 0) {
			if (self::enabled() && self::cacheable($name)) {
				self::$_Engine->set($name, $value, $life);
			}
		}
		
		public static function expire($name) {
			if (self::enabled() && self::cacheable($name)) {
				self::$_Engine->expire($name);
			}
		}
		
		public static function enabled() {
			return self::$_config['enable'];
		}
		
		public static function cacheable($name) {
			$parts = explode('.', $name);
			$path = array();
			while (count($parts) > 0) {
				$path[] = array_shift($parts);
				$p = implode('.', $path);
				
				if (Matrix::pathExists(self::$_config['noCache'], $p)
					&& Matrix::pathGet(self::$_config['noCache'], $p) === true) {
					return false;
				}
			}
			return true;
		}
	}