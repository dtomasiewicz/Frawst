<?php
	namespace Frawst;
	
	abstract class Helper extends Base implements HelperInterface {
		private $__View;
		
		public function __construct(ViewInterface $view) {
			$this->__View = $view;
		}
		
		public function view() {
			return $this->__View;
		}
		
		public function setup() {
			
		}
		
		public function teardown() {
			
		}
	}