<?php
	namespace Frawst;
	use \Frawst\Exception,
	    \Frawst\View\AppView;
	
	class Response {
		protected $_Request;
		protected $_data;
		protected $_View;
		protected $_headers = array(
			'Content-Type' => 'text/html'
		);
		
		public function __construct($request) {
			$this->_Request = $request;
		}
		
		public function __get($name) {
			switch($name) {
				case 'Request':
					return $this->_Request;
				default:
					throw new Exception\Frawst('Invalid Response property: '.$name);
			}
		}
		
		public function data($data = null) {
			if(!is_null($data)) {
				$this->_data = $data;
			}
			return $this->_data;
		}
		
		public function contentType($mime = null) {
			if(!is_null($mime)) {
				$this->_headers['Content-Type'] = $mime;
			}
			return $this->_headers['Content-Type'];
		}
		
		public function redirect($to = '', $external = false) {
			if(!$external) {
				$to = rtrim(WEB_ROOT.'/'.$to, '/');
				// some browsers (e.g. Firefox) fail to pass non-standard headers to next page
				// this is somewhat of a hack to get it to work
				if($this->_Request->isAjax()) {
					$to .= AJAX_SUFFIX;
				}
			}
			$this->location($to);
			return false;
		}
		
		public function location($location = null) {
			if(!is_null($location)) {
				$this->_headers['Location'] = $location;
			}
			return $this->_headers['Location'];
		}
		
		/**
		 * Renders the view. If a redirect has been queued, it will happen instead
		 * @return string The rendered view
		 */
		public function render() {
			$data = is_array($this->_data)
				? $this->_data
				: array('status' => $this->_data);
				
			$this->_View = new AppView($this);
			return $this->_View->render(str_replace('/', DIRECTORY_SEPARATOR, $this->_Request->route()), $data);
		}
		
		public function send() {
			foreach($this->_headers as $key => $value) {
				header($key.': '.$value);
				
				// make sure it doesn't continue to render if redirected
				if($key == 'Location') {
					exit();
				}
			}
			
			echo $this->render();
		}
	}