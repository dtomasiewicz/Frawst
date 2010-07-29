<?php
	namespace Frawst\Library;
	use \Frawst\Exception;
	
	abstract class Validator {
		const BLANK = '/^\s*$/';
		const VALID_INTEGER = '/^-?\d+$/';
		
		/**
		 * Validates an object, returning an array of errors
		 */
		public static function checkObject($object, $rules) {
			$errors = array();
			
			foreach($rules as $field => $fieldRules) {
				$errors[$field] = static::check($object->$field, $fieldRules, $object);
				
				if(count($errors[$field]) == 0) {
					unset($errors[$field]);
				}
			}
			
			return $errors;
		}
		
		/**
		 * Checks a value, returns an array of errors
		 * @param mixed $value
		 * @param array $rules
		 * @param object $object An object containing rule callbacks
		 */
		public static function check($value, $rules, $object = null) {
			$errors = array();
				
			if(!is_array($rules) || isset($rules['rule'])) {
				// it it's a single rule, make it an array of rules
				$rules = array($rules);
			}
			
			/**
			 * Rules will not be checked if the value is blank and not required
			 */
			if(preg_match(static::BLANK, $value) && !(in_array('required', $rules) || array_key_exists('required', $rules))) {
				return $errors;
			}
			
			foreach($rules as $rule => $params) {
				// parse rule into common format: (string) $rule => (array) $params
				if(!is_array($params)) {
					if(is_string($rule)) {
						$params = array($rule => $params);
					} else {
						$rule = $params;
						$params = array();
					}
				} elseif(isset($params['rule'])) {
					$rule = $params['rule'];
				}
				
				// test the rule
				$rule = 'valid'.ucfirst($rule);
				if(method_exists(get_called_class(), $rule)) {
					if(is_string($error = static::$rule($value, $params))) {
						$errors[] = $error;
					}
				} elseif(!is_null($object) && method_exists($object, $rule)) {
					if(is_string($error = $object->$rule($value, $params))) {
						$errors[] = $error;
					}
				} else {
					throw new Exception\Frawst('Could not find a callback for unknown validation rule: '.$rule);
				}
			}
			
			return $errors;
		}
		
		public static function validInteger($value, $params = array()) {
			$message = isset($params['message']) ? $params['message'] : 'Must be a valid integer.';
			return preg_match(self::VALID_INTEGER, $value) ? true : $message;
		}
		public static function validRequired($value, $params = array()) {
			$message = isset($params['message']) ? $params['message'] : 'Required.';
			return preg_match(self::BLANK, $value) ? $message : true;
		}
		public static function validMaxLength($value, $params = array()) {
			$message = isset($params['message']) ? $params['message'] : 'Maximum length of '.$params['maxLength'].' characters.';
			return strlen($value) <= $params['maxLength'] ? true : $message;
		}
		public static function validMinLength($value, $params = array()) {
			$message = isset($params['message']) ? $params['message'] : 'Minimum length of '.$params['minLength'].' characters.';
			return strlen($value) >= $params['minLength'] ? true : $message;
		}
	}