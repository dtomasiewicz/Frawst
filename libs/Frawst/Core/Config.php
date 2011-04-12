<?php
	namespace Frawst\Core;
	
	/**
	 * Config
	 *
	 * Reads variables from configuration files.
	 * @todo Make this instantiable
	 */	
	abstract class Config  {
		private static $data = array();
		
		public static function read($file, $key = null) {
			if (!array_key_exists($file, self::$data)) {
				self::load($file);
			}
			return Matrix::pathExists(self::$data[$file], $key)
				? Matrix::pathGet(self::$data[$file], $key)
				: null;
		}
		
		private static function load($file) {
			if(null !== $path = Loader::loadPath('configs/'.$file)) {
				self::$data[$file] = require $path;
			}
		}
	}