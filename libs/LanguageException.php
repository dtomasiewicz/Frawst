<?php
	namespace Frawst;
	
	class LanguageException extends \Frawst\Exception {
		public $severity;
		
		public function __construct($message, $code, $severity, $file, $line) {
			parent::__construct($message, $code);
			$this->severity = $severity;
			$this->file = $file;
			$this->line = $line;
		}
	}