<?php
	namespace Corelativ;
	
	abstract class Validator {
		const BLANK = '/^\s*$/';
		const VALID_INTEGER = '/^-?\d+$/';
		
		/**
		 * Performs technical validation on an object. Returns an array of errors.
		 */
		public static function check($object, $rules) {
			$errors = array();
			
			foreach($rules as $field => $fieldRules) {
				$errors[$field] = array();
				
				if(!is_array($fieldRules) || isset($fieldRules['rule'])) {
					// it it's a single rule, make it an array of rules
					$fieldRules = array($fieldRules);
				}
				
				// don't validate if it's required or not blank
				if(!preg_match(self::BLANK, $object->$field) || in_array('required', $fieldRules) || array_key_exists('required', $fieldRules)) {
					foreach($fieldRules as $rule => $params) {
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
						if(method_exists(__CLASS__, $rule)) {
							if(is_string($error = call_user_func(array(__CLASS__, $rule), $object->$field, $params))) {
								$errors[$field][] = $error;
							}
						} elseif(method_exists($object, $rule)) {
							if(is_string($error = $object->$rule($object->$field, $params))) {
								$errors[$field][] = $error;
							}
						} else {
							//@todo exception
							exit('Could not find a valid callback for the validation rule: '.$rule);
						}
					}
				}
				
				if(count($errors[$field]) == 0) {
					unset($errors[$field]);
				}
			}
			
			return (count($errors) == 0) ? true : $errors;
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