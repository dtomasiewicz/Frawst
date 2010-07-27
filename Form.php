<?php
	namespace Frawst;
	use \Frawst\Library\Matrix,
	    \Frawst\Exception;
	
	abstract class Form extends Matrix {
		protected static $fields;
		protected $defaults;
		
		protected $errors = array();
		
		public function __construct($data = array()) {
			parent::__construct($data);
			$this->defaults = static::$fields;
		}
		
		public function setDefaults($defaults) {
			$this->defaults = $defaults+$this->defaults;
		}
		
		public function setErrors($errors) {
			$this->errors = $errors;
		}
		
		public function addErrors($field, $errors) {
			if(count($errors)) {
				if(!Matrix::pathExists($this->errors, $field)) {
					Matrix::pathSet($this->errors, $field, $errors);
				} else {
					Matrix::pathSet($this->errors, $field, Matrix::pathGet($this->errors, $field) + $errors);
				}
			}
		}
		
		public function errors($field = null) {
			if(Matrix::pathExists($this->errors, $field)) {
				return Matrix::pathGet($this->errors, $field);
			} else {
				return array();
			}
		}
				
		/**
		 * Returns the form name
		 */
		public static function name() {
			$class = explode('\\', get_called_class());
			return end($class);
		}
		
		/**
		 * Returns false if there are any entries in the $data array that
		 * are not specified in this Form.
		 * @param array $data
		 * @return bool
		 */
		public static function compatible($data) {
			foreach(Matrix::flatten($data) as $field => $value) {
				if(!Matrix::pathExists(static::$fields, $field)) {
					return false;
				}
			}
			
			return true;
		}
		
		/**
		 * If a value does not exist in the form data, use the default
		 * value instead.
		 */
		public function get($field = null) {
			if(parent::exists($field)) {
				return parent::get($field);
			} else {
				return Matrix::pathGet($this->defaults, $field);
			}
		}
	}