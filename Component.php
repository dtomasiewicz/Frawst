<?php
	namespace Frawst;
	
	abstract class Component {
		protected $_Controller;
		
		public function __construct($controller) {
			$this->_Controller = $controller;
			$this->_init();
		}
		
		public function __get($name) {
			if($name == 'Controller') {
				return $this->_Controller;
			} else {
				throw new Exception\Frawst('Invalid Component property: '.$name);
			}
		}
		
		protected function _init() {
			
		}
	}