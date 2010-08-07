<?php
	namespace Frawst\Library;
	use \Frawst\Exception;
	
	/**
	 * Used for sets of objects that are intended to be iterated and counted
	 * like arrays, but allows storage of extra data that arrays do not.
	 */
	class Collection extends ArrayList {
		protected $_type;
		
		public function __construct($type, $data = null) {
			$this->_type = $type;
			parent::__construct($data);
		}
		
		public function set($index, $value) {
			if ($this->welcomes($value)) {
				parent::set($index, $value);
			} else {
				$this->__notWelcome($value);
			}
		}
		
		public function unshift($item) {
			if ($this->welcomes($item)) {
				return parent::unshift($item);
			} else {
				$this->__notWelcome($item);
			}
		}
		
		public function push($item) {
			if ($this->welcomes($item)) {
				return parent::push($item);
			} else {
				$this->__notWelcome($item);
			}
		}
		
		private function __notWelcome($item) {
			$type = is_object($item) ? get_class($item) : gettype($item);
			throw new Exception\Frawst('Variable of type '.$type.' is not welcome in a Collection of type '.$this->type().'.');
		}
		
		public function welcomes($item) {
			return $item instanceof $this->_type;
		}
		
		public function type() {
			return $this->_type;
		}
		
		/**
		 * Getting a value from the collection will return an array of that
		 * value from each collection item.
		 */
		public function __get($name) {
			$values = array();
			foreach ($this->get() as $key => $item) {
				$values[$key] = $item->$name;
			}
			return $values;
		}
		
		/**
		 * Setting a value to the collection will attempt to set that value
		 * in all collection items.
		 */
		public function __set($name, $value) {
			foreach ($this->get() as $item) {
				$item->$name = $value;
			}
		}
		
		/**
		 * Calling a method will return an array of the result of that method
		 * being called on all items in the collection.
		 */
		public function __call($method, $args) {
			$results = array();
			foreach ($this->get() as $key => $item) {
				$results[$key] = call_user_func_array(array($item, $method), $args);
			}
			return $results;
		}
		
		/**
		 * Indexes the objects by the specified property.
		 * @todo this could probably be done with only one loop
		 */
		public function indexBy($property) {
			$indexed = array();
			foreach ($this->get() as $key => $item) {
				$indexed[$item->$property] = $item;
				unset($this[$key]);
			}
			foreach ($indexed as $key => $item) {
				$this[$key] = $item;
			}
		}
		
		/**
		 * Sorts objects in the collection by the specified property.
		 */
		public function sortBy($property, $direction = ASC) {
			parent::usort(function($a, $b) {
				global $property, $direction;
				
				if ($a->$property == $b->$property) {
					return 0;
				} else {
					return (($a->$property < $b->$property) ? -1 : 1) * $direction;
				}
			});
		}
	}