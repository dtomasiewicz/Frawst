<?php
	namespace Frawst;
	
	/**
	 * Similar to an ArrayList, but all objects stored must be instances of
	 * a common base class.
	 */
	class Collection implements \ArrayAccess, \Iterator, \Countable, JSONEncodable {
		private $__type;
		private $__data;
		
		/**
		 * @param string $type The name of the class which all members of this
		 *                     collection must be instances of
		 * @para array|Iterator $data Members to add to the collection upon creation
		 */
		public function __construct($type, $data = null) {
			$this->__type = $type;
			$this->__data = array();
			if(is_array($data) || $data instanceof \Iterator) {
				foreach($data as $item) {
					$this->push($item);
				}
			}
		}
		
		public function get($index) {
			if((int)$index <= count($this->__data)) {
				return $this->__data[$index];
			} else {
				return null;
			}
		}
		
		public function set($index, $item) {
			if((int)$index <= count($this->__data) && $item instanceof $this->__type) {
				$this->__data[(int)$index] = $item;
			}
		}
		
		public function insert($index, $item) {
			if((int)$index <= count($this->__data) && $item instanceof $this->__type) {
				$this->__data = array_splice($this->__data, (int)$index, 0, array($item));
			}
		}
		
		public function remove($index) {
			if((int)$index <= count($this->__data)) {
				$splice = array_splice($this->__data, (int)$index, 1);
				return array_pop($splice);
			} else {
				return null;
			}
		}
		
		public function push($item) {
			if($item instanceof $this->__type) {
				array_push($this->__data, $item);
			}
		}
		public function pop() {
			return array_pop($this->__data);
		}
		public function shift() {
			return array_shift($this->__data);
		}
		public function unshift($item) {
			if($item instanceof $this->__type) {
				array_unshift($this->__data, $item);
			}
		}
		
		public function type() {
			return $this->__type;
		}
		
		/**
		 * Attempts to get the value of the specified property from each object
		 * in this colleciton, and returns them as an array.
		 * @param string $property
		 * @return array
		 */
		public function getAll($property) {
			$values = array();
			foreach($this->__data as $item) {
				$values[] = $item->$property;
			}
			return $values;
		}
		
		public function __get($name) {
			return $this->getAll($name);
		}
		
		/**
		 * Attempts to set the value of the specified property to all objects
		 * in the set.
		 * @param string $property Name of the property
		 * @param mixed $value Value to set
		 */
		public function setAll($property, $value) {
			foreach($this->__data as $item) {
				$item->$property = $value;
			}
		}
		
		public function __set($property, $value) {
			$this->setAll($property, $value);
		}
		
		/**
		 * Attempts to call a method on all members of this collection and returns
		 * the results as an array
		 */
		public function invokeAll($method, $args) {
			$results = array();
			foreach($this->__data as $item) {
				$results[] = call_user_func_array(array($item, $method), $args);
			}
			return $results;
		}
		
		public function __call($method, $args) {
			return $this->invokeAll($method, $args);
		}
		
		public function merge($other) {
			if(is_array($other) || $other instanceof \Iterator) {
				foreach($other as $item) {
					$this->push($item);
				}
			}
		}
		
		/**
		 * Returns a map of the objects, keyed by the specified property.
		 * @param string $property
		 * @param bool $preserveLast When true, and a key conflict is encountered,
		 *                           only the item with the larger index will appear in
		 *                           the map. If false, only the item with the smaller
		 *                           index will appear in the map.
		 * @return Map
		 */
		public function mapBy($property, $preserveLast = true) {
			$map = new Map($this->__type);
			foreach($this->__data as $item) {
				$key = $item->$property;
				if($preserveLast || !$map->exists($key)) {
					$map->put($key, $item);
				}
			}
			return $map;
		}
		
		/**
		 * Sorts objects in the collection using the given callback method.
		 * @param callable|string $property The property to sort by. If the collection's type
		 *                                  implements the Comparable interface, you can omit
		 *                                  this parameter to allow compareTo to handle sorting.
		 */
		public function sort($callback = null) {
			usort($this->__data, $callback);
		}
		
		private function sortComparable() {
			usort($this->__data, function($a, $b) {
				return $a->compareTo($b);
			});
		}
		
		public function sortBy($property, $direction = 1) {
			$this->sort(function($a, $b) use ($property, $direction) {
				if($a->$property == $b->$property) {
					return 0;
				} else {
					return (($a->$property < $b->$property) ? -1 : 1) * $direction;
				}
			});
		}
		
		/**
		 * Iterator methods
		 */
		public function current() {
			return current($this->__data);
		}
		public function rewind() {
			return reset($this->__data);
		}	
		public function key() {
			return key($this->__data);
		}
		public function next() {
			return next($this->__data);
		}	
		public function valid() {
			return key($this->__data) !== null;
		}
		
		/**
		 * Countable method
		 */
		public function count() {
			return count($this->__data);
		}
		
		/**
		 * ArrayAccess methods
		 */
		public function offsetExists($offset) {
			return (int)$offset <= count($this->__data);
		}
		public function offsetGet($offset = null) {
			return $this->get($offset);
		}
		public function offsetSet($offset, $value) {
			if (is_null($offset)) {
				return $this->push($value);
			} else {
				return $this->set($offset, $value);
			}
		}
		public function offsetUnset($offset) {
			$this->remove($offset);
		}
		
		/**
		 * Simulates implode/explode (requires all items
		 * to be castable as strings).
		 */
		public function implode($glue) {
			return implode($glue, $this->__data);
		}
		public function explode($glue) {
			return explode($glue, $this->__data);
		}
		
		/**
		 * Prepares the list for JSON-encoding
		 * @return string JSON-encodable data
		 */
		public function toJSON() {
			return Serialize::toJSON($this->__data, 0, false);
		}
	}