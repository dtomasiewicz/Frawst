<?php
	namespace Frawst\Core;
	
	/**
	 * Interface Dependencies:
	 *   Frawst\RouteInterface (Frawst\Route)
	 */
	class View {
		private $helpers;
		private $response;
		private $data;
		private $layout;
		
		public function __construct(Response $response) {
			$this->response = $response;
			$this->helpers = array();
			$this->data = array();
			$this->layout = 'default';
		}
		
		public static function factory(Response $response) {
			$c = get_called_class();
			return new $c($response);
		}
		
		public static function exists($module, $name) {
			return self::templatePath($module, 'content/'.$name) !== null;
		}
		
		public function get($key) {
			return Matrix::pathExists($this->data, $key)
				? Matrix::pathGet($this->data, $key)
				: null;
		}
		
		public function set($key, $value = null) {
			if(is_array($key)) {
				foreach($key as $k => $v) {
					$this->set($k, $v);
				}
			} else {
				Matrix::pathSet($this->data, $key, $value);
			}
		}
		
		public function response() {
			return $this->response;
		}
		
		public function render($data) {
			$output = $this->renderContent($data);
			
			if (!$this->isAjax() && is_string($this->layout)) {
				$output = $this->renderFile(
					'layout/'.$this->layout,
					array('content' => $output)
				);
			}
			
			// teardown all helpers
			foreach($this->helpers as $helper) {
				$helper->teardown();
			}
			$this->helpers = array();
			
			return $output;
		}
		
		/**
		 * Renders the content template with the data supplied from the controller. If
		 * the template file does not exist, the response will be sent as JSON, without
		 * a layout.
		 * @return string
		 */
		protected function renderContent($data) {
			if(null !== $template = $this->findTemplate()) {
				if(!is_array($data)) {
					$data = array('data' => $data);
				}
				return $this->renderFile($template, $data);
			} else {
				// if the template does not exist, send as JSON
				$this->layout = null;
				$this->response->header('Content-Type', 'application/json');
				return Serialize::tojSON($data);
			}
		}
		
		/**
		 * Attempts to find the template file for rendering. This is based on
		 * the request route and the response status.
		 * @return string The path to the template, relative to the views direcetory
		 */
		protected function findTemplate() {
			$status = $this->response->status();
			
			$module = $this->Module->name();
			if($this->response->isOk()) {
				return 'content/'.$this->Route->template();
			} else {
				// attempt to find an error document to render
				$dir = 'error/'.$this->Route->controller();
				
				$exhausted = false;
				while(!$exhausted) {
					if(null !== self::templatePath($module, $template = $dir.'/'.$status)) {
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
		protected static function templatePath($module, $file) {
			return Loader::loadPath('views/'.$module.'/'.$file);
		}
		
		protected function renderFile($file, $data) {
			$module = $this->Module->name();
			if(null !== $file = self::templatePath($module, $file)) {
				extract($data);
				ob_start();
				require($file);
				return ob_get_clean();
			} else {
				throw new Exception('Non-existent view template: '.$file);
			}
		}
		
		private function partial($partial, $data = array()) {
			return $this->renderFile('partial/'.$partial, $data);
		}

		public function isAjax() {
			return $this->response->request()->isAjax();
		}
		
		public function path($route = null) {
			if($route === null) {
				return $this->Module->path($this->Route->resolved());
			} else {
				return $this->Module->path($route);
			}
		}
		
		public function root($resource = '') {
			return $this->Module->resource($resource);
		}
		
		public function modGet($changes = array()) {
			$qs = '?';
			foreach ($changes + $this->response->request()->get() as $key => $value) {
				$qs .= $key.'='.$value.'&';
			}
			return rtrim($this->Route->resolved().$qs, '?&');
		}

		private function ajax($route, array $data = array(), $method = 'GET', array $headers = array()) {
			$headers['X-Requested-With'] = 'XmlHttpRequest';
			return $this->Module->request($route, $data, $method, $headers)->execute()->render();
		}
 		
 		public function helper($name) {
 			if (!array_key_exists($name, $this->helpers)) {
 				if(null === $this->helpers[$name] = Helper::factory($this->Module->name(), $name, $this)) {
 					$this->helpers[$name] = Helper::factory('Core', $name, $this);
 				}
 				if(null !== $this->helpers[$name]) {
 					$this->helpers[$name]->setup();
 				}
 			}
 			
 			return $this->helpers[$name];
 		}
 		
 		public function layout() {
 			return $this->layout;
 		}
 		
 		public function setLayout($layout = null) {
 			$this->layout = $layout;
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
			} elseif($h = $this->helper($name)) {
				return $h;
			} else {
				throw new Exception('Invalid view property: '.$name);
			}
		}
	}