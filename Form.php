<?php
	namespace Frawst;
	use \Frawst\Library\Matrix,
	    \Frawst\Library\Validator,
	    \Frawst\Exception;
	
	abstract class Form extends Matrix {
		protected static $_fields = array();
		protected static $_validate = array();
		protected $_defaults;
		
		protected $_errors = array();
		
		public function __construct ($data = array()) {
			parent::__construct($data);
			$this->_defaults = static::$_fields;
		}
		
		public function setDefaults ($defaults) {
			$this->_defaults = $defaults+$this->_defaults;
		}
		
		public function setErrors ($errors) {
			$this->_errors = $errors;
		}
		
		public function addErrors ($field, $errors) {
			if (count($errors)) {
				if (!Matrix::pathExists($this->_errors, $field)) {
					Matrix::pathSet($this->_errors, $field, $errors);
				} else {
					Matrix::pathMerge($this->_errors, $field, $errors);
				}
			}
		}
		
		public function addError ($field, $error) {
			$this->addErrors($field, array($error));
		}
		
		public function errors ($field = null) {
			return Matrix::pathExists($this->_errors, $field)
				? Matrix::pathGet($this->_errors, $field)
				: array();
		}
				
		/**
		 * Returns the form name
		 */
		public static function name () {
			$class = explode('\\', get_called_class());
			return end($class);
		}
		
		/**
		 * Returns false if there are any entries in the $data array that
		 * are not specified in this Form.
		 * @param array $data
		 * @return bool
		 */
		public static function compatible ($data) {
			foreach (Matrix::flatten($data) as $field => $value) {
				if (!Matrix::pathExists(static::$_fields, $field)) {
					return false;
				}
			}
			
			return true;
		}
		
		/**
		 * If a value does not exist in the form data, use the default
		 * value instead.
		 */
		public function get ($field = null) {
			return parent::exists($field)
				? parent::get($field)
				: Matrix::pathGet($this->_defaults, $field);
		}
		
		public function validate () {
			$this->_errors = array();
			foreach (static::$_validate as $field => $rules) {
				if (count($errors = Validator::check($this[$field], $rules, $this))) {
					Matrix::pathSet($this->_errors, $field, $errors);
				}
			}
			return count($this->_errors) == 0;
		}
		
		public function valid ($field = null) {
			return Matrix::pathExists($this->_errors, field)
				? (bool) (count(Matrix::pathGet($this->_errors, $field)) == 0)
				: true;
		}
	}