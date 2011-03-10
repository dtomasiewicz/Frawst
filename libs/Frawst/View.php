<?php
	namespace Frawst;
	
	/**
	 * Interface Dependencies:
	 *   Frawst\RouteInterface (Frawst\Route)
	 */
	class View extends Base implements ViewInterface {
		private $__helpers;
		private $__Response;
		private $__data;
		private $__layout;
		
		public function __construct(ResponseInterface $response) {
			$this->__Response = $response;
			$this->__helpers = array();
			$this->__data = array();
			$this->__layout = 'default';
		}
		
		public function exists($key) {
			return Matrix::pathExists($this->__data, $key);
		}
		
		public function get($key) {
			return Matrix::pathGet($this->__data, $key);
		}
		
		public function set($key, $value = null) {
			if(is_array($key)) {
				foreach($key as $k => $v) {
					$this->set($k, $v);
				}
			} else {
				Matrix::pathSet($this->__data, $key, $value);
			}
		}
		
		public function remove($key) {
			Matrix::pathUnset($this->__data, $key);
		}
		
		public function response() {
			return $this->__Response;
		}
		
		public function request() {
			return $this->__Response->request();
		}
		
		public function render($data) {
			$output = $this->_renderContent($data);
			
			if (!$this->isAjax() && is_string($this->__layout)) {
				$output = $this->_renderFile(
					'layout/'.$this->__layout,
					array('content' => $output)
				);
			}
			
			// teardown all helpers
			foreach($this->__helpers as $helper) {
				$helper->teardown();
			}
			$this->__helpers = array();
			
			return $output;
		}
		
		/**
		 * Renders the content template with the data supplied from the controller. If
		 * the template file does not exist, the response will be sent as JSON, without
		 * a layout.
		 * @return string
		 */
		protected function _renderContent($data) {
			if(null !== $template = $this->__findTemplate()) {
				if(!is_array($data)) {
					$data = array('data' => $data);
				}
				return $this->_renderFile($template, $data);
			} else {
				// if the template does not exist, send as JSON
				$this->__layout = null;
				$this->__Response->header('Content-Type', 'application/json');
				return Serialize::tojSON($data);
			}
		}
		
		/**
		 * Attempts to find the template file for rendering. This is based on
		 * the request route and the response status.
		 * @return string The path to the template, relative to the views direcetory
		 */
		protected function __findTemplate() {
			$status = $this->__Response->status();
			
			if($this->__Response->isOk()) {
				if(null !== $this->__templatePath($template = 'controller/'.$this->request()->route()->controller())) {
					return $template;
				} else {
					return null;
				}
			} else {
				// attempt to find an error document to render
				$dir = 'error/'.$this->request()->route()->controller();
				
				$exhausted = false;
				while(!$exhausted) {
					if(null !== $this->__templatePath($template = $dir.'/'.$status)) {
						return $template;
					} else {
						if(false !== $pos = strrpos($dir, '/')) {
							$dir = substr($dir, 0, $pos);
						} else {
							$exhausted = true;
						}
					}
				}
				
				return null;
			}
		}
		
		/**
		 * Returns the absolute path to the specified template file.
		 * @param string $file
		 * @return string the absolute path to the file, or null if it does not exist
		 */
		protected function __templatePath($file) {
			return Loader::loadPath('views/'.$file);
		}
		
		protected function _renderFile($___file, $___data) {
			if(null !== $___file = $this->__templatePath($___file)) {
				extract($___data);
				ob_start();
				require($___file);
				return ob_get_clean();
			} else {
				throw new Exception('Non-existent view template: '.$___file);
			}
		}
		
		public function partial($partial, $data = array()) {
			return $this->_renderFile('partial/'.$partial, $data);
		}

		public function isAjax() {
			return $this->__Response->request()->isAjax();
		}
		
		public function path($route = null) {
			if($route === null) {
				return $this->__Response->request()->route()->path();
			} else {
				$c = $this->getImplementation('Frawst\RouteInterface');
				return $c::getPath($route);
			}
		}
		
		public function webroot($resource = '') {
			return WEB_ROOT.$resource;
		}
		
		public function modGet($changes = array()) {
			$qs = '?';
			foreach ($changes + $this->__Response->request()->get() as $key => $value) {
				$qs .= $key.'='.$value.'&';
			}
			return rtrim($this->__Response->request()->route()->resolved().$qs, '?&');
		}

		public function ajax($route, $data = array(), $method = 'GET') {
			if(!($route instanceof RouteInterface)) {
				$routeClass = $this->getImplementation('Frawst\RouteInterface');
				$route = new $routeClass($route);
			}
			
			$request = $this->__Response->request()->subRequest($route, $data, $method);
			return $request->execute()->render();
		}
 		
 		public function helper($name) {
 			$name = $this->getImplementation('ns:Frawst\HelperInterface').'\\'.$name;
 			if (!isset($this->__helpers[$name])) {
 				if(class_exists($name)) {
 					$this->__helpers[$name] = new $name($this);
 					$this->__helpers[$name]->setup();
 				} else {
 					return null;
 				}
 			}
 			return $this->__helpers[$name];
 		}
 		
 		public function layout($layout = null) {
 			if ($layout !== null) {
 				$this->__layout = $layout;
 			}
 			return $this->__layout;
 		}
		
		public function __get($name) {
			if($name == 'Request') {
				return $this->__Response->request();
			} elseif($name == 'Response') {
				return $this->__Response;
			} elseif($h = $this->helper($name)) {
				return $h;
			} else {
				throw new Exception('Invalid view property: '.$name);
			}
		}
	}