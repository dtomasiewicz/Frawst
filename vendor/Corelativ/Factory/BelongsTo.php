<?php
	namespace Corelativ\Factory;
	use \Corelativ\Model;
	
	class BelongsTo extends Singular {
		public function uniqueProperty() {
			if($this->related === false) {
				return array($this->Object->primaryKeyField() => $this->Subject->{$this->objectKeyField});
			} elseif(is_null($this->related)) {
				// don't return anything if the related object has been unrelated
				return array('1 LIT' => 2);
			} else {
				return array($this->Object->primaryKeyField() => $this->related->primaryKey());
			}
		}
		
		public function save() {
			if($this->changed) {
				if($this->related instanceof Model) {
					$this->Subject->{$this->objectKeyField} = $this->related->primaryKey();
				} else {
					$this->Subject->{$this->objectKeyField} = 0;
				}
				// false means don't save relationships-- otherwise we'd get an infinite loop
				$this->Subject->save(false);
				$this->changed = false;
			}
		}
	}