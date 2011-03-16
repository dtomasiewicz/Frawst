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
	 *   ns:Frawst\ControllerInterface     (Frawst\Controller)
	 *     Namespace prefix for controllers.
	 */
	class Request extends Base implements RequestInterface {
		
		const METHOD_GET = 'GET';
		const METHOD_POST = 'POST';
		const METHOD_DELETE = 'DELETE';
		const METHOD_PUT = 'PUT';
		
		/**
		 * @var float The time at which the request was first invoked.
		 */
		private $startTime;
		
		/**
		 * The request controller
		 * @var Frawst\Controller
		 */
		private $Controller;
		
		/**
		 * @var Frawst\Root Request route
		 */
		private $Route;
		
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
		 * Persistent data to be sent to sub-requests.
		 */
		private $persist;
	
		/**
		 * Constructor
		 * @param mixed $route
		 * @param array $data
		 * @param string $method
		 * @param array $headers
		 * @param array $persist
		 */
		public function __construct(RouteInterface $route, array $data, $method, array $headers, array $persist) {
			$this->startTime = microtime(true);
			$this->Route = $route;
			$this->data = $data;
		  	$this->method = strtoupper($method);
		  	$this->headers = $headers;
			$this->persist = $persist;
			
			$this->forms = array();
			$this->Controller = null;
		}
		
		public static function factory($route, array $data = array(), $method = self::METHOD_GET,
		  array $headers = array(), array $persist = array()) {
			if(!($route instanceof RouteInterface)) {
				$rClass = self::getClassImplementation('Frawst\RouteInterface');
				$route = $rClass::resolve($route);
			}
			$c = get_called_class();
			return new $c($route, $data, $method, $headers, $persist);
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
		 * Creates a sub-request with the same persistent data and headers as this request,
		 * in AJAX mode.
		 * @param string $route
		 * @return Frawst\Request The sub-request object
		 */
		public function subRequest($route, $data = array(), $method = 'GET') {
			$headers = $this->headers;
			$headers['X-Requested-With'] = 'XMLHttpRequest';
			return self::factory($route, $data, $method, $headers, $this->persist);
		}
		
		/**
		 * Executes the controller action and sets the return data to this
		 * Request's response object.
		 * @return mixed The response object for this Request
		 */
		public function execute() {
			$cClass = $this->getImplementation('Frawst\ControllerInterface');
			$resClass = $this->getImplementation('Frawst\ResponseInterface');
			$response = $resClass::factory($this);
			
			$controller = $this->Route->controller();
			if($controller !== null) {
				$this->Controller = $cClass::factory($controller, $response);
			
				try {
					$response->data($this->Controller->execute());
				} catch(\Exception $e) {
					$response->data('<div class="Frawst-Debug">'.
						'<h1>A Controller Problem Occurred!</h1>'.
						'<pre>'.$e.'</pre></div>');
				}
			
				$this->Controller = null;
			} else {
				if($this->Route->template() === null) {
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
		
		public function param($index = null) {
			return $this->Route->param($index);
		}

		public function option($name = null) {
			return $this->Route->option($name);
		}
		
		/**
		 * Returns GET data. See Request::data() for argument/return docs
		 * @param string $key
		 * @param string $default
		 * @return array
		 */
		public function get($key = null, $default = null) {
			return $this->method == self::METHOD_GET
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
			return $this->method == self::METHOD_POST
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
			return $this->method == self::METHOD_PUT
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
			return $this->method == self::METHOD_DELETE
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
				$fClass = $this->getImplementation('Frawst\FormInterface');
				$this->forms[$name] = $fClass::factory($name, $this->data, $checkToken);
			}
			return $this->forms[$name];
		}
		
		/**
		 * Get and set persistent data (passed on to sub-requests)
		 * @param string $key A key for the data being set or retrieved
		 * @param mixed $value The value being persisted
		 * @return mixed The value stored under the persisted value
		 */
		public function persist($key, $value = null) {
			if (!is_null($value)) {
				$this->persist[$key] = $value;
			}
			
			return array_key_exists($key, $this->persist)
				? $this->persist[$key]
				: null;
		}
		
		/**
		 * @return float The runtime elapsed for this request.
		 */
		public function runtime() {
			return microtime(true) - $this->startTime;
		}
		
		public function route() {
			return $this->Route;
		}
		
		public function __get($name) {
			if($name == 'Route') {
				return $this->Route;
			} else {
				throw new Exception('Invalid request property: '.$name);
			}
		}
	}