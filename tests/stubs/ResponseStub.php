<?php
	namespace Frawst\Test;
	use Frawst\ResponseInterface,
	    Frawst\RequestInterface;
	
	require_once 'Stub.php';

	class ResponseStub extends Stub implements ResponseInterface {
		public function __construct(RequestInterface $request) {}
		public function data($data) {return $this->getSeed('data', func_get_args());}
		public function headers() {return $this->getSeed('headers', func_get_args());}
		public function header($name, $value) {return $this->getSeed('header', func_get_args());}
		public function status($status) {return $this->getSeed('status', func_get_args());}
		public function render() {return $this->getSeed('render', func_get_args());}
		public function send() {return $this->getSeed('send', func_get_args());}
		public function isOk() {return $this->getSeed('isOk', func_get_args());}
	}