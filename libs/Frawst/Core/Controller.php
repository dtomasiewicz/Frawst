<?php
	namespace Frawst\Core;
	
	/**
	 * Base Controller class for the Frawst framework.
	 */
	abstract class Controller {
		
		/**
		 * @var array The component objects in use by the controller
		 */
		private $components;
		
		/**
		 * @var array An associative array of data used internally by the controller
		 */
		private $data;
		
		private $response;
		
		/**
		 * Constructor.
		 * @param Frawst\Request The request using the controller
		 */
		public function __construct(Response $response) {
			$this->response = $response;
			$this->components = array();
		}
		
		public function response() {
			return $this->response;
		}
		
		/**
		 * Invoked before execution.
		 * @return bool false if execution should not proceed, true otherwise
		 */
		protected function before() {
			return true;
		}
		
		/**
		 * Invoked after execution.
		 * @param mixed $data The value returned from execution
		 * @return mixed The data that should be stored in the Response
		 */
		protected function after($data) {
			return $data;
		}
		
		/**
		 * Attempts to access a component. If the component class exists but has not yet been instantiated
		 * for this controller, instantiation will happen first.
		 * @param string $name Name of the component
		 * @return Component The component object, or null if the component does not exist
		 */
		public function component($name) {
			if (!array_key_exists($name, $this->components)) {
				if(null === $this->components[$name] = Component::factory($this->Module->name(), $name, $this)) {
					$this->components[$name] = Component::factory('Core', $name, $this);
				}
				if(null !== $this->components[$name]) {
					$this->components[$name]->setup();
				}
			}
			
			return $this->components[$name];
		}
		
		/**
		 * Executes the controller logic. _before() and _after() are invoked before and after
		 * the method logic.
		 * @return mixed The data to be sent in the Response
		 */
		public function execute() {
			if(false !== $data = $this->before()) {
				if(method_exists($this, $method = strtolower($this->Request->method()))) {
					$data = call_user_func_array(array($this, $method), $this->Route->param());
				} else {
					$data = $this->response->forbidden();
				}
			}
			
			$data = $this->after($data);
			
			// teardown components
			foreach($this->components as $component) {
				$component->teardown();
			}
			$this->components = array();
			
			return $data;
		}
		
		public function __get($name) {
			if($name == 'Request') {
				return $this->response->request();
			} elseif($name == 'Response') {
				return $this->response;
			} elseif($name == 'Module') {
				return $this->response->request()->module();
			} elseif($name == 'Route') {
				return $this->response->request()->route();
			} elseif($c = $this->component($name)) {
				return $c;
			} else {
				throw new Exception('Invalid controller property: '.$name);
			}
		}
		
		private static function className($module, $controller) {
			return 'Frawst\\'.$module.'\Controller\\'.str_replace('/', '\\', $controller);
		}
		
		public static function factory($name, Response $response) {
			$c = self::className($response->Module->name(), $name);
			if(class_exists($c)) {
				return new $c($response);
			} else {
				return null;
			}
		}
		
		public static function exists($module, $name) {
			return class_exists(self::className($module, $name));
		}
		
		public static function isAbstract($module, $name) {
			$class = self::className($module, $name);
			if(class_exists($class)) {
				$r = new \ReflectionClass($class);
				return $r->isAbstract();
			} else {
				throw new Exception('Cannot determine if non-existant controller is abstract: '.$name);
			}
		}
	}