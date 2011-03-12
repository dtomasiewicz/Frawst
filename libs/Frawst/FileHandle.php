<?php
	namespace Frawst;
	
	class FileHandle extends Base {
		private $handle;
		
		public function __construct($handle) {
			$this->handle = $handle;
		}
		
		public function eof() {
			return feof($this->handle);
		}
		
		public function read($length) {
			return fread($this->handle, $length);
		}
		
		public function close() {
			fclose($this->handle);
		}
	}