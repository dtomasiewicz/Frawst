<?php
	namespace Frawst;
	
	/**
	 * Base Component class for the Frawst framework.
	 * 
	 * Components are modular extensions of controller (business) logic. Components are
	 * instantiated on-demand and each controller instance has its own set of components.
	 */
	abstract class Component extends Base implements ComponentInterface {
		private $Controller;
		
		public function __construct(ControllerInterface $controller) {
			$this->Controller = $controller;
		}
		
		public function setup() {
			
		}
		
		public function teardown() {
			
		}
		
		public function controller() {
			return $this->Controller;
		}
		
		public static function factory($name, ControllerInterface $controller = null) {
			$c = self::className($name);
			if(class_exists($c)) {
				return new $c($controller);
			} else {
				return null;
			}
		}
		
		public static function exists($name) {
			return class_exists(self::className($name));
		}
		
		private static function className($name) {
			return 'Frawst\Component\\'.str_replace('/', '\\', $name);
		}
	}