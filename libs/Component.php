<?php
	namespace Frawst;
	
	/**
	 * Base Component class for the Frawst framework.
	 * 
	 * Components are modular extensions of controller (business) logic. Components are
	 * instantiated on-demand and each controller instance has its own set of components.
	 */
	abstract class Component implements ComponentInterface {
		protected $_Controller;
		
		public function __construct(ControllerInterface $controller) {
			$this->_Controller = $controller;
		}
		
		public function setup() {
			
		}
		
		public function teardown() {
			
		}
		
		public function controller() {
			return $this->_Controller;
		}
	}