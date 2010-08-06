<?php
	namespace Corelativ\Factory;
	use \DataPane;
	
	class HasOne extends Singular {
		public function uniqueProperty () {
			//@TODO make this behavior different if object is set
			return array($this->subjectKeyField => $this->Subject->primaryKey());
		}
		
		public function save () {
			if ($this->changed) {
				$objectTable = $this->Object->tableName();
			
				// unlink old relation if exists
				$delink = new DataPane\Query('update', $objectTable, array(), $this->Data);
				$delink->values = array(
					$this->subjectKeyField => 0
				);
				$delink->where = new DataPane\ConditionSet(array(
					$this->subjectKeyField => $this->Subject->primaryKey()
				));
				$delink->limit = 1;
				$delink->exec($this->Object->dataSource());
			
				// link the new association
				if ($this->related instanceof \Corelativ\Model) {
					$this->related->{$this->subjectKeyField} = $this->Subject->primaryKey();
					if ($this->related->save()) {
						$this->changed = false;
					}
				} else {
					$this->changed = false;
				}
			}
		}
	}