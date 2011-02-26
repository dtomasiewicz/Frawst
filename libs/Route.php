<?php
	namespace Frawst;
	
	/**
	 * Frawst routing class.
	 */
	class Route implements RouteInterface {
		/**
		 * @var string The original route supplied to this class in the constructor,
		 *             before custom routing rules are applied.
		 */
		private $__original;
		
		/**
		 * @var string The route after custom routing rules are applied.
		 */
		private $__route;
		
		/**
		 * @var array The stack of controller names represented by the route.
		 */
		private $__controller;
		
		/**
		 * @var array Array of parameters defined by this route.
		 */
		private $__params;

		/**
		 * @var array Array of named parameters (options) parsed from custom routing rules.
		 */
		private $__options;
		
		/**
		 * Constructor.
		 * @param string $route The route to parse
		 * @param bool $customRoute If set to true, will check the route against
		 *                          any specified custom routing rules.
		 */
		public function __construct($route, $customRoute = false) {
			$this->__original = $route;

			if($customRoute) {
				$this->__routeCustom();
			} else {
				$this->__route = $route;
			}
			
			$this->__resolve();
		}

		/**
		 * Applies custom routing rules to the original route as defined in Config:Routing.rules.
		 * Some example definitions:
		 *     'user/^[0-9]+$:id' => 'user/view/:id'
		 *         user/6 --> user/view/6
		 */
		protected function __routeCustom() {
			if(is_array($rules = Config::read('Routing', 'rules'))) {
				foreach($rules as $pattern => $newRoute) {
					if($this->__matchRoute($pattern, $this->__original, $newRoute)) {
						return;
					}
				}
			}
			
			$this->__route = $this->__original;
			$this->__options = array();
		}

		private function __matchRoute($pattern, $route, $newRoute) {
			$r = explode('/', $route);
			$p = explode('/', $pattern);

			// first loop through the pattern segments. if we get to the end, we have a match
			$opts = array();
			$remainder = count($r);
			for($i = 0; $i < count($p); $i++) {
				if($i < count($r)) {
					$parts = explode(':', $p[$i]);
					$regex = $parts[0];
					$opt = isset($parts[1]) ? $parts[1] : null;
					
					if($p[$i] === '...') {
						$remainder = $i;
					} elseif(substr($regex, 0, 1) === '^') {
						if(!preg_match('/'.$regex.'/', $r[$i])) {
							return false;
						} elseif($opt !== null) {
							$opts[$opt] = $r[$i];
						}
					} elseif($opt !== null) {
						$opts[$opt] = $r[$i];
					} elseif($p[$i] !== $r[$i]) {
						return false;
					}
				} else {
					return false;
				}
			}
			
			// match! now build the new route
			$nr = explode('/', $newRoute);
			$new = array();
			for($i = 0; $i < count($nr); $i++) {
				if(substr($nr[$i], 0, 1) === ':') {
					if(isset($opts[$opt = substr($nr[$i], 1)])) {
						$new[] = $opts[$opt];
					} else {
						throw new Exception('Route option used in match but not specified in pattern: '.$opt);
					}
				} elseif($nr[$i] === '...') {
					for($c = $remainder; $c < count($r); $c++) {
						$new[] = $r[$c];
					}
				} else {
					$new[] = $nr[$i];
				}
			}

			$this->__route = implode('/', $new);
			$this->__options = $opts;

			return true;
		}
		
		/**
		 * Determines the controller and parameters based on the route.
		 * @param string $route
		 */
		protected function __resolve() {
			$route = explode('/', trim($this->__route, '/'));
			
			// ignore blank route segments
			foreach($route as $key => $segment) {
				if($segment === '') {
					unset($route[$key]);
					$route = array_values($route);
				}
			}
			
			$class = 'Frawst\\Controller';
			$this->__controller = '';
			$exists = true;
			while($exists && count($route)) {
				$name = ucfirst(strtolower($route[0]));
				if(class_exists($c = $class.'\\'.$name) || class_exists($c .= 'Controller')) {
					$this->__controller .= '/'.$name;
					array_shift($route); 
					$class = $c;
				} else {
					$exists = false;
				}
			}
			
			$reflection = strlen($this->__controller)
				? new \ReflectionClass($class)
				: false;
			
			if(!$reflection || $reflection->isAbstract()) {
				$this->__controller .= '/Index';
				$class .= '\\Index';
			}
			
			$this->__controller = ltrim($this->__controller, '/');
			$this->__params = $route;
		}
		
		public function controller() {
			return $this->__controller;
		}
		
		/**
		 * @return array Parameters for this route
		 */
		public function param($param = null) {
			if($param === null) {
				return $this->__params;
			} elseif(isset($this->__params[$param])) {
				return $this->__params[$param];
			} else {
				return null;
			}
		}

		public function option($option = null) {
			if($option === null) {
				return $this->__options;
			} elseif(isset($this->__options[$option])) {
				return $this->__options[$option];
			} else {
				return null;
			}
		}
		
		public function original() {
			return $this->__original;
		}
		
		public function resolved($params = true) {
			$route = $this->__controller;
			if($params && count($this->__params)) {
				$route .= '/'.implode('/', $this->__params);
			}
			return $route;
		}
		
		public static function getPath($route) {
			return URL_REWRITE ? WEB_ROOT.$route : WEB_ROOT.'index.php/'.$route;
		}
		
		public function path() {
			return self::getPath($this->resolved());
		}
	}