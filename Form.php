<?php
	namespace Frawst;
	use \Frawst\Library\Matrix,
	    \Frawst\Library\Security,
	    \Frawst\Library\Session,
	    \Frawst\Library\Validator,
	    \Frawst\Exception;
	
	/**
	 * Base Frawst Form class
	 * 
	 * This class can be extended to rigidly define properties of your application's
	 * FORMs. This allows separation of form validation from your controller logic, and
	 * prevents duplication of form processing if you use the same form in multiple areas
	 * of your site. It also helps protect against XSS by ensuring no additional fields
	 * are passed in (as long as you check for compatibility before using the form) and all
	 * required fields are present.
	 * 
	 * This class is NOT used to output form markup! For that, you can use the Form helper. Simply
	 * pass the name of your Form class in the open() method, and the fields will be (re)populated
	 * automatically.
	 */
	abstract class Form extends Matrix {
		protected static $_method = 'POST';
		
		/**
		 * An array of key/value pairs where the keys are all fields that may
		 * be submitted with this form, and the values are their default values.
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
		protected static $_requiredPresent = array();
		
		/**
		 * Default data for repopulating form fields. Keys should be in expanded format.
		 * @vara array
		 */
		protected $_defaults;
		
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
		
		public function __construct($data = array()) {
			parent::__construct($data);
			$this->_defaults = static::$_fields;
		}
		
		public function setDefaults($defaults) {
			$this->_defaults = $defaults+$this->_defaults;
		}
		
		public function setErrors($errors) {
			$this->_errors = $errors;
		}
		
		public function addErrors($field, $errors) {
			if (count($errors)) {
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
		
		public function errors($field = null) {
			return Matrix::pathExists($this->_errors, $field)
				? Matrix::pathGet($this->_errors, $field)
				: array();
		}
				
		/**
		 * Returns the form name
		 */
		public static function name() {
			$class = explode('\\', get_called_class());
			return end($class);
		}
		
		public static function method() {
			return strtoupper(static::$_method);
		}
		
		/**
		 * Determines whether or not the given data is compatible with this form.
		 * @param array $data
		 * @return bool
		 */
		public static function compatible($data, $allowExtraFields = false) {
			if(!$allowExtraFields) {
				foreach (Matrix::flatten($data) as $field => $value) {
					if (!Matrix::pathExists(static::$_fields, $field)) {
						return false;
					}
				}
			}
			
			$requiredPresent = static::$_requiredPresent === true
				? array_keys(static::$_fields)
				: static::$_requiredPresent;
			
			foreach($requiredPresent as $field) {
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
		public static function load($data) {
			if(static::$_useToken) {
				if(!isset($data[static::TOKEN_NAME]) || !static::checkToken($data[static::TOKEN_NAME])) {
					return null;
				} else {
					unset($data[static::TOKEN_NAME]);
				}
			}
			
			if(static::compatible($data)) {
				$c = get_called_class();
				return new $c($data);
			} else {
				return null;
			}
		}
		
		public static function checkToken($token) {
			$parts = explode('-', $token);
			if(count($parts) != 2) {
				return false;
			} else {
				return (bool) (Security::hash(Session::id().$parts[1]) == $parts[0]);
			}
		}
		
		/**
		 * Storage-less token creation. Hashes the current microtime with the SESSION id
		 * and returns the hash concatenated to the microtime. When the token is checked, the
		 * microtime is re-hashed with the SESSION id, and if it matches the given hash,
		 * the token is valid.
		 * @return string The form token
		 */
		public static function makeToken() {
			if(static::$_useToken) {
				$time = microtime(true);
				return Security::hash(Session::id().$time).'-'.$time;
			} else {
				return null;
			}
		}
		
		/**
		 * If a value does not exist in the form data, use the default
		 * value instead.
		 */
		public function get($field = null) {
			return parent::exists($field)
				? parent::get($field)
				: Matrix::pathGet($this->_defaults, $field);
		}
		
		public function validate() {
			$this->_errors = array();
			foreach (static::$_validate as $field => $rules) {
				if (count($errors = Validator::check($this[$field], $rules, $this))) {
					Matrix::pathSet($this->_errors, $field, $errors);
				}
			}
			return count($this->_errors) == 0;
		}
		
		public function valid($field = null) {
			return Matrix::pathExists($this->_errors, field)
				? (bool) (count(Matrix::pathGet($this->_errors, $field)) == 0)
				: true;
		}
	}