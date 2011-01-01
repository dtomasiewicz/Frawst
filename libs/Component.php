<?php
	namespace Frawst;
	
	/**
	 * Base Component class for the Frawst framework.
	 * 
	 * Components are modular extensions of controller (business) logic. Components are
	 * instantiated on-demand and each controller instance has its own set of components.
	 */
	abstract class Component {
		protected $_Controller;
		
		public function __construct($controller) {
			$this->_Controller = $controller;
			$this->_init();
		}
		
		/**
		 * Immitates read-only properties.
		 * @param string $name
		 * @return mixed
		 */
		public function __get($name) {
			if ($name == 'Controller') {
				return $this->_Controller;
			} else {
				throw new Exception('Invalid Component property: '.$name);
			}
		}
		
		/**
		 * Dummy initialization function. Will be called after construction.
		 */
		protected function _init() {
			
		}
	}