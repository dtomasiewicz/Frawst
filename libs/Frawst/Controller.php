<?php
	namespace Frawst;
	
	/**
	 * Base Controller class for the Frawst framework.
	 */
	abstract class Controller extends Object implements ControllerInterface {
		
		/**
		 * @var array The component objects in use by the controller
		 */
		private $__components;
		
		/**
		 * @var array An associative array of data used internally by the controller
		 */
		private $__data;
		
		/**
		 * @var Frawst\Request The request using the controller
		 */
		private $__Request;
		
		private $__Response;
		
		/**
		 * Constructor.
		 * @param Frawst\Request The request using the controller
		 */
		public function __construct(RequestInterface $request, ResponseInterface $response) {
			$this->__Request = $request;
			$this->__Response = $response;
			$this->__components = array();
		}
		
		public function request() {
			return $this->__Request;
		}
		
		public function response() {
			return $this->__Response;
		}
		
		/**
		 * Invoked before execution.
		 * @return bool false if execution should not proceed, true otherwise
		 */
		protected function _before() {
			return true;
		}
		
		/**
		 * Invoked after execution.
		 * @param mixed $data The value returned from execution
		 * @return mixed The data that should be stored in the Response
		 */
		protected function _after($data) {
			return $data;
		}
		
		/**
		 * Attempts to access a component. If the component class exists but has not yet been instantiated
		 * for this controller, instantiation will happen first.
		 * @param string $name Name of the component
		 * @return mixed The component object, or false if the component does not exist
		 */
		public function component($name) {
			if (!isset($this->__components[$name])) {
				if(class_exists($name)) {
					$this->__components[$name] = new $name($this);
					$this->__components[$name]->setup();
				} else {
					$this->__components[$name] = false;
				}
			}
			return $this->__components[$name];
		}
		
		/**
		 * Executes the controller logic. _before() and _after() are invoked before and after
		 * the method logic.
		 * @return mixed The data to be sent in the Response
		 */
		public function execute() {
			if(false !== $data = $this->_before()) {
				if(method_exists($this, $method = strtolower($this->__Request->method()))) {
					$data = call_user_func_array(array($this, $method), $this->__Request->param());
				}
			}
			
			$data = $this->_after($data);
			
			// teardown components
			foreach($this->__components as $component) {
				$component->teardown();
			}
			$this->__components = array();
			
			return $data;
		}
	}