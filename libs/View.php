<?php
	namespace Frawst;
	use \Frawst\Library\Serialize;
	
	class View {
		protected $_helpers;
		protected $_Response;
		protected $_layoutData = array();
		protected $_layout = 'default';
		
		protected $_templateDir;
		
		public function __construct($response) {
			$this->_Response = $response;
			
			$paths = Loader::getPaths('views');
			$this->_templateDir = $paths[0];
		}
		
		/**
		 * Attempt to load Helpers on-demand
		 */
		public function __get($name) {
			if ($name == 'Response') {
				return $this->_Response;
			} elseif($name == 'Request') {
				return $this->_Response->Request;
			} elseif ($helper = $this->_helper($name)) {
				return $helper;
			} else {
				throw new Exception\Frawst('Invalid helper: '.$name);
			}
 		}
		
		public function render($data) {
			$content = $this->_renderContent($data);
			
			if (!$this->isAjax() && is_string($this->_layout)) {
				return $this->_renderFile(
					'layout/'.$this->_layout,
					array('content' => $content) + $this->_layoutData
				);
			} else {
				return $content;
			}
		}
		
		/**
		 * Renders the content template with the data supplied from the controller. If
		 * the template file does not exist, the response will be sent as JSON, without
		 * a layout.
		 * @return string
		 */
		protected function _renderContent($data) {
			$template = 'controller/'.$this->Request->route();
			
			if(null !== $this->_templatePath($template)) {
				if(!is_array($data)) {
					$data = array('status' => $data);
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
		 * Returns the absolute path to the specified template file.
		 * @param string $file
		 * @return string the absolute path to the file, or null if it does not exist
		 */
		protected function _templatePath($file) {
			if(file_exists($path = $this->_templateDir.DIRECTORY_SEPARATOR.str_replace('/', DIRECTORY_SEPARATOR, $file).'.php')) {
				return $path;
			} else {
				return null;
			}
		}
		
		protected function _renderFile($___file, $___data) {
			if(null !== $___file = $this->_templatePath($___file)) {
				extract($___data);
				ob_start();
				require($___file);
				return ob_get_clean();
			} else {
				throw new Exception\Frawst('Non-existent view template: '.$___file);
			}
		}
		
		public function partial($partial, $data = array()) {
			return $this->_renderFile('partial/'.$partial, $data);
		}

		public function isAjax() {
			return $this->_Response->Request->isAjax();
		}
		
		public function path($route = null) {
			return $this->_Response->Request->path($route);
		}
		
		public function webroot($resource = '') {
			return WEB_ROOT.$resource;
		}
		
		public function modGet($changes = array()) {
			$qs = '?';
			foreach ($changes + $this->_Response->Request->get() as $key => $value) {
				$qs .= $key.'='.$value.'&';
			}
			return rtrim($this->_Response->Request->route(true).$qs, '?&');
		}

		public function ajax($route, $data = array(), $method = 'GET') {
			$request = $this->_Response->Request->subRequest($route, $data, $method);
			return $request->execute()->render();
		}
 		
 		protected function _helper($name) {
 			if (!isset($this->_helpers[$name])) {
 				$this->_helpers[$name] = class_exists($class = '\\Frawst\\Helper\\'.$name)
 					? new $class($this)
 					: false;
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