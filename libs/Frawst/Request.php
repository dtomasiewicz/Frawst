<?php
	namespace Frawst;
	
	/**
	 * Frawst Request Handler
	 * 
	 * A Request object simulates an HTTP request to a particular route in your
	 * application.
	 * 
	 * Interface Dependencies:
	 *   Frawst\RequestInterface  (Frawst\Request)
	 *   Frawst\ResponseInterface (Frawst\Response)
	 *   Frawst\FormInterface     (Frawst\Form)
	 *   ns:Frawst\Controller     (Frawst\Controller)
	 *     Namespace prefix for controllers.
	 */
	class Request extends Object implements RequestInterface {
		
		const METHOD_GET = 'GET';
		const METHOD_POST = 'POST';
		const METHOD_DELETE = 'DELETE';
		const METHOD_PUT = 'PUT';
		
		/**
		 * @var float The time at which the request was first invoked.
		 */
		private $__startTime;
		
		/**
		 * The request controller
		 * @var Frawst\Controller
		 */
		private $__Controller;
		
		/**
		 * @var Frawst\Root Request route
		 */
		private $__Route;
		
		/**
		 * Associative array of headers sent to this request
		 * @var array
		 */
		private $__headers;
		
		/**
		 * The method through which this request was performed
		 * @example 'POST'
		 * @var string
		 */
		private $__method;
		
		/**
		 * Associative array of data sent to this request
		 * @example form data from a POST request, querystring data from GET
		 * @var array
		 */
		private $__data;
		
		/**
		 * @var array An array of forms submitted to the request
		 */
		private $__forms;
		
		/**
		 * Persistent data to be sent to sub-requests.
		 */
		private $__persist;
	
		/**
		 * Constructor
		 * @param mixed $route
		 * @param array $data
		 * @param string $method
		 * @param array $headers
		 * @param array $persist
		 */
		public function __construct(RouteInterface $route, $data = array(), $method = self::METHOD_GET, $headers = array(), $persist = array()) {
			$this->__startTime = microtime(true);
		  	
			$this->__data = $data;
		  	$this->__method = strtoupper($method);
		  	$this->__headers = $headers;
			$this->__persist = $persist;
			$this->__Route = $route;
				
			$this->__Controller = null;
		}
		
		/**
		 * @return array Associative array of request headers
		 */
		public function headers() {
			return $this->__headers;
		}
		
		/**
		 * Gets the value of a request header
		 * @param string $name
		 * @return string The value of the request header, or null if not set
		 */
		public function header($name) {
			return isset($this->__headers[$name])
				? $this->__headers[$name]
				: null;
		}
		
		/**
		 * Whether or not this request will be rendered as AJAX (layoutless)
		 * @return bool
		 */
		public function isAjax() {
			return (bool) (isset($this->__headers['X-Requested-With']) &&
				strtolower($this->__headers['X-Requested-With']) == 'xmlhttprequest'); 
		}
		
		/**
		 * Creates a sub-request with the same persistent data and headers as this request,
		 * in AJAX mode.
		 * @param string $route
		 * @return Frawst\Request The sub-request object
		 */
		public function subRequest(RouteInterface $route, $data = array(), $method = 'GET') {
			$headers = $this->__headers;
			$headers['X-Requested-With'] = 'XMLHttpRequest';
			$reqClass = $this->getImplementation('Frawst\RequestInterface');
			return new $reqClass($route, $data, $method, $headers, $this->__persist);
		}
		
		/**
		 * Executes the controller action and sets the return data to this
		 * Request's response object.
		 * @return mixed The response object for this Request
		 */
		public function execute() {
			$resClass = $this->getImplementation('Frawst\ResponseInterface');
			$response = new $resClass($this);
			
			$controllerClass = $this->getImplementation('ns:Frawst\Controller').'\\'.str_replace('/', '\\', $this->__Route->controller());
			$this->__Controller = new $controllerClass($this, $response);
			
			try {
				$response->data($this->__Controller->execute());
			} catch(\Exception $e) {
				$response->data('<div class="Frawst-Debug">'.
					'<h1>A Controller Problem Occurred!</h1>'.
					'<pre>'.$e.'</pre></div>');
			}
			
			$this->__Controller = null;
			
			return $response;
		}
		
		/**
		 * @return string The request method (POST, GET, etc.)
		 */
		public function method() {
			return $this->__method;
		}
		
		public function param($index = null) {
			return $this->__Route->param($index);
		}

		public function option($name = null) {
			return $this->__Route->option($name);
		}
		
		/**
		 * Returns GET data. See Request::data() for argument/return docs
		 * @param string $key
		 * @param string $default
		 * @return array
		 */
		public function get($key = null, $default = null) {
			return $this->__method == self::METHOD_GET
				? $this->data($key, $default)
				: null;
		}
		
		/**
		 * Returns POST data. See Request::data() for argument/return docs
		 * @param string $key
		 * @param string $default
		 * @return array
		 */
		public function post($key = null, $default = null) {
			return $this->__method == self::METHOD_POST
				? $this->data($key, $default)
				: null;
		}
		
		/**
		 * Returns PUT data. See Request::data() for argument/return docs
		 * @param string $key
		 * @param string $default
		 * @return array
		 */
		public function put($key = null, $default = null) {
			return $this->__method == self::METHOD_PUT
				? $this->data($key, $default)
				: null;
		}
		
		/**
		 * Returns DELETE data. See Request::data() for argument/return docs
		 * @param string $key
		 * @param string $default
		 * @return array
		 */
		public function delete($key = null, $default = null) {
			return $this->__method == self::METHOD_DELETE
				? $this->data($key, $default)
				: null;
		}
		
		/**
		 * Returns request data
		 * @param string $key A dot-style associative array index
		 * @param string $default The value to return if the specified index was
		 *                        not found
		 * @return mixed
		 */
		public function data($key = null, $default = null) {
			if (Matrix::pathExists($this->__data, $key)) {
				return Matrix::pathGet($this->__data, $key);
			} else {
				return $default;
			}
		}
		
		/**
		 * Returns a Form object using the request data, if it is compatible.
		 * @param string $formName The name of the form.
		 * @return Frawst\Form
		 */
		public function form($formName = null) {
			if($formName === null) {
				$formClass = $this->getImplementation('Frawst\FormInterface');
				return $formClass::load($this->__data, true);
			} elseif (isset($this->__forms[$formName])) {
				return $this->__forms[$formName];
			} elseif(class_exists($formName) && $formName::method() == $this->method()) {
				return $this->__forms[$formName] = $formName::load($this->__data);
			} else {
				return null;
			}
		}
		
		/**
		 * Get and set persistent data (passed on to sub-requests)
		 * @param string $key A key for the data being set or retrieved
		 * @param mixed $value The value being persisted
		 * @return mixed The value stored under the persisted value
		 */
		public function persist($key, $value = null) {
			if (!is_null($value)) {
				$this->__persist[$key] = $value;
			}
			
			return array_key_exists($key, $this->__persist)
				? $this->__persist[$key]
				: null;
		}
		
		/**
		 * @return float The runtime elapsed for this request.
		 */
		public function getRuntime() {
			return microtime(true) - $this->__startTime;
		}
		
		public function route() {
			return $this->__Route;
		}
	}