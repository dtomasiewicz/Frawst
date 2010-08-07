<?php
	namespace Corelativ;
	use \Corelativ\Factory,
		\DataPane;
	
	/**
	 * The main Corelativ ORM wrapper.
	 * @todo make a new type of exception for this
	 */
	class Mapper {
		protected $_Data;
		protected $_Cache;
		protected $_config;
		protected $_factories = array();
		
		public function __construct($config = array(), DataPane\Controller $data = null, $cache = null) {
			$this->_Data = $data;
			$this->_Cache = $cache;
			$this->_config = $config;
			
			Model::$defaultMapper = $this;
		}
		
		public function factory($modelName) {
			if (!isset($this->_factories[$modelName])) {
				if (class_exists('\\Corelativ\\Model\\'.$modelName)) {
					$this->_factories[$modelName] = new Factory(array('model' => $modelName), $this);
				} else {
					$this->_factories[$modelName] = false;
				}
			}
			
			return $this->_factories[$modelName];
		}
		
		public function __get($name) {
			switch($name) {
				case 'Data':
					return $this->_Data;
				case 'Cache':
					return $this->_Cache;
				default:
					if($factory = $this->factory($name)) {
						return $factory;
					} else {
						//@todo exception
						exit('Invalid ORM property: '.$name);
					}
			}
		}
	}