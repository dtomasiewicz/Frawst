<?php
	namespace Corelativ;
	use \DataPane;
	
	class Factory {
		protected $Mapper;
		protected $Data;
		protected $Cache;
		protected $Object;
		
		public function __construct($config, $mapper) {
			$this->Mapper = $mapper;
			$this->Data = $mapper->getDataController();
			$this->Cache = $mapper->getCacheController();
			$modelName = $config['model'];
			$modelClass = '\\Corelativ\\Model\\'.$modelName;
			$this->Object = new $modelClass($this, $this->Mapper);
		}
		
		public function find($params = array()) {
			$params = $this->normalizeParams($params);
			$params->limit = 1;
			
			if(($result = $this->findAll($params)) && count($result) > 0) {
				return $result[0];
			} else {
				return false;
			}
		}
		
		public function findAll($params = array()) {
			$params = $this->normalizeParams($params);
			
			if($params = $this->beforeFind($params)) {
				if($results = $params->exec($this->Object->dataSource())) {
					$return = new ModelSet($this->Object->modelName());
					
					if($params->paginated()) {
						$return->page = $params->page;
						$params->type = 'count';
						$return->totalRecords = $params->exec($this->Object->dataSource());
						$return->totalPages = ceil($return->totalRecords / $params->limit);
					}
					
					$class = get_class($this->Object);
					foreach($results as $result) {
						$return[] = new $class($result, $this->Mapper);
					}
					
					return $return;
				} else {
					//@todo exception
					exit('Error in find operation: '.$this->Data->error($this->Object->dataSource()));
				}
			}
		}
		
		public function delete($params = array()) {
			$params = $this->normalizeParams($params, 'delete');
			$params->limit = 1;
			
			return $params->exec($this->Object->dataSource());
		}
		
		public function deleteAll($params = array()) {
			$params = $this->normalizeParams($params, 'delete');
			
			return $params->exec($this->Object->dataSource());
		}
		
		/**
		 * Normalizes find parameters to a ModelQuery object
		 */
		protected function normalizeParams($params, $type = 'select') {
			if(!($params instanceof DataPane\Query)) {
				if($params instanceof DataPane\ConditionSet) {
					$params = array('where' => $params);
				}
				$params = new ModelQuery($type, $this->Object->tableName(), $params, $this->Data);
			}
			return $params;
		}
		
		public function create($data = array()) {
			$class = get_class($this->Object);
			$model = new $class(array(), $this->Mapper);
			$model->set($data);
			return $model;
		}
		
		/**
		 * Magic methods to allow this factory to behave transparently
		 * as an empty instance of the model it creates.
		 */
		public function __call($method, $args) {
			if(method_exists($this->Object, $method)) {
				return call_user_func_array(array($this->Object, $method), $args);
			} else {
				if(substr($method, 0, 6) == 'findBy') {
					$mode = 'findBy';
				} elseif(substr($method, 0, 9) == 'findAllBy') {
					$mode = 'findAllBy';
				} elseif(substr($method, 0, 8) == 'deleteBy') {
					$mode = 'deleteBy';
				} elseif(substr($method, 0, 11) == 'deleteAllBy') {
					$mode = 'deleteAllBy';
				}
				
				if(isset($mode)) {
					$field = lcfirst(substr($method, strlen($mode)));
					$value = array_shift($args);
					if(!isset($args[0])) {
						$args[0] = array();
					}
					$args[0] = $this->normalizeParams($args[0]);
					$args[0]->where = new DataPane\ConditionSet(array($field => $value, $args[0]->where));
					return call_user_func_array(array($this, substr($mode, 0, -2)), $args);
				} else {
					//@todo exception
					exit('invalid model/factory method: '.$method);
				}
			}
		}
		public function __get($name) {
			return $this->Object->$name;
		}
		public function __set($name, $value) {
			$this->Object->$name = $value;
		}
	}