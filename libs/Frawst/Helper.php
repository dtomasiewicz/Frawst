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
	}