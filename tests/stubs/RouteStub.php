<?php
	namespace Frawst\Test;
	use Frawst\RouteInterface;
	
	require_once 'Stub.php';

	class RouteStub extends Stub implements RouteInterface {
		public function __construct($route = null) {}
		public function param($key = null) {return $this->getSeed('param', func_get_args());}
		public function option($name = null) {return $this->getSeed('option', func_get_args());}
		public function original() {return $this->getSeed('original', func_get_args());}
		public function resolved() {return $this->getSeed('resolved', func_get_args());}
		public static function getPath($route = null) {return static::getClassStatic('getPath', func_get_args());}
		public function path() {return $this->getSeed('path', func_get_args());}
		public function controller() {return $this->getSeed('controller', func_get_args());}
		public function template() {return $this->getSeed('template', func_get_args());}
	}