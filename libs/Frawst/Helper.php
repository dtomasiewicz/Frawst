<?php
	namespace Frawst;
	
	abstract class Helper extends Base implements HelperInterface {
		private $View;
		
		public function __construct(ViewInterface $view) {
			$this->View = $view;
		}
		
		public function view() {
			return $this->View;
		}
		
		public function setup() {
			
		}
		
		public function teardown() {
			
		}
		
		private static function className($name) {
			return 'Frawst\Helper\\'.str_replace('/', '\\', $name);
		}
		
		public static function factory($name, ViewInterface $view) {
			$c = self::className($name);
			if(class_exists($c)) {
				return new $c($view);
			} else {
				return null;
			}
		}
		
		public static function exists($name) {
			return class_exists(self::className($name));
		}
	}