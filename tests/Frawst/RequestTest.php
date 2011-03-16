<?php
	namespace Frawst\Test;
	use \Frawst\Request;
	
	require_once 'PHPUnit/Framework.php';
	require_once '../bootstrap.php';
	require_once TEST_ROOT.'stubs/RouteStub.php';
	require_once TEST_ROOT.'stubs/ResponseStub.php';
	
	class RequestTest extends \PHPUnit_Framework_TestCase {
		
		public function setUp() {
			Request::setClassImplementation('Frawst\ResponseInterface', 'Frawst\Test\ResponseStub');
			Request::setClassImplementation('Frawst\RouteInterface', 'Frawst\Test\RouteStub');
			RouteStub::seedClassReturn('resolve', new RouteStub());
		}
		
		public function testIsAjax() {
			// case should not matter
			$headers = array('X-Requested-With' => 'xmlhttprequest');
			$request = new Request(new RouteStub(), array(), Request::METHOD_GET, $headers, array());
			$this->assertTrue(true, $request->isAjax());
			
			$headers = array('X-Requested-With' => 'XmlHttpRequest');
			$request = new Request(new RouteStub(), array(), Request::METHOD_GET, $headers, array());
			$this->assertTrue($request->isAjax());
			
			// ignore any other value
			$headers = array('X-Requested-With' => 'SomethingElse');
			$request = new Request(new RouteStub(), array(), Request::METHOD_GET, $headers, array());
			$this->assertFalse(false, $request->isAjax());
		}
		
		public function testSubRequest() {
			$headers = array('X-Test-Header' => 'Test');
			$request = new Request(new RouteStub(), array('test-data' => 'test'), Request::METHOD_GET, $headers, array());
			$sub = $request->subRequest('');
			
			// headers should be passed on to sub-requests
			$this->assertEquals('Test', $sub->header('X-Test-Header'));
			
			// request data should NOT be passed to sub-requests
			$this->assertEquals(null, $sub->data('test-data'));
			
			// sub-requests should be in AJAX mode
			$this->assertTrue($sub->isAjax());
		}
		
	}