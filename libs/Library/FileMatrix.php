<?php
	namespace Frawst\Library;
	use \Frawst\Exception;
		
	class FileMatrix extends Matrix {
		protected $_directory;
		
		public function __construct($directory, $data = array()) {
			parent::__construct($data);
			$this->_directory = $directory;
		}
		
		public function offsetExists($offset) {
			if (parent::offsetExists($offset)) {
				return true;
			} else {
				$path = $this->_directory.str_replace('.', DIRECTORY_SEPARATOR, $offset);
				return file_exists($path);
			}
		}
		
		public function offsetGet($offset) {
			if (parent::offsetExists($offset)) {
				return parent::offsetGet($offset);
			}
			
			$path = $this->_directory.str_replace('.', DIRECTORY_SEPARATOR, $offset);
			
			if (!file_exists($path) || !is_readable(dirname($path))) {
				throw new Exception\File('Cannot get value from FileMatrix at: '.$offset);
			}
			
			$data = self::read($path);
			parent::offsetSet($offset, $data);
			
			return $data;
		}
		
		public function offsetSet($offset, $value) {
			$path = $this->_directory.str_replace('.', DIRECTORY_SEPARATOR, $offset);
			
			if (!file_exists(dirname($path))) {
				mkdir(dirname($path), 0, true);
			}
			
			if (!is_writable(dirname($path))) {
				throw new Exception\File('Cannot set value to FileMatrix at '.$offset.': Directory is not writable.');
			}
			
			file_put_contents($path, serialize($value));
			parent::offsetSet($offset, $value);
		}
		
		public function offsetUnset($offset) {
			$path = $this->_directory.str_replace('.', DIRECTORY_SEPARATOR, $offset);
			
			if (file_exists($path)) {
				if (is_writable($path)) {
					if (is_dir($path)) {
						rmdir($path);
					} else {
						unlink($path);
					}
					parent::offsetUnset($offset);
				} else {
					throw new Exception\File('Cannot unset value from FileMatrix at '.$offset.': Directory is not writable.');
				}
			}
		}
		
		private function read($path) {
			if (is_dir($path)) {
				$data = array();
				$handle = opendir($path);
				while (false !== ($file = readdir($handle))) {
					if ($file != '.' && $file != '..') {
						$data[$file] = self::read($path.DIRECTORY_SEPARATOR.$file);
					}
				}
				closedir($handle);
			} else {
				$data = unserialize(file_get_contents($path));
			}
			return $data;
		}
	}