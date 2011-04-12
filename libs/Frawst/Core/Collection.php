<?php
	namespace Frawst\Core;
	
	/**
	 * Similar to an ArrayList, but all objects stored must be instances of
	 * a common base class.
	 */
	class Collection  implements \ArrayAccess, \Iterator, \Countable, JSONEncodable {
		private $type;
		private $data;
		
		/**
		 * @param string $type The name of the class which all members of this
		 *                     collection must be instances of
		 * @para array|Iterator $data Members to add to the collection upon creation
		 */
		public function __construct($type, $data = null) {
			$this->type = $type;
			$this->data = array();
			if(is_array($data) || $data instanceof \Iterator) {
				foreach($data as $item) {
					$this->push($item);
				}
			}
		}
		
		public function get($index) {
			if((int)$index <= count($this->data)) {
				return $this->data[$index];
			} else {
				return null;
			}
		}
		
		public function set($index, $item) {
			if((int)$index <= count($this->data) && $item instanceof $this->type) {
				$this->data[(int)$index] = $item;
			}
		}
		
		public function insert($index, $item) {
			if((int)$index <= count($this->data) && $item instanceof $this->type) {
				$this->data = array_splice($this->data, (int)$index, 0, array($item));
			}
		}
		
		public function remove($index) {
			if((int)$index <= count($this->data)) {
				$splice = array_splice($this->data, (int)$index, 1);
				return array_pop($splice);
			} else {
				return null;
			}
		}
		
		public function push($item) {
			if($item instanceof $this->type) {
				array_push($this->data, $item);
			}
		}
		public function pop() {
			return array_pop($this->data);
		}
		public function shift() {
			return array_shift($this->data);
		}
		public function unshift($item) {
			if($item instanceof $this->type) {
				array_unshift($this->data, $item);
			}
		}
		
		public function type() {
			return $this->type;
		}
		
		/**
		 * Attempts to get the value of the specified property from each object
		 * in this colleciton, and returns them as an array.
		 * @param string $property
		 * @return array
		 */
		public function getAll($property) {
			$values = array();
			foreach($this->data as $item) {
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
			foreach($this->data as $item) {
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
			foreach($this->data as $item) {
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
			$map = new Map($this->type);
			foreach($this->data as $item) {
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
			usort($this->data, $callback);
		}
		
		public function sortComparable() {
			usort($this->data, function($a, $b) {
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
			return current($this->data);
		}
		public function rewind() {
			return reset($this->data);
		}	
		public function key() {
			return key($this->data);
		}
		public function next() {
			return next($this->data);
		}	
		public function valid() {
			return key($this->data) !== null;
		}
		
		/**
		 * Countable method
		 */
		public function count() {
			return count($this->data);
		}
		
		/**
		 * ArrayAccess methods
		 */
		public function offsetExists($offset) {
			return (int)$offset <= count($this->data);
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
			return implode($glue, $this->data);
		}
		public function explode($glue) {
			return explode($glue, $this->data);
		}
		
		/**
		 * Prepares the list for JSON-encoding
		 * @return string JSON-encodable data
		 */
		public function toJSON() {
			return Serialize::toJSON($this->data, 0, false);
		}
	}