<?php
	namespace Frawst\Test;
	
	class Stub {
		private static $__classSeeds = array();
		private $__seeds;
		
		public function __construct() {
			$this->__seeds = array();
		}
		
		public function seedReturn($method, $return, $args = null) {
			self::__addSeed($this->__seeds, $method, array('return' => $return, 'args' => $args));
		}
		
		public static function seedClassReturn($method, $return, $args = null) {
			self::__addClassSeed(get_called_class(), $method, array('return' => $return, 'args' => $args));
		}
		
		public function seedImplementation($method, $impl) {
			self::__addSeed($this->__seeds, $method, array('impl' => $impl, 'args' => null));
		}
		
		public static function seedClassImplementation($method, $impl) {
			self::__addClassSeed(get_called_class(), $method, array('impl' => $impl, 'args' => null));
		}
		
		public function clearSeeds($method = null) {
			if($method === null) {
				$this->__seeds = array();
			} elseif(isset($this->__seeds[$method])) {
				unset($this->__seeds[$method]);
			}
		}
		
		public static function clearClassSeeds($method = null) {
			$cc = get_called_class();
			if($cc === __CLASS__) {
				self::$__classSeeds = array();
			} elseif(isset(self::$__classSeeds[$cc])) {
				if($method === null) {
					unset(self::$__classSeeds[$cc]);
				} elseif(isset(self::$__classSeeds[$cc][$method])) {
					unset(self::$__classSeeds[$cc][$method]);
				}
			}
		}
		
		private static function __addSeed(&$seeds, $method, $seed) {
			if(!isset($seeds[$method])) {
				$seeds[$method] = array();
			}
			
			$seeds[$method][] = $seed;
		}
		
		private static function __addClassSeed($class, $method, $seed) {
			if(!isset(self::$__classSeeds[$class])) {
				self::$__classSeeds[$class] = array();
			}
			self::__addSeed(self::$__classSeeds[$class], $method, $seed);
		}
		
		public static function getClassSeed($method, $args = null) {
			$cc = get_called_class();
			if(isset(self::$__classSeeds[$cc])
			  && $seed = self::__getSeed(self::$__classSeeds[$cc], $method, $args)) {
				return self::__evalSeed($seed, $args);
			} else {
				self::__seedNotFound($cc, $method, $args);
			}
		}
		
		public function getSeed($method, $args = null) {
			if($seed = self::__getSeed($this->__seeds, $method, $args)) {
				return self::__evalSeed($seed, $args);
			} else {
				return static::getClassSeed($method, $args);
			}
		}
		
		public function __call($method, $args) {
			return $this->getSeed($method, $args);
		}
		
		public static function __callStatic($method, $args) {
			return static::getClassSeed($method, $args);
		}
		
		/**
		 * Get a seed based on method and arguments.
		 *   seeded return > seeded implementation
		 */
		private static function __getSeed(&$seeds, $method, $args) {
			$matchSeed = null;
			if(isset($seeds[$method])) {
				foreach($seeds[$method] as &$seed) {
					if($seed['args'] === null) {
						if($matchSeed === null || array_key_exists('impl', $matchSeed)) {
							$matchSeed = $seed;
						}
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
			}
			return $matchSeed;
		}
		
		private static function __evalSeed(&$seed, $args) {
			if(isset($seed['impl'])) {
				return call_user_func_array($seed['impl'], $args);
			} else {
				return $seed['return'];
			}
		}
		
		private static function __seedNotFound($class, $method, $args) {
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
			throw new \Exception($class.'::'.$method.'() was not seeded for '.$argsType);
		}
		
	}