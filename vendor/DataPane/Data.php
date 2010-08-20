<?php
	namespace DataPane;
	use \Frawst\Exception;
	
	abstract class Data {
		protected static $_sources;
		protected static $_config;
		
		public static function init($config) {
			self::$_sources = array();
			self::$_config = $config + array(
				'source' => array()
			);
		}
		
		public static function source($source = 'default') {
			if (!isset(self::$_sources[$source])) {
				if (isset(self::$_config['source'][$source])) {
					// separate the driver from the driver's driver
					$config = self::$_config['source'][$source];
					$driver = explode('/', $config['driver']);
					if(isset($driver[1])) {
						$config['driver'] = $driver[1];
					} else {
						unset($config['driver']);
					}
					
					$class = '\\DataPane\\Driver\\'.$driver[0];
					self::$_sources[$source] = new $class($config);
				} else {
					throw new Exception\Data('Invalid data source: '.$source);
				}
			}
			
			return self::$_sources[$source];
		}
		
		/**
		 * For chaining.
		 */
		public static function select($tables, $fields = null) {
			if (!is_null($fields)) {
				$fields = (array) $fields;
			}
			return new Query('select', $tables, array('fields' => $fields));
		}
		
		public static function update($tables) {
			return new Query('update', $tables, array());
		}
		
		public static function insert($tables) {
			return new Query('insert', $tables, array());
		}
		
		public static function delete($tables) {
			return new Query('delete', $tables, array());
		}
		
		public static function count($tables) {
			return new Query('count', $tables, array());
		}
		
		/**
		 * By default, call any not-found functions on the default connection.
		 */
		public static function __callStatic($method, $args) {
			return call_user_func_array(array(self::source(), $method), $args);
		}
	}