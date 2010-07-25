<?php
	namespace DataPane;
	
	/**
	 * Query building object
	 */
	class Query {
		protected $type;
		protected $tables;
		protected $options;
		protected $fields;
		protected $where;
		protected $group;
		protected $having;
		protected $order;
		protected $limit;
		protected $offset;
		protected $values;
		
		protected $Data;
		protected $source;
		
		public function __construct($type, $tables, $params = array(), $data = null) {
			$this->type = $type;
			$this->tables = (array) $tables;
			
			if($params instanceof ConditionSet) {
				$params = array('where' => $params);
			}
			
			// defaults
			$params += array(
				'options' => array(),
				'fields' => null,
				'where' => new ConditionSet(),
				'group' => array(),
				'having' => new ConditionSet(),
				'order' => array(),
				'limit' => null,
				'offset' => 0,
				'values' => array(),
				'source' => 'default'
			);
			
			$this->options = $params['options'];
			$this->fields = $params['fields'];
			$this->where = $params['where'];
			$this->group = $params['group'];
			$this->having = $params['having'];
			$this->order = $params['order'];
			$this->limit = $params['limit'];
			$this->offset = $params['offset'];
			$this->values = $params['values'];
			$this->source = $params['source'];
			
			$this->Data = $data;
		}
		
		public function where($field, $value = null) {
			if($field instanceof ConditionSet) {
				$this->where = $field;
			} else {
				$this->where = new ConditionSet();
				$this->where->add($field, $value);
			}
			return $this;
		}
		
		public function orderBy($field, $direction = 'ASC') {
			$this->order = array();
			if(is_array($field)) {
				foreach($field as $f => $d) {
					$this->order[$f] = $d;
				}
			} else {
				$this->order[$field] = $direction;
			}
			return $this;
		}
		
		public function limit($limit, $offset = null) {
			$this->limit = $limit;
			$this->offset = $offset;
			return $this;
		}
		
		public function groupBy($field) {
			$this->group = (array) $field;
			return $this;
		}
		
		public function having($field, $value = null) {
			if($field instanceof ConditionSet) {
				$this->having = $field;
			} else {
				$this->having = new ConditionSet();
				$this->having->add($field, $value);
			}
			return $this;
		}
		
		public function set($field, $value = null) {
			if(is_array($field)) {
				$this->values = $field;
			} else {
				$this->values = array($field => $value);
			}
			return $this;
		}
		
		public function values($values) {
			$this->values = $values;
		}
		
		public function options($options) {
			$this->options = (array) $options;
		}
		
		public function exec($source = null) {
			$source = is_null($source) ? $this->source : $source;
			return $this->Data->query($this, $source);
		}
		
		public function __get($name) {
			if(property_exists($this, $name)) {
				return $this->$name;
			} else {
				//@todo exception
				exit('getting invalid query property: '.$name);
			}
		}
		
		public function __set($name, $value) {
			if(property_exists($this, $name)) {
				$this->$name = $value;
			} else {
				//@todo exception
				exit('setting invalid query property: '.$name);
			}
		}
	}