<?php
	namespace Frawst\Core;
	
	/**
	 * Base Component class for the Frawst framework.
	 * 
	 * Components are modular extensions of controller (business) logic. Components are
	 * instantiated on-demand and each controller instance has its own set of components.
	 */
	abstract class Component  {
		private $controller;
		
		public function __construct(Controller $controller) {
			$this->controller = $controller;
		}
		
		public function setup() {
			
		}
		
		public function teardown() {
			
		}
		
		public function controller() {
			return $this->controller;
		}
		
		public static function factory($module, $name, Controller $controller = null) {
			$c = self::className($module, $name);
			if(class_exists($c)) {
				return new $c($controller);
			} else {
				return null;
			}
		}
		
		public static function exists($name) {
			return class_exists(self::className($name));
		}
		
		private static function className($module, $name) {
			return 'Frawst\\'.$module.'\Component\\'.str_replace('/', '\\', $name);
		}
		
		public function __get($name) {
			if($name == 'Controller') {
				return $this->controller;
			} else {
				return $this->controller->$name;
			}
		}
	}