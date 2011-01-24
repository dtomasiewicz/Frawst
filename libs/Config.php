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
		
		public static function read($dotPath) {
			$segs = explode('.', $dotPath);
			if (!array_key_exists($segs[0], self::$_data)) {
				if ($path = Loader::importPath('configs\\'.$segs[0])) {
					$cfg = array();
					require($path);
					self::$_data[$segs[0]] = $cfg;
				} else {
					return self::$_data[$segs[0]] = null;
				}
			}
			
			return Matrix::pathGet(self::$_data, $dotPath);
		}
	}