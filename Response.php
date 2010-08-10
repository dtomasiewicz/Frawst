<?php
	namespace Frawst;
	use \Frawst\Exception,
	    \Frawst\View\AppView;
	
   /**
    * Handles a response to a Request. In charge of response headers, redirection,
    * and rendering of a View if necessary.
    */
	class Response {
		/**
		 * The Request object to which this Response is responding to.
		 * @access public-read
		 * @var Request
		 */
		protected $_Request;
		
		/**
		 * Response data, most likely the return value of the Request's
		 * controller's execute() method.
		 * @var array
		 */
		protected $_data;
		
		/**
		 * Associative array of response headers. Never sent if the response
		 * is simply "rendered" (as a sub-request).
		 * @var array
		 */
		protected $_headers = array();
		
		/**
		 * For internal redirects only, used if trying to render a redirected
		 * request
		 * @var string
		 */
		protected $_redirect;
		
		/**
		 * View object. Should be able to render() the response data into an
		 * information string.
		 * @access public-read
		 * @var AppView
		 */
		protected $_View;
		
		public function __construct($request) {
			$this->_Request = $request;
		}
		
		/**
		 * Faux readonly property simulation.
		 * @param string $name
		 * @return mixed A readonly property
		 */
		public function __get($name) {
			switch ($name) {
				case 'Request':
					return $this->_Request;
				default:
					throw new Exception\Frawst('Invalid Response property: '.$name);
			}
		}
		
		/**
		 * Returns the response data. Will set the data first, if provided.
		 * @param mixed $data If not null, data will be set to this.
		 * @return mixed Response data
		 */
		public function data($data = null) {
			if (!is_null($data)) {
				$this->_data = $data;
			}
			return $this->_data;
		}
		
		/**
		 * @return array Associative array of response headers
		 */
		public function headers() {
			return $this->_headers;
		}
		
		/**
		 * Returns the value of a response header. Will set the value first,
		 * if provided.
		 * @param string $name The name of the header
		 * @param string $value The value to set the header to
		 * @return The response header value
		 */
		public function header($name, $value = null) {
			if (!is_null($value)) {
				$this->_headers[$name] = $value;
			}
			
			return isset($this->_headers[$name])
				? $this->_headers[$name]
				: null;
		}
		
		/**
		 * Convenience method for setting/getting the response Content-Type.
		 * @param string $mime Mimetype to set
		 * @return string The response Content-Type
		 */
		public function contentType($mime = null) {
			return $this->header('Content-Type', $mime);
		}
		
		/**
		 * Queues the Response for redirection. Will NOT occur immediately, so
		 * it is important to break or return in the calling context if further
		 * execution is not desired. This is so that sub-requests to controller
		 * actions do not result in an unexpected redirect.
		 * 
		 * HTTP redirection occurs when send() is invoked, before rendering. If the
		 * redirect is internal and render() is invoked instead of send(), a sub-
		 * request will be created to the target route, and the rendering of that
		 * request will be returned instead.
		 * 
		 * @param string $to The destination route or (if external) URI
		 * @param bool $external If specifying a URI instead of an internal
		 *                       route, set this to true.
		 * @return bool false
		 */
		public function redirect($to = '', $external = false) {
			if (!$external) {
				$this->_redirect = $to = trim($to, '/');
				// some browsers (e.g. Firefox) fail to pass non-standard headers to next page
				// this is somewhat of a hack to get it to work
				if ($this->_Request->isAjax()) {
					$to .= AJAX_SUFFIX;
				}
				$root = URL_REWRITE ? WEB_ROOT : WEB_ROOT.'/index.php';
				$to = $root.'/'.$to;
			}
			
			$this->header('Location', $to);
			
			return false;
		}
		
		/**
		 * Renders the view. If internally redirected, will render a sub-request.
		 * @return string The rendered view
		 */
		public function render() {
			if (isset($this->_redirect)) {
				return $this->_Request->subRequest($this->_redirect, array(), 'GET', $this->_Request->headers())->execute()->render();
			} elseif ($this->header('Location')) {
				throw new Exception\Frawst('Cannot render a request pending an external redirection.');
			} else {
				$this->_View = new AppView($this);
				return $this->_View->render(str_replace('/', DIRECTORY_SEPARATOR, $this->_Request->route()), $this->_data);
			}
		}
		
		/**
		 * Sends any response headers to the browser, along with the view rendering.
		 * 
		 * Headers are sent after the view is rendered and before it is outputted,
		 * in case the headers are changed from within the view. The only exception is
		 * the Location (redirect) header, which will be sent first since rendering a
		 * redirected request would be a waste of time.
		 */
		public function send() {
			if ($redirect = $this->header('Location')) {
				header('Location: '.$redirect);
				exit;
			}
			
			$out = $this->render();
			
			foreach ($this->_headers as $key => $value) {
				header($key.': '.$value);
			}
			
			echo $out;
			// do not combine with above line, in case the rendering is an integer
			exit;
		}
	}