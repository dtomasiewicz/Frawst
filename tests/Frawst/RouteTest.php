<?php
	namespace Frawst\Test;
	use \Frawst\Route;
	
	require_once 'PHPUnit/Framework.php';
	require_once '../bootstrap.php';
	
	Framework::setupMock('Frawst\ViewInterface', 'ViewMock');
	Framework::setupMock('Frawst\ControllerInterface', 'ControllerMock');
	
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
			ViewMock::seedClassReturn('exists', false);
			ViewMock::seedClassReturns('exists', true, array(
				array('some/static/page'),
				array('user/index'),
				array('user/view')
			));
			
			ControllerMock::seedClassReturn('exists', false);
			ControllerMock::seedClassReturns('exists', true, array(
				array('Index'),
				array('User'),
				array('User/Index'),
				array('User/View')
			));
			ControllerMock::seedClassReturn('isAbstract', false);
			ControllerMock::seedClassReturns('isAbstract', true, array(
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
			
			// empty route
			$route = Route::resolve('');
			$this->assertEquals(null, $route->controller());
			$this->assertEquals(null, $route->template());
			$this->assertEquals(array(), $route->param());
			
			// route with one empty parameter
			$route = Route::resolve('/');
			$this->assertEquals(null, $route->controller());
			$this->assertEquals(null, $route->template());
			$this->assertEquals(array(''), $route->param());
			
			// non-existent controller/view
			$route = Route::resolve('index');
			$this->assertEquals(null, $route->controller());
			$this->assertEquals(null, $route->template());
			$this->assertEquals(array('index'), $route->param());
		}
		
	}