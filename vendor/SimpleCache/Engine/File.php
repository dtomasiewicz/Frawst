<?php
	namespace SimpleCache\Engine;
	use \Frawst\Library\Matrix,
		\Frawst\Library\FileMatrix,
		\SimpleCache\Engine;
	
	class File extends Engine {
		private $directory;
		private $data;
		private $expires;
		
		public function __construct($config) {
			$this->directory = $config['directory'];
			$this->data = new FileMatrix($this->directory);
			
			if(file_exists($expires = $this->directory.DIRECTORY_SEPARATOR.'__expiration')) {
				$this->expires = unserialize(file_get_contents($expires));
			} else {
				$this->expires = array();
			}
		}
		
		private function setExpires($name, $time = null) {
			if(is_null($time)) {
				Matrix::pathUnset($this->expires, $name);
			} else {
				Matrix::pathSet($this->expires, $name, $time);
			}
			file_put_contents($this->directory.DIRECTORY_SEPARATOR.'__expiration', serialize($this->expires));
		}
		
		public function read($name) {
			if(!Matrix::pathExists($this->expires, $name) || Matrix::pathGet($this->expires, $name) < time()) {
				unset($this->data[$name]);
				$this->setExpires($name);
				return null;
			}
			
			return $this->data[$name];
		}
		
		public function write($name, $value = null, $expires = null) {
			if(is_numeric($expires)) {
				$expires += time();
			}
			$this->data[$name] = $value;
			$this->setExpires($name, $expires);
		}
	}