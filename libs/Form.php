<?php
	namespace Frawst;
	
	/**
	 * Base Frawst Form class
	 * 
	 * This class can be extended to rigidly define properties of your application's
	 * FORMs. This allows separation of form validation from your controller logic, and
	 * prevents duplication of form processing if you use the same form in multiple areas
	 * of your site. It also helps protect against XSS by ensuring no additional fields
	 * are passed in (as long as you check for compatibility before using the form) and all
	 * required fields are present. Anti-CSRF form tokens are also implemented and will be
	 * injected by default for all forms as long as you are using the FORM helper.
	 * abstract 
	 * This class is NOT used to output form markup! For that, you can use the Form helper. Simply
	 * pass the name of your Form class in the open() method, and the fields will be (re)populated
	 * automatically.
	 */
	class Form implements \ArrayAccess {
		protected $_data;
		protected $_submitted;
		
		protected static $_method = 'POST';
		
		/**
		 * An associative array of fields for this form.
		 * @var array
		 */
		protected static $_fields = array();
		
		/**
		 * An array of key/value pairs where the keys are form fields in dot-path form
		 * and the values are validation rules.
		 * @var array
		 */
		protected static $_validate = array();
		
		/**
		 * A list of fields that MUST exist for a set of data to be compatible
		 * with this form. If set to true, ALL fields are required to be present.
		 * Field names are in dot-path format.
		 * @var mixed
		 */
		protected static $_required = array();
		
		/**
		 * Whether or not to require a token for this form. If the form is vulnerable
		 * to CSRF, this should be set to true.
		 * @var bool
		 */
		protected static $_useToken = true;
		const TOKEN_NAME = '__TOKEN';
		
		/**
		 * An associative array of errors for this form, where the keys are field names
		 * in dot-path format and the values are arrays of error messages.
		 * @var array
		 */
		protected $_errors = array();
		
		/**
		 * Instantiates the form object with the given data. If the data contains fields
		 * not specified in $_fields, they are ignored.
		 * @param array $data
		 */
		public function __construct($data = null) {
			if($data === null) {
				$this->_submitted = false;
				$this->_data = array();
			} else {
				$this->_submitted = true;
				$this->_data = $data;
			}
		}
		
		public function submitted() {
			return $this->_submitted;
		}
		
		protected static function _generalKey($key) {
			$gKey = array();
			foreach(explode('.', $key) as $v) {
				if(is_numeric($v)) {
					$gKey[] = '*';
				} else {
					$gKey[] = $v;
				}
			}
			return implode('.', $gKey);
		}
		
		protected function _defaultValue($field) {
			if(array_key_exists($field, static::$_fields)) {
				return static::$_fields[$field];
			} else{
				$gKey = self::_generalKey($field);
				if(array_key_exists($gKey, static::$_fields)) {
					return static::$_fields[$gKey];
				}
			}
			
			return null;
		}
		
		public function get($offset) {
			return Matrix::pathExists($this->_data, $offset)
				? Matrix::pathGet($this->_data, $offset)
				: $this->_defaultValue($offset);
		}
		
		public function exists($offset) {
			return Matrix::pathExists($this->_data, $offset) || $this->_defaultValue($offset) !== null;
		}
		
		public function offsetExists($offset) {
			return $this->exists($offset);
		}
		public function offsetGet($offset) {
			return $this->get($offset);
		}
		public function offsetSet($offset, $value) {
			
		}
		public function offsetUnset($offset) {
			
		}
		
		/**
		 * Sets the form errors. Will clear any existing errors.
		 * @param array $errors
		 */
		public function setErrors($errors) {
			$this->_errors = $errors;
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
				if (!Matrix::pathExists($this->_errors, $field)) {
					Matrix::pathSet($this->_errors, $field, $errors);
				} else {
					Matrix::pathMerge($this->_errors, $field, $errors);
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
			return Matrix::pathExists($this->_errors, $field)
				? Matrix::pathGet($this->_errors, $field)
				: array();
		}
		
		/**
		 * Validates the form based on validation rules set it in $_validate. May be
		 * overridden in extending classes to customize validation. Errors are stored
		 * by field name in $_errors
		 * @return bool True if no errors were found, false otherwise
		 */
		public function validate() {
			$this->_errors = array();
			foreach (static::$_validate as $field => $rules) {
				if (count($errors = Validator::check($this[$field], $rules, $this))) {
					Matrix::pathSet($this->_errors, $field, $errors);
				}
			}
			return count($this->_errors) == 0;
		}
		
		/**
		 * Checks the specified field for validation errors. If no field is given, checks
		 * the whole form for validation errors.
		 * @param string $field A field name, or null to check the whole form
		 * @return bool True if errors exist
		 */
		public function valid($field = null) {
			return Matrix::pathExists($this->_errors, field)
				? (bool) (count(Matrix::pathGet($this->_errors, $field)) == 0)
				: true;
		}
		
		public static function name() {
			$class = explode('\\', get_called_class());
			return end($class);
		}
		
		public static function method() {
			return strtoupper(static::$_method);
		}
		
		/**
		 * Determines whether or not the given data is compatible with this form. May
		 * be extended to customize behaviour.
		 * @param array $data
		 * @return bool
		 */
		public static function compatible($data, $allowExtraFields = false) {
			if(!$allowExtraFields) {
				foreach(Matrix::flatten($data) as $key => $value) {
					if(!array_key_exists($key, static::$_fields)) {
						if(!array_key_exists(self::_generalKey($key), static::$_fields)) {
							return false;
						}
					}
				}
			}
			
			$required = static::$_required === true
				? array_keys(static::$_fields)
				: static::$_required;
			
			foreach($required as $field) {
				if(!Matrix::pathExists($data, $field)) {
					return false;
				}
			}
			
			return true;
		}
		
		/**
		 * Attempts to load an instance of the form with the given data.
		 * @param array $data The form data
		 * @return Frawst\Form A form object if the data is valid, otherwise null.
		 */
		public static function load($data, $allowExtraFields = false) {
			if(static::$_useToken) {
				if(!isset($data[static::TOKEN_NAME]) || !static::checkToken($data[static::TOKEN_NAME])) {
					return null;
				} else {
					unset($data[static::TOKEN_NAME]);
				}
			}
			
			if(static::compatible($data, $allowExtraFields)) {
				$c = get_called_class();
				return new $c($data);
			} else {
				return null;
			}
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
			return static::$_useToken
				? Security::makeToken(get_called_class())
				: null;
		}
	}