<?php
	namespace Frawst;
	
	/**
	 * Base Controller class for the Frawst framework.
	 */
	abstract class Controller implements \ArrayAccess {
		protected $_components;
		protected $_data;
		protected $_Request;
		
		public function __construct($request) {
			$this->_Request = $request;
		}
		
		protected function _before() {
			return true;
		}
		
		public function __get($name) {
			if ($name == 'Request') {
				return $this->_Request;
			} elseif ($name == 'Response') {
				return $this->_Request->Response;
			} elseif ($c = $this->_component($name)) {
				return $c;
			} else {
				throw new Exception\Controller('Invalid controller property: '.$name);
			}
		}
		
		protected function _component($name) {
			if (!isset($this->_components[$name])) {
				$this->_components[$name] = class_exists($class = '\\Frawst\\Component\\'.$name)
					? new $class($this)
					: false;
			}
			return $this->_components[$name];
		}
		
		public function execute() {
			if($this->_before()) {
				if(method_exists($this, $method = strtolower($this->Request->method()))) {
					return call_user_func_array(array($this, $method), $this->Request->params());
				}
			}
			
			return false;
		}
		
		public function offsetExists($offset) {
			return array_key_exists($offset, $this->_data);
		}
		public function offsetGet($offset) {
			return $this->_data[$offset];
		}
		public function offsetSet($offset, $value) {
			$this->_data[$offset] = $value;
		}
		public function offsetUnset($offset) {
			unset($this->_data[$offset]);
		}
	}