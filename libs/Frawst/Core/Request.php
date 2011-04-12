<?php
	namespace Frawst\Core;
	
	/**
	 * Frawst Request Handler
	 * 
	 * A Request object simulates an HTTP request to a particular route in your
	 * application.
	 */
	class Request {
		
		const METHOD_GET = 'GET';
		const METHOD_POST = 'POST';
		const METHOD_DELETE = 'DELETE';
		const METHOD_PUT = 'PUT';
		
		private $module;
		
		/**
		 * @var float The time at which the request was first invoked.
		 */
		private $startTime;
		
		/**
		 * The request controller
		 * @var Frawst\Controller
		 */
		private $controller;
		
		/**
		 * @var Frawst\Root Request route
		 */
		private $route;
		
		/**
		 * Associative array of headers sent to this request
		 * @var array
		 */
		private $headers;
		
		/**
		 * The method through which this request was performed
		 * @example 'POST'
		 * @var string
		 */
		private $method;
		
		/**
		 * Associative array of data sent to this request
		 * @example form data from a POST request, querystring data from GET
		 * @var array
		 */
		private $data;
		
		/**
		 * @var array An array of forms submitted to the request
		 */
		private $forms;
	
		/**
		 * Constructor
		 * @param mixed $route
		 * @param array $data
		 * @param string $method
		 * @param array $headers
		 */
		public function __construct(Module $module, Route $route, array $data, $method, array $headers) {
			$this->module = $module;
			$this->startTime = microtime(true);
			$this->route = $route;
			$this->data = $data;
		  	$this->method = strtoupper($method);
		  	$this->headers = $headers;
			
			$this->forms = array();
			$this->controller = null;
		}
		
		public static function factory(Module $module, Route $route, array $data = array(), $method = self::METHOD_GET,
		  array $headers = array()) {
			return new Request($module, $route, $data, $method, $headers);
		}
		
		public function module() {
			return $this->module;
		}
		
		/**
		 * Gets the value of a request header
		 * @param string $name
		 * @return string The value of the request header, or null if not set
		 */
		public function header($name = null) {
			if($name === null) {
				return $this->headers;
			} elseif(array_key_exists($name, $this->headers)) {
				return $this->headers[$name];
			} else {
				return null;
			}
		}
		
		/**
		 * Whether or not this request will be rendered as AJAX (layoutless)
		 * @return bool
		 */
		public function isAjax() {
			return (bool) (isset($this->headers['X-Requested-With']) &&
				strtolower($this->headers['X-Requested-With']) == 'xmlhttprequest'); 
		}
		
		/**
		 * Executes the controller action and sets the return data to this
		 * Request's response object.
		 * @return mixed The response object for this Request
		 */
		public function execute() {
			$response = Response::factory($this);
			
			$controller = $this->route->controller();
			if($controller !== null) {
				$this->controller = Controller::factory($controller, $response);
				
				try {
					$response->data($this->controller->execute());
				} catch(\Exception $e) {
					$response->data('<div class="Frawst-Debug">'.
						'<h1>A Controller Problem Occurred!</h1>'.
						'<pre>'.$e.'</pre></div>');
				}
			
				$this->controller = null;
			} else {
				if($this->route->template() === null) {
					$response->notFound();
				}
				$response->data(null);
			}
			
			return $response;
		}
		
		/**
		 * @return string The request method (POST, GET, etc.)
		 */
		public function method() {
			return $this->method;
		}
		
		/**
		 * @param int $index The parameter index
		 * @return string The route parameter at the given index
		 */
		public function param($index = null) {
			return $this->route->param($index);
		}

		/**
		 * @param string $name The option name
		 * @return string The route option specified by name
		 */
		public function option($name = null) {
			return $this->route->option($name);
		}
		
		/**
		 * Returns GET data. See Request::data() for argument/return docs
		 * @param string $key
		 * @param array|string $default
		 * @return array|string
		 */
		public function get($key = null, $default = null) {
			return $this->method == self::METHOD_GET
				? $this->data($key, $default)
				: null;
		}
		
		/**
		 * Returns POST data. See Request::data() for argument/return docs
		 * @param string $key
		 * @param array|string $default
		 * @return array|string
		 */
		public function post($key = null, $default = null) {
			return $this->method == self::METHOD_POST
				? $this->data($key, $default)
				: null;
		}
		
		/**
		 * Returns PUT data. See Request::data() for argument/return docs
		 * @param string $key
		 * @param array|string $default
		 * @return array|string
		 */
		public function put($key = null, $default = null) {
			return $this->method == self::METHOD_PUT
				? $this->data($key, $default)
				: null;
		}
		
		/**
		 * Returns DELETE data. See Request::data() for argument/return docs
		 * @param string $key
		 * @param array|string $default
		 * @return array|string
		 */
		public function delete($key = null, $default = null) {
			return $this->method == self::METHOD_DELETE
				? $this->data($key, $default)
				: null;
		}
		
		/**
		 * Returns request data
		 * @param string $key A dot-style associative array index
		 * @param array|string $default The value to return if the specified index was
		 *                        not found
		 * @return array|string
		 */
		public function data($key = null, $default = null) {
			if (Matrix::pathExists($this->data, $key)) {
				return Matrix::pathGet($this->data, $key);
			} else {
				return $default;
			}
		}
		
		/**
		 * Returns a Form object using the request data, if it is compatible.
		 * @param string $formName The name of the form.
		 * @return Frawst\Form
		 */
		public function form($name = null, $checkToken = true) {
			if(!array_key_exists($name, $this->forms)) {
				$this->forms[$name] = Form::factory($this->module()->name(), $name, $this->data, $checkToken);
			}
			return $this->forms[$name];
		}
		
		/**
		 * @return float The runtime elapsed for this request.
		 */
		public function runtime() {
			return microtime(true) - $this->startTime;
		}
		
		/**
		 * @return RouteInterface The request route
		 */
		public function route() {
			return $this->route;
		}
		
		public function __get($name) {
			if($name == 'Route') {
				return $this->route;
			} elseif($name == 'Module') {
				return $this->module;
			} else {
				throw new Exception('Invalid request property: '.$name);
			}
		}
	}