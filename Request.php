<?php
	namespace Frawst;
	use \Frawst\Library\Matrix,
		\Frawst\View\AppView,
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
		protected $Data;
		
		/**
		 * The data mapper to be used by this request
		 * @access public (get)
		 * @var object
		 */
		protected $Mapper;
		
		/**
		 * The cache controller to be used by this request
		 * @access public (get)
		 * @var object
		 */
		protected $Cache;
		
		/**
		 * The request controller
		 * @var Frawst\Controller
		 */
		protected $Controller;
		
		/**
		 * An array of request route segments
		 * @var array
		 */
		protected $route;
		
		/**
		 * The name of the request action, should be the name of a
		 * public method of $Controller
		 * @var string
		 */
		protected $action;
		
		/**
		 * An array of (unnamed) request parameters, not to be confused
		 * with GET data
		 * @var array
		 */
		protected $params;
		
		/**
		 * Associative array of headers sent to this request
		 * @var array
		 */
		protected $headers;
		
		/**
		 * The method through which this request was performed
		 * @example 'POST'
		 * @var string
		 */
		protected $method;
		
		/**
		 * Associative array of data sent to this request
		 * @example form data from a POST request, querystring data from GET
		 * @var array
		 */
		protected $requestData;
		
		/**
		 * The data returned by executing the request action; usually an
		 * associative array of view-bound data
		 * @var mixed
		 */
		protected $actionData;
		
		/**
		 * If a redirect is queued, the destination is stored here to allow
		 * full execution of the request action
		 * @var string
		 */
		protected $redirectTo;
		
		/**
		 * The View used by this request for rendering
		 * @var Frawst\AppView
		 */
		protected $View;
		
		/**
		 * Constructor
		 * @param string $route
		 * @param array $headers
		 * @param object $data
		 * @param object $mapper
		 * @param object $cache
		 */
		public function __construct($route, $headers = array(), $data = null, $mapper = null, $cache = null) {
			$this->headers = $headers;
			$this->Data = $data;
			$this->Mapper = $mapper;
			$this->Cache = $cache;
			$this->dispatch($route);
		}
		
		/**
		 * Hacked to give the illusion of public readonly properties
		 * @param string $name
		 * @return object
		 */
		public function __get($name) {
			switch($name) {
				case 'Data':
					return $this->Data;
				case 'Mapper':
					return $this->Mapper;
				case 'Cache':
					return $this->Cache;
				default:
					throw new Exception\Frawst('Trying to access undeclared property Request::$'.$name);
			}
		}
		
		/**
		 * Whether or not this request will be rendered as AJAX (layoutless)
		 * @return bool
		 */
		public function isAjax() {
			return (bool) (isset($this->headers['X-Requested-With']) && $this->headers['X-Requested-With'] == 'XMLHttpRequest'); 
		}
		
		/**
		 * Queues a redirect. If a redirect is queued, it will happen before a
		 * view is rendered. This is to allow controllers to make sub-requests
		 * to others and use the data returned from execute() without worrying about
		 * the top-level request being redirected.
		 * @param string $to The route to redirect to
		 * @param bool $external If true, $to should be a URI instead of a route
		 * @return true
		 */
		public function queueRedirect($to = '', $external = false) {
			$this->redirectTo = $external ? $to : $this->path($to);
			// some browsers (e.g. Firefox) fail to pass non-standard headers to next page
			// this is somewhat of a hack to get it to work
			if($this->isAjax()) {
				$this->redirectTo .= AJAX_SUFFIX;
			}
			return true;
		}
		
		/**
		 * Attempts to perform a queued redirection
		 * @return bool false if there was no redirect queued
		 */
		public function redirect() {
			if(isset($this->redirectTo)) {
				exit(header('Location: '.$this->redirectTo));
			} else {
				return false;
			}
		}
		
		/**
		 * Returns the full path from the web root to the given route
		 * @param string $route If null, will use the current route
		 * @return string The path relative to the web root
		 */
		public function path($route = null) {
			if(is_null($route)) {
				$route = $this->route();
			}
			
			return WEB_ROOT.'/'.trim($route, '/\\');
		}
		
		/**
		 * Returns the resolved route of the current request, with parameters.
		 * If $changes is an associative array and the request method is GET,
		 * will also append a querystring with $changes applied to the current
		 * GET data (useful for pagination and sorting).
		 * @param array $changes Associative array of changes to be made to GET data
		 * @return string The resolved route with changes applied
		 */
		public function route($changes = null) {
			$route = strtolower(implode('/', $this->route)).'/'.implode($this->params);
			
			if($this->method == 'GET' && is_array($changes)) {
				$get = $changes + $this->requestData;
			
				$qString = '?';
				foreach($get as $key => $value) {
					if(is_int($key)) {
						$route .= '/'.$value;
					} else {
						$qString .= $key.'='.$value.'&';
					}
				}
				$route = $route . rtrim($qString, '&?');
			}

			return $route;
		}
		
		/**
		 * Convenience method for creating, executing, and rendering
		 * a full request
		 * @param string $route
		 * @param string $method
		 * @param array $requestData
		 * @param array $headers
		 * @param object $Data
		 * @param object $Mapper
		 * @param object $Cache
		 * @return string The rendered request view
		 */
		public static function make($route, $method = 'GET', $requestData = array(), $headers = array(), $data = null, $mapper = null, $cache = null) {
			$request = new Request($route, $headers, $data, $mapper, $cache);
			$request->execute($method, $requestData);
			return $request->render();
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
		public function subRequest($route, $method = 'GET', $requestData = array(), $headers = array()) {
			return self::make($route, $method, $requestData, $headers, $this->Data, $this->Mapper, $this->Cache);
		}
		
		/**
		 * Determines the controller, action, and request parameters based
		 * on the given route. Also instantiates the controller.
		 * @param string $route
		 */
		public function dispatch($route) {
			$route = explode('/', strtolower(trim($route, '/')));
			
			// get top-level (root) controller
			$name = 'Root';
			if(isset($route[0])) {
				$name = ucfirst($route[0]);
				if(class_exists('\\Frawst\\Controller\\'.$name)) {
					array_shift($route);
				} else {
					$name = 'Root';
				}
			}
			$class = '\\Frawst\\Controller\\'.$name;
			$this->route[] = $name;
			
			// check for sub-controllers
			$exists = true;
			while(count($route) && $exists) {
				$subname = ucfirst($route[0]);
				if(class_exists($c = $class.'\\'.$subname)) {
					$this->route[] = $subname;
					$class = $c;
					array_shift($route);
				} else {
					$exists = false;
				}
			}
			
			// if the class is abstract, use the /Main subcontroller
			$rClass = new \ReflectionClass($class);
			if($rClass->isAbstract()) {
				$this->route[] = 'Main';
				$class .= '\\Main';
			}
			
			$this->Controller = new $class($this);
			
			// determine action
			if(isset($route[0]) && $this->Controller->actionExists($route[0])) {
				$this->action = array_shift($route);
			} else {
				$this->action = 'index';
			}
			$this->route[] = $this->action;
			$this->params = $route;
		}
		
		/**
		 * Executes the controller action
		 * @param string $method Request method (POST, GET, etc)
		 * @param array $data Request data
		 * @return mixed The value returned from the action
		 */
		public function execute($method = 'GET', $data = array()) {
			// REST hack for browsers that don't support PUT and DELETE methods
			if(isset($data['___METHOD']) && in_array($data['___METHOD'], array('GET', 'POST', 'PUT', 'DELETE'))) {
				$method = $data['___METHOD'];
				unset($data['___METHOD']);
			} else {
				$this->method = strtoupper($method);
			}
			
			$this->requestData = $data;
			
			return $this->actionData = $this->Controller->execute($this->action, $this->params);
		}
		
		/**
		 * Renders the view. If a redirect has been queued, it will happen instead
		 * @return string The rendered view
		 */
		public function render() {
			if(isset($this->redirectTo) && !$this->redirect()) {
				throw new Exception\Frawst('Attempting to render view, but action did return an array. Request must redirect, but was not queued.');
			} else {
				$data = is_array($this->actionData)
					? $this->actionData
					: array('status' => $this->actionData);
					
				$this->View = new AppView($this, $this->isAjax());
				$contentFile = implode(DIRECTORY_SEPARATOR, $this->route);
				return $this->View->render($contentFile, $data);
			}
		}
		
		/**
		 * @return string The name of the request action
		 */
		public function action() {
			return $this->action;
		}
		
		/**
		 * @return string The request method
		 */
		public function method() {
			return $this->method;
		}
		
		/**
		 * Returns GET data. See Request::data() for argument/return docs
		 * @param string $key
		 * @param string $default
		 * @return array
		 */
		public function getData($key = null, $default = null) {
			return $this->method == 'GET'
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
			return $this->method == 'POST'
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
			return $this->method == 'PUT'
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
			return $this->method == 'DELETE'
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
			if(Matrix::pathExists($this->requestData, $key)) {
				return Matrix::pathGet($this->requestData, $key);
			} else {
				return $default;
			}
		}
	}