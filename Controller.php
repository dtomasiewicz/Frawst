<?php
	namespace Frawst;
	
	abstract class Controller implements \ArrayAccess {
		protected $_components;
		protected $_data;
		protected $_Request;
		protected $_Cache;
		
		public function __construct($request) {
			$this->_Request = $request;
			$this->_Cache = $request->Cache;
		}
		
		public function __get($name) {
			if($name == 'Request') {
				return $this->_Request;
			} elseif($name == 'Response') {
				return $this->_Request->Response;
			} elseif($name == 'Cache') {
				return $this->_Cache;
			} elseif($c = $this->_component($name)) {
				return $c;
			} elseif($m = $this->_model($name)) {
				return $m;
			} else {
				throw new Exception\Controller('Invalid controller property: '.$namel);
			}
		}
		
		protected function _component($name) {
			if(!isset($this->_components[$name])) {
				$this->_components[$name] = class_exists($class = '\\Frawst\\Component\\'.$name)
					? new $class($this)
					: false;
			}
			return $this->_components[$name];
		}
		
		protected function _model($name) {
			return $this->_Request->Mapper->factory($name);
		}
		
		public function hasAction($action) {
			if($action[0] == '_') {
				return false;
			} else {
				return (bool) (method_exists($this, $action) && !method_exists(__CLASS__, $action));
			}
		}
		
		public function execute($action, $params) {
			$this->_persist = array();
			
			if($this->_beforeAction() !== false) {
				$actionData = call_user_func_array(array($this, $action), $params);
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