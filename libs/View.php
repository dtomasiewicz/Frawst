<?php
	namespace Frawst;
	
	class View implements ViewInterface {
		protected $_helpers;
		protected $_Response;
		protected $_layoutData;
		protected $_layout = 'default';
		
		private $__injected;
		
		public function __construct(ResponseInterface $response) {
			$this->_Response = $response;
			$this->_helpers = array();
			$this->_layoutData = array();
			
			$this->__injected = new Injector();
		}
		
		public function inject($key, $value) {
			$this->__injected->set($key, $value);
		}
		
		public function response() {
			return $this->_Response;
		}
		
		public function request() {
			return $this->response()->request();
		}
		
		public function render($data) {
			$output = $this->_renderContent($data);
			
			if (!$this->isAjax() && is_string($this->_layout)) {
				$output = $this->_renderFile(
					'layout/'.$this->_layout,
					array('content' => $output) + $this->_layoutData
				);
			}
			
			// teardown all helpers
			foreach($this->_helpers as $helper) {
				$helper->teardown();
			}
			$this->_helpers = array();
			
			return $output;
		}
		
		/**
		 * Renders the content template with the data supplied from the controller. If
		 * the template file does not exist, the response will be sent as JSON, without
		 * a layout.
		 * @return string
		 */
		protected function _renderContent($data) {
			if(null !== $template = $this->_findTemplate()) {
				if(!is_array($data)) {
					$data = array('data' => $data);
				}
				return $this->_renderFile($template, $data);
			} else {
				// if the template does not exist, send as JSON
				$this->_layout = null;
				$this->_Response->header('Content-Type', 'application/json');
				return Serialize::tojSON($data);
			}
		}
		
		/**
		 * Attempts to find the template file for rendering. This is based on
		 * the request route and the response status.
		 * @return string The path to the template, relative to the views direcetory
		 */
		protected function _findTemplate() {
			$status = $this->_Response->status();
			if($this->_Response->isOk()) {
				if(null !== $this->_templatePath($template = 'controller/'.$this->request()->route()->controller())) {
					return $template;
				} else {
					return null;
				}
			} else {
				// attempt to find an error document to render
				$dir = 'error/'.$this->request()->route()->controller();
				
				$exhausted = false;
				while(!$exhausted) {
					if(null !== $this->_templatePath($template = $dir.'/'.$status)) {
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
		protected function _templatePath($file) {
			return Loader::loadPath('views/'.$file);
		}
		
		protected function _renderFile($___file, $___data) {
			if(null !== $___file = $this->_templatePath($___file)) {
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
			return $this->_Response->request()->isAjax();
		}
		
		public function path($route = null) {
			if($route === null) {
				return $this->_Response->request()->route()->path();
			} else {
				$c = $this->__injected->get('Frawst\RouteInterface');
				return $c::getPath($route);
			}
		}
		
		public function webroot($resource = '') {
			return WEB_ROOT.$resource;
		}
		
		public function modGet($changes = array()) {
			$qs = '?';
			foreach ($changes + $this->_Response->request()->get() as $key => $value) {
				$qs .= $key.'='.$value.'&';
			}
			return rtrim($this->_Response->request()->route()->resolved().$qs, '?&');
		}

		public function ajax($route, $data = array(), $method = 'GET') {
			if(!($route instanceof RouteInterface)) {
				$routeClass = $this->__injected->get('Frawst\RouteInterface');
				$route = new $routeClass($route);
			}
			
			$request = $this->_Response->request()->subRequest($route, $data, $method);
			return $request->execute()->render();
		}
 		
 		public function helper($name) {
 			if (!isset($this->_helpers[$name])) {
 				if(class_exists($name)) {
 					$this->_helpers[$name] = new $name($this);
 					$this->_helpers[$name]->setup();
 				} else {
 					$this->_helpers[$name] = false;
 				}
 			}
 			return $this->_helpers[$name];
 		}
 		
 		public function layout($layout = null) {
 			if (!is_null($layout)) {
 				$this->_layout = $layout;
 			}
 			return $this->_layout;
 		}
	}