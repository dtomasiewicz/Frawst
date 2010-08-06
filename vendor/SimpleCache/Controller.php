<?php
	namespace SimpleCache;
	use \Frawst\Library\Matrix;
	
	class Controller {
		protected $_Engine;
		protected $_config;
		
		public function __construct ($config = array()) {
			// defaults
			$this->_config = $config + array(
				'engine' => 'File',
				'enable' => false,
				'noCache' => array()
			);
			
			if ($this->_config['enable']) {
				$class = '\\SimpleCache\\Engine\\'.$this->_config['engine'];
				$this->_Engine = new $class($config);
			}
		}
		
		public function get ($name) {
			if ($this->enabled() && $this->cacheable($name)) {
				return $this->_Engine->get($name);
			} else {
				return null;
			}
		}
		
		public function set ($name, $value, $life = 0) {
			if ($this->enabled() && $this->cacheable($name)) {
				$this->_Engine->set($name, $value, $life);
			}
		}
		
		public function expire ($name) {
			if ($this->enabled() && $this->cacheable($name)) {
				$this->_Engine->expire($name);
			}
		}
		
		public function enabled () {
			return $this->_config['enable'];
		}
		
		public function cacheable ($name) {
			$parts = explode('.', $name);
			$path = array();
			while (count($parts) > 0) {
				$path[] = array_shift($parts);
				$p = implode('.', $path);
				
				if (Matrix::pathExists($this->_config['noCache'], $p)
					&& Matrix::pathGet($this->_config['noCache'], $p) === true) {
					return false;
				}
			}
			return true;
		}
	}