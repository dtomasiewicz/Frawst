<?php
	namespace Frawst\Test;
	use Frawst\ControllerInterface,
	    Frawst\RequestInterface,
	    Frawst\ResponseInterface;
	
	require_once 'Stub.php';
	
	class ControllerStub extends Stub implements ControllerInterface {
		public function __construct(RequestInterface $request, ResponseInterface $response) {}
		public function request() {return $this->getSeed('request', func_get_args());}
		public function response() {return $this->getSeed('response', func_get_args());}
		public function execute() {return $this->getSeed('execute', func_get_args());}
		public function component($name) {return $this->getSeed('component', func_get_args());}
		public static function controllerExists($controller) {return self::getClassSeed('controllerExists', func_get_args());}
		public static function controllerIsAbstract($controller) {return self::getClassSeed('controllerIsAbstract', func_get_args());}
		public static function controllerClass($controller) {return self::getClassSeed('controllerClass', func_get_args());} 
	}