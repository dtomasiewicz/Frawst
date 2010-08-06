<?php
	namespace Corelativ\Factory;
	use \Corelativ\Model,
		\DataPane\ConditionSet,
		\DataPane\Query;
	
	abstract class Singular extends Relation {
		protected $related = false;
		protected $changed = false;
		
		protected function uniqueCondition () {
			return new ConditionSet($this->uniqueProperty());
		}
		
		public function set ($id = null) {
			if ($id instanceof Model) {
				$this->related = $id;
			} elseif (is_null($id) || $id == 0) {
				$this->related = null;
			} else {
				// allow them to change properties on the fly
				$properties = array();
				if (is_array($id)) {
					$properties = $id;
					$id = $properties[$this->Object->primaryKeyField()];
				}
				
				// want to find WITHOUT restrictions
				$this->related = parent::find(new ConditionSet(array(
					$this->Object->primaryKeyField() => $id
				)));
				
				if ($this->related) {
					$this->related->set($properties);
				}
			}
			
			$this->changed = true;
		}
		
		public function validate () {
			if ($this->related instanceof Model) {
				return $this->related->validate() ? true : $this->related->errors();
			} else {
				return true;
			}
		}
		
		abstract protected function uniqueProperty ();
	}