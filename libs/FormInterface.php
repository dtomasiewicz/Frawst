<?php
	namespace Frawst;
	
	interface FormInterface {
		public function __construct($data);
		
		public function submitted();
		
		public function get($offset);
		
		public function exists($offset);
		
		public function setErrors($errors);
		
		public function addErrors($field, $errors);
		
		public function addError($field, $error);
		
		public function errors($field);
		
		public function validate();
		
		public function valid($field);
		
		public static function name();
		
		public static function method();
		
		public static function compatible($data, $allowExtraFields);
		
		public static function load($data, $allowExtraFields);
		
		public static function checkToken($token);
		
		public static function makeToken();
	}