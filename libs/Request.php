<?php
	namespace Frawst;
	
	/**
	 * Frawst Request Handler
	 * 
	 * A Request object simulates an HTTP request to a particular route in your
	 * application.
	 */
	class Request {
		
		/**
		 * @var float The time at which the request was first invoked.
		 */
		protected $_startTime;
		
		/**
		 * The request controller
		 * @var Frawst\Controller
		 */
		protected $_Controller;
		
		/**
		 * @var Frawst\Root Request route
		 */
		protected $_Route;
		
		/**
		 * Associative array of headers sent to this request
		 * @var array
		 */
		protected $_headers;
		
		/**
		 * The method through which this request was performed
		 * @example 'POST'
		 * @var string
		 */
		protected $_method;
		
		/**
		 * Associative array of data sent to this request
		 * @example form data from a POST request, querystring data from GET
		 * @var array
		 */
		protected $_data;
		
		/**
		 * @var array An array of forms submitted to the request
		 */
		protected $_forms;
		
		/**
		 * The response object for this request
		 * @access public-read
		 * @var Frawst\Response
		 */
		protected $_Response;
		
		/**
		 * Persistent data to be sent to sub-requests.
		 */
		protected $_persist;
	
		/**
		 * Constructor
		 * @param mixed $route
		 * @param array $data
		 * @param string $method
		 * @param array $headers
		 * @param array $persist
		 */
		public function __construct($route, $data = array(), $method = 'GET', $headers = array(), $persist = array()) {
			$this->_startTime = microtime(true);
		  	
			$this->_data = $data;
		  	$this->_method = strtoupper($method);
		  	$this->_headers = $headers;
			$this->_persist = $persist;
			
			$this->_Route = $route instanceof Route
				? $route
				: new Route($route);
			
			$controllerClass = $this->_Route->controllerClass();
			$this->_Controller = new $controllerClass($this);
		}
		
		/**
		 * Hacked to give the illusion of public readonly properties
		 * @param string $name
		 * @return object A read-only property
		 */
		public function __get($name) {
			switch ($name) {
				case 'Response':
					return $this->_Response;
				case 'Route':
					return $this->_Route;
				default:
					throw new \Frawst\Exception('Trying to access undeclared property Request::$'.$name);
			}
		}
		
		/**
		 * @return array Associative array of request headers
		 */
		public function headers() {
			return $this->_headers;
		}
		
		/**
		 * Gets the value of a request header
		 * @param string $name
		 * @return string The value of the request header, or null if not set
		 */
		public function header($name) {
			return isset($this->_headers[$name])
				? $this->_headers[$name]
				: null;
		}
		
		/**
		 * Whether or not this request will be rendered as AJAX (layoutless)
		 * @return bool
		 */
		public function isAjax() {
			return (bool) (isset($this->_headers['X-Requested-With']) &&
				strtolower($this->_headers['X-Requested-With']) == 'xmlhttprequest'); 
		}
		
		/**
		 * Returns the full path from the web root to the given route
		 * @param string $route If null, will use the current route with parameters
		 * @return string The path relative to the web root
		 */
		public function path($route = null) {
			if (is_null($route)) {
				$route = $this->_Route->reconstruct();
			}
			return URL_REWRITE ? WEB_ROOT.$route : WEB_ROOT.'index.php/'.$route;
		}
		
		/**
		 * Returns the resolved route of the current request.
		 * @param bool $params If true, request parameters will also be appended
		 * @return string The resolved route
		 */
		public function route($params = false) {
			return $this->_Route->reconstruct($params);
		}
		
		/**
		 * Creates a sub-request with the same persistent data and headers as this request,
		 * in AJAX mode.
		 * @param string $route
		 * @return Frawst\Request The sub-request object
		 */
		public function subRequest($route, $data = array(), $method = 'GET') {
			$headers = $this->_headers;
			$headers['X-Requested-With'] = 'XMLHttpRequest';
			return new Request($route, $data, $method, $headers, $this->_persist);
		}
		
		/**
		 * Executes the controller action and sets the return data to this
		 * Request's response object.
		 * @return mixed The response object for this Request
		 */
		public function execute() {
			if(!isset($this->_Response)) {
				$this->_Response = new Response($this);
				try {
					$this->_Response->data($this->_Controller->execute());
				} catch(\Exception $e) {
					$this->_Response->data('<div class="Frawst-Debug">'.
						'<h1>A Controller Problem Occurred!</h1>'.
						'<pre>'.$e.'</pre></div>');
				}
			}
			return $this->_Response;
		}
		
		/**
		 * @return string The request method (POST, GET, etc.)
		 */
		public function method() {
			return $this->_method;
		}
		
		public function param($param = null) {
			return $this->_Route->param($param);
		}

		public function option($option = null) {
			return $this->_Route->option($option);
		}
		
		/**
		 * Returns GET data. See Request::data() for argument/return docs
		 * @param string $key
		 * @param string $default
		 * @return array
		 */
		public function get($key = null, $default = null) {
			return $this->_method == 'GET'
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
			return $this->_method == 'POST'
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
			return $this->_method == 'PUT'
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
			return $this->_method == 'DELETE'
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
			if (Matrix::pathExists($this->_data, $key)) {
				return Matrix::pathGet($this->_data, $key);
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
				return \Frawst\Form\MyForm::load($this->_data, true);
			} elseif (isset($this->_forms[$formName])) {
				return $this->_forms[$formName];
			} elseif(class_exists($class = 'Frawst\\Form\\'.$formName) && $class::method() == $this->method()) {
				return $this->_forms[$formName] = $class::load($this->_data);
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
				$this->_persist[$key] = $value;
			}
			
			return array_key_exists($key, $this->_persist)
				? $this->_persist[$key]
				: null;
		}
		
		/**
		 * @return float The runtime elapsed for this request.
		 */
		public function getRuntime() {
			return microtime(true) - $this->_startTime;
		}
	}