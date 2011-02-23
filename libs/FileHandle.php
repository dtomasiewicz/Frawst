<?php
	namespace Frawst;
	
	class FileHandle {
		protected $_handle;
		
		public function __construct($handle) {
			$this->_handle = $handle;
		}
		
		public function eof() {
			return feof($this->_handle);
		}
		
		public function read($length) {
			return fread($this->_handle, $length);
		}
		
		public function close() {
			fclose($this->_handle);
		}
	}