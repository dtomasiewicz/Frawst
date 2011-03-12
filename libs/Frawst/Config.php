<?php
	namespace Frawst;
	
	/**
	 * Config
	 *
	 * Reads variables from configuration files.
	 * @todo Make this instantiable
	 */	
	abstract class Config extends Base {
		private static $data = array();
		
		public static function read($file, $value = null) {
			if (!array_key_exists($file, self::$data)) {
				if ($path = Loader::loadPath('configs/'.$file)) {
					$cfg = array();
					require($path);
					self::$data[$file] = $cfg;
					return Matrix::pathExists(self::$data[$file], $value)
						? Matrix::pathGet(self::$data[$file], $value)
						: null;
				} else {
					return self::$data[$file] = null;
				}
			}
		}
	}