<?php
	namespace Frawst;
	use \Frawst\View\MyView;
	
   /**
    * Handles a response to a Request. In charge of response headers, redirection,
    * and rendering of a View if necessary.
    * 
    * Interface Dependencies
	*   Frawst\RequestInterface (Frawst\Request)
	*   Frawst\ViewInterface    (Frawst\View)
	*   Frawst\RouteInterface   (Frawst\Route)
    */
	class Response extends Base implements ResponseInterface {
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
		
		private static $__statusMessages = array(
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
		private $__Request;
		
		/**
		 * @var array Response data, most likely the return value of the Request's
		 * controller's execute() method.
		 */
		private $__data;
		
		/**
		 * @var array Associative array of response headers. Never sent if the response
		 * is simply "rendered" (as a sub-request).
		 */
		private $__headers = array();
		
		/**
		 * @var string For internal redirects only, used if trying to render a redirected
		 * request
		 */
		private $__internalRedirect;
		
		/**
		 * @var Frawst\View View object. Should be able to render() the response data into an
		 * information string.
		 */
		private $__View;
		
		/**
		 * @var int HTTP status code (200 for "ok", 404 for "not found", etc.)
		 */
		private $__status;
		
		/**
		 * Constructor.
		 * @param Frawst\Request Request that is being responded to
		 */
		public function __construct(RequestInterface $request) {
			$this->__Request = $request;
			$this->__status = self::STATUS_OK;
			$this->__View = null;
		}
		
		public function request() {
			return $this->__Request;
		}
		
		/**
		 * Returns the response data. Will set the data first, if provided.
		 * @param mixed $data If not null, data will be set to this.
		 * @return mixed Response data
		 */
		public function data($data = null) {
			if (!is_null($data)) {
				$this->__data = $data;
			}
			return $this->__data;
		}
		
		/**
		 * @return array Associative array of response headers
		 */
		public function headers() {
			return $this->__headers;
		}
		
		/**
		 * Returns the value of a response header. Will set the value first,
		 * if provided.
		 * @param string $name The name of the header
		 * @param string $value The value to set the header to
		 * @return string The response header value or null if not set
		 */
		public function header($name, $value = null) {
			if(is_array($name)) {
				foreach($name as $key => $val) {
					$this->header($key, $val);
				}
			} elseif(null !== $value) {
				$this->__headers[$name] = $value;
			} else {
				return isset($this->__headers[$name])
					? $this->__headers[$name]
					: null;
			}
		}
		
		/**
		 * Set and/or retrieve the status code for the response.
		 * @param int $status If not null, status will be set to this
		 */
		public function status($status = null) {
			if(null !== $status) {
				$this->__status = $status;
			}
			
			return $this->__status;
		}
		
		public function isOk() {
			return $this->__status == static::STATUS_OK;
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
				$to = $this->request()->route()->resolved();
			}
			
			if (!$external) {
				$this->__internalRedirect = $to = trim($to, '/');
				// some browsers (e.g. Firefox) fail to pass non-standard headers to next page
				// this is somewhat of a hack to get it to work
				if ($this->__Request->isAjax()) {
					$to .= AJAX_SUFFIX;
				}
				$to = URL_REWRITE ? WEB_ROOT.$to : WEB_ROOT.'index.php/'.$to;
			}
			
			$this->status($status);
			$this->header('Location', $to);
			
			return false;
		}
		
		public function contentType($setTo = null) {
			return $this->header('Content-Type', $setTo);
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
			return $this->__status >= 300 && $this->__status < 400
				? true
				: false;
		}
		
		/**
		 * Renders the view. If internally redirected, will create a request
		 * to the redirected page and render it.
		 * @return string The rendered view
		 */
		public function render() {
			try {
				if(!is_string($this->__data)) {
					if(isset($this->__internalRedirect)) {
						$reqClass = $this->getImplementation('Frawst\RequestInterface');
						$routeClass = $this->getImplementation('Frawst\RouteInterface');
						$req = new $reqClass(new $routeClass($this->__internalRedirect), array(), 'GET', $this->__Request->headers());
						$this->__data = $req->execute()->render();
					} elseif($this->mustRedirect()) {
						throw new \Frawst\Exception('Cannot render a request pending an external redirection.');
					} else {
						if(!is_array($this->__data)) {
							$this->__data = array($this->__data);
						}
						$viewClass = $this->getImplementation('Frawst\ViewInterface');
						$this->__View = new $viewClass($this);
						$this->__data = $this->__View->render($this->__data);
						$this->__View = null;
					}
				}
				return $this->__data;
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
			if($this->__data instanceof File && !$this->__data->exists()) {
				$this->status(self::STATUS_NOT_FOUND);
			}
			
			if($this->__status != self::STATUS_OK) {
				$statusHeader = 'HTTP/1.0 '.$this->__status;
				if(isset(self::$__statusMessages[$this->__status])) {
					$statusHeader .= ' '.self::$__statusMessages[$this->__status];
				}
				header($statusHeader);
				
				if($this->mustRedirect() && $redirect = $this->header('Location')) {
					header('Location: '.$redirect);
					exit;
				}
			} elseif($this->__data instanceof File) {
				if($this->header('Content-Type') === null) {
					// no Content-Type set, transfer as attachment
					$this->header(array(
						'Content-Type'              => 'application/octet-stream',
						'Content-Description'       => 'File Transfer',
						'Content-Disposition'       => 'attachment; filename='.$this->__data->transferName(),
						'Content-Transfer-Encoding' => 'Binary',
						'Expires'                   => '0',
						'Cache-Control'             => 'must-revalidate, post-check=0, pre-check=0',
						'Pragma'                    => 'public'
					));
				}
				
				$this->header('Content-Length', $this->__data->size());
				$this->__sendHeaders();
				ob_clean();
				flush();
				$this->__data->read(true);
				exit;
			} else {
				$out = $this->render();
				$this->__sendHeaders();
				echo $out;
			
				exit;
			}
		}
		
		private function __sendHeaders() {
			foreach ($this->__headers as $name => $value) {
				header($name.': '.$value);
			}
		}
	}
