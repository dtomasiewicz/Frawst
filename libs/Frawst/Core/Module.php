<?php
	namespace Frawst\Core;
	
	class Module {
		const SELF_ADDRESS = '127.0.0.1';
		const SELF_PORT = '80';
		
		private $name;
		private $data;
		private $routeBase;
		private $resourceBase;
		private $clientAddress;
		private $clientPort;
		
		public function __construct($name, $routeBase, $resourceBase, $clientAddress, $clientPort) {
			$this->name = $name;
			$this->routeBase = $routeBase;
			$this->resourceBase = $resourceBase;
			$this->clientAddress = $clientAddress;
			$this->clientPort = $clientPort;
			$this->data = array();
		}
		
		public function name() {
			return $this->name;
		}
		
		public function clientAddress() {
			return $this->clientAddress;
		}
		
		public function clientPort() {
			return $this->clientPort;
		}
		
		public function set($key, $value) {
			$this->data[$key] = $value;
		}
		
		public function get($key) {
			return array_key_exists($key, $this->data)
				? $this->data[$key]
				: null;
		}
		
		public static function factory($name, $routeBase = null, $resourceBase = null, $clientAddress = self::SELF_ADDRESS, $clientPort = self::SELF_PORT) {
			if($routeBase === null) {
				$routeBase = \Frawst\URL_REWRITE
					? \Frawst\PUBLIC_ROOT
					: \Frawst\PUBLIC_ROOT.'index.php/';
			}
			if($resourceBase === null) {
				$resourceBase = \Frawst\PUBLIC_ROOT;
			}
			return new Module($name, $routeBase, $resourceBase, $clientAddress, $clientPort);
		}
		
		public function resource($resource = '') {
			return $this->resourceBase.$resource;
		}
		
		public function path($route) {
			return $this->routeBase.$route;
		}
		
		public function request($route, $data, $method, $headers, $routeCustom = false) {
			return Request::factory($this, Route::resolve($this->name, $route, $routeCustom), $data, $method, $headers);
		}
		
	}