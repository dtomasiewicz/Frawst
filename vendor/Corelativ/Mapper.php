<?php
	namespace Corelativ;
	use \Corelativ\Factory,
		\DataPane;
	
	/**
	 * The main Corelativ ORM wrapper.
	 * @todo make a new type of exception for this
	 */
	class Mapper {
		protected $Data;
		protected $Cache;
		protected $config;
		private $factories = array();
		
		public function __construct ($config = array(), DataPane\Controller $data = null, $cache = null) {
			$this->Data = $data;
			$this->Cache = $cache;
			$this->config = $config;
			
			Model::$defaultMapper = $this;
		}
		
		public function factory ($modelName) {
			if (!isset($this->factories[$modelName])) {
				if (class_exists('\\Corelativ\\Model\\'.$modelName)) {
					$this->factories[$modelName] = new Factory(array('model' => $modelName), $this);
				} else {
					return false;
				}
			}
			
			return $this->factories[$modelName];
		}
		
		public function __get ($model) {
			return $this->factory($model);
		}
		
		public function getDataController () {
			return $this->Data;
		}
		
		public function getCacheController () {
			return $this->Cache;
		}
	}