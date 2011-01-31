<?php
	namespace Frawst;
	
   /**
    * Handles a response to a Request. In charge of response headers, redirection,
    * and rendering of a View if necessary.
    */
	class Response {
		const STATUS_OK = 200;
		
		const STATUS_FOUND = 302;
		
		const STATUS_BAD_REQUEST = 400;
		const STATUS_UNAUTHORIZED = 401;
		const STATUS_FORBIDDEN = 403;
		const STATUS_NOT_FOUND = 404;
		const STATUS_METHOD_NOT_ALLOWED = 405;
		const STATUS_NOT_ACCEPTABLE = 406;
		const STATUS_PROXY_AUTHENTICATION_REQUIRED = 407;
		const STATUS_REQUEST_TIMEOUT = 408;
		
		const STATUS_INTERNAL_SERVER_ERROR = 500;
		const STATUS_NOT_IMPLEMENTED = 501;
		const STATUS_BAD_GATEWAY = 502;
		const STATUS_SERVICE_UNAVAILABLE = 503;
		const STATUS_GATEWAY_TIMEOUT = 504;
		
		protected static $_statusMessages = array(
			self::STATUS_OK => 'OK',
			
			self::STATUS_FOUND => 'Found',
			
			self::STATUS_BAD_REQUEST => 'Bad Request',
			self::STATUS_UNAUTHORIZED => 'Unauthorized',
			self::STATUS_FORBIDDEN => 'Forbidden',
			self::STATUS_NOT_FOUND => 'Not Found',
			self::STATUS_METHOD_NOT_ALLOWED => 'Method Not Allowed',
			self::STATUS_NOT_ACCEPTABLE => 'Not Acceptable',
			self::STATUS_PROXY_AUTHENTICATION_REQUIRED => 'Proxy Authentication Required',
			self::STATUS_REQUEST_TIMEOUT => 'Request Time-out',
			
			self::STATUS_INTERNAL_SERVER_ERROR => 'Internal Server Error',
			self::STATUS_NOT_IMPLEMENTED => 'Not Implemented',
			self::STATUS_BAD_GATEWAY => 'Bad Gateway',
			self::STATUS_SERVICE_UNAVAILABLE => 'Service Unavailable',
			self::STATUS_GATEWAY_TIMEOUT => 'Gateway Time-out'
		);
		
		/**
		 * The Request object to which this Response is responding to.
		 * @var Request
		 */
		protected $_Request;
		
		/**
		 * @var array Response data, most likely the return value of the Request's
		 * controller's execute() method.
		 */
		protected $_data;
		
		/**
		 * @var array Associative array of response headers. Never sent if the response
		 * is simply "rendered" (as a sub-request).
		 */
		protected $_headers = array();
		
		/**
		 * @var string For internal redirects only, used if trying to render a redirected
		 * request
		 */
		protected $_internalRedirect;
		
		/**
		 * @var Frawst\View View object. Should be able to render() the response data into an
		 * information string.
		 */
		protected $_View;
		
		/**
		 * @var int HTTP status code (200 for "ok", 404 for "not found", etc.)
		 */
		protected $_status;
		
		/**
		 * Constructor.
		 * @param Frawst\Request Request that is being responded to
		 */
		public function __construct($request) {
			$this->_Request = $request;
			$this->_status = self::STATUS_OK;
		}
		
		/**
		 * Read-only immitation.
		 * @param string $name
		 * @return mixed
		 */
		public function __get($name) {
			switch ($name) {
				case 'Request':
					return $this->_Request;
				default:
					throw new \Frawst\Exception('Invalid Response property: '.$name);
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
		 * @return string The response header value or null if not set
		 */
		public function header($name, $value = null) {
			if (null !== $value) {
				$this->_headers[$name] = $value;
			}
			
			return isset($this->_headers[$name])
				? $this->_headers[$name]
				: null;
		}
		
		/**
		 * Set and/or retrieve the status code for the response.
		 * @param int $status If not null, status will be set to this
		 */
		public function status($status = null) {
			if(null !== $status) {
				$this->_status = $status;
			}
			
			return $this->_status;
		}
		
		/**
		 * Queues the Response for redirection. Will NOT occur immediately, so
		 * it is important to break or return in the calling context if further
		 * execution is not desired. This is so that sub-requests do not
		 * unexpectedly redirect the entire top-level request.
		 * 
		 * HTTP redirection occurs when send() is invoked, before rendering. If the
		 * redirect is internal and render() is invoked instead of send(), a sub-
		 * request will be created to the target route, and the rendering of that
		 * request will be returned instead.
		 * 
		 * @param string $to The destination route or (if external) URI
		 * @param int $status The status to send as the HTTP response code
		 * @param bool $external If specifying a URI instead of an internal
		 *                       route, set this to true.
		 * @return bool false
		 */
		public function redirect($to = null, $status = self::STATUS_FOUND, $external = false) {
			if($to === null) {
				$to = $this->Request->route(true);
			}
			
			if (!$external) {
				$this->_internalRedirect = $to = trim($to, '/');
				// some browsers (e.g. Firefox) fail to pass non-standard headers to next page
				// this is somewhat of a hack to get it to work
				if ($this->_Request->isAjax()) {
					$to .= AJAX_SUFFIX;
				}
				$to = URL_REWRITE ? WEB_ROOT.$to : WEB_ROOT.'index.php/'.$to;
			}
			
			$this->status($status);
			$this->header('Location', $to);
			
			return false;
		}
		
		/**
		 * Sets the response status to Not Found
		 * @return bool false
		 */
		public function notFound() {
			$this->status(self::STATUS_NOT_FOUND);
			return false;
		}
		
		/**
		 * Sets the response status to Forbidden
		 * @return bool false
		 */
		public function forbidden() {
			$this->status(self::STATUS_FORBIDDEN);
			return false;
		}
		
		/**
		 * @return bool True if this response must be redirected, false otherwise.
		 */
		public function mustRedirect() {
			return $this->_status >= 300 && $this->_status < 400
				? true
				: false;
		}
		
		/**
		 * Renders the view. If internally redirected, will render a sub-request.
		 * @return string The rendered view
		 */
		public function render() {
			try {
				if (isset($this->_internalRedirect)) {
					return $this->_Request->subRequest($this->_internalRedirect, array(), 'GET')->execute()->render();
				} elseif ($this->mustRedirect()) {
					throw new \Frawst\Exception('Cannot render a request pending an external redirection.');
				} elseif(is_string($this->_data)) {
					return $this->_data;
				} else {
					$class = VIEW_CLASS;
					$this->_View = new $class($this);
					return $this->_View->render($this->_data);
				}
			} catch(\Exception $e) {
				return '<div class="Frawst-Debug">'.
					'<h1>A Rendering Problem Occurred!</h1>'.
					'<pre>'.$e.'</pre></div>';
			}
		}
		
		/**
		 * Sends any response headers to the browser, along with the view rendering.
		 * 
		 * Headers are sent after the view is rendered and before it is outputted,
		 * in case the headers are changed from within the view. The only exception is
		 * the Location (redirect) header, which will be sent first since rendering a
		 * redirected request would be a waste of time.
		 * 
		 * @param strint $viewClass The name of the class to use for rendering the view
		 */
		public function send() {
			if($this->_status != self::STATUS_OK) {
				$statusHeader = 'HTTP/1.0 '.$this->_status;
				if(isset(static::$_statusMessages[$this->_status])) {
					$statusHeader .= ' '.static::$_statusMessages[$this->_status];
				}
				header($statusHeader);
				
				if($this->mustRedirect() && $redirect = $this->header('Location')) {
					header('Location: '.$redirect);
					exit;
				}
			}
			
			$out = $this->render();
			
			foreach ($this->_headers as $name => $value) {
				header($name.': '.$value);
			}
			
			echo $out;
			// do not combine with above line, in case the rendering is an integer
			exit;
		}
	}