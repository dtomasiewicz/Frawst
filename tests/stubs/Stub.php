<?php
	namespace Frawst;
	
	class Stub {
		private static $__seedsStatic;
		private $__seeds;
		
		public function __construct() {
			$this->__seeds = array();
		}
		private static function __addMethodSeed(&$seeds, $method, $return, $args) {
			if(!isset($seeds[$method])) {
				$seeds[$method] = array();
			}
			
			$seeds[$method][] = array(
				'args' => $args,
				'return' => $return
			);
		}
		private static function __getMethodSeed(&$seeds, $method, $args) {
			if(isset($seeds[$method])) {
				$matchSeed = null;
				foreach($seeds[$method] as &$seed) {
					if($seed['args'] === null) {
						$matchSeed = $seed;
					} elseif(is_array($args)) {
						if(count($args) == count($seed['args'])) {
							$match = true;
							for($i = 0; $i < count($args); $i++) {
								if($args[$i] !== $seed['args'][$i]) {
									$match = false;
								}
							}
							if($match) {
								$matchSeed = $seed;
							}
						}
					}
				}
				if($matchSeed) {
					return $matchSeed['return'];
				}
			}
			
			if($args === null) {
				$argsType = 'a null argument set';
			} elseif(is_array($args)) {
				$argsType = array();
				foreach($args as $arg) {
					if(is_object($arg)) {
						$argsType[] = get_class($arg);
					} else {
						$argsType[] = gettype($arg);
					}
				}
				$argsType = '('.implode(',', $argsType).')'; 
			}
			throw new \Exception(get_called_class().'::'.$method.'() was not seeded for '.$argsType);
		}
		public function seedMethod($method, $return, $args = null) {
			static::__addMethodSeed($this->__seeds, $method, $return, $args);
		}
		public static function seedMethodStatic($method, $return, $args = null) {
			static::__addMethodSeed(self::$__seedsStatic, $method, $return, $args);
		}
		protected function _getSeed($method, $args = null) {
			return static::__getMethodSeed($this->__seeds, $method, $args);
		}
		protected static function _getSeedStatic($method, $args = null) {
			return static::__getMethodSeed(self::$__seedsStatic, $method, $args);
		}
		public function __call($method, $args) {
			return static::__getMethodSeed($this->__seeds, $method, $args);
		}
		public static function __callStatic($method, $args) {
			return static::__getMethodSeed(self::$__seedsStatic, $method, $args);
		}
	}