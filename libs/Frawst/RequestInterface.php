<?php
	namespace Frawst;
	
	interface RequestInterface {
		public function header($name);
		public function isAjax();
		public function subRequest($route, $data, $method);
		public function execute();
		public function method();
		public function param($index);
		public function option($name);
		public function data($key);
		public static function factory($route, array $data, $method, array $headers, array $persist);
	}