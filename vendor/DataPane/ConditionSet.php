<?php
	namespace DataPane;
	
	class ConditionSet implements \Iterator, \Countable {
		public $operand;
		private $conditions = array();
		
		public function __construct($conditions = array(), $operand = 'AND') {
			$this->operand = $operand;
			$this->add($conditions);
		}
		
		public function add($field, $value = null) {
			if(is_array($field)) {
				foreach($field as $f => $v) {
					$this->add($f, $v);
				}
			} elseif($field instanceof ConditionSet) {
				$this->conditions[] = $field;
			} elseif($value instanceof ConditionSet) {
				$this->conditions[] = $value;
			} else {
				$condition = new Condition();
				
				if(!is_null($value)) {
					$field = trim($field);
					if(substr($field, -6) == ' (LIT)') {
						$condition->quote = false;
						$field = substr($offset, 0, -6);
					}
					
					// get operator
					if(($space = strpos($field, ' ')) !== false) {
						list($condition->field, $condition->operator) = explode(' ', $field);
					} else {
						$condition->operator = '=';
						$condition->field = $field;
					}
					
					if(is_array($value) || $value instanceof Query) {
						if($condition->operator == '=') {
							$condition->operator = 'IN';
						} elseif($condition->operator == '!=') {
							$condition->operator = 'NOT IN';
						}
						
						// this will convert empty IN() or NOT IN() blocks to 1=2 or 1=1
						if(is_array($value) && count($value) == 0) {
							$condition->quote = false;
							$condition->field = 1;
							if($condition->operator == 'IN') {
								$value = 2;
							} else {
								$value = 1;
							}
							$condition->operator = '=';
						}
					}
					
					$condition->value = $value;
				} else {
					$condition->sql = $field;
					$condition->quote = false;
				}
				
				$this->conditions[] = $condition;
			}
		}
		
		public function count() {
			return count($this->conditions);
		}
		
		public function current() {
			return current($this->conditions);
		}
		
		public function key() {
			return key($this->conditions);
		}
		
		public function next() {
			return next($this->conditions);
		}
		
		public function rewind() {
			return reset($this->conditions);
		}
		
		public function valid() {
			return key($this->conditions) !== null;
		}
	}