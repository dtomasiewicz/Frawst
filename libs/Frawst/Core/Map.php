<?php
	namespace Frawst\Core;
	
	class Map  implements \ArrayAccess, \Countable, \JSONEncodable {
		private $type;
		private $data;
		
		public function __construct($type, $data = null) {
			$this->type = $type;
			if($data !== null) {
				$this->merge($data);
			}
		}
		
		public function merge($other) {
			if(is_array($other) || $other instanceof \Iterator) {
				foreach($other as $key => $item) {
					$this->put($key, $item);
				}
			}
		}
		
		public function exists($key) {
			return array_key_exists((string)$key, $this->data);
		}
		
		public function put($key, $item) {
			if($item instanceof $this->type) {
				$this->data[(string)$key] = $item;
			}
		}
		
		public function get($key) {
			if($this->exists($key)) {
				return $this->data[(string)$key];
			} else {
				return null;
			}
		}
		
		public function remove($key) {
			$item = $this->get($key);
			unset($this->data[(string)$key]);
			return $item;
		}
		
		public function count() {
			return count($this->data);
		}
		
		public function offsetExists($key) {
			return $this->exists($key);
		}
		public function offsetGet($key) {
			return $this->get($key);
		}
		public function offsetSet($key, $item) {
			$this->put($key, $item);
		}
		public function offsetUnset($key) {
			$this->remove($key, $item);
		}
		
		public function toJSON() {
			return Serialize::toJSON($this->data, \JSON_FORCE_OBJECT, false);
		}
	}