<?php
	namespace Frawst;
	
	class File extends Base implements FileInterface {
		private $name;
		private $transferName;
		
		public function __construct($name) {
			$this->name = $name;
			$this->transferName = null;
		}
		
		public static function factory($name) {
			return new File($name);
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
				$this->setTransferName($name);
			}
			
			return $this->transferName === null ? basename($this->name) : $this->transferName;
		}
		
		public function setTransferName($name) {
			$this->transferName = $name;
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