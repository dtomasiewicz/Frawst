<?php
	namespace Frawst;
	
	/**
	 * Config
	 *
	 * Reads variables from configuration files.
	 * @todo Make this instantiable
	 */	
	abstract class Config extends Base {
		private static $__data = array();
		
		public static function read($file, $value = null) {
			if (!array_key_exists($file, self::$__data)) {
				if ($path = Loader::loadPath('configs/'.$file)) {
					$cfg = array();
					require($path);
					self::$__data[$file] = $cfg;
					return Matrix::pathExists(self::$__data[$file], $value)
						? Matrix::pathGet(self::$__data[$file], $value)
						: null;
				} else {
					return self::$__data[$file] = null;
				}
			}
		}
	}