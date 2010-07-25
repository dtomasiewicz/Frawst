<?php
	namespace Frawst\Library;
	use \Serializable,
		\Frawst\Exception;
	
	class Matrix implements \ArrayAccess {
		private $data;
		
		public function __construct($data = array()) {
			$this->data = $data;
		}
		
		/**
		 * Returns a flattened version of the data (one-dimensional array
		 * with dot-separated paths as its keys).
		 */
		public static function flatten(&$array, $path = null) {
			$data = self::pathGet($array, $path);
			
			if(is_null($path)) {
				$path = '';
			} else {
				$path .= '.';
			}
			
			$flat = array();
						
			foreach($data as $key => $value) {
				if(is_array($value)) {
					$flat += self::flatten($data, $path.$key);
				} else {
					$flat[$path.$key] = $value;
				}
			}
			
			return $flat;
		}
		
		/**
		 * Expands a flattened array to an n-dimensional matrix.
		 */
		public static function expand($flat) {
			$matrix = array();
			
			foreach($flat as $key => $value) {
				Matrix::pathSet($matrix, $key, $value);
			}
			
			return $matrix;
		}
		
		/**
		 * Required for ArrayAccess
		 */
		public function offsetGet($offset) {
			return is_null($offset) ? $this->data : self::pathGet($this->_data, $offset);
		}
		public function offsetSet($offset, $value) {
			self::pathSet($this->data, $offset, $value);
		}
		public function offsetExists($offset) {
			return self::pathExists($this->data, $offset);
		}
		public function offsetUnset($offset) {
			self::pathUnset($this->data, $offset);
		}
		
		/**
		 * Parses a dot-separated path to a bracket-separated (array-style)
		 * path:
		 *   'User.name.length' -> 'User[name][length]'
		 * @param string $dotPath The dot-separated path
		 * @param bool $bracketFirst Whether or not to put brackets around the
		 *                           first component of the path.
		 * @return string The bracket-separated path.
		 */
		public static function dotToBracket($dotPath, $bracketFirst = false) {
			$segs = explode('.', $dotPath);
			$path = $bracketFirst ? '' : array_shift($segs);
			return count($segs) ? $path.'['.implode('][', $segs).']' : $path;
		}
		
		/**
		 * Converts a bracket-separated path to a dot-separated path:
		 *   'User[name][length]' -> 'User.name.length'
		 * @param string $bracketPath The bracket-separated path
		 * @return string The dot-separated path
		 */
		public static function bracketToDot($bracketPath) {
			$segs = explode($bracketPath, '][');
			// first component may or may not be bracketed
			foreach($subSegs = explode('[', array_shift($segs), 1) as $subSeg) {
				array_unshift($segs, $subSeg);
			}
			return implode('.', $segs);
		}
		
		/**
		 * Verifies that a value exists at the specified dot-separated path.
		 * @param array $array The array to search
		 * @param string $path The path to search for
		 * @return bool True if the path is set, false otherwise
		 */
		public static function pathExists(&$array, $path) {
			if(is_null($path)) {
				return true;
			}
			
			$segs = explode('.', $path);
			
			$target =& $array;
			while(count($segs) > 0) {
				$key = array_shift($segs);
				if(array_key_exists($key, $target)) {
					$target =& $target[$key];
				} else {
					return false;
				}
			}
			
			return true;
		}
		
		/**
		 * Finds the value at a point in a multi-dimensional array indicated
		 * by a dot-separated path;
		 * @param array $array The array to search
		 * @param string $path The dot-separated path to search for
		 * @return mixed The value at the given location, by reference
		 */
		public static function &pathGet(&$array, $path) {
			if(is_null($path)) {
				return $array;
			}
			
			$segs = explode('.', $path);
			
			$target =& $array;
			while(count($segs) > 0) {
				$key = array_shift($segs);
				if(array_key_exists($key, $target)) {
					$target =& $target[$key];
				} else {
					throw new Exception\Frawst('Path does not exist: '.$path);
				}
			}
			
			return $target;
		}
		
		/**
		 * Sets a value to an array at index indicated by a dot-separated
		 * path, by reference.
		 * @param array $array The array to set to
		 * @param string $path The dot-separated path to set to
		 * @param mixed $value The value to set
		 */
		public static function pathSet(&$array, $path, $value) {
			$segs = explode('.', $path);
			
			$target =& $array;
			while(count($segs) > 1) {
				$key = array_shift($segs);
				if(!array_key_exists($key, $target) || !is_array($target[$key])) {
					$target[$key] = array();
				}
				$target =& $target[$key];
			}
			
			// push-style set
			if($segs[0] == '') {
				$target[] = $value;
			} else {
				$target[$segs[0]] = $value;
			}
		}
		
		/**
		 * Unsets the specified dot-separated path.
		 * @param array $array The array to unset from
		 * @param string $path The path to unset
		 */
		public static function pathUnset(&$array, $path) {
			$segs = explode('.', $path);
			
			$target =& $array;
			while(count($segs) > 1) {
				$key = array_shift($segs);
				if(array_key_exists($key, $target)) {
					$target =& $target[$key];
				} else {
					return;
				}
			}
			
			unset($target[$segs[0]]);
		}
	}