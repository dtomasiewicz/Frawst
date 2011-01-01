<?php
	namespace Frawst;
	use \Frawst\Glue\InvalidMethodException;

	/**
	 * Used for registering an abstract interface for a service (e.g. caching
	 * mechanism). This interface can then be used by your application or the
	 * core and is engine-agnostic.
	 */
	class Glue {
		protected static $_services = array();
		
		/**
		 * @var bool If true, errors or calls to invalid itnerface functions will be ignored,
		 */
		protected $_silent;

		public function __construct($silent = false) {
			$this->_silent = $silent;
		}

		public static function service($name) {
			if(!isset(self::$_services[$name])) {
				$class = 'Frawst\\Glue\\'.$name;
				self::$_services[$name] = class_exists($class)
					? new $class()
					: false;
			}
	
			return self::$_services[$name];
		}

		public function __call($method, $args) {
			if($this->_silent) {
				return null;
			} else {
				throw new InvalidMethodException('Non-existent glue method: '.$method);
			}
		}
	}