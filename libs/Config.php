<?php
	namespace Frawst;
	
	/**
	 * Config
	 *
	 * Reads variables from configuration files.
	 * @todo Make this instantiable
	 */	
	abstract class Config {
		protected static $_data = array();
		
		public static function read($file, $value = null) {
			if (!array_key_exists($file, self::$_data)) {
				if ($path = Loader::loadPath('configs/'.$file)) {
					$cfg = array();
					require($path);
					self::$_data[$file] = $cfg;
					return Matrix::pathExists(self::$_data[$file], $value)
						? Matrix::pathGet(self::$_data[$file], $value)
						: null;
				} else {
					return self::$_data[$file] = null;
				}
			}
		}
	}