<?php
	namespace Frawst;
	
	abstract class Helper {
		protected $_View;
		
		public function __construct($view) {
			$this->_View = $view;
		}
		
		public function __get($name) {
			if ($name == 'View') {
				return $this->_View;
			} else {
				throw new Exception('Invalid View property: '.$name);
			}
		}
		
		public function setup() {
			
		}
		
		public function teardown() {
			
		}
	}