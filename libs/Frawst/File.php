<?php
	namespace Frawst;
	
	class File extends Object {
		private $__name;
		private $__transferName;
		
		public function __construct($name) {
			$this->__name = $name;
			$this->__transferName = null;
		}
		
		public function name() {
			return $this->__name;
		}
		
		public function exists() {
			return file_exists($this->__name);
		}
		
		public function size() {
			return $this->exists() ? filesize($this->__name) : 0;
		}
		
		public function transferName($name = null) {
			if($name !== null) {
				$this->__transferName = $name;
			}
			
			return $this->__transferName === null ? basename($this->__name) : $this->__transferName;
		}
		
		public function open($mode) {
			return new FileHandle(fopen($this->__name, $mode));
		}
		
		public function read($send = false) {
			if($send) {
				return readfile($this->__name);
			} else {
				return file_get_contents($this->__name);
			}
		}
	}