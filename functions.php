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
	
	if(function_exists('get_magic_quotes_gpc') && get_magic_quotes_gpc()) {
	 	function stripslashes_deep($value) {
			$value = (is_array($value)) ?
				array_map('stripslashes_deep', $value) :
				stripslashes($value);
			return $value;
		}
		
		// undo magic quotes		
		$_GET = stripslashes_deep($_GET);
		$_POST = stripslashes_deep($_POST);
		$_COOKIE = stripslashes_deep($_COOKIE);
	}
	
	function get_serial_class($serial) {
		$types = array('s' => 'string', 'a' => 'array', 'b' => 'bool', 'i' => 'int', 'd' => 'float', 'N;' => 'null');
		
		$parts = explode(':', $serial, 4);
		return isset($types[$parts[0]]) ? $types[$parts[0]] : trim($parts[2], '"'); 
	}