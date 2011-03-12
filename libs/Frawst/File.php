<?php
	namespace Frawst;
	
	class File extends Base {
		private $name;
		private $transferName;
		
		public function __construct($name) {
			$this->name = $name;
			$this->transferName = null;
		}
		
		public function name() {
			return $this->name;
		}
		
		public function exists() {
			return file_exists($this->name);
		}
		
		public function size() {
			return $this->exists() ? filesize($this->name) : 0;
		}
		
		public function transferName($name = null) {
			if($name !== null) {
				$this->transferName = $name;
			}
			
			return $this->transferName === null ? basename($this->name) : $this->transferName;
		}
		
		public function open($mode) {
			return new FileHandle(fopen($this->name, $mode));
		}
		
		public function read($send = false) {
			if($send) {
				return readfile($this->name);
			} else {
				return file_get_contents($this->name);
			}
		}
	}