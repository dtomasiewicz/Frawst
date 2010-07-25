<?php
	namespace Corelativ\Factory;
	use \Corelativ\Model,
		\DataPane;
	
	class HasMany extends Multiple {
		//@todo add support for relating non-saved models?
		protected function uniqueCondition() {
			// make sure it's not flagged for removal
			//@todo make this return entries in additions as well?
			$condition = new DataPane\ConditionSet($this->uniqueProperty() + array(
				$this->Object->primaryKeyField().' NOT IN' => array_keys($this->removals)
			));
			return $condition;
		}
		
		private function uniqueProperty() {
			return array($this->subjectKeyField => $this->Subject->primaryKey());
		}
		
		public function add($object, $properties = array()) {
			if($object instanceof Iterator || is_array($object)) {
				// adding multiple objects-- add each individually
				foreach($object as $obj) {
					if(is_array($obj)) {
						$this->add($obj[$this->objectPrimaryKeyField], $obj);
					} else {
						$this->add($obj);
					}
				}
			} else {
				if(!($object instanceof Model)) {
					// the actual adding
					$object = parent::find(new DataPane\ConditionSet(array(
						$this->objectPrimaryKeyField => $object
					)));
				}
				
				if($object instanceof Model) {
					$this->additions[$object->primaryKey()] = $object;
					$object->set($properties);
					if(isset($this->removals[$object])) {
						unset($this->removals[$object]);
					}
				}
			}
		}
		
		public function remove($object) {
			if($object instanceof \Iterator || is_array($object)) {
				// removing multiple objects-- remove each individually
				foreach($object as $obj) {
					$this->remove($obj);
				}
			} else {
				if($object instanceof Model) {
					$object = $object->primaryKey();
				}
				
				if($object) {
					$this->removals[$object] = $object;
					if(isset($this->additions[$object])) {
						unset($this->additions[$object]);
					}
				}
			}
		}
		
		public function create($data = array()) {
			return parent::create($this->uniqueProperty() + $data);
		}
		
		public function validate() {
			$errors = array();
			foreach($this->additions as $addition) {
				$validate = $addition->validate();
				if(is_array($validate)) {
					$errors[$addition->primaryKey()] = $validate;
				}
			}
			return (count($errors) == 0) ? true : $errors;
		}
		
		public function save() {
			// if this represents ALL of the associated objects, de-associate any objects that aren't in additions
			if($this->allInclusive) {
				$delink = new DataPane\Query('update', $this->objectTableName);
				$delink->values = array(
					$this->subjectKeyField => 0
				);
				$delink->where = new DataPane\ConditionSet(array(
					$this->subjectKeyField => $this->subject->primaryKey()
				));
				$this->Data->query($delink, $this->Object->dataSource());
			}
			
			// remove defuct associations
			if(count($this->removals)) {
				$remove = new DataPane\Query('update', $objectTable);
				$remove->values = array(
					$this->subjectKeyField => 0
				);
				$remove->where = new DataPane\ConditionSet(array(
					$this->objectPrimaryKeyField => array_keys($this->removals)
				));
				$this->Data->query($remove, $this->Object->dataSource());
			}
			
			// add new associations
			foreach($this->additions as $addition) {
				$addition->{$this->subjectKeyField} = $this->subject->primaryKey();
				$addition->save();
			}
			
			$this->allInclusive = false;
			$this->resetLists();
		}
		
		protected function resetLists() {
			$this->additions = array();
			$this->removals = array();
		}
	}