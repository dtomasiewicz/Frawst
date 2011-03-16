<?php
	namespace Frawst;
	
	interface ResponseInterface {
		public function data($data);
		public function header($name, $value);
		public function status($status);
		public function render();
		public function send();
		public function isOk();
		public static function factory(RequestInterface $request);
	}