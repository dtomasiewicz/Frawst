<?php
	namespace Frawst;
	
	/**
	 * Frawst routing class.
	 */
	class Route {
		/**
		 * @var string The original route supplied to this class in the constructor,
		 *             before custom routing rules are applied.
		 */
		protected $_originalRoute;
		
		/**
		 * @var string The resolved route after custom routing rules are applied.
		 */
		protected $_route;
		
		/**
		 * @var array The stack of controller names represented by the route.
		 */
		protected $_controllers;
		
		/**
		 * @var string The classname of the bottom-level controller in this route.
		 */
		protected $_controllerClass;
		
		/**
		 * @var array Array of parameters defined by this route.
		 */
		protected $_params;

		/**
		 * @var array Array of named parameters (options) parsed from custom routing rules.
		 */
		protected $_options;
		
		/**
		 * Constructor.
		 * @param string $route The route to parse
		 * @param bool $customRoute If set to true, will check the route against
		 *                          any specified custom routing rules.
		 */
		public function __construct($route, $customRoute = false) {
			$this->_originalRoute = $route;

			if($customRoute) {
				$this->_routeCustom();
			} else {
				$this->_route = $route;
			}
			
			$this->_dispatch();
		}

		/**
		 * Applies custom routing rules to the original route as defined in Config:Routing.rules.
		 * Some example definitions:
		 *     'user/^[0-9]+$:id' => 'user/view/:id'
		 *         user/6 --> user/view/6
		 */
		protected function _routeCustom() {
			if(is_array($rules = Config::read('Routing.rules'))) {
				foreach($rules as $pattern => $newRoute) {
					if($this->_matchRoute($pattern, $this->_originalRoute, $newRoute)) {
						return;
					}
				}
			}
			
			$this->_route = $this->_originalRoute;
			$this->_options = array();
		}

		private function _matchRoute($pattern, $route, $newRoute) {
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

			$this->_route = implode('/', $new);
			$this->_options = $opts;

			return true;
		}
		
		/**
		 * Determines the controller and parameters based on the route.
		 * @param string $route
		 */
		protected function _dispatch() {
			$route = explode('/', trim($this->_route, '/'));
			
			// ignore blank route segments
			foreach($route as $key => $segment) {
				if($segment === '') {
					unset($route[$key]);
					$route = array_values($route);
				}
			}
			
			$class = 'Frawst\\Controller';
			$this->_controllers = array();
			$exists = true;
			while($exists && count($route)) {
				$name = ucfirst(strtolower($route[0]));
				if(class_exists($c = $class.'\\'.$name)) {
					$this->_controllers[] = $name;
					array_shift($route); 
					$class = $c;
				} else {
					$exists = false;
				}
			}
			
			$reflection = count($this->_controllers)
				? new \ReflectionClass($class)
				: false;
			
			if(!$reflection || $reflection->isAbstract()) {
				$this->_controllers[] = 'Index';
				$class .= '\\Index';
			}
			
			$this->_controllerClass = $class;
			$this->_params = $route;
		}
		
		/**
		 * @return string Class name of the bottom-level controller
		 */
		public function controllerClass() {
			return $this->_controllerClass;
		}
		
		/**
		 * @return array Parameters for this route
		 */
		public function param($param = null) {
			if($param === null) {
				return $this->_params;
			} elseif(isset($this->_params[$param])) {
				return $this->_params[$param];
			} else {
				return null;
			}
		}

		public function option($option = null) {
			if($option === null) {
				return $this->_options;
			} elseif(isset($this->_options[$option])) {
				return $this->_options[$option];
			} else {
				return null;
			}
		}
		
		/**
		 * Reconstructs the route with the full controller stack.
		 * @param bool $params Whether or not to append the parameters to the route
		 * @return string The reconstructed route
		 */
		public function reconstruct($params = true) {
			$route = implode('/', $this->_controllers);
			if($params && count($this->_params)) {
				$route .= '/'.implode('/', $this->_params);
			}
			return $route;
		}
	}