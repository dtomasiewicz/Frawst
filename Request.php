<?php
	namespace Frawst;
	use \Frawst\Library\Matrix,
		\Frawst\Exception;
	
	/**
	 * Request object. Each request consists of a single controller and,
	 * optionally, a single View.
	 * @author ShudderTech
	 *
	 */
	class Request {
		/**
		 * The data controller to be used by this request
		 * @access public (get)
		 * @var object
		 */
		protected $_DataController;
		
		/**
		 * The data mapper to be used by this request
		 * @access public (get)
		 * @var object
		 */
		protected $_DataMapper;
		
		/**
		 * The cache controller to be used by this request
		 * @access public (get)
		 * @var object
		 */
		protected $_CacheController;
		
		/**
		 * The request controller
		 * @var Frawst\Controller
		 */
		protected $_Controller;
		
		/**
		 * An array of request route segments
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
		
		protected $_Response;
		
		/**
		 * Constructor
		 * @param string $route
		 * @param array $headers
		 * @param object $data
		 * @param object $mapper
		 * @param object $cache
		 */
		public function __construct($route, $headers = array(), $data = null, $mapper = null, $cache = null) {
			$this->_headers = $headers;
			$this->_DataController = $data;
			$this->_DataMapper = $mapper;
			$this->_CacheController = $cache;
			$this->_dispatch($route);
			
			$this->_Response = new Response($this);
		}
		
		/**
		 * Hacked to give the illusion of public readonly properties
		 * @param string $name
		 * @return object
		 */
		public function __get($name) {
			switch($name) {
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
			if(is_null($route)) {
				$route = $this->route(true);
			}
			return rtrim(WEB_ROOT.'/'.$route, '/');
		}
		
		/**
		 * Returns the resolved route of the current request, with parameters.
		 * If $changes is an associative array and the request method is GET,
		 * will also append a querystring with $changes applied to the current
		 * GET data (useful for pagination and sorting).
		 * @param array $changes Associative array of changes to be made to GET data
		 * @return string The resolved route with changes applied
		 */
		public function route($params = false) {
			$route = implode('/', $this->_route);
			
			if($params) {
				$route .= '/'.implode($this->_params);
			}
			
			return $route;
		}
		
		/**
		 * Executes and renders a sub-request, using the same data controller,
		 * data mapper, and cache controller as this request.
		 * @param string $route
		 * @param string $method
		 * @param array $requestData
		 * @param array $headers
		 * @return string The rendered request view
		 */
		public function subRequest($route, $headers = array()) {
			return new Request($route, $headers, $this->Data, $this->Mapper, $this->Cache);
		}
		
		/**
		 * Determines the controller, action, and request parameters based
		 * on the given route. Also instantiates the controller.
		 * @param string $route
		 */
		protected function _dispatch($route) {
			$route = explode('/', trim($route, '/'));
			if($route[0] == '') {
				unset($route[0]);
			}
			
			// get top-level (root) controller
			$name = 'Root';
			if(isset($route[0])) {
				$name = ucfirst(strtolower($route[0]));
				if(class_exists('\\Frawst\\Controller\\'.$name)) {
					array_shift($route);
				} else {
					$name = 'Root';
				}
			}
			$class = '\\Frawst\\Controller\\'.$name;
			$this->_route[] = $name;
			
			// check for sub-controllers
			$exists = true;
			while(count($route) && $exists) {
				$subname = ucfirst(strtolower($route[0]));
				if(class_exists($c = $class.'\\'.$subname)) {
					$this->_route[] = $subname;
					$class = $c;
					array_shift($route);
				} else {
					$exists = false;
				}
			}
			
			// if the class is abstract, use the /Main subcontroller
			$rClass = new \ReflectionClass($class);
			if($rClass->isAbstract()) {
				$this->_route[] = 'Main';
				$class .= '\\Main';
			}
			
			$this->_Controller = new $class($this);
			
			// determine action
			if(isset($route[0]) && $this->_Controller->hasAction($action = strtolower(ltrim($route[0], '_')))) {
				$this->_action = $action;
				array_shift($route);
			} else {
				$this->_action = 'index';
			}
			
			$this->_route[] = $this->_action;
			$this->_params = $route;
		}
		
		/**
		 * Executes the controller action
		 * @param string $method Request method (POST, GET, etc)
		 * @param array $data Request data
		 * @return mixed The value returned from the action
		 */
		public function execute($method = 'GET', $data = array()) {
			$this->_method = strtoupper($method);			
			$this->_data = $data;
			
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
		 * @return string The request method
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
		 * Returns the request data
		 * @param string $key A dot-style associative array index
		 * @param string $default The value to return if the specified index was
		 *                        not found
		 * @return mixed
		 */
		public function data($key = null, $default = null) {
			if(Matrix::pathExists($this->_data, $key)) {
				return Matrix::pathGet($this->_data, $key);
			} else {
				return $default;
			}
		}
		
		/**
		 * @return Frawst\Form
		 */
		public function form($formName) {
			if(isset($this->_Form) && $this->_Form->name() == $formName) {
				return $this->_Form;
			} elseif(!empty($this->_data) && class_exists($class = 'Frawst\\Form\\'.$formName) && $class::compatible($this->_data)) {
				return $this->_Form = new $class($this->_data);
			}
			
			return null;
		}
	}