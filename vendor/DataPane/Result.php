<?php
	namespace DataPane;
	
	abstract class Result implements \ArrayAccess, \Iterator, \Countable {
		private $results = array();
		
		abstract function __construct();
		
		/**
		 * Iterator methods
		 */
		public function current() {
			return current($this->results);
		}
		public function rewind() {
			return reset($this->results);
		}	
		public function key() {
			return key($this->results);
		}
		public function next() {
			return next($this->results);
		}	
		public function valid() {
			return key($this->results) !== null;
		}
		
		/**
		 * Countable method
		 */
		public function count() {
			return count($this->results);
		}
		
		/**
		 * ArrayAccess methods
		 */
		public function offsetExists($offset) {
			return isset($this->results[$offset]);
		}
		public function offsetGet($offset) {
			if(is_null($offset)) {
				return $this->results;
			} else {
				return $this->results[$offset];
			}
		}
		public function offsetSet($offset, $value) {
			if(is_null($offset)) {
				$this->results[] = $value;
			} else {
				$this->results[$offset] = $value;
			}
		}
		public function offsetUnset($offset) {
			unset($this->results[$offset]);
		}
	}