<?php
	namespace Frawst\Library;
	use \Frawst\Exception;
	
	/**
	 * Handles all array-type structure that also require supplemental information
	 * or methods. This may get changed to simply extend ArrayObject
	 */
	class ArrayList implements \ArrayAccess, \Iterator, \Countable {
		protected $_data = array();
		
		public function __construct($data = null) {
			if (is_array($data)) {
				foreach ($data as $key => $value) {
					$this[$key] = $value;
				}
			}
		}
		
		/**
		 * Simulates implode/explode (requires all items
		 * to be castable as strings).
		 */
		public function implode($glue) {
			return implode($glue, $this->_data);
		}
		public function explode($glue) {
			return explode($glue, $this->_data);
		}
		
		public function keys() {
			return array_keys($this->_data);
		}
		
		/**
		 * Merges another arraylist into this one
		 */
		public function merge($other, $overwrite = false) {
			foreach ($other as $key => $value) {
				if ($overwrite) {
					$this[$key] = $value;
				} else {
					$this[] = $value;
				}
			}
		}
		
		public function usort($callback) {
			usort($this->_data, $callback);
		}
		
		/**
		 * Iterator methods
		 */
		public function current() {
			return current($this->_data);
		}
		public function rewind() {
			return reset($this->_data);
		}	
		public function key() {
			return key($this->_data);
		}
		public function next() {
			return next($this->_data);
		}	
		public function valid() {
			return key($this->_data) !== null;
		}
		
		/**
		 * Countable method
		 */
		public function count() {
			return count($this->_data);
		}
		
		/**
		 * ArrayAccess methods
		 */
		public function offsetExists($offset) {
			return array_key_exists($offset, $this->_data);
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
		 * Basic array methods
		 */
		public function push($item) {
			return array_push($this->_data, $item);
		}
		public function pop() {
			return array_pop($this->_data);
		}
		public function shift() {
			return array_shift($this->_data);
		}
		public function unshift($item) {
			return array_unshift($this->_data, $item);
		}
		public function get($index = null) {
			return is_null($index) ? $this->_data : $this->_data[$index];
		}
		public function set($index, $value) {
			if (is_array($index) || $index instanceof \Iterator) {
				foreach ($index as $i => $v) {
					$this->set($i, $v);
				}
			} else {
				$this->_data[$index] = $value;
			}
		}
		public function remove($index) {
			unset($this->_data[$index]);
		}
		public function reverse() {
			$this->_data = array_reverse($this->_data);
		}
	}