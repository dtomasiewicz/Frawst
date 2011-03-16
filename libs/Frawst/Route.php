<?php
	namespace Frawst;
	
	/**
	 * Frawst routing class.
	 */
	class Route extends Base implements RouteInterface {
		
		/**
		 * @var array The existing controller this route has been resolved to.
		 */
		private $controller;
		
		/**
		 * @var string The existing content template this route has been resolved to.
		 */
		private $template;
		
		/**
		 * @var array Array of parameters defined by this route.
		 */
		private $params;

		/**
		 * @var array Array of named parameters (options) parsed from custom routing rules.
		 */
		private $options;
		
		/**
		 * @var string The original route supplied to this class in the constructor,
		 *             before custom routing rules are applied.
		 */
		private $original;
		
		/**
		 * Constructor.
		 * @param string $route The route to parse
		 * @param bool $customRoute If set to true, will check the route against
		 *                          any specified custom routing rules.
		 */
		public function __construct($controller = null, $template = null, $params = array(), $options = array(), $original = null) {
			$this->controller = $controller;
			$this->template = $template;
			$this->params = $params;
			$this->options = $options;
			$this->original = $original === null ? $controller : $original;
		}

		/**
		 * Applies custom routing rules to the original route as defined in Config:Routing.rules.
		 * Some example definitions:
		 *     'user/^[0-9]+$:id' => 'user/view/:id'
		 *         user/6 --> user/view/6
		 */
		private static function routeCustom($route) {
			if(is_array($rules = self::callClassImplementation('configRead', 'Routing', 'rules'))) {
				foreach($rules as $pattern => $newRoute) {
					if(is_array($match = self::matchRoute($pattern, $route, $newRoute))) {
						return $match;
					}
				}
			}
			
			return array($route, array());
		}

		private static function matchRoute($pattern, $route, $newRoute) {
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

			return array($this->route, $opts);
		}
		
		public static function resolve($route, $routeCustom = false) {
			
			if($routeCustom) {
				list($route, $options) = self::routeCustom($route);
			} else {
				$options = array();
			}
			
			$vClass = static::getClassImplementation('Frawst\ViewInterface');
			$cClass = static::getClassImplementation('Frawst\ControllerInterface');
			
			if($vClass::contentExists($content = strtolower($route))) {
				$template = $content;
				$c = str_replace(' ', '/', ucwords(str_replace('/', ' ', $route)));
				if($cClass::controllerExists($c)) {
					$controller = $c;
				} else {
					$controller = null;
				}
				$params = array();
			} else {
				$parts = explode('/', $route);
				if($parts[0] == '') {
					array_shift($parts);
				}
				$params = $parts;
				$controller = null;
				
				$exists = true;
				$abstract = true;
				while($exists && $abstract && count($parts)) {
					$c = $controller === null
						? ucfirst(strtolower($parts[0]))
						: $controller.'/'.ucfirst(strtolower($parts[0]));
					if($parts[0] != '' && $cClass::controllerExists($c)) {
						array_shift($parts);
						$controller = $c;
						$abstract = $cClass::controllerIsAbstract($controller);
					} else {
						$exists = false;
					}
				}
				
				while($abstract) {
					$c = $controller === null
						? 'Index'
						: $controller.'/Index';
					if($cClass::controllerExists($c)) {
						$controller = $c;
						$abstract = $cClass::controllerIsAbstract($controller);
					} else {
						$controller = null;
						$abstract = false;
					}
				}
				
				$template = null;
				if($controller !== null) {
					$params = $parts;
					if($vClass::contentExists($c = strtolower($controller))) {
						$template = $c;
					}
				}
			}
			
			return new Route($controller, $template, $params, $options, $route);
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
			return $this->template;
		}
	}