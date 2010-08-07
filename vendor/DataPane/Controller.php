<?php
	namespace DataPane;
	use \DataPane\Query;
	
	class Controller implements \ArrayAccess {
		protected $_sources = array();
		protected $_config;
		protected $_Cache;
		
		public function __construct($config, $cache = null) {
			$this->_config = $config;
			$this->_Cache = $cache;
		}
		
		public function offsetGet($offset) {
			return $this->source($offset);
		}
		
		public function offsetSet($offset, $value) {
			//@todo exception
			exit('cannot set to an offset of a data controller');
		}
		
		public function offsetUnset($offset) {
			$this->_sources[$offset]->close();
		}
		
		public function offsetExists($offset) {
			return array_key_exists($offset, $this->_sources);
		}
		
		public function source($source = 'default') {
			if (!isset($this->_sources[$source])) {
				if (isset($this->_config[$source])) {
					// attempt to open the datasource
					$class = '\\DataPane\\Driver\\'.$this->_config[$source]['driver'];
						$this->_sources[$source] = new $class($this->_config[$source]);
					$this->_sources[$source]->connect();
				} else {
					//@todo exception
					exit('Invalid data source: '.$source);
				}
			}
			
			return $this->_sources[$source];
		}
		
		/**
		 * For chaining.
		 */
		public function select($tables, $fields = null) {
			if (!is_null($fields)) {
				$fields = (array) $fields;
			}
			return new Query('select', $tables, array('fields' => $fields), $this);
		}
		
		public function update($tables) {
			return new Query('update', $tables, array(), $this);
		}
		
		public function insert($tables) {
			return new Query('insert', $tables, array(), $this);
		}
		
		public function delete($tables) {
			return new Query('delete', $tables, array(), $this);
		}
		
		public function count($tables) {
			return new Query('count', $tables, array(), $this);
		}
		
		/**
		 * By default, call any not-found functions on the default connection.
		 */
		public function __call($method, $args) {
			return call_user_func_array(array($this->source(), $method), $args);
		}
	}