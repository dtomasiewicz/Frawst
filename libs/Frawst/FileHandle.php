<?php
	namespace Frawst;
	
	class FileHandle extends Base {
		private $__handle;
		
		public function __construct($handle) {
			$this->__handle = $handle;
		}
		
		public function eof() {
			return feof($this->__handle);
		}
		
		public function read($length) {
			return fread($this->__handle, $length);
		}
		
		public function close() {
			fclose($this->__handle);
		}
	}