<?php
	namespace Frawst\Test;
	use \Frawst\Route;
	
	require_once 'PHPUnit/Framework.php';
	require_once '../bootstrap.php';
	require_once TEST_ROOT.'stubs/ViewStub.php';
	require_once TEST_ROOT.'stubs/ControllerStub.php';
	
	class RouteTest extends \PHPUnit_Framework_TestCase {
		
		/**
		 * Seeds return values that simulate the controller setup:
		 *   Index
		 *   User (a)
		 *     View
		 * and the view content setup:
		 *   some/
		 *     static/
		 *       page
		 *   user/
		 *     view
		 */
		public function setUp() {
			Route::setClassImplementation('Frawst\ViewInterface', 'Frawst\Test\ViewStub');
			Route::setClassImplementation('Frawst\ControllerInterface', 'Frawst\Test\ControllerStub');
			
			ViewStub::seedClassReturn('contentExists', false);
			ViewStub::seedClassReturns('contentExists', true, array(
				array('some/static/page'),
				array('user/index'),
				array('user/view')
			));
			
			ControllerStub::seedClassReturn('controllerExists', false);
			ControllerStub::seedClassReturns('controllerExists', true, array(
				array('Index'),
				array('User'),
				array('User/Index'),
				array('User/View')
			));
			ControllerStub::seedClassReturn('controllerIsAbstract', false);
			ControllerStub::seedClassReturns('controllerIsAbstract', true, array(
				array('Index'),
				array('User')
			));
		}
		
		public function testResolve() {
			// controller-less (static page)
			$route = Route::resolve('some/static/page');
			$this->assertEquals(null, $route->controller());
			$this->assertEquals('some/static/page', $route->template());
			$this->assertEquals(array(), $route->param());
			
			// controller and view both exist
			$route = Route::resolve('user/view');
			$this->assertEquals('User/View', $route->controller());
			$this->assertEquals('user/view', $route->template());
			$this->assertEquals(array(), $route->param());
			
			// case-insensitivity, parameters
			$route = Route::resolve('uSeR/VieW/5/6/7');
			$this->assertEquals('User/View', $route->controller());
			$this->assertEquals('user/view', $route->template());
			$this->assertEquals(array('5', '6', '7'), $route->param());
			
			// string parameter
			$route = Route::resolve('user/edit/5');
			$this->assertEquals('User/Index', $route->controller());
			$this->assertEquals('user/index', $route->template());
			$this->assertEquals(array('edit', '5'), $route->param());
			
			// empty route, abstract controller with no index
			$route = Route::resolve('');
			$route2 = Route::resolve('index');
			$this->assertEquals(null, $route->controller());
			$this->assertEquals(null, $route->template());
			$this->assertEquals(array(), $route->param());
			$this->assertEquals(null, $route2->controller());
			$this->assertEquals(null, $route2->template());
			$this->assertEquals(array('index'), $route2->param());
		}
		
	}