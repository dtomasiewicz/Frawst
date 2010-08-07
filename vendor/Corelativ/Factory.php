<?php
	namespace Corelativ;
	use \DataPane;
	
	class Factory {
		protected $_Mapper;
		protected $_Data;
		protected $_Cache;
		protected $_Object;
		
		public function __construct($config, $mapper) {
			$this->_Mapper = $mapper;
			$this->_Data = $mapper->Data;
			$this->_Cache = $mapper->Cache;
			
			$class = '\\Corelativ\\Model\\'.$config['model'];
			$this->_Object = new $class($this, $this->_Mapper);
		}
		
		public function __get($name) {
			switch($name) {
				case 'Mapper':
					return $this->_Mapper;
				case 'Data':
					return $this->_Data;
				case 'Cache':
					return $this->_Cache;
				case 'Object':
					return $this->_Object;
				default:
					return $this->_Object->$name;
			}
		}
		
		public function find($params = array()) {
			$params = $this->_normalizeParams($params);
			$params->limit = 1;
			
			if (($result = $this->findAll($params)) && count($result) > 0) {
				return $result[0];
			} else {
				return false;
			}
		}
		
		public function findAll($params = array()) {
			$params = $this->_normalizeParams($params);
			
			if ($params = $this->beforeFind($params)) {
				if ($results = $params->exec($this->_Object->dataSource())) {
					$return = new ModelSet($this->_Object->modelName());
					
					if ($params->paginated()) {
						$return->page = $params->page;
						$params->type = 'count';
						$return->totalRecords = $params->exec($this->_Object->dataSource());
						$return->totalPages = ceil($return->totalRecords / $params->limit);
					}
					
					$class = get_class($this->_Object);
					foreach ($results as $result) {
						$return[] = new $class($result, $this->_Mapper);
					}
					
					return $return;
				} else {
					//@todo exception
					exit('Error in find operation: '.$this->_Data[$this->_Object->dataSource()]->error());
				}
			}
		}
		
		public function delete($params = array()) {
			$params = $this->_normalizeParams($params, 'delete');
			$params->limit = 1;
			
			return $params->exec($this->_Object->dataSource());
		}
		
		public function deleteAll($params = array()) {
			$params = $this->_normalizeParams($params, 'delete');
			
			return $params->exec($this->_Object->dataSource());
		}
		
		/**
		 * Normalizes find parameters to a ModelQuery object
		 */
		protected function _normalizeParams($params, $type = 'select') {
			if (!($params instanceof DataPane\Query)) {
				if ($params instanceof DataPane\ConditionSet) {
					$params = array('where' => $params);
				}
				$params = new ModelQuery($type, $this->_Object->tableName(), $params, $this->_Data);
			}
			return $params;
		}
		
		public function create($data = array()) {
			$class = get_class($this->_Object);
			$model = new $class(array(), $this->_Mapper);
			$model->set($data);
			return $model;
		}
		
		/**
		 * Magic methods to allow this factory to behave transparently
		 * as an empty instance of the model it creates.
		 */
		public function __call($method, $args) {
			if (method_exists($this->_Object, $method)) {
				return call_user_func_array(array($this->_Object, $method), $args);
			} else {
				if (substr($method, 0, 6) == 'findBy') {
					$mode = 'findBy';
				} elseif (substr($method, 0, 9) == 'findAllBy') {
					$mode = 'findAllBy';
				} elseif (substr($method, 0, 8) == 'deleteBy') {
					$mode = 'deleteBy';
				} elseif (substr($method, 0, 11) == 'deleteAllBy') {
					$mode = 'deleteAllBy';
				}
				
				if (isset($mode)) {
					$field = lcfirst(substr($method, strlen($mode)));
					$value = array_shift($args);
					if (!isset($args[0])) {
						$args[0] = array();
					}
					$args[0] = $this->_normalizeParams($args[0]);
					$args[0]->where = new DataPane\ConditionSet(array($field => $value, $args[0]->where));
					return call_user_func_array(array($this, substr($mode, 0, -2)), $args);
				} else {
					//@todo exception
					exit('invalid model/factory method: '.$method);
				}
			}
		}
	}