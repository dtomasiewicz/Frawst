<?php
	namespace Frawst;
	
	class File {
		protected $_name;
		protected $_transferName;
		protected $_readHandle;
		
		public function __construct($name) {
			$this->_name = $name;
		}
		
		public function name() {
			return $this->_name;
		}
		
		public function exists() {
			return file_exists($this->_name);
		}
		
		public function size() {
			return $this->_name === null ? 0 : filesize($this->_name);
		}
		
		public function transferName($name = null) {
			if($name !== null) {
				$this->_transferName = $name;
			}
			
			return $this->_transferName === null ? basename($this->_name) : $this->_transferName;
		}
		
		public function open($mode) {
			return new FileHandle(fopen($this->_name, $mode));
		}
		
		public function read($send = false) {
			if($send) {
				return readfile($this->_name);
			} else {
				return file_get_contents($this->_name);
			}
		}
	}