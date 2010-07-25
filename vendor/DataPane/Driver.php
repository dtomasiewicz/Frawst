<?php
	namespace DataPane;
	
	abstract class Driver {
		protected $config;
		
		public function __construct($config = array()) {
			$this->config = $config;
		}
	}