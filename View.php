<?php
	namespace Frawst;
	
	class View {
		protected $_helpers;
		protected $_Response;
		protected $_layoutData = array();
		protected $_layout = 'default';
		
		public function __construct($request) {
			$this->_Response = $request;
		}
		
		/**
		 * Attempt to load Helpers on-demand
		 */
		public function __get($name) {
			if ($name == 'Response') {
				return $this->_Response;
			} elseif ($helper = $this->_helper($name)) {
				return $helper;
			} else {
				throw new Exception\Frawst('Invalid helper: '.$name);
			}
 		}
		
		public function render($file, $data = array()) {
			if (($path = Loader::importPath('Frawst\\View\\'.$file)) !== null) {
				if (!is_array($data)) {
					$data = array('status' => $data);
				}
				$content = $this->_renderFile($path, $data);
				if (!$this->isAjax() && is_string($this->_layout)
				  && !is_null($layoutPath = Loader::importPath('Frawst\\View\\layout\\'.$this->_layout))) {
					$render = $this->_renderFile(
						$layoutPath,
						array('content' => $content) + $this->_layoutData
					);
				} else {
					$render = $content;
				}
				return $render;
			} else {
				throw new Exception\Frawst('Invalid view: '.$file);
			}
		}
		
		protected function _renderFile($___file, $___data) {
			extract($___data);
			ob_start();
			require($___file);
			return ob_get_clean();
		}
		
		public function partial($partial, $data = array()) {
			return $this->_renderFile(Loader::importPath('Frawst\\View\\partial\\'.$partial), $data);
		}

		public function isAjax() {
			return $this->_Response->Request->isAjax();
		}
		
		public function path($route = null) {
			return $this->_Response->Request->path($route);
		}
		
		public function modGet($changes = array()) {
			$qs = '?';
			foreach ($changes + $this->_Response->Request->getData() as $key => $value) {
				$qs .= $key.'='.$value.'&';
			}
			return rtrim($this->_Response->Request->route(true).$qs, '?&');
		}

		public function ajax($route, $data = array(), $method = 'GET', $headers = array()) {
			$headers['X-Requested-With'] = 'XMLHttpRequest';
			$request = $this->_Response->Request->subRequest($route, $data, $method, $headers);
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