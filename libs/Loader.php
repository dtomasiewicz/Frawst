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
		protected static $_paths = array();
		
		/**
		 * Adds a path to the loader.
		 * 
		 * @param string $path An actual filesystem path
		 * @param string $pathType The namespace covered by the path, * = global namespace
		 * @param string $scope The loading priority of the path.
		 */
		public static function addPath($path, $pathType = '*') {
			if (!isset(self::$_paths[$pathType])) {
				self::$_paths[$pathType] = array();
			}
			
			array_unshift(
				self::$_paths[$pathType],
				rtrim($path, DIRECTORY_SEPARATOR).DIRECTORY_SEPARATOR
			);
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
		public static function importPath($class) {
			$parts = explode('\\', trim($class, '\\'));
			$base = array();
			
			while (count($parts) > 0) {
				array_unshift($base, array_pop($parts));
				$pathType = implode('\\', $parts);
				$subPath = implode(DIRECTORY_SEPARATOR, $base);
				
				if ($pathType == '') {
					$pathType = '*';
				}
				
				if (isset(self::$_paths[$pathType])) {
					foreach (self::$_paths[$pathType] as $rootPath) {
						if (file_exists($file = $rootPath.DIRECTORY_SEPARATOR.$subPath.'.php')) {
							return $file;
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
		public static function import($class) {
			if (null !== $path = self::importPath($class)) {
				require $path;
				return true;
			} else {
				return false;
			}
		}
		
		/**
		 * Generates an array of paths that will be checked for the given namespace, in order
		 * of their priority.
		 * @param string $name The namespace to check
		 * @param string $scope The scope to check, defaults to all scopes
		 * @return array Array of loader paths for the given namespace, in the order they will
		 *               be checked
		 */
		public static function getPaths($name = '*') {
			$paths = array();
			
			$prefix = $name == '*'
				? array()
				: explode('\\', trim($name, '\\'));
			$subDir = '';
			
			$finished = false;
			while(!$finished) {
				$type = count($prefix)
					? implode('\\', $prefix)
					: '*';
				
				if(isset(self::$_paths[$type])) {
					foreach(self::$_paths[$type] as $path) {
						if(file_exists($full = $path.$subDir)) {
							$paths[] = $full;
						}
					}
				}
				
				if(count($prefix)) {
					$subDir = array_pop($prefix).$subDir.DIRECTORY_SEPARATOR;
				} else {
					$finished = true;
				}
			}
			
			return $paths;
		}
	}