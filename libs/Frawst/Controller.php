<?php
	namespace Frawst;
	
	/**
	 * Base Controller class for the Frawst framework.
	 */
	abstract class Controller extends Base implements ControllerInterface {
		
		/**
		 * @var array The component objects in use by the controller
		 */
		private $components;
		
		/**
		 * @var array An associative array of data used internally by the controller
		 */
		private $data;
		
		/**
		 * @var Frawst\Request The request using the controller
		 */
		private $Request;
		
		private $Response;
		
		/**
		 * Constructor.
		 * @param Frawst\Request The request using the controller
		 */
		public function __construct(RequestInterface $request, ResponseInterface $response) {
			$this->Request = $request;
			$this->Response = $response;
			$this->components = array();
		}
		
		public function request() {
			return $this->Request;
		}
		
		public function response() {
			return $this->Response;
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
			$name = $this->getImplementation('ns:Frawst\ComponentInterface').'\\'.$name;
			if (!array_key_exists($name, $this->components)) {
				if(class_exists($name)) {
					$this->components[$name] = new $name($this);
					$this->components[$name]->setup();
				} else {
					$this->components[$name] = null;
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
					$data = call_user_func_array(array($this, $method), $this->Request->param());
				} else {
					$data = $this->Response->forbidden();
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
				return $this->Request;
			} elseif($name == 'Response') {
				return $this->Response;
			} elseif($c = $this->component($name)) {
				return $c;
			} else {
				throw new Exception('Invalid controller property: '.$name);
			}
		}
		
		public static function controllerExists($controller) {
			return class_exists(self::controllerClass($controller));
		}
		
		public static function controllerClass($controller) {
			return 'Frawst\Controller\\'.str_replace('/', '\\', $controller);
		}
		
		public static function controllerIsAbstract($controller) {
			$class = self::controllerClass($controller);
			if(class_exists($class)) {
				$r = new \ReflectionClass($class);
				return $r->isAbstract();
			} else {
				throw new Exception('Cannot determine if non-existant controller is abstract: '.$controller);
			}
		}
	}