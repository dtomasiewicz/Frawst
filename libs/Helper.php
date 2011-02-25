<?php
	namespace Frawst;
	
	abstract class Helper {
		protected $_View;
		
		public function __construct($view) {
			$this->_View = $view;
		}
		
		public function view() {
			return $this->_View;
		}
		
		public function setup() {
			
		}
		
		public function teardown() {
			
		}
	}