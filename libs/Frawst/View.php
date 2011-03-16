<?php
	namespace Frawst;
	
	/**
	 * Interface Dependencies:
	 *   Frawst\RouteInterface (Frawst\Route)
	 */
	class View extends Base implements ViewInterface {
		private $helpers;
		private $Response;
		private $data;
		private $layout;
		
		public function __construct(ResponseInterface $response) {
			$this->Response = $response;
			$this->helpers = array();
			$this->data = array();
			$this->layout = 'default';
		}
		
		public static function factory(ResponseInterface $response) {
			$c = get_called_class();
			return new $c($response);
		}
		
		public static function exists($name) {
			return self::templatePath('content/'.$name) !== null;
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
			return $this->Response;
		}
		
		public function request() {
			return $this->Response->request();
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
				$this->Response->header('Content-Type', 'application/json');
				return Serialize::tojSON($data);
			}
		}
		
		/**
		 * Attempts to find the template file for rendering. This is based on
		 * the request route and the response status.
		 * @return string The path to the template, relative to the views direcetory
		 */
		protected function findTemplate() {
			$status = $this->Response->status();
			
			if($this->Response->isOk()) {
				if(null !== self::templatePath($template = 'content/'.$this->request()->route()->template())) {
					return $template;
				} else {
					return null;
				}
			} else {
				// attempt to find an error document to render
				$dir = 'error/'.$this->request()->route()->controller();
				
				$exhausted = false;
				while(!$exhausted) {
					if(null !== self::templatePath($template = $dir.'/'.$status)) {
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
		protected static function templatePath($file) {
			return Loader::loadPath('views/'.$file);
		}
		
		protected function renderFile($file, $data) {
			if(null !== $file = self::templatePath($file)) {
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
			return $this->Response->request()->isAjax();
		}
		
		public function path($route = null) {
			if($route === null) {
				return $this->Response->request()->route()->path();
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
			foreach ($changes + $this->Response->request()->get() as $key => $value) {
				$qs .= $key.'='.$value.'&';
			}
			return rtrim($this->Response->request()->route()->resolved().$qs, '?&');
		}

		private function ajax($route, $data = array(), $method = 'GET') {
			$request = $this->Response->request()->subRequest($route, $data, $method);
			return $request->execute()->render();
		}
 		
 		public function helper($name) {
 			if (!array_key_exists($name, $this->helpers)) {
 				$hClass = $this->getImplementation('Frawst\HelperInterface');
 				if(null !== $this->helpers[$name] = $hClass::factory($name, $this)) {
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
				return $this->Response->request();
			} elseif($name == 'Response') {
				return $this->Response;
			} elseif($h = $this->helper($name)) {
				return $h;
			} else {
				throw new Exception('Invalid view property: '.$name);
			}
		}
	}