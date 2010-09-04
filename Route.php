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
		 * This is the stack of (sub-)controllers represented by the route. For
		 * example, in a request to Root/Main/index where Root and Main are controllers,
		 * this would hold array('Root', 'Main')
		 * @var array
		 */
		protected $_controllers;
		
		/**
		 * @var string The classname of the bottom-level controller in this route.
		 */
		protected $_controllerClass;
		
		/**
		 * @var string The name of the action defined by this route.
		 */
		protected $_action;
		
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
		 * Determines the controller, action, and parameters for this
		 * route.
		 * @param string $route
		 */
		protected function _dispatch() {
			$route = $this->_route == '' ? array() : explode('/', $this->_route);
			
			// ignore blank route segments
			foreach($route as $key => $segment) {
				if($segment === '') {
					unset($route[$key]);
					$route = array_values($route);
				}
			}
			
			// get top-level (root) controller
			$this->_controllers = array();
			$name = count($route)
				? ucfirst(strtolower($route[0]))
				: null;
			
			if(!is_null($name) && class_exists($class = 'Frawst\\Controller\\'.$name)) {
				$this->_controllers[] = $name;
				array_shift($route);
			} elseif(class_exists($class = 'Frawst\\Controller\\Root')) {
				$this->_controllers[] = 'Root';
			} else {
				exit(404);
			}
			
			// check for sub-controllers
			$exists = true;
			while (count($route) && $exists) {
				$name = ucfirst(strtolower($route[0]));
				if (class_exists($c = $class.'\\'.$name)) {
					$this->_controllers[] = $name;
					array_shift($route);
					$class = $c;
				} else {
					$exists = false;
				}
			}
			
			// if the class is abstract, use the /Main subcontroller
			$rClass = new \ReflectionClass($class);
			if ($rClass->isAbstract()) {
				if(class_exists($class .= '\\Main')) {
					$this->_controllers[] = 'Main';
				} else {
					exit(404);
				}
			}
			
			// determine action
			if (count($route) && $class::_hasAction($action = strtolower(str_replace('_', '', $route[0])))) {
				$this->_action = $action;
				array_shift($route);
			} elseif ($class::_hasAction('index')) {
				$this->_action = 'index';
			} else {
				exit(404);
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
		 * @return string The action for this route
		 */
		public function action() {
			return $this->_action;
		}
		
		/**
		 * @return array Parameters for this route
		 */
		public function params() {
			return $this->_params;
		}
		
		/**
		 * Reconstructs the route with the full controller stack and action name, even
		 * if defaults or overrides were used. For example, if a request to '/' resolves
		 * to controllers Root, Main and action 'index', this would return 'Root/Main/index'.
		 * @param bool $params Whether or not to append the parameters to the route
		 * @return string The reconstructed route
		 */
		public function reconstruct($params = true) {
			$route = implode('/', $this->_controllers).'/'.$this->_action;
			if($params && count($this->_params)) {
				$route .= '/'.implode('/', $this->_params);
			}
			return $route;
		}
	}