<?php
	namespace Frawst\Test;
	
	require_once 'Double.php';
	
	class Framework {
		public static function mockImplementation($interface, $name) {
			$className = __NAMESPACE__.'\\'.$name;
			var_dump($className);
			if(class_exists($className)) {
				return $className;
			}
			
			$reflection = new \ReflectionClass($interface);
			
			$def = 'namespace '.__NAMESPACE__.";\n"
			     . 'class '.$name.' extends Double implements \\'.$interface." {\n";
			
			foreach($reflection->getMethods() as $method) {
				$access = 'public';
				if($method->isProtected()) {
					$access = 'protected';
				} elseif($method->isPrivate()) {
					$access = 'private';
				}
				
				$static = $method->isStatic() ? ' static' : '';
				
				$params = '';
				foreach($method->getParameters() as $param) {
					$typehint = '';
					if($param->getClass() !== null) {
						$typehint = '\\'.$param->getClass()->getName().' ';
					} elseif($param->isArray()) {
						$typehint = 'array ';
					}
					
					$name = $param->getName();
					$ref = $param->isPassedByReference()?'&':'';
					
					$default = '';
					if($param->isDefaultValueAvailable()) {
						$dv = $param->getDefaultValue();
						$default = ' = ';
						if(is_string($dv)) {
							$default .= '\''.$dv.'\'';
						} elseif(is_null($dv)) {
							$default .= 'null';
						} elseif(is_bool($dv)) {
							$default .= $dv ? 'true' : 'false';
						} elseif(is_array($dv)) {
							//@todo make this copy the array correctly
							$default .= 'array()';
						} else {
							$default .= $dv;
						}
					}
					
					$params .= $typehint.$ref.'$'.$name.$default.', ';
				}
				$params = rtrim($params, ', ');
				
				$def .= $access.$static.' function '.$method->getName().'('.$params.") {\n";
				if($method->isStatic()) {
					$def .= "return self::getClassSeed('".$method->getName()."', func_get_args());\n";
				} else {
					$def .= "return \$this->getSeed('".$method->getName()."', func_get_args());\n";
				}
				$def .= "}\n";
			}
			
			$def .= "}\n";
			
			eval($def);
			
			return $className;
		}
		
		public static function setupMock($interface, $name) {
			$className = self::mockImplementation($interface, $name);
			\Frawst\Base::setClassImplementation($interface, $className);
		}
	}