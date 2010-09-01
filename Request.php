<?php
	namespace Frawst;
	use \Frawst\Library\Matrix,
		\Frawst\Exception;
	
	/**
	 * Frawst Request Handler
	 * 
	 * A Request object simulates an HTTP request to a particular route in your
	 * application.
	 */
	class Request {
		
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
		 * A Form object, created if formdata was submitted with the request
		 * @var Frawst\Form
		 */
		protected $_Form;
		
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
			if (isset($data['___FORMNAME'])) {
		  		$formName = $data['___FORMNAME'];
		  		unset($data['___FORMNAME']);
		  		$this->_data = $data;
		  		$this->_Form = $this->form($formName);
		  	} else {
		  		$this->_data = $data;
		  	}
		  	
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
					throw new Exception\Frawst('Trying to access undeclared property Request::$'.$name);
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
			$root = URL_REWRITE ? WEB_ROOT : WEB_ROOT.'/index.php';
			return $root.'/'.$route;
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
			$this->_Response = new Response($this);
			$this->_Response->data($this->_Controller->_execute($this->_Route->action(), $this->_method, $this->_Route->params()));
			return $this->_Response;
		}
		
		/**
		 * @return string The request method (POST, GET, etc.)
		 */
		public function method() {
			return $this->_method;
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
		 * @param string $formName The name of the form. If not provided, will check for a
		 *                         ___FORMNAME key in the request data.
		 * @return Frawst\Form
		 */
		public function form($formName = null) {
			if (isset($this->_Form)) {
				return is_null($formName) || $this->_Form->name() == $formName
					? $this->_Form
					: null;
			} elseif (empty($this->_data) || is_null($formName)) {
				return null;
			} elseif (class_exists($class = 'Frawst\\Form\\'.$formName) && $class::compatible($this->_data)) {
				return new $class($this->_data);
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
	}