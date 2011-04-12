<?php
	namespace Frawst\Core;
	
	abstract class Helper {
		private $view;
		
		public function __construct(View $view) {
			$this->view = $view;
		}
		
		public function view() {
			return $this->view;
		}
		
		public function setup() {
			
		}
		
		public function teardown() {
			
		}
		
		private static function className($module, $name) {
			return 'Frawst\\'.$module.'\Helper\\'.str_replace('/', '\\', $name);
		}
		
		public static function factory($module, $name, View $view) {
			$c = self::className($module, $name);
			if(class_exists($c)) {
				return new $c($view);
			} else {
				return null;
			}
		}
		
		public static function exists($name) {
			return class_exists(self::className($name));
		}
		
		public function __get($name) {
			if($name == 'View') {
				return $this->view;
			} else {
				return $this->view->$name;
			}
		}
	}