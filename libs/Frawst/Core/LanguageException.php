<?php
	namespace Frawst\Core;
	
	class LanguageException extends \Frawst\Core\Exception {
		public $severity;
		
		public function __construct($message, $code, $severity, $file, $line) {
			parent::__construct($message, $code);
			$this->severity = $severity;
			$this->file = $file;
			$this->line = $line;
		}
	}