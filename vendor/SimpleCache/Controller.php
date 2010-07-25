<?php
	namespace SimpleCache;
	use \Frawst\Library\Matrix;
	
	class Controller {
		protected $Engine;
		protected $config;
		protected $noCache;
		
		public function __construct($config = array()) {
			// defaults
			$this->config = $config + array(
				'engine' => 'File',
				'enable' => false,
				'noCache' => array()
			);
			
			$this->noCache = $this->config['noCache'];
			
			if($this->config['enable']) {
				$class = '\\SimpleCache\\Engine\\'.$this->config['engine'];
				$this->Engine = new $class($config);
			}
		}
		public function read($name) {
			if($this->config['enable'] && !$this->noCache($name)) {
				return $this->Engine->read($name);
			}
		}
		public function write($name, $value = null, $expires = null) {
			if($this->config['enable'] && !$this->noCache($name)) {
				$this->Engine->write($name, $value, $expires);
				return true;
			}
			return false;
		}
		private function noCache($name) {
			$parts = explode('.', $name);
			$path = array();
			while(count($parts) > 0) {
				$path[] = array_shift($parts);
				$p = implode('.', $path);
				
				if(Matrix::pathExists($this->noCache, $p) && Matrix::pathGet($this->noCache, $p) === true) {
					return true;
				}
			}
			return false;
		}
	}