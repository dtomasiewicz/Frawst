<?php
	namespace Frawst\Test;
	use Frawst\ViewInterface,
	    Frawst\ResponseInterface;
	
	require_once 'Stub.php';
	
	class ViewStub extends Stub implements ViewInterface {
		public function render($data) {return $this->getSeed('render', func_get_args());}
		public function partial($partial, $data) {return $this->getSeed('partial', func_get_args());}
		public function isAjax() {return $this->getSeed('isAjax', func_get_args());}
		public function path($route) {return $this->getSeed('path', func_get_args());}
		public function webroot($resource) {return $this->getSeed('webroot', func_get_args());}
		public function ajax($route, $data, $method) {return $this->getSeed('ajax', func_get_args());}
		public function helper($name) {return $this->getSeed('helper', func_get_args());}
		public function layout() {return $this->getSeed('layout', func_get_args());}
		public function setLayout($layout) {return $this->getSeed('setLayout', func_get_args());}
		public static function factory(ResponseInterface $response) {return self::getClassSeed('factory', func_get_args());}
		public static function exists($content) {return self::getClassSeed('contentExists', func_get_args());}
	}