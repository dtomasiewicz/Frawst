<?php
	namespace SimpleCache\Engine;
	use \Frawst\Library\Matrix,
		\Frawst\Library\FileMatrix,
		\SimpleCache\Engine,
		\Frawst\Exception;
	
	class File extends Engine {
		protected $_directory;
		protected $_data;
		protected $_expire;
		
		public function __construct ($config) {
			$this->_directory = $config['directory'];
			$this->_data = new FileMatrix($this->_directory);
			$this->_expire = isset($this->_data['__expire'])
				? $this->_data['__expire']
				: array();
		}
		
		public function expires ($name, $time = null) {
			if (!is_null($time)) {
				if ($time > time() || $time == 0) {
					Matrix::pathSet($this->_expire, $name, $time);
				} else {
					Matrix::pathUnset($this->_expire, $name);
				}
				$this->_data['__expire'] = $this->_expire;
			}
			
			return Matrix::pathExists($this->_expire, $name)
				? Matrix::pathGet($this->_expire, $name)
				: null;
		}
		
		public function get ($name) {
			return $this->exists($name)
				? $this->_data[$name]
				: null;
		}
		
		public function exists ($name) {
			if (!is_null($expire = $this->expires($name))) {
				if ($expire == 0 || $expire > time()) {
					return true;
				}
			}
			
			return false;
		}
		
		public function set ($name, $value, $life = 0) {
			if ($life != 0) {
				$life += time();
			}
			echo 'writing '.$name;
			$this->_data[$name] = $value;
			$this->expires($name, $life);
		}
		
		public function expire ($name) {
			unset($this->_data[$name]);
			$this->expires($name, time()-3600);
		}
	}