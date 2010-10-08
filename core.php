<?php
	/**
	 * Frawst Framework Version 0.1 (development)
	 * Author: Daniel Tomasiewicz < www.fourstaples.com >
	 * 
	 * This is the main bootstrap file, in which environment variables are set,
	 * the autoloader is registered, and the top-level HTTP request is handled.
	 */
	
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
		define('Frawst\\ROOT', dirname(__FILE__));
	}
		
	if(!defined('Frawst\\APP_ROOT')) {
		define('Frawst\\APP_ROOT', dirname(ROOT).DIRECTORY_SEPARATOR.APP_NAME);
	}
	
	if(!defined('Frawst\\WEB_ROOT')) {
		define('Frawst\\WEB_ROOT', rtrim(dirname($_SERVER['SCRIPT_NAME']), '/'));
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
	
	require 'Loader.php';
	spl_autoload_register(array('Frawst\\Loader', 'import'));
	
	ini_set('display_errors', '1');
	error_reporting(E_ALL | E_STRICT);
	set_error_handler(function($code, $message, $file, $line) {
		throw new Language($message, 0, $code, $file, $line);
	});
	
	// Paths in which libraries can be held.
	Loader::addPath(ROOT, 'Frawst', 'core');
	Loader::addPath(ROOT.DIRECTORY_SEPARATOR.'vendor', '*', 'core');
	Loader::addPath(APP_ROOT, 'Frawst', 'app');
	Loader::addPath(APP_ROOT.DIRECTORY_SEPARATOR.'vendor', '*', 'app');
	
	// Additional bootstrapping
	Loader::import('Frawst\bootstrap');
	
	date_default_timezone_set(Config::read('Frawst.timezone'));
	setlocale(LC_ALL, Config::read('Frawst.locale'));
	
	$method = $_SERVER['REQUEST_METHOD'];
	if ($method == 'GET') {
		$data = $_GET;
	} elseif ($method == 'POST') {
		$data = $_POST;
	} else {
		parse_str(file_get_contents('php://input'), $data = array());
	}
	
	// REST hack for browsers that don't support all methods. only works if the
	// originating script passes this magic parameter, of course
	if (isset($data['___METHOD']) && in_array($m = strtoupper($data['___METHOD']), array('GET', 'POST', 'PUT', 'DELETE'))) {
		$method = $m;
		unset($data['___METHOD']);
	}
	
	// I HATE YOU MAGIC QUOTES
	if (function_exists('get_magic_quotes_gpc') && get_magic_quotes_gpc()) {
	 	function stripslashes_deep($value) {
			$value = (is_array($value)) ?
				array_map('Frawst\\stripslashes_deep', $value) :
				stripslashes($value);
			return $value;
		}
		
		$requestData = stripslashes_deep($data);
		$_COOKIE = stripslashes_deep($_COOKIE);
		$_GET = stripslashes_deep($_GET);
		$_POST = stripslashes_deep($_POST);
	}
	
	// Pull request route, method, and variables from $_SERVER
	$headers = array();
	$route = ltrim(isset($_SERVER['PATH_INFO'])
		? $_SERVER['PATH_INFO']
		: '', '/');
	
	// Extract HTTP headers from the ugly $_SERVER array
	foreach($_SERVER as $key => $value) {
		if(strpos($key, 'HTTP_') === 0) {
			$headers[str_replace(' ', '-', ucwords(str_replace('_', ' ', strtolower(substr($key, 5)))))] = $value;
		}
	}
	
	// hack to get redirected AJAX requests working in Firefox
	// and other browsers that don't re-send non-standard headers
	// when redirected
	if (substr($route, -strlen(AJAX_SUFFIX)) == AJAX_SUFFIX) {
		$headers['X-Requested-With'] = 'XMLHttpRequest';
		$route = substr($route, 0, -strlen(AJAX_SUFFIX));
	}
	
	$request = new Request(new Route($route, true), $data, $method, $headers);
	$request->execute()->send();