<?php
	namespace Frawst\Test;
	
	class Double {
		private static $classSeeds = array();
		private $seeds;
		
		private static $staticCalls = array();
		private $calls;
		
		public function __construct() {
			$this->seeds = array();
			$this->calls = array();
		}
		
		public function seedReturn($method, $return, $args = null) {
			self::addSeed($this->seeds, $method, array('return' => $return, 'args' => $args));
		}
		
		public function seedReturns($method, $return, $argLists) {
			foreach($argLists as $argList) {
				$this->seedReturn($method, $return, $argList);
			}
		}
		
		public static function seedClassReturn($method, $return, $args = null) {
			self::addClassSeed(get_called_class(), $method, array('return' => $return, 'args' => $args));
		}
		
		public static function seedClassReturns($method, $return, $argLists) {
			foreach($argLists as $argList) {
				self::seedClassReturn($method, $return, $argList);
			}
		}
		
		public function seedImplementation($method, $impl) {
			self::addSeed($this->seeds, $method, array('impl' => $impl, 'args' => null));
		}
		
		public static function seedClassImplementation($method, $impl) {
			self::addClassSeed(get_called_class(), $method, array('impl' => $impl, 'args' => null));
		}
		
		public function clearSeeds($method = null) {
			if($method === null) {
				$this->seeds = array();
			} elseif(isset($this->seeds[$method])) {
				unset($this->seeds[$method]);
			}
		}
		
		public static function clearClassSeeds($method = null) {
			$cc = get_called_class();
			if($cc === __CLASS__) {
				self::$classSeeds = array();
			} elseif(isset(self::$classSeeds[$cc])) {
				if($method === null) {
					unset(self::$classSeeds[$cc]);
				} elseif(isset(self::$classSeeds[$cc][$method])) {
					unset(self::$classSeeds[$cc][$method]);
				}
			}
		}
		
		public static function getClassSeed($method, $args = null) {
			$cc = get_called_class();
			if(isset(self::$classSeeds[$cc])
			  && $seed = self::getSeedInternal(self::$classSeeds[$cc], $method, $args)) {
				return self::evalSeed($seed, $args);
			} else {
				self::seedNotFound($cc, $method, $args);
			}
		}
		
		public function getSeed($method, $args = null) {
			if($seed = self::getSeedInternal($this->seeds, $method, $args)) {
				return self::evalSeed($seed, $args);
			} else {
				return static::getClassSeed($method, $args);
			}
		}
		
		public function getTimesCalled($method) {
			if(isset($this->calls[$method])) {
				return count($this->calls[$method]);
			} else {
				return 0;
			}
		}
		
		public static function getStaticTimesCalled($method) {
			$cc = get_called_class();
			if(isset(self::$classCalls[$cc]) && isset(self::$classCalls[$cc][$method])) {
				return count(self::$classCalls[$cc][$method]);
			} else {
				return 0;
			}
		}
		
		private static function addSeed(&$seeds, $method, $seed) {
			if(!isset($seeds[$method])) {
				$seeds[$method] = array();
			}
			
			$seeds[$method][] = $seed;
		}
		
		private static function addClassSeed($class, $method, $seed) {
			if(!isset(self::$classSeeds[$class])) {
				self::$classSeeds[$class] = array();
			}
			self::addSeed(self::$classSeeds[$class], $method, $seed);
		}
		
		public static function addStaticCall($method, $args) {
			$cc = get_called_class();
			if(!isset(self::$staticCalls[$cc])) {
				self::$staticCalls[$cc] = array();
			}
			if(!isset(self::$staticCalls[$cc][$method])) {
				self::$staticCalls[$cc][$method] = array();
			}
			self::$staticCalls[$cc][$method][] = $args;
		}
		
		public function addCall($method, $args) {
			if(!isset($this->calls[$method])) {
				$this->calls[$method] = array();
			}
			$this->calls[$method][] = $args;
		}
		
		/**
		 * Get a seed based on method and arguments.
		 *   seeded return > seeded implementation
		 */
		private static function getSeedInternal(&$seeds, $method, $args) {
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
		
		private static function evalSeed(&$seed, $args) {
			if(isset($seed['impl'])) {
				return call_user_func_array($seed['impl'], $args);
			} else {
				return $seed['return'];
			}
		}
		
		private static function seedNotFound($class, $method, $args) {
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