<?php
	namespace Frawst;
	
	class Injector {
		
		private static $__defaults = array(
			'Frawst\RouteInterface' => 'Frawst\Route',
			'Frawst\RequestInterface' => 'Frawst\Request',
			'Frawst\ResponseInterface' => 'Frawst\Response',
			'Frawst\ViewInterface' => 'Frawst\View',
			'Frawst\FormInterface' => 'Frawst\Form',
			'controllerNamespace' => 'Frawst\Controller'
		);
		
		private $__data;
		
		public function __construct() {
			$this->__data = array();
		}
		
		public function set($key, $value) {
			$this->__data[$key] = $value;
		}
		
		public function get($key) {
			if(array_key_exists($key, $this->__data)) {
				return $this->__data[$key];
			} else {
				return array_key_exists($key, self::$__defaults)
					? self::$__defaults[$key]
					: null;
			}
		}
		
		public static function register($key, $value) {
			self::$__defaults[$key] = $value;
		}
	}