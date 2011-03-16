<?php
	namespace Frawst\Test;
	use Frawst\ControllerInterface,
	    Frawst\RequestInterface,
	    Frawst\ResponseInterface;
	
	require_once 'Stub.php';
	
	class ControllerStub extends Stub implements ControllerInterface {
		public function request() {return $this->getSeed('request', func_get_args());}
		public function response() {return $this->getSeed('response', func_get_args());}
		public function execute() {return $this->getSeed('execute', func_get_args());}
		public function component($name) {return $this->getSeed('component', func_get_args());}
		public static function factory($name, ResponseInterface $response) {return self::getClassSeed('factory', func_get_args());}
		public static function exists($name) {return self::getClassSeed('controllerExists', func_get_args());}
		public static function isAbstract($name) {return self::getClassSeed('controllerIsAbstract', func_get_args());} 
	}