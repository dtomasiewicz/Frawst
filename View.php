<?php
	namespace Frawst;
	
	class View {
		protected $helpers;
		protected $Request;
		protected $layoutData = array();
		protected $layout = 'default';
		
		public function __construct($request) {
			$this->Request = $request;
		}
		
		public function render($file, $data = array()) {
			if(($path = Loader::importPath('Frawst\\View\\'.$file)) !== null) {
				$content = $this->renderFile($path, $data);
				if(!$this->isAjax() && !is_null($layoutPath = Loader::importPath('Frawst\\View\\layout\\'.$this->layout))) {
					return $this->renderFile($layoutPath, array('content' => $content) + $this->layoutData);
				} else {
					return $content;
				}
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