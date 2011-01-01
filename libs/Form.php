<?php
	namespace Frawst;
	use \Frawst\Library\Matrix,
	    \Frawst\Library\Security,
	    \Frawst\Library\Session,
	    \Frawst\Library\Validator;
	
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
	 * 
	 * This class is NOT used to output form markup! For that, you can use the Form helper. Simply
	 * pass the name of your Form class in the open() method, and the fields will be (re)populated
	 * automatically.
	 */
	abstract class Form extends Matrix {
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
		
		/**
		 * Instantiates the form object with the given data. If the data contains fields
		 * not specified in $_fields, they are ignored.
		 * @param array $data
		 */
		public function __construct($data = array()) {
			$this->_defaults = static::$_fields;
			parent::__construct($data);
		}
		
		/**
		 * Sets default values for this form. These are the values that should be
		 * used as defaults if no data already exists from an unsuccessful submission.
		 * Keys should be in dot-path format.
		 * @param array $defaults
		 */
		public function populate($defaults) {
			foreach($defaults as $key => $value) {
				Matrix::pathSet($this->_defaults, $key, $value);
			}
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
		 * If a value does not exist in the form data, use the default
		 * value instead.
		 */
		public function get($field = null) {
			if(parent::exists($field)) {
				return parent::get($field);
			} else {
				return Matrix::pathExists($this->_defaults, $field)
					? Matrix::pathGet($this->_defaults, $field)
					: null;
			}
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
			$requiredPresent = static::$_requiredPresent === true
				? array_keys(Matrix::flatten(static::$_fields))
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