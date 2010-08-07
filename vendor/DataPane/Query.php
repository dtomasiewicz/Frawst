<?php
	namespace DataPane;
	
	/**
	 * Query building object
	 */
	class Query {
		protected $_type;
		protected $_tables;
		protected $_options;
		protected $_fields;
		protected $_where;
		protected $_group;
		protected $_having;
		protected $_order;
		protected $_limit;
		protected $_offset;
		protected $_values;
		
		protected $_Data;
		protected $_source;
		
		public function __construct($type, $tables, $params = array(), $data = null) {
			$this->_type = $type;
			$this->_tables = (array) $tables;
			
			if ($params instanceof ConditionSet) {
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
			
			foreach($params as $key => $value) {
				$this->$key = $value;
			}
			
			$this->_Data = $data;
		}
		
		public function where($field, $value = null) {
			if ($field instanceof ConditionSet) {
				$this->where = $field;
			} else {
				$this->where = new ConditionSet();
				$this->where->add($field, $value);
			}
			return $this;
		}
		
		public function orderBy($field, $direction = 'ASC') {
			$this->order = array();
			if (is_array($field)) {
				foreach ($field as $f => $d) {
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
			if ($field instanceof ConditionSet) {
				$this->having = $field;
			} else {
				$this->having = new ConditionSet();
				$this->having->add($field, $value);
			}
			return $this;
		}
		
		public function set($field, $value = null) {
			if (is_array($field)) {
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
			return $this->_Data[$source]->query($this);
		}
		
		public function __get($name) {
			if (property_exists($this, $prop = '_'.$name)) {
				return $this->$prop;
			} else {
				//@todo exception
				exit('getting invalid query property: '.$name);
			}
		}
		
		public function __set($name, $value) {
			if (property_exists($this, $prop = '_'.$name)) {
				$this->$prop = $value;
			} else {
				//@todo exception
				exit('setting invalid query property: '.$name);
			}
		}
	}