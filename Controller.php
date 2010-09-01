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
		
		public static function _hasAction($action) {
			if ($action[0] == '_') {
				return false;
			} else {
				return (bool) (method_exists(get_called_class(), $action) && !method_exists(__CLASS__, $action));
			}
		}
		
		public function _execute($action, $method, $params) {
			$this->_persist = array();
			
			if ($this->_beforeAction() !== false) {
				if(!method_exists($this, $call = $action.'_'.$method)) {
					$call = $action;
				} 
				$actionData = call_user_func_array(array($this, $call), $params);
				$this->_afterAction();
			} else {
				return false;
			}
			
			return $actionData;
		}
		
		protected function _beforeAction() {
			
		}
		
		protected function _afterAction() {
			
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