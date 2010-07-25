<?php
	namespace Frawst\Exception;
	
	class Language extends Frawst {
		private $severity;
		
		public function __construct($message, $code, $severity, $file, $line) {
			parent::__construct($message, $code);
			$this->severity = $severity;
			$this->file = $file;
			$this->line = $line;
		}
	}