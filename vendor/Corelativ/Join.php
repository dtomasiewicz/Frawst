<?php
	namespace Corelativ;
	
	/**
	 * Generic Join class that impersonates a Model _only_ as much
	 * as is necessary for Relations to treat it as such. Does NOT
	 * extend model because a model must have a unique table, whereas
	 * different types of joins will have different tables.
	 */
	class Join {
		public $data;
		
		public function __construct($data = array()) {
			$this->data = $data;
		}
		public function __get($name) {
			return $this->data[$name];
		}
		public function __set($name, $value) {
			$this->data[$name] = $value;
		}
	}