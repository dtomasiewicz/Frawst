<?php
	namespace Corelativ\Factory;
	use Corelativ\Factory;
	
	/**
	 * implementation notes:
	 *  through needs to be specified outside of constructor
	 *  type needs to be determined outside of constructor
	 */
	abstract class Relation extends Factory {
		protected $subjectAlias;
		protected $subjectKeyField;
		protected $objectAlias;
		protected $objectKeyField;
		protected $Subject;
		
		public function __construct($config, $mapper) {
			parent::__construct($config, $mapper);
			$this->Subject = $config['subject'];
			
			$this->subjectAlias = isset($config['subjectAlias'])
				? $config['subjectAlias']
				: $this->Subject->modelName();
			
			$this->objectAlias = isset($config['objectAlias'])
				? $config['objectAlias']
				: $this->Object->modelName();
				
			$this->subjectKeyField = isset($config['subjectKeyField'])
				? $config['subjectKeyField']
				: lcfirst($this->subjectAlias).ucfirst($this->Subject->primaryKeyField());
			
			$this->objectKeyField = isset($config['objectKeyField'])
				? $config['objectKeyField']
				: lcfirst($this->objectAlias).ucfirst($this->Object->primaryKeyField());
		}
		/*
		public function __call($method, $args) {
			if(substr($method, 0, 4) == 'find' || substr($method, 0, 6) == 'delete') {
				// if it's a find/delete operation, inject the additional condition
				$condition = $this->uniqueCondition();
				
				// if doing a findBy operation, shift indices over one
				$shift = preg_match('/^(find|delete)(All)?By\w+$/', $method) ? 1 : 0;
				
				if(isset($args[$shift])) {
					if(is_array($args[$shift]) || $args[$shift] instanceof Query || $args[$shift] instanceof ConditionSet) {
						$args[$shift] = Model::addCondition($args[$shift], $condition);
					} else {
						$args[$shift+1] = $args[$shift];
						$args[$shift] = $condition;
					}
				} else {
					$args[$shift] = $condition;
				}
			}
			
			return call_user_func_array(array(Model::factory($this->objectName), $method), $args);
		}*/
		
		public function findAll($params = array()) {
			$params = $this->normalizeParams($params);
			$condition = $this->uniqueCondition();
			$condition->add($params->where);
			$params->where = $condition;
			return parent::findAll($params);
		}
		
		abstract protected function uniqueCondition();
		abstract public function set($related);
		abstract public function save();
	}