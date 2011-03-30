<?php
	namespace Frawst;
	
	interface FileInterface {
		public static function factory($name);
		public function name();
		public function exists();
		public function size();
		public function transferName();
		public function setTransferName();
		/**
		 * @param $mode The mode to open the file in. See php.net/fopen
		 * @return FileHandleInterface
		 */
		public function open($mode);
		public function read($send);
	}