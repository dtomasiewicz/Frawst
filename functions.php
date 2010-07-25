<?php
	use \Frawst\Loader,
		\Frawst\Exception;
	
	function __autoload($class) {
		Loader::import($class);
	}
	
	function error_handler($code, $message, $file, $line) {
		throw new Exception\Language($message, 0, $code, $file, $line);
	}
	set_error_handler('error_handler');
	
	function get_serial_class($serial) {
		$types = array('s' => 'string', 'a' => 'array', 'b' => 'bool', 'i' => 'int', 'd' => 'float', 'N;' => 'null');
		
		$parts = explode(':', $serial, 4);
		return isset($types[$parts[0]]) ? $types[$parts[0]] : trim($parts[2], '"'); 
	}