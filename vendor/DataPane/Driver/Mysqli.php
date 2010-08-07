<?php
	namespace DataPane\Driver;
	use \DataPane\Driver,
		\DataPane\Query,
		\DataPane\Condition,
		\DataPane\ConditionSet,
		\DataPane\Result;
	
	/**
	 * MySQLi Datasource implementation
	 */
	class Mysqli extends Driver {
		private $link;
		
		public function connect() {
			$this->link = new \mysqli($this->config['host'], $this->config['user'], $this->config['password'], $this->config['database']);
		}
		
		public function close() {
			return $this->link->close();
		}
		
		public function query($q) {
			if ($q instanceof Query) {
				// query object
				$sql = $this->parseQuery($q);
			} else {
				$sql = $q;
			}
			echo $sql.'<br>';
			$results = $this->link->query($sql);
			
			if ($results instanceof \MySQLi_Result) {
				$results = new Result\Mysqli($results);
				
				if ($q instanceof Query && $q->type == 'count') {
					$results = (int) $results[0]['COUNT(*)'];
				}
			}
			
			return $results;
		}
		
		/**
		 * Parses a Query object into MySQL syntax
		 */
		private function parseQuery(Query $query) {
			if ($query->type == 'select') {
				$sql = 'SELECT '.$this->parseFields($query->fields).' FROM '.
					$this->parseTables($query->tables);
					
				if (strlen($cSql = $this->parseConditions($query->where)))
					$sql .= ' WHERE '.$cSql;
				
				if (count($query->group))
					$sql .= ' GROUP BY '.$this->parseGroup($query->group);
				
				if (strlen($cSql = $this->parseConditions($query->having)))
					$sql .= ' HAVING '.$cSql;
				
				if (count($query->order))
					$sql .= ' ORDER BY '.$this->parseOrder($query->order);
				
				if (!is_null($query->limit))
					$sql .= ' LIMIT '.$this->parseLimit($query->limit, $query->offset);
			}
			elseif ($query->type == 'count') {
				$sql = 'SELECT COUNT(*) FROM '.
					$this->parseTables($query->tables);
				
				if (strlen($cSql = $this->parseConditions($query->where)))
					$sql .= ' WHERE '.$cSql;
			}		
			elseif ($query->type == 'insert' || $query->type == 'replace') {
				$sql = strtoupper($query->type).' ';
				
				if (count($query->options)) {
					$sql .= implode(' ', $query->options).' ';
				}
				
				$sql .= 'INTO '.$this->parseTables($query->tables).' ('.$this->parseFields(array_keys($query->values)).') VALUES ("'.
					implode('","', array_map(array($this, 'escape'), $query->values)).'")';
			}
			elseif ($query->type == 'delete') {
				$sql = 'DELETE FROM '.$this->parseTables($query->tables);
				
				if (strlen($cSql = $this->parseConditions($query->where)))
					$sql .= ' WHERE '.$cSql;
				
				if (count($query->order))
					$sql .= ' ORDER BY '.$this->parseorder($query->order);
				
				if (!is_null($query->limit))
					$sql .= ' LIMIT '.$this->parseLimit($query->limit, $query->offset);
			}
			elseif ($query->type == 'update') {
				$sql = 'UPDATE '.$this->parseTables($query->tables).' SET ';
				foreach ($query->values as $field => $value) {
					$sql .= $field.' = "'.$this->escape($value).'",';
				}
				$sql = rtrim($sql, ',');
				
				if (strlen($cSql = $this->parseConditions($query->where)))
					$sql .= ' WHERE '.$cSql;
				
				if (count($query->order))
					$sql .= ' ORDER BY '.$this->parseorder($query->order);
				
				if (!is_null($query->limit))
					$sql .= ' LIMIT '.$this->parseLimit($query->limit, $query->offset);
			}
			else throw new Exception\Data('Unidentified query type: '.$query->type);
			
			return $sql;
		}
		
		private function parseFields($fields) {
			if (is_array($fields) && count($fields)) {
				$sql = '';
				foreach ($fields as $key => $value) {
					if (!is_numeric($key)) {
						$sql .= $value.' AS '.$key;
					} else {
						$sql .= $value;
					}
					$sql .= ',';
				}
				
				return substr($sql, 0, -1);
			}
			else return '*';
		}
		
		private function parseTables($tables) {
			$sql = '';
			
			foreach ($tables as $alias => $table) {
				if ($table instanceof Query) {
					$sql .= '('.$this->parseQuery($table).')';
				} else {
					$sql .= $table;
				}
				if (is_string($alias)) {
					$sql .= ' AS '.$alias;
				}
				$sql .= ', ';
			}
			
			return substr($sql, 0, -2);
		}		
		
		/**
		 * Parses a ConditionSet object into SQL
		 */
		private function parseConditions(ConditionSet $conditions) {
			$sql = '';
			
			foreach ($conditions as $condition) {
				if ($condition instanceof ConditionSet && count($condition)) {
					$sql .= '('.$this->parseConditions($condition).') '.$conditions->operand.' ';
				} elseif ($condition instanceof Condition) {
					$sql .= $this->parseCondition($condition).' '.$conditions->operand.' ';
				}
			}
			return substr($sql, 0, -2-strlen($conditions->operand));
		}
		
		private function parseCondition(Condition $condition) {
			if (!is_null($condition->sql)) {
				return $condition->sql;
			}
			
			$field = $condition->field;
			$operator = $condition->operator;
			$value = $condition->value;
			
			if (is_array($value)) {
				if ($operator == 'BETWEEN') {
					$value = $this->parseValue($value[0], $condition->quote).' AND '.$this->parseValue($value[1], $condition->quote);
				} else {
					$value = $this->parseValue($value, $condition->quote);
				}
			} else {
				$value = $this->parseValue($value, $condition->quote);
			}
			
			if ($operator == 'SEARCH') {
				$field = 'MATCH('.$field.')';
				$operator = 'AGAINST';
				$value = '('.$value.' IN BOOLEAN MODE)';
			}
			
			return $field.' '.$operator.' '.$value;
		}
		
		private function parseValue($value, $quote = true) {
			if ($value instanceof Query) {
				return '('.$this->parseQuery($value).')';
			} elseif (is_array($value)) {
				return '('.implode(',', array_map(array($this, 'parseValue'), $value)).')';
			} else {
				return $quote ? $this->quote($value) : $value;
			}
		}
		
		private function quote($value) {
			if (is_string($value)) {
				return '"'.$this->escape($value).'"';
			} else {
				return $value;
			}
		}
	
		private function parseGroup($group) {
			return implode(',', $group);
		}
		
		/**
		 * Parses order instructions
		 */
		private function parseOrder($order) {
			$sql = '';
			foreach ($order as $field => $direction) {
				if (!is_string($field)) {
					$field = $direction;
					$direction = 'ASC';
				}
				$sql .= $field.' '.$direction.',';
			}
			return substr($sql, 0, -1);
		}
		
		/**
		 * Parses limiting instructions
		 */
		private function parseLimit($limit, $offset = null) {
			$sql = $limit;
			if ($offset != 0) {
				$sql = $offset.','.$sql;
			}
			return $sql;
		}
		
		/**
		 * Description of fields
		 */
		public function schema($table) {
			$schema = array();
			if ($fields = $this->query('DESCRIBE '.$table)) {
				foreach ($fields as $field) {
					$schema[$field['Field']] = $field;
				}
				return $schema;
			} else {
				throw new Exception\Data($this->error());
			}
		}
		
		/**
		 * Determines the default value for a field, given a
		 * field description.
		 */
		public function defaultValue($desc) {
			if (!is_null($desc['Default'])) {
				return $desc['Default'];
			} elseif ($desc['Null'] != 'NO') {
				return null;
			} else {
				$type = $desc['Type'];
				if (false !== $pos = strpos($type, '(')) {
					$type = substr($type, 0, $pos);
				}
				switch ($type) {
					case 'int':
						return 0;
					case 'datetime':
						return '0000-00-00 00:00:00';
					case 'date':
						return '0000-00-00';
					default:
						return '';
				}
			}
		}
		
		/**
		 * Escapes data for safe injection into SQL
		 */
		public function escape($string) {
			return $this->link->real_escape_string($string);
		}
		
		public function error() {
			return $this->link->error;
		}
		
		public function insertId() {
			return $this->link->insert_id;
		}
	}
	/*
	class MysqliDebug extends Debug {
		private $executionTime;
		
		public function __construct($message, $executionTime) {
			parent::__construct('MySQLi', $message);
			$this->executionTime = round($executionTime, 6);
		}
		
		public function __toString() {
			return parent::__toString().' [execution time: '.$this->executionTime.']';
		}
	}*/