<?php
	namespace Frawst;
	
	class Object {
		private $__impls = array();
		
		private static $__defaultImpls = array(
			'Frawst\Object' => array(
				'Frawst\RequestInterface' => 'Frawst\Request',
				'Frawst\ResponseInterface' => 'Frawst\Response',
				'Frawst\FormInterface' => 'Frawst\Form',
				'Frawst\ViewInterface' => 'Frawst\View',
				'Frawst\RouteInterface' => 'Frawst\Route',
				'ns:Frawst\Controller' => 'Frawst\Controller',
				'configRead' => array('Frawst\Config', 'read')
			)
		);
		
		public static function setDefaultImplementation($interface, $implementation) {
			$class = get_called_class();
			if(!array_key_exists($class, self::$__defaultImpls)) {
				self::$__defaultImpls[$class] = array();
			}
			self::$__defaultImpls[$class][$interface] = $implementation;
		}
		
		public function setImplementation($interface, $implementation) {
			$this->__impls[$interface] = $implementation;
		}
		
		public static function getDefaultImplementation($interface) {
			$class = get_called_class();
			while($class !== false) {
				if(array_key_exists($class, self::$__defaultImpls) && array_key_exists($interface, self::$__defaultImpls[$class])) {
					return self::$__defaultImpls[$class][$interface];
				} else {
					$class = get_parent_class($class);
				}
			}
		}
		
		public function getImplementation($interface) {
			return array_key_exists($interface, $this->__impls)
				? $this->__impls[$interface]
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