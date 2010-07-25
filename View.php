<?php
	namespace Frawst;
	
	class View {
		/**#@+
		 * Response MIME type
		 * @var string
		 */
		const RESPONSE_JSON = 'application/json';
		const RESPONSE_HTML = 'text/html';
		const RESPONSE_TEXT = 'text/plain';
		const RESPONSE_XML = 'text/xml';
		
		protected $responseType;
		
		protected $helpers;
		protected $Request;
		protected $layoutData = array();
		protected $layout = 'default';
		
		public function __construct($request) {
			$this->Request = $request;
			$this->responseType = self::RESPONSE_HTML;
		}
		
		/**
		 * Sets the response content-type, or simply returns it
		 * @return string The response content-type
		 */
		public function responseType($mime = null) {
			if(!is_null($mime)) {
				$this->responseType = $mime;
			}
			return $this->responseType;
		}
		
		/**
		 * Convenience method for responding as JSON
		 */
		public function respondAsJson() {
			$this->responseType = self::RESPONSE_JSON;
		}
		
		public function render($file, $data = array()) {
			if(($path = Loader::importPath('Frawst\\View\\'.$file)) !== null) {
				$content = $this->renderFile($path, $data);
				if(!$this->isAjax() && !is_null($this->layout) && !is_null($layoutPath = Loader::importPath('Frawst\\View\\layout\\'.$this->layout))) {
					$render = $this->renderFile($layoutPath, array('content' => $content) + $this->layoutData);
				} else {
					$render = $content;
				}
				header('Content-Type: '.$this->responseType);
				return $render;
			} else {
				throw new Exception\Frawst('Invalid view: '.$file);
			}
		}
		
		private function renderFile($___file, $___data) {
			extract($___data);
			ob_start();
			require($___file);
			return ob_get_clean();
		}
		
		protected function partial($partial, $data = array()) {
			return $this->renderFile(Loader::importPath('Frawst\\View\\partial\\'.$partial), $data);
		}

		public function isAjax() {
			return $this->Request->isAjax();
		}
		
		public function path($route = null) {
			return $this->Request->path($route);
		}

		/**
		 * Makes a sub-request. If $headers is set to true, the request will behave
		 * as if it were ajax.
		 */
		public function subRequest($route, $method = 'GET', $data = array(), $headers = true) {
			if($headers === true) {
				$headers = array('X-Requested-With' => 'XMLHttpRequest');
			} elseif(!is_array($headers)) {
				$headers = array();
			}
			
			return $this->Request->subRequest($route, $method, $data, $headers);
		}
		
		/**
		 * Attempt to load Helpers on-demand
		 */
		public function __get($name) {
			if($name == 'Request') {
				return $this->Request;
			} elseif($helper = $this->helper($name)) {
				return $helper;
			} else {
				throw new Exception\Frawst('Invalid helper: '.$name);
			}
 		}
 		
 		public function helper($name) {
 			if(!isset($this->helpers[$name])) {
 				$this->helpers[$name] = class_exists($class = '\\Frawst\\Helper\\'.$name)
 					? new $class($this)
 					: false;
 			}
 			return $this->helpers[$name];
 		}
 		
 		public function useLayout($layout) {
 			$this->layout = $layout;
 		}
	}