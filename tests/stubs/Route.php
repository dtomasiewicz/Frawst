<?php
	namespace Frawst;
	
	require_once 'Stub.php';

	class Route extends Stub implements RouteInterface {
		public function __construct($route = null) {}
		public function controller() {return $this->_getSeed('controller', func_get_args());}
		public function param($key = null) {return $this->_getSeed('param', func_get_args());}
		public function option($name = null) {return $this->_getSeed('option', func_get_args());}
		public function original() {return $this->_getSeed('original', func_get_args());}
		public function resolved() {return $this->_getSeed('resolved', func_get_args());}
		public static function getPath($route = null) {return parent::_getSeedStatic('getPath', func_get_args());}
		public function path() {return $this->_getSeed('path', func_get_args());}
	}