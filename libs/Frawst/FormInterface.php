<?php
	namespace Frawst;
	
	interface FormInterface {
		public function get($offset);
		public function set($offset, $value);
		public function setErrors($errors);
		public function addErrors($field, $errors);
		public function addError($field, $error);
		public function errors($field);
		public function validate();
		public function valid($field);
		public static function tokenName();
		public static function checkToken($token);
		public static function makeToken();
		public static function factory($name, $data);
		public static function className($name);
	}