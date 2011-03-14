<?php
	namespace Frawst;
	
	/**
	 * Frawst routing class.
	 */
	class Route extends Base implements RouteInterface {
		/**
		 * @var string The original route supplied to this class in the constructor,
		 *             before custom routing rules are applied.
		 */
		private $original;
		
		/**
		 * @var string The route after custom routing rules are applied.
		 */
		private $route;
		
		/**
		 * @var array The stack of controller names represented by the route.
		 */
		private $controller;
		
		/**
		 * @var array Array of parameters defined by this route.
		 */
		private $params;

		/**
		 * @var array Array of named parameters (options) parsed from custom routing rules.
		 */
		private $options;
		
		/**
		 * Constructor.
		 * @param string $route The route to parse
		 * @param bool $customRoute If set to true, will check the route against
		 *                          any specified custom routing rules.
		 */
		public function __construct($route, $customRoute = false) {
			$this->original = $route;

			if($customRoute) {
				$this->routeCustom();
			} else {
				$this->route = $route;
			}
			
			$this->resolve();
		}

		/**
		 * Applies custom routing rules to the original route as defined in Config:Routing.rules.
		 * Some example definitions:
		 *     'user/^[0-9]+$:id' => 'user/view/:id'
		 *         user/6 --> user/view/6
		 */
		protected function routeCustom() {
			if(is_array($rules = $this->callImplementation('configRead', 'Routing', 'rules'))) {
				foreach($rules as $pattern => $newRoute) {
					if($this->matchRoute($pattern, $this->original, $newRoute)) {
						return;
					}
				}
			}
			
			$this->route = $this->original;
			$this->options = array();
		}

		private function matchRoute($pattern, $route, $newRoute) {
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

			$this->route = implode('/', $new);
			$this->options = $opts;

			return true;
		}
		
		/**
		 * Determines the controller and parameters based on the route.
		 * @param string $route
		 */
		protected function resolve() {
			$route = explode('/', trim($this->route, '/'));
			
			// ignore blank route segments
			foreach($route as $key => $segment) {
				if($segment === '') {
					unset($route[$key]);
					$route = array_values($route);
				}
			}
			
			$class = 'Frawst\Controller';
			$this->controller = '';
			$exists = true;
			while($exists && count($route)) {
				$name = ucfirst(strtolower($route[0]));
				if(class_exists($c = $class.'\\'.$name) || class_exists($c .= 'Controller')) {
					$this->controller .= '/'.$name;
					array_shift($route); 
					$class = $c;
				} else {
					$exists = false;
				}
			}
			
			$reflection = strlen($this->controller)
				? new \ReflectionClass($class)
				: false;
			
			if(!$reflection || $reflection->isAbstract()) {
				$this->controller .= '/Index';
				$class .= '\Index';
			}
			
			$this->controller = ltrim($this->controller, '/');
			$this->params = $route;
		}
		
		public function controller() {
			return $this->controller;
		}
		
		/**
		 * @return array Parameters for this route
		 */
		public function param($param = null) {
			if($param === null) {
				return $this->params;
			} elseif(isset($this->params[$param])) {
				return $this->params[$param];
			} else {
				return null;
			}
		}

		public function option($option = null) {
			if($option === null) {
				return $this->options;
			} elseif(isset($this->options[$option])) {
				return $this->options[$option];
			} else {
				return null;
			}
		}
		
		public function original() {
			return $this->original;
		}
		
		public function resolved($params = true) {
			$route = $this->controller;
			if($params && count($this->params)) {
				$route .= '/'.implode('/', $this->params);
			}
			return $route;
		}
		
		public static function getPath($route) {
			return URL_REWRITE ? WEB_ROOT.$route : WEB_ROOT.'index.php/'.$route;
		}
		
		public function path() {
			return self::getPath($this->resolved());
		}
		
		public function template() {
			return strtolower($this->controller);
		}
	}