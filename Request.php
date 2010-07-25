<?php
	namespace Frawst;
	use \Frawst\Library\Matrix,
		\Frawst\Library\Inflector,
		\Frawst\View\AppView,
		\Frawst\Exception;
	
	class Request {
		const RESPONSE_JSON = 'application/json';
		const RESPONSE_HTML = 'text/html';
		
		protected $Data;
		protected $Mapper;
		protected $Cache;
		protected $Controller;
		
		protected $route;
		protected $action;
		protected $params;
		protected $headers;
		
		protected $method;
		protected $requestData;
		protected $responseType;
		
		protected $actionData;
		protected $redirectTo;
		
		protected $View;
		
		public function __construct($route, $headers = array(), $data = null, $mapper = null, $cache = null) {
			$this->headers = $headers;
			$this->Data = $data;
			$this->Mapper = $mapper;
			$this->Cache = $cache;
			$this->responseType = self::RESPONSE_HTML;
			$this->dispatch($route);
		}
		
		/**
		 * Hacked performance of public readonly properties.
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
		
		public function isAjax() {
			return (bool) (isset($this->headers['X-Requested-With']) && $this->headers['X-Requested-With'] == 'XMLHttpRequest'); 
		}
		
		public function isHtml() {
			return (bool) ($this->responseType == self::RESPONSE_HTML);
		}
		
		public function isJson() {
			return (bool) ($this->responseType == self::RESPONSE_JSON);
		}
		
		public function respondAs($mime) {
			$this->responseType = $mime;
		}
		
		public function respondAsJson() {
			$this->responseType = self::RESPONSE_JSON;
		}
		
		public function responseType() {
			return $this->responseType;
		}
		
		public function queueRedirect($to = '', $external = false) {
			$this->redirectTo = $external ? $to : $this->path($to);
			// some browsers (e.g. Firefox) fail to pass non-standard headers to next page
			// this is somewhat of a hack to get it to work
			if($this->isAjax()) {
				$this->redirectTo .= AJAX_SUFFIX;
			}
			return true;
		}
		
		public function redirect() {
			if(isset($this->redirectTo)) {
				exit(header('Location: '.$this->redirectTo));
			} else {
				return false;
			}
		}
		
		/**
		 * Returns the full path from the web root to the specified
		 * location within the app's home directory.
		 */
		public function path($route = null) {
			if(is_null($route)) {
				$route = $this->route();
			}
			
			return WEB_ROOT.'/'.trim($route, '/\\');
		}
		
		/**
		 * Returns the resolved route of the current request.
		 * 
		 * @signature[1] [boolean = false [, boolean = true]]
		 * @param boolean $changes If set to true, the querystring will
		 *                         be appended to the route.
		 * @param boolean $htmlSafe If set to true, ampersands (&) in
		 *                          the returned string will use &amp;
		 *                          
		 * @signature[2] array[, boolean = true]
		 * @param array $changes An array of changes to make to the request
		 *                       parameters and get (querystring) variables.
		 * @param boolean $htmlSafe If set to true, ampersands (&) in
		 *                          the returned string will use &amp;
		 */
		public function route($changes = false, $htmlSafe = true) {
			$route = strtolower(implode('/', $this->route));
			
			if($changes === true) {
				$changes = array();
			}
			
			if(is_array($changes)) {
				$params = $changes + $this->params;
			
				if($this->method == 'GET') {
					$params += $this->requestData;
				}
			
				$qString = '?';
				foreach($params as $key => $value) {
					if(is_int($key)) {
						$route .= '/'.$value;
					} else {
						$qString .= $key.'='.$value.'&';
					}
				}
				$route = $route . rtrim($qString, '&?');
			}

			if($htmlSafe) {
				return str_replace('&', '&amp;', $route);
			} else {
				return $route;
			}
		}
		
		/**
		 * Convenience method for creating, executing, and rendering
		 * a full request.
		 */
		public static function make($route, $method = 'GET', $requestData = array(), $headers = array(), $data = null, $mapper = null, $cache = null) {
			$request = new Request($route, $headers, $data, $mapper, $cache);
			$request->execute($method, $requestData);
			return $request->render();
		}
		
		public function subRequest($route, $method = 'GET', $requestData = array(), $headers = array()) {
			return self::make($route, $method, $requestData, $headers, $this->Data, $this->Mapper, $this->Cache);
		}
		
		/**
		 * Dispatches and instantiates the necessary controllers and
		 * determines the action and parameters. The controllers are
		 * prepended to $this->controllers so that $this->controllers[0]
		 * is always the action controller once dispatching finishes.
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
			if(isset($route[0]) && $this->Controller->actionExists($this->action = Inflector::camelBack($route[0]))) {
				array_shift($route);
			} else {
				$this->action = 'index';
			}
			$this->route[] = $this->action;
			$this->params = $route;
		}
		
		/**
		 * Each controller setup() routine gets the data from all of its
		 * parent controllers' setup routines.
		 */
		public function execute($method = 'GET', $data = array()) {
			$this->method = strtoupper($method);
			$this->requestData = $data;
			
			return $this->actionData = $this->Controller->execute($this->action, $this->params);
		}
		
		/**
		 * Renders the request view.
		 * @return The rendered view
		 */
		public function render() {
			if(isset($this->redirectTo) && !$this->redirect()) {
				throw new Exception\Frawst('Attempting to render view, but action did return an array. Request must redirect, but was not queued.');
			} else {
				$data = is_array($this->actionData)
					? $this->actionData
					: array('status' => $this->actionData);
				
				if($this->responseType == self::RESPONSE_JSON) {
					header('Content-Type: '.self::RESPONSE_JSON);
					return json_encode($data);
				} elseif($this->responseType == self::RESPONSE_HTML) {
					$this->View = new AppView($this, $this->isAjax());
					$contentFile = implode(DIRECTORY_SEPARATOR, $this->route);
					return $this->View->render($contentFile, $data);
				}
			}
		}
		
		public function action() {
			return $this->action;
		}
		
		public function method() {
			return $this->method;
		}
		
		public function postData($key = null, $default = null) {
			if($this->method == 'POST') {
				return $this->data($key, $default);
			} else {
				return null;
			}
		}
		
		public function data($key = null, $default = null) {
			if(Matrix::pathExists($this->requestData, $key)) {
				return Matrix::pathGet($this->requestData, $key);
			} else {
				return $default;
			}
		}
	}