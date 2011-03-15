<?php
	namespace Frawst;
	
	class Base {
		private $impls = array();
		
		private static $defaultImpls = array(
			'Frawst\Base' => array(
				'Frawst\RequestInterface' => 'Frawst\Request',
				'Frawst\ResponseInterface' => 'Frawst\Response',
				'Frawst\FormInterface' => 'Frawst\Form',
				'Frawst\ViewInterface' => 'Frawst\View',
				'Frawst\RouteInterface' => 'Frawst\Route',
				'Frawst\ControllerInterface' => 'Frawst\Controller',
				'Frawst\ComponentInterface' => 'Frawst\Component',
				'Frawst\HelperInterface' => 'Frawst\Helper',
				'ns:Frawst\ControllerInterface' => 'Frawst\Controller',
				'ns:Frawst\ComponentInterface' => 'Frawst\Component',
				'ns:Frawst\HelperInterface' => 'Frawst\Helper',
				'ns:Frawst\FormInterface' => 'Frawst\Form',
				'configRead' => array('Frawst\Config', 'read')
			)
		);
		
		public static function setDefaultImplementation($interface, $implementation) {
			$class = get_called_class();
			if(!array_key_exists($class, self::$defaultImpls)) {
				self::$defaultImpls[$class] = array();
			}
			self::$defaultImpls[$class][$interface] = $implementation;
		}
		
		public function setImplementation($interface, $implementation) {
			$this->impls[$interface] = $implementation;
		}
		
		public static function getDefaultImplementation($interface) {
			$class = get_called_class();
			while($class !== false) {
				if(array_key_exists($class, self::$defaultImpls) && array_key_exists($interface, self::$defaultImpls[$class])) {
					return self::$defaultImpls[$class][$interface];
				} else {
					$class = get_parent_class($class);
				}
			}
		}
		
		public function getImplementation($interface) {
			return array_key_exists($interface, $this->impls)
				? $this->impls[$interface]
				: static::getDefaultImplementation($interface);
		}
		
		public static function callDefaultImplementation($impl) {
			$args = func_get_args();
			array_shift($args);
			return call_user_func_array(static::getDefaultImplementation($impl), $args);
		}
		
		public function callImplementation($impl) {
			$args = func_get_args();
			array_shift($args);
			return call_user_func_array($this->getImplementation($impl), $args);
		}
	}