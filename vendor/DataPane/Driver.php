<?php
	namespace DataPane;
	
	abstract class Driver {
		protected $_config;
		
		public function __construct($config = array()) {
			$this->_config = $config;
		}
	}