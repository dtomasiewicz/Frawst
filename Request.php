<?php
	namespace Frawst;
	use \Frawst\Library\Matrix,
		\Frawst\Exception;
	
	/**
	 * Request object. Each request consists of a single controller and,
	 * optionally, a single View.
	 */
	class Request {
		/**
		 * The data controller to be used by this request
		 * @access public-read
		 * @var object
		 */
		protected $_DataController;
		
		/**
		 * The data mapper to be used by this request
		 * @access public-read
		 * @var object
		 */
		protected $_DataMapper;
		
		/**
		 * The cache controller to be used by this request
		 * @access public-read
		 * @var object
		 */
		protected $_CacheController;
		
		/**
		 * The request controller
		 * @var Frawst\Controller
		 */
		protected $_Controller;
		
		/**
		 * An array of request route segments (controllers and an action)
		 * @var array
		 */
		protected $_route;
		
		/**
		 * The name of the request action, should be the name of a
		 * public method of $Controller
		 * @var string
		 */
		protected $_action;
		
		/**
		 * An array of (unnamed) request parameters, not to be confused
		 * with GET data
		 * @var array
		 */
		protected $_params;
		
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
		 * @param string $route
		 * @param array $data
		 * @param string $method
		 * @param array $headers
		 * @param object $dataController
		 * @param object $dataMapper
		 * @param object $cacheController
		 * @param array $persist
		 */
		public function __construct($route, $data = array(), $method = 'GET', $headers = array(),
		  $dataController = null, $dataMapper = null, $cacheController = null, $persist = array()) {
		  	if (isset($data['___FORMNAME'])) {
		  		$formName = $data['___FORMNAME'];
		  		unset($data['___FORMNAME']);
		  		$this->_data = $data;
		  		$this->_Form = $this->form($formName);
		  	} else {
		  		$this->_data = $data;
		  	}
		  	
		  	$this->_method = $method;
		  	$this->_headers = $headers;
		  	
			$this->_DataController = $dataController;
			$this->_DataMapper = $dataMapper;
			$this->_CacheController = $cacheController;
			
			$this->_dispatch($route);
			
			$this->_persist = $persist;
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
				case 'Data':
					return $this->_DataController;
				case 'Mapper':
					return $this->_DataMapper;
				case 'Cache':
					return $this->_CacheController;
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
		 * @param string $route If null, will use the current route
		 * @return string The path relative to the web root
		 */
		public function path($route = null) {
			if (is_null($route)) {
				$route = $this->route(true);
			}
			return rtrim(WEB_ROOT.'/'.$route, '/');
		}
		
		/**
		 * Returns the resolved route of the current request.
		 * @param bool $params If true, request parameters will also be appended
		 * @return string The resolved route
		 */
		public function route($params = false) {
			$route = implode('/', $this->_route);
			
			if ($params) {
				$route .= '/'.implode($this->_params);
			}
			
			return $route;
		}
		
		/**
		 * Creates a sub-request with the same DataController, DataMapper, and
		 * CacheController as this one.
		 * @param string $route
		 * @param array $headers
		 * @return Frawst\Request The sub-request object
		 */
		public function subRequest($route, $data = array(), $method = 'GET', $headers = array()) {
			return new Request($route, $data, $method, $headers, $this->Data, $this->Mapper, $this->Cache, $this->_persist);
		}
		
		/**
		 * Determines the controller, action, and request parameters based
		 * on the given route. Also instantiates the controller.
		 * @param string $route
		 */
		protected function _dispatch($route) {
			$route = explode('/', trim($route, '/'));
			if ($route[0] == '') {
				unset($route[0]);
			}
			
			// get top-level (root) controller
			$name = 'Root';
			if (isset($route[0])) {
				$name = ucfirst(strtolower($route[0]));
				if (class_exists('\\Frawst\\Controller\\'.$name)) {
					array_shift($route);
				} else {
					$name = 'Root';
				}
			}
			if (class_exists($class = '\\Frawst\\Controller\\'.$name)) {
				$this->_route[] = $name;
			} else {
				exit(404);
			}
			
			// check for sub-controllers
			$exists = true;
			while (count($route) && $exists) {
				$subname = ucfirst(strtolower($route[0]));
				if (class_exists($c = $class.'\\'.$subname)) {
					$this->_route[] = $subname;
					$class = $c;
					array_shift($route);
				} else {
					$exists = false;
				}
			}
			
			// if the class is abstract, use the /Main subcontroller
			$rClass = new \ReflectionClass($class);
			if ($rClass->isAbstract()) {
				$this->_route[] = 'Main';
				$class .= '\\Main';
			}
			
			$this->_Controller = new $class($this);
			
			// determine action
			if (isset($route[0]) && $this->_Controller->hasAction($action = strtolower(ltrim($route[0], '_')))) {
				$this->_action = $action;
				array_shift($route);
			} elseif ($this->_Controller->hasAction('index')) {
				$this->_action = 'index';
			} else {
				//@todo better 404 handling
				exit(404);
			}
			
			$this->_route[] = $this->_action;
			$this->_params = $route;
		}
		
		/**
		 * Executes the controller action and sets the return data to this
		 * Request's response object.
		 * @param string $method Request method (POST, GET, etc)
		 * @param array $data Request data
		 * @return mixed The response object for this Request
		 * @todo move $method and $data to constructor, attempt to construct form in constructor
		 *       (get rid of ___FORMNAME early)
		 */
		public function execute() {
			$this->_Response = new Response($this);
			$this->_Response->data($this->_Controller->execute($this->_action, $this->_params));
			return $this->_Response;
		}
		
		/**
		 * @return string The name of the request action
		 */
		public function action() {
			return $this->_action;
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
		public function getData($key = null, $default = null) {
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
		public function postData($key = null, $default = null) {
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
		public function putData($key = null, $default = null) {
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
		public function deleteData($key = null, $default = null) {
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