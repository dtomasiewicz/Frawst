<?php
	namespace Frawst;
	
	class Loader {
		
		/**
		 * A multidimensional array of paths in the format:
		 * 
		 *   $paths[scope][pathType][] = <a filesystem path>
		 *   
		 * @var array
		 */
		private static $paths = array(
			'priority' => array(),
			'app' => array(),
			'core' => array()
		);
		
		/**
		 * Adds a path to the loader.
		 * 
		 * @param string $path An actual filesystem path
		 * @param string $pathType The namespace covered by the path, * = global namespace
		 * @param string $scope The loading priority of the path.
		 */
		public static function addPath($path, $pathType = '*', $scope = 'priority') {
			if(!isset(self::$paths[$scope][$pathType])) {
				self::$paths[$scope][$pathType] = array();
			}
			
			self::$paths[$scope][$pathType][] = $path;
		}
		
		/**
		 * Gets the path of a library based on added paths. Paths will be
		 * checked in order or priority, then order added, then specificity.
		 * For example, if a path is added as such:
		 * 
		 *   Loader::addPath('/app/includes/Some/Path', 'Some\Path', 'app');
		 *   
		 * And the class \Some\Path\SomeClass is loaded, priority paths will
		 * be tested first, followed by app paths and finally core paths.
		 * Paths with type Some\Path will be checked first for a file called
		 * SomeClass.php. If not found, paths with type Some will be checked
		 * for a file called Path/SomeClass.php. Finally, paths with type *
		 * will be checked for a file called Some/Path/SomeClass.php
		 * 
		 * @param string $class The name of the class to be checked
		 * @param scope The scope to check. If null, all scopes will be checked.
		 * @return The path to the requested library if it is found, or null.
		 */
		public static function importPath($class, $scope = null) {
			if(is_null($scope)) {
				// scope not set, try all of them
				foreach(array_keys(self::$paths) as $scope) {
					if(null !== $path = self::importPath($class, $scope)) {
						return $path;
					}
				}
			} else {
				$s = microtime(true);
				
				$parts = explode('\\', trim($class, '\\'));
				$base = array();
				
				while(count($parts) > 0) {
					array_unshift($base, array_pop($parts));
					$pathType = implode('\\', $parts);
					$subPath = implode(DIRECTORY_SEPARATOR, $base);
					
					if($pathType == '') {
						$pathType = '*';
					}
					
					if(isset(self::$paths[$scope][$pathType])) {
						foreach(self::$paths[$scope][$pathType] as $rootPath) {
							if(file_exists($file = $rootPath.DIRECTORY_SEPARATOR.$subPath.'.php')) {
								return $file;
							}
						}
					}
				}
			}
			
			return null;
		}
		
		/**
		 * Loads libraries using importPath
		 * 
		 * @param string $class The name of the class to load
		 * @param string $scope The scope to look in. If null, all scopes will be checked.
		 * @return True if the library exists and is loaded, false otherwise.
		 */
		public static function import($class, $scope = null) {
			if(null !== $path = self::importPath($class, $scope)) {
				require $path;
				return true;
			} else {
				return false;
			}
		}
	}