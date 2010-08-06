<?php
	namespace Frawst;
	use \Frawst\Library\Matrix,
		\Frawst\Loader,
		\Frawst\Exception;
	
	/**
	 * Config
	 *
	 * Reads variables from configuration files.
	 */	
	abstract class Config {
		protected static $_data = array();
		
		public static function read ($dotPath) {
			$segs = explode('.', $dotPath);
			if (!array_key_exists($segs[0], self::$_data)) {
				if ($path = Loader::importPath('Frawst\\config\\'.$segs[0])) {
					$cfg = array();
					require($path);
					self::$_data[$segs[0]] = $cfg;
				} else {
					throw new Exception\Frawst('Could not load configuration file: '.$segs[0]);
				}
			}
			
			return Matrix::pathGet(self::$_data, $dotPath);
		}
	}