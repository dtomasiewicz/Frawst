<?php
	namespace Frawst;
	
	define('Frawst\\VERSION', '0.1-dev');
	
	if(!defined('Frawst\\SCRIPT_START')) {
		define('Frawst\\SCRIPT_START', microtime(true));
	}
	
	if(!defined('Frawst\\APP_NAME')) {
		define('Frawst\\APP_NAME', basename(dirname(dirname($_SERVER['SCRIPT_FILENAME']))));
	}
	
	if(!defined('Frawst\\DOMAIN')) {
		define('Frawst\\DOMAIN', $_SERVER['HTTP_HOST']);
	}
	
	if(!defined('Frawst\\ROOT')) {
		define('Frawst\\ROOT', dirname(__FILE__).DIRECTORY_SEPARATOR);
	}
		
	if(!defined('Frawst\\APP_ROOT')) {
		define('Frawst\\APP_ROOT', dirname(ROOT).DIRECTORY_SEPARATOR.APP_NAME.DIRECTORY_SEPARATOR);
	}
	
	if(!defined('Frawst\\WEB_ROOT')) {
		define('Frawst\\WEB_ROOT', rtrim(dirname($_SERVER['SCRIPT_NAME']), '/').'/');
	}
	
	if(!defined('Frawst\\AJAX_SUFFIX')) {
		define('Frawst\\AJAX_SUFFIX', '___AJAX___');
	}
	
	if(!defined('Frawst\\URL_REWRITE')) {
		if(function_exists('apache_get_modules') && in_array('mod_rewrite', apache_get_modules())) {
			define('Frawst\\URL_REWRITE', true);
		} else {
			define('Frawst\\URL_REWRITE', false);
		}
	}