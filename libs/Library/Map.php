<?php
	namespace Frawst;
	
	class Map {
		protected $_data;
		
		public function __construct($items = array()) {
			$this->_data = array();
			
			foreach($items as $key => $value) {
				$this->put($key, $value);
			}
		}
		
		public function put($key, $value) {
			$this->_data[$key] = $value;
		}
	}