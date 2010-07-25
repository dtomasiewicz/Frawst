<?php
	namespace Frawst;
	
	abstract class Component {
		protected $Controller;
		
		public function __construct($Controller) {
			$this->Controller = $Controller;
			$this->init();
		}
		public function init() {
			
		}
	}