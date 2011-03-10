<?php
	namespace Frawst\Test;
	use \Frawst\Request;
	
	require_once 'PHPUnit/Framework.php';
	require_once '../bootstrap.php';
	require_once TEST_ROOT.'stubs/RouteStub.php';
	require_once TEST_ROOT.'stubs/ResponseStub.php';
	
	class RequestTest extends \PHPUnit_Framework_TestCase {
		
		public function setUp() {
			Request::setDefaultImplementation('Frawst\ResponseInterface', 'Frawst\Test\ResponseStub');
			Request::setDefaultImplementation('Frawst\RouteInterface', 'Frawst\Test\RouteStub');
			Request::setDefaultImplementation('ns:Frawst\Controller', 'Frawst\Test');
		}
		
		public function testIsAjax() {
			$route = new RouteStub();
			
			// case should not matter
			$headers = array('X-Requested-With' => 'xmlhttprequest');
			$request = new Request($route, array(), Request::METHOD_GET, $headers);
			$this->assertTrue(true, $request->isAjax());
			
			$headers = array('X-Requested-With' => 'XmlHttpRequest');
			$request = new Request($route, array(), Request::METHOD_GET, $headers);
			$this->assertTrue($request->isAjax());
			
			// ignore any other value
			$headers = array('X-Requested-With' => 'SomethingElse');
			$request = new Request($route, array(), Request::METHOD_GET, $headers);
			$this->assertFalse(false, $request->isAjax());
		}
		
		public function testSubRequest() {
			$route = new RouteStub();
			
			$headers = array('X-Test-Header' => 'Test');
			$request = new Request($route, array('test-data' => 'test'), Request::METHOD_GET, $headers);
			$sub = $request->subRequest($route);
			
			// headers should be passed on to sub-requests
			$this->assertEquals('Test', $sub->header('X-Test-Header'));
			
			// request data should NOT be passed to sub-requests
			$this->assertEquals(null, $sub->data('test-data'));
			
			// sub-requests should be in AJAX mode
			$this->assertTrue($sub->isAjax());
		}
		
	}