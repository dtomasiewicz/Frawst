<?php
	namespace Frawst;
	
	interface FileHandleInterface {
		public static function factory($handle);
		public function eof();
		public function read($length);
		public function close();
	}