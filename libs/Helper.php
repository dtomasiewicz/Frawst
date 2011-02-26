<?php
	namespace Frawst;
	
	abstract class Helper implements HelperInterface {
		protected $_View;
		
		public function __construct(ViewInterface $view) {
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