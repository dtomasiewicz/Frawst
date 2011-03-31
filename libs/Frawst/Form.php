<?php
	namespace Frawst;
	
	/**
	 * Base Frawst Form class
	 * 
	 * This class can be extended to rigidly define properties of your application's
	 * FORMs. This allows separation of form validation from your controller logic, and
	 * prevents duplication of form processing if you use the same form in multiple areas
	 * of your site. Anti-CSRF form tokens are also implemented and will be
	 * injected by default for all forms as long as you are using the FORM helper.
	 * abstract 
	 * 
	 * This class is NOT used to output form markup! For that, you can use the Form helper. Simply
	 * pass the name of your Form class in the open() method, and the fields will be (re)populated
	 * automatically.
	 */
	class Form extends Base implements FormInterface, \ArrayAccess {
		private $data;
		
		/**
		 * An array of key/value pairs where the keys are form fields in dot-path form
		 * and the values are validation rules.
		 * @var array
		 */
		protected static $validate = array();
		
		/**
		 * An associative array of errors for this form, where the keys are field names
		 * in dot-path format and the values are arrays of error messages.
		 * @var array
		 */
		private $errors = array();
		
		/**
		 * Instantiates the form object with the given data. If the data contains fields
		 * not specified in $fields, they are ignored.
		 * @param array $data
		 */
		public function __construct($data) {
			$this->data = $data;
			$this->filter();
		}
		
		public static function className($name) {
			return 'Frawst\Form\\'.str_replace('/', '\\', $name);
		}
		
		public static function factory($name = null, $data = array(), $checkToken = true) {
			$c = $name === null
				? get_called_class()
				: self::className($name);
			
			if($checkToken) {
				if(!isset($data[$c::tokenName()]) || !$c::checkToken($data[$c::tokenName()])) {
					return null;
				}
				unset($data[$c::tokenName()]);
			}
			return new $c($data);
		}
		
		public function get($offset = null) {
			return Matrix::pathExists($this->data, $offset)
				? Matrix::pathGet($this->data, $offset)
				: null;
		}
		public function set($offset, $value) {
			Matrix::pathSet($this->data, $offset, $value);
		}
		
		public function offsetExists($offset) {
			return $this->get($offset) !== null;
		}
		public function offsetGet($offset) {
			return $this->get($offset);
		}
		public function offsetSet($offset, $value) {
			$this->set($offset, $value);
		}
		public function offsetUnset($offset) {
			$this->set($offset, null);
		}
		
		/**
		 * Sets the form errors. Will clear any existing errors.
		 * @param array $errors
		 */
		public function setErrors($errors) {
			$this->errors = $errors;
		}
		
		/**
		 * Adds errors to a field.
		 * @param string $field
		 * @param array $errors
		 */
		public function addErrors($field, $errors = array()) {
			if(is_array($field)) {
				foreach($field as $f => $e) {
					$this->addErrors($f, $e);
				}
			} elseif(count($errors)) {
				if (!Matrix::pathExists($this->errors, $field)) {
					Matrix::pathSet($this->errors, $field, $errors);
				} else {
					Matrix::pathMerge($this->errors, $field, $errors);
				}
			}
		}
		
		public function addError($field, $error) {
			$this->addErrors($field, array($error));
		}
		
		/**
		 * Returns an array of errors for the specified field. If no field is specified,
		 * returns all errors for this form.
		 * @param string $field
		 */
		public function errors($field = null) {
			return Matrix::pathExists($this->errors, $field)
				? Matrix::pathGet($this->errors, $field)
				: array();
		}
		
		/**
		 * Validates the form based on validation rules set it in $validate. May be
		 * overridden in extending classes to customize validation. Errors are stored
		 * by field name in $errors
		 * @return bool True if no errors were found, false otherwise
		 */
		public function validate() {
			$this->errors = array();
			foreach (static::$validate as $field => $rules) {
				if (count($errors = Validator::check($this[$field], $rules, $this))) {
					Matrix::pathSet($this->errors, $field, $errors);
				}
			}
			return count($this->errors) == 0;
		}
		
		/**
		 * Called when the form is constructed. Can be used to filter properties
		 * (for example, format a phone number).
		 */
		protected function filter() {
			
		}
		
		/**
		 * Checks the specified field for validation errors. If no field is given, checks
		 * the whole form for validation errors.
		 * @param string $field A field name, or null to check the whole form
		 * @return bool True if errors exist
		 */
		public function valid($field = null) {
			return Matrix::pathExists($this->errors, $field)
				? (bool) (count(Matrix::pathGet($this->errors, $field)) == 0)
				: true;
		}
		
		public static function tokenName() {
			return '___TOKEN';
		}
		
		/**
		 * Verifies a given token to determine if it is valid for this session and form. 
		 * @param string $token
		 * @return bool True if the token is valid, false otherwise.
		 */
		public static function checkToken($token) {
			return Security::checkToken($token, get_called_class());
		}
		
		/**
		 * Storage-less token creation. Hashes the current microtime with the SESSION id
		 * and returns the hash concatenated to the microtime. When the token is checked, the
		 * microtime is re-hashed with the SESSION id, and if it matches the given hash,
		 * the token is valid.
		 * @return string The form token
		 */
		public static function makeToken() {
			return Security::makeToken(get_called_class());
		}
	}