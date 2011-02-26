<?php
	namespace Frawst;
	
	/**
	 * Frawst Request Handler
	 * 
	 * A Request object simulates an HTTP request to a particular route in your
	 * application.
	 */
	class Request implements RequestInterface {
		
		const METHOD_GET = 'GET';
		const METHOD_POST = 'POST';
		const METHOD_DELETE = 'DELETE';
		const METHOD_PUT = 'PUT';
		
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
		 * Persistent data to be sent to sub-requests.
		 */
		protected $_persist;
		
		private $__injected;
	
		/**
		 * Constructor
		 * @param mixed $route
		 * @param array $data
		 * @param string $method
		 * @param array $headers
		 * @param array $persist
		 */
		public function __construct(RouteInterface $route, $data = array(), $method = self::METHOD_GET, $headers = array(), $persist = array()) {
			$this->_startTime = microtime(true);
		  	
			$this->_data = $data;
		  	$this->_method = strtoupper($method);
		  	$this->_headers = $headers;
			$this->_persist = $persist;
			$this->_Route = $route;
				
			$this->_Controller = null;
			
			$this->__injected = new Injector();
		}
		
		public function inject($key, $value) {
			$this->__injected->set($key, $value);
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
		 * Creates a sub-request with the same persistent data and headers as this request,
		 * in AJAX mode.
		 * @param string $route
		 * @return Frawst\Request The sub-request object
		 */
		public function subRequest(RouteInterface $route, $data = array(), $method = 'GET') {
			$headers = $this->_headers;
			$headers['X-Requested-With'] = 'XMLHttpRequest';
			$reqClass = $this->__injected->get('Frawst\RequestInterface');
			return new $reqClass($route, $data, $method, $headers, $this->_persist);
		}
		
		/**
		 * Executes the controller action and sets the return data to this
		 * Request's response object.
		 * @return mixed The response object for this Request
		 */
		public function execute() {
			$resClass = $this->__injected->get('Frawst\ResponseInterface');
			$response = new $resClass($this);
			
			$controllerClass = $this->__injected->get('controllerNamespace').'\\'.str_replace('/', '\\', $this->_Route->controller());
			$this->_Controller = new $controllerClass($this, $response);
			
			try {
				$response->data($this->_Controller->execute());
			} catch(\Exception $e) {
				$response->data('<div class="Frawst-Debug">'.
					'<h1>A Controller Problem Occurred!</h1>'.
					'<pre>'.$e.'</pre></div>');
			}
			
			$this->_Controller = null;
			
			return $response;
		}
		
		/**
		 * @return string The request method (POST, GET, etc.)
		 */
		public function method() {
			return $this->_method;
		}
		
		public function param($index = null) {
			return $this->_Route->param($index);
		}

		public function option($name = null) {
			return $this->_Route->option($name);
		}
		
		/**
		 * Returns GET data. See Request::data() for argument/return docs
		 * @param string $key
		 * @param string $default
		 * @return array
		 */
		public function get($key = null, $default = null) {
			return $this->_method == self::METHOD_GET
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
			return $this->_method == self::METHOD_POST
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
			return $this->_method == self::METHOD_PUT
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
			return $this->_method == self::METHOD_DELETE
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
				$formClass = $this->__injected->get('Frawst\FormInterface');
				return $formClass::load($this->_data, true);
			} elseif (isset($this->_forms[$formName])) {
				return $this->_forms[$formName];
			} elseif(class_exists($formName) && $formName::method() == $this->method()) {
				return $this->_forms[$formName] = $formName::load($this->_data);
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
		
		public function route() {
			return $this->_Route;
		}
	}