<?php
	namespace Corelativ\Factory;
		
	abstract class Multiple extends Relation {
		protected $additions;
		protected $removals;
		protected $allInclusive;
		
		public function __construct($config, $mapper) {
			parent::__construct($config, $mapper);
			$this->allInclusive = false;
			$this->resetLists();
		}
		
		public function set($value = array()) {
			// detect what needs to be added, removed, or set
			$add = null;
			$remove = null;
			$set = null;
			if(is_array($value) && count($value) > 0) {
				if(array_key_exists('add', $value)) {
					$add = $value['add'];
					unset($value['add']);
				}
				if(array_key_exists('remove', $value)) {
					$remove = $value['remove'];
					unset($value['remove']);
				}
				if(count($value)) {
					$set = $value;
				}
			} else {
				$set = $value;
			}
			
			// set, add, and remove the values
			if(!is_null($set)) {
				$this->allInclusive = true;
				$this->resetLists();
				$this->add($set);
			}
			if(!is_null($add)) {
				$this->add($add);
			}
			if(!is_null($remove)) {
				$this->remove($remove);
			}
		}
		
		abstract protected function resetLists();
		abstract public function add($related);
		abstract public function remove($related);
	}