<?php
	namespace Frawst;
	
	class Injector {
		private $__deps;
		private static $__defaults = array(
			'Frawst\RouteInterface' => 'Frawst\Route',
			'Frawst\RequestInterface' => 'Frawst\Request',
			'Frawst\ResponseInterface' => 'Frawst\Response',
			'Frawst\ViewInterface' => 'Frawst\View',
			'Frawst\FormInterface' => 'Frawst\Form'
		);
		
		public function __construct($deps = array()) {
			$this->__deps = (array) $deps;
		}
		
		public function __set($dep, $value) {
			$this->__deps[$dep] = $value;
		}
		
		public function __get($dep) {
			return array_key_exists($dep, $this->__deps)
				? $this->__deps[$dep]
				: null;
		}
		
		public function inject($deps) {
			$this->__deps = (array)$deps + $this->__deps;
		}
		
		public static function registerDefault($interface, $class) {
			self::$__defaults[$interface] = $class;
		}
		
		public static function defaultClass($interface) {
			return isset(self::$__defaults[$interface]) ? self::$__defaults[$interface] : null;
		}
	}