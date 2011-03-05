<?php
	namespace Frawst;
	
	define('Frawst\\TEST_ROOT', dirname(__FILE__).'/');
	
	define('Frawst\\DOMAIN', 'example.com');
	define('Frawst\\APP_NAME', 'test');
	define('Frawst\\ROOT', dirname(dirname(__FILE__)).'/');
	define('Frawst\\APP_ROOT', ':no_exists/');
	define('Frawst\\WEB_ROOT', '/');
	define('Frawst\\URL_REWRITE', true);
	
	require_once '../bootstrap.php';
	
	spl_autoload_register(function($class) {
		if(file_exists($f = ROOT.'libs/'.str_replace('\\', DIRECTORY_SEPARATOR, $class).'.php')) {
			require_once $f;
		}
	});