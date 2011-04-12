<?php
	/**
	 * This code is no longer in use.
	 */
	namespace Frawst\Core;
	
	class Base {
		private $impls = array();
		
		private static $classImpls = array(
			'Frawst\Base' => array(
				'Frawst\RequestInterface' => 'Frawst\Request',
				'Frawst\ResponseInterface' => 'Frawst\Response',
				'Frawst\FormInterface' => 'Frawst\Form',
				'Frawst\ViewInterface' => 'Frawst\View',
				'Frawst\RouteInterface' => 'Frawst\Route',
				'Frawst\ControllerInterface' => 'Frawst\Controller',
				'Frawst\ComponentInterface' => 'Frawst\Component',
				'Frawst\HelperInterface' => 'Frawst\Helper',
				'configRead' => array('Frawst\Config', 'read')
			)
		);
		
		public static function setClassImplementation($interface, $implementation) {
			$class = get_called_class();
			if(!array_key_exists($class, self::$classImpls)) {
				self::$classImpls[$class] = array();
			}
			self::$classImpls[$class][$interface] = $implementation;
		}
		
		public function setImplementation($interface, $implementation) {
			$this->impls[$interface] = $implementation;
		}
		
		public static function getClassImplementation($interface) {
			$class = get_called_class();
			while($class !== false) {
				if(array_key_exists($class, self::$classImpls) && array_key_exists($interface, self::$classImpls[$class])) {
					return self::$classImpls[$class][$interface];
				} else {
					$class = get_parent_class($class);
				}
			}
		}
		
		public function getImplementation($interface) {
			return array_key_exists($interface, $this->impls)
				? $this->impls[$interface]
				: static::getClassImplementation($interface);
		}
		
		public static function callClassImplementation($impl) {
			$args = func_get_args();
			array_shift($args);
			return call_user_func_array(static::getClassImplementation($impl), $args);
		}
		
		public function callImplementation($impl) {
			$args = func_get_args();
			array_shift($args);
			return call_user_func_array($this->getImplementation($impl), $args);
		}
	}