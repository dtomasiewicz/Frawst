<?php
	namespace Frawst;
	use Frawst\Core\Loader,
	    Frawst\Core\Config,
	    Frawst\Core\LanguageException;
	
	require 'definitions.php';
	require 'libs/Frawst/Core/Base.php';
	require 'libs/Frawst/Core/Loader.php';
	
	spl_autoload_register(array('Frawst\Core\Loader', 'loadClass'));
	
	Loader::addBasePath(ROOT);
	Loader::addBasePath(APP_ROOT);
	
	if(!NO_CONFLICT) {
		if(ENVIRONMENT != 'production') {
			ini_set('display_errors', '1');
			error_reporting(E_ALL | E_STRICT);
			set_error_handler(function($code, $message, $file, $line) {
				throw new LanguageException($message, 0, $code, $file, $line);
			});
		} else {
			ini_set('display_errors', '0');
			error_reporting(E_NONE);
		}
		
		date_default_timezone_set(Config::read('frawst', 'timezone'));
		setlocale(LC_ALL, Config::read('frawst', 'locale'));
	}
	
	if(file_exists($bs = APP_ROOT.'bootstrap.php')) {
		require $bs;
	}
	