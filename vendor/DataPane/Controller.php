<?php
	namespace DataPane;
	use \DataPane\Query;
	
	class Controller {
		private $sources = array();
		protected $config;
		protected $Cache;
		
		public function __construct($config, $cache = null) {
			$this->config = $config;
			$this->Cache = $cache;
		}
		
		public function source($name = 'default') {
			if(isset($this->sources[$name])) {
				return $this->sources[$name];
			} elseif(isset($this->config[$name])) {
				// attempt to open the datasource
				$class = '\\DataPane\\Driver\\'.$this->config[$name]['driver'];
				$this->sources[$name] = new $class($this->config[$name]);
				$this->sources[$name]->connect();
				return $this->sources[$name];
			} else {
				//@todo exception
				exit('Invalid data source: '.$name);
			}
		}
		
		public function query($query, $source = 'default') {
			return $this->source($source)->query($query);
		}
		
		public function schema($table, $source = 'default') {
			return $this->source($source)->schema($table);
		}
		
		public function defaultValue($desc, $source = 'default') {
			return $this->source($source)->defaultValue($desc);
		}
		
		public function insertId($source = 'default') {
			return $this->source($source)->insertId();
		}
		
		public function error($source = 'default') {
			return $this->source($source)->error();
		}
		
		/**
		 * For chaining.
		 */
		public function select($tables, $fields = null) {
			if(!is_null($fields)) {
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