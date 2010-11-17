<?php
	namespace Frawst\Library;
	use \Frawst\Exception;
	
	/**
	 * Similar to an ArrayList, but all objects stored must be instances of
	 * a common base class.
	 */
	class Collection extends ArrayList {
		
		const SORT_ASC = 1;
		const SORT_DESC = -1;
		
		protected $_type;
		
		/**
		 * @param string $type The name of the class which all members of this
		 *                     collection must be instances of
		 * @para array $data Members to add to the collection upon creation
		 */
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
		 * Attempts to get the value of the specified property from each object
		 * in this colleciton, and returns them as an array.
		 * @param string $property
		 * @return array
		 */
		public function getAll($property) {
			$values = array();
			foreach($this->_data as $key => $item) {
				$values[$key] = $item->$property;
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
			foreach($this->_data as $key => $item) {
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
		public function callAll($method, $args) {
			$results = array();
			foreach($this->_data as $key => $item) {
				$results[$key] = call_user_func_array(array($item, $method), $args);
			}
			return $results;
		}
		
		public function __call($method, $args) {
			return $this->call($method, $args);
		}
		
		/**
		 * Indexes the objects by the specified property.
		 */
		public function indexBy($property) {
			$indexed = array();
			foreach ($this->_data as $key => $item) {
				$indexed[$item->$property] = $item;
			}
			$this->_data = $indexed;
		}
		
		/**
		 * Sorts objects in the collection by the specified property.
		 * @param string $property The property to sort by
		 * @param int $direction
		 */
		public function sortBy($property, $direction = self::SORT_ASC) {
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