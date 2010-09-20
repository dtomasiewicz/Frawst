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
		 * Constructor.
		 * @param string $route The route to parse
		 * @param bool $customRoute If set to true, will check the route against
		 *                          any specified custom routing rules.
		 */
		public function __construct($route, $customRoute = false) {
			$this->_originalRoute = $route;
			
			if($customRoute && is_array($rules = Config::read('Routing.rules'))) {
				foreach($rules as $customPattern => $customRoute) {
					if(preg_match($customPattern, $route)) {
						$route = preg_replace($customPattern, $customRoute, $route);
						break;
					}
				}
			}
			
			$this->_route = $route;
			$this->_dispatch();
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
		public function params() {
			return $this->_params;
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