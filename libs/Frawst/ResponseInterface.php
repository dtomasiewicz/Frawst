<?php
	namespace Frawst;
	
	interface ResponseInterface {
		public function __construct(RequestInterface $request);
		public function data($data);
		public function headers();
		public function header($name, $value);
		public function status($status);
		public function render();
		public function send();
		public function isOk();
	}