<?php
	namespace DataPane;
	use \DataPane\Query;
	
	class Controller implements \ArrayAccess {
		private $sources = array();
		protected $config;
		protected $Cache;
		protected $activeSource;
		
		public function __construct($config, $cache = null) {
			$this->config = $config;
			$this->Cache = $cache;
		}
		
		public function offsetGet($offset) {
			return $this->source($offset);
		}
		
		public function offsetSet($offset, $value) {
			//@todo exception
			exit('cannot set to an offset of a data controller');
		}
		
		public function offsetUnset($offset) {
			$this->sources[$offset]->close();
		}
		
		public function offsetExists($offset) {
			return array_key_exists($offset, $this->sources);
		}
		
		public function source($source = 'default') {
			if (!isset($this->sources[$source])) {
				if (isset($this->config[$source])) {
					// attempt to open the datasource
					$class = '\\DataPane\\Driver\\'.$this->config[$source]['driver'];
						$this->sources[$source] = new $class($this->config[$source]);
					$this->sources[$source]->connect();
				} else {
					//@todo exception
					exit('Invalid data source: '.$source);
				}
			}
			
			return $this->sources[$source];
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