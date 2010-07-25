<?php
	namespace Frawst;
	
	abstract class Controller implements \ArrayAccess {
		private $components;
		private $data = array();
		private $persist = array();
		protected $Request;
		protected $Cache;
		
		public function __construct($request) {
			$this->Request = $request;
			$this->Cache = $request->Cache;
		}
		
		/**
		 * Attempt to load Models or Components on demand. My reasoning for checking
		 * in this order is: Checking if a component is already loaded is fast and can
		 * be bypassed quickly on failure. Checking if a Model factory exists is a bit
		 * slower since it will attempt to load the model. Attempting to load a component
		 * will only happen once for each controller instance.
		 */
		public function __get($name) {
			if($this->component($name)) {
				return $this->components[$name];
			} elseif($model = $this->model($name)) {
				return $model;
			} else {
				throw new Exception\Controller('Model or Component unavailable: '.$name);
			}
		}
		
		public function component($name) {
			if(!isset($this->components[$name])) {
				$this->components[$name] = class_exists($class = '\\Frawst\\Component\\'.$name)
					? new $class($this)
					: false;
			}
			return $this->components[$name];
		}
		
		public function model($name) {
			return $this->Request->Mapper->factory($name);
		}
		
		/**
		 * Determines if this controller contains the action specified.
		 * A valid action is any public method that is not inherited from
		 * Controller. Currently the only (non-hack) way to check this from
		 * within the Controller class is with the Reflection API. As Reflection
		 * is slated to be removed from the Core in 6.0, another solution will
		 * need to be found to allow for widespread PHP6 PNP support. In order for
		 * an action to be valid, it must:
		 *  a) Exist
		 *  b) Be public
		 *  c) Be declared in get_class($this)
		 *  d) Not exist in Controller
		 */
		public function actionExists($action) {
			if(!method_exists(get_class($this), $action)) {
				return false;
			} else {
				$method = new \ReflectionMethod(get_class($this), $action);
				if(!$method->isPublic() || $method->getDeclaringClass()->name != get_class($this)) {
					return false;
				}
			}
			return true;
		}
		
		public function execute($action, $params) {
			$this->data = array();
			
			$before = $this->beforeAction();
			if(is_array($before)) {
				$this->data = $before + $this->data;
			}
			
			$action = call_user_func_array(array($this, $action), $params);
			if(is_array($action)) {
				$this->data = $action + $this->data;
			}
			
			$after = $this->afterAction();
			if(is_array($after)) {
				$this->data = $after + $this->data;
			}
			
			return $this->data;
		}
		
		protected function beforeAction() {
			
		}
		
		protected function afterAction() {
			
		}
		
		public function offsetExists($offset) {
			return array_key_exists($offset, $this->persist);
		}
		public function offsetGet($offset) {
			return $this->persist[$offset];
		}
		public function offsetSet($offset, $value) {
			$this->persist[$offset] = $value;
		}
		public function offsetUnset($offset) {
			unset($this->persist[$offset]);
		}
		
		protected function data($key = null, $default = null) {
			return $this->Request->data($key, $default);
		}
		
		protected function postData($key = null, $default = null) {
			return $this->Request->postData($key, $default);
		}
	}