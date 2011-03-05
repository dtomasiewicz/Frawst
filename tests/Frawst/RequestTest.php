<?php
	namespace Frawst;
	
	require_once 'PHPUnit/Framework.php';
	require_once '../bootstrap.php';
	require_once TEST_ROOT.'stubs/Route.php';
	
	class RequestTest extends \PHPUnit_Framework_TestCase {
		public function testIsAjax() {
			$route = new Route();
			
			$headers = array('X-Requested-With' => 'xmlhttprequest');
			$request = new Request($route, array(), Request::METHOD_GET, $headers);
			$this->assertEquals(true, $request->isAjax());
			
			$headers = array('X-Requested-With' => 'XmlHttpRequest');
			$request = new Request($route, array(), Request::METHOD_GET, $headers);
			$this->assertEquals(true, $request->isAjax());
			
			$headers = array('X-Requested-With' => 'SomethingElse');
			$request = new Request($route, array(), Request::METHOD_GET, $headers);
			$this->assertEquals(false, $request->isAjax());
			
			$request = new Request($route, array(), Request::METHOD_GET, $headers);
			$this->assertEquals(false, $request->isAjax());
		}
	}