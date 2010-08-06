<?php
	namespace Corelativ\Factory;
	use \Corelativ\Model,
		\Corelativ\ModelQuery,
		\Corelativ\Join,
		\DataPane;
	
	class HasAndBelongsToMany extends Multiple {
		private $joins;
		private $tableName;
		private $dataSource;
		public $Through;
		
		public function __construct ($config, $mapper) {
			parent::__construct($config, $mapper);
			
			if (isset($config['through'])) {
				$class = '\\Corelativ\\Model\\'.$config['through'];
				$hasMany = new HasMany(array('model' => $config['through'], 'subject' => $this->Subject), $mapper);
				$this->Through = new $class($hasMany, $mapper);
				$this->tableName = $this->Through->tableName();
				$this->dataSource = $this->Through->dataSource();
			} else {
				$this->Through = null;
				$this->tableName = min(
					$this->subjectAlias.'_'.$this->objectAlias,
					$this->objectAlias.'_'.$this->subjectAlias
				);
				
				$this->dataSource = isset($config['dataSource'])
					? $config['dataSource']
					: 'default';
			}
		}
		
		public function uniqueCondition () {
			$objectPK = $this->Object->primaryKeyField();
			
			if ($this->allInclusive) {
				// if it's all-inclusive, obviously every related object will be in joins or additions.
				// so no need for a subquery
				if (is_null($this->Through)) {
					$objIds = array_keys($this->joins)+array_keys($this->additions);
				} else {
					$objIds = array();
					foreach ($this->joins as $join) {
						$objIds[] = $join->{$this->objectKeyField};
					}
					foreach ($this->additions as $addition) {
						$objIds[] = $addition->{$this->objectKeyField};
					}
				}
				return new DataPane\ConditionSet(array($objectPK.' IN' => $objIds));
			} else {
				if (is_null($this->Through)) {
					// object ID could be in the table or in additions
					$condition = new DataPane\ConditionSet(array(
						$objectPK.' IN' => $this->selectObjectKeys(),
						$objectPK.' IN ' => array_keys($this->additions)
					), 'OR');
					
					// but object ID shouldn't be flagged for removal
					if (count($this->removals)) {
						$condition = new DataPane\ConditionSet(array(
							$condition,
							$objectPK.' NOT IN' => array_keys($this->removals)
						));
					}
					return $condition;
				} else {
					// object ID could be in the table or in additions. removals were
					// accounted for in selectObjectIds()
					$objectPKs = array();
					foreach ($this->additions as $addition) {
						$objectPKs[] = $addition->{$this->objectKeyField};
					}
					return new DataPane\ConditionSet(array(
						$objectPK.' IN' => $this->selectObjectKeys(),
						$objectPK.' IN ' => $objectPKs
					), 'OR');
				}
			}
		}
		
		private function selectObjectKeys () {
			// this doesn't need a datacontroller since it is always
			// parsed inside another query
			$q = new ModelQuery('select', $this->tableName);
			$q->fields = array($this->objectKeyField);
			$q->where = new DataPane\ConditionSet(array(
				$this->subjectKeyField => $this->Subject->primaryKey()
			));
			// don't include joins that are flagged for removal
			if (!is_null($this->Through)) {
				$q->where->add($this->Through->primaryKeyField().' NOT IN', array_keys($this->removals));
			}
			return $q;
		}
		
		public function add ($object, $supplemental = array()) {
			if ($object instanceof \Iterator || is_array($object)) {
				// adding multiple objects-- add each individually
				foreach ($object as $key => $obj) {
					if (is_array($obj)) {
						$this->add($key, $obj);
					} else {
						$this->add($obj);
					}
				}
			} elseif ($object instanceof Model) {
				// add by primaryKey, not the object!
				$this->add($object->primaryKey(), $supplemental);
			} else {
				// the actual adding
				$joinData = array(
					$this->subjectKeyField => $this->Subject->primaryKey(),
					$this->objectKeyField => $object
				) + $supplemental;
				
				if (is_null($this->Through)) {
					// since unmodelized joins contain a unique key pair, they can be removed and added
					// without being saved
					$this->additions[$object] = new Join($joinData);
					if (isset($this->removals[$object])) {
						unset($this->removals[$object]);
					}
				} else {
					$this->additions[] = $this->Through->create($joinData);
				}
			}
		}
		
		public function remove ($object) {
			if ($object instanceof \Iterator || is_array($object)) {
				// adding multiple objects-- add each individually
				foreach ($object as $obj) {
					$this->remove($obj);
				}
			} elseif ($object instanceof Model) {
				// add by primaryKey, not the object!
				$this->remove($object->primaryKey());
			} else {
				// remember, in a modelized hABTM, $object represents the join ID. otherwise it represents the object ID.
				$this->removals[$object] = $object;
				if (is_null($this->Through) && isset($this->additions[$object])) {
					unset($this->additions[$object]);
				}
				if (isset($this->joins[$object])) {
					unset($this->joins[$object]);
				}
			}
		}
		
		public function validate () {
			//@TODO this if you dare
			return true;
		}
		
		public function save () {
			if (is_null($this->Through)) {
				// delete joins flagged for removal
				if ($this->allInclusive || count($this->removals)) {
					$delete = new Query('delete', $this->tableName, array(), $this->Data);
					$delete->where->add($this->objectKeyField.' IN', array_keys($this->removals));
					
					if ($this->allInclusive) {
						// if all-inclusive, delete joins not found in the set
						$delete->where = new DataPane\ConditionSet(array(
							$delete->where,
							$this->objectKeyField.' NOT IN' => array_keys($this->joins)
						), 'OR');
					}
					
					$delete->where = new DataPane\ConditionSet(array(
						$delete->where,
						$this->subjectKeyField => $this->Subject->primaryKey(),
					));
					
					$delete->exec($this->dataSource);
				}
				
				// now insert the additions
				//@TODO make this use a multiple-insert query instead of multiple queries
				foreach ($this->additions as $objectKey => $join) {
					$add = new Query('insert', $this->tableName, array(), $this->Data);
					$add->options = array('IGNORE');
					$add->values = $join->data;
					$add->exec($this->dataSource);
					$this->joins[$objectKey] = $join;
				}
				
				//NOTE: if set was all-inclusive, it should still be
				$this->additions = array();
				$this->removals = array();
			} else {
				$this->saveThrough();
			}
		}
		
		private function saveThrough () {
			foreach ($this->additions as $addition) {
				$addition->save();
				$this->joins[$addition->primaryKey()] = $addition;
			}
			
			$params = array();
			$params['where'] = new DataPane\ConditionSet(array(
				$this->objectKeyField.' IN' => array_keys($this->removals)
			));
			
			if ($this->allInclusive) {
				// if all-inclusive, delete joins not found in the set
				$delete->where = new DataPane\ConditionSet(array(
					$delete->where,
					$this->Through->primaryKeyField().' NOT IN' => array_keys($this->joins)
				), 'OR');
			}
			
			$this->Through->delete($params);
			
			//NOTE: if set was all-inclusive, it should still be
			$this->additions = array();
			$this->removals = array();
		}
		
		protected function resetLists () {
			$this->joins = array();
			$this->additions = array();
			$this->removals = array();
		}
	}