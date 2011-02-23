<?php
	namespace Frawst;
	
	/**
	 * Base Controller class for the Frawst framework.
	 */
	abstract class Controller {
		
		/**
		 * @var array The component objects in use by the controller
		 */
		protected $_components;
		
		/**
		 * @var array An associative array of data used internally by the controller
		 */
		protected $_data;
		
		/**
		 * @var Frawst\Request The request using the controller
		 */
		protected $_Request;
		
		protected $_Response;
		
		/**
		 * Constructor.
		 * @param Frawst\Request The request using the controller
		 */
		public function __construct($request, $response) {
			$this->_Request = $request;
			$this->_Response = $response;
			$this->_components = array();
		}
		
		/**
		 * Read-only immitation.
		 * @param string $name
		 * @return mixed
		 */
		public function __get($name) {
			if ($name == 'Request') {
				return $this->_Request;
			} elseif ($name == 'Response') {
				return $this->_Response;
			} elseif ($c = $this->component($name)) {
				return $c;
			} else {
				throw new ControllerException('Invalid controller property: '.$name);
			}
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
			if (!isset($this->_components[$name])) {
				if(class_exists($class = 'Frawst\\Component\\'.$name)) {
					$this->_components[$name] = new $class($this);
					$this->_components[$name]->setup();
				} else {
					$this->_components[$name] = false;
				}
			}
			return $this->_components[$name];
		}
		
		/**
		 * Executes the controller logic. _before() and _after() are invoked before and after
		 * the method logic.
		 * @return mixed The data to be sent in the Response
		 */
		public function execute() {
			if(false !== $data = $this->_before()) {
				if(method_exists($this, $method = strtolower($this->Request->method()))) {
					$data = call_user_func_array(array($this, $method), $this->Request->param());
				}
			}
			
			$data = $this->_after($data);
			
			// teardown components
			foreach($this->_components as $component) {
				$component->teardown();
			}
			$this->_components = array();
			
			return $data;
		}
	}