<?php
	namespace Frawst;
	
	/**
	 * Frawst Framework Version 0.1 (development)
	 * Author: Daniel Tomasiewicz < www.fourstaples.com >
	 * 
	 * This is the main bootstrap file, through which everything happens.
	 */
	error_reporting(E_ALL);
	define('Frawst\\VERSION', '0.1dev');
	define('Frawst\\SCRIPT_START', microtime(true));
	
	/**
	 * Required definitions
	 */
	defined('Frawst\\APP_NAME')
		or define('Frawst\\APP_NAME', basename(dirname($_SERVER['SCRIPT_NAME'])));
	defined('Frawst\\DOMAIN')
		or define('Frawst\\DOMAIN', $_SERVER['HTTP_HOST']);
	defined('Frawst\\ROOT')
		or define('Frawst\\ROOT', dirname(__FILE__));	
	defined('Frawst\\APP_ROOT')
		or define('Frawst\\APP_ROOT', dirname(ROOT).DIRECTORY_SEPARATOR.APP_NAME);
	defined('Frawst\\WEB_ROOT')
		or define('Frawst\\WEB_ROOT', dirname($_SERVER['SCRIPT_NAME']));
	defined('Frawst\\AJAX_SUFFIX')
		or define('Frawst\\AJAX_SUFFIX', '___AJAX___');
	
	require('Loader.php');
	require('functions.php');
	
	/**
	 * Paths in which libraries can be held.
	 */
	Loader::addPath(ROOT, 'Frawst', 'core');
	Loader::addPath(ROOT.DIRECTORY_SEPARATOR.'vendor', '*', 'core');
	Loader::addPath(APP_ROOT, 'Frawst', 'app');
	Loader::addPath(APP_ROOT.DIRECTORY_SEPARATOR.'vendor', '*', 'app');
	//@todo this probably should be in an app-specific bootstrap since it's specific to Corelativ
	Loader::addPath(APP_ROOT.DIRECTORY_SEPARATOR.'Model', 'Corelativ\Model', 'app');
	
	/**
	 * App-specific bootstrapping
	 */
	if(file_exists($file = APP_ROOT.DIRECTORY_SEPARATOR.'bootstrap.php')) {
		require($file);
	}
	
	date_default_timezone_set(Config::read('general.timezone'));
	setlocale(LC_ALL, Config::read('general.locale'));
	
	$data = null;
	$mapper = null;
	$cache = null;
	if($cacheConfig = Config::read('cache')) {
		$c = $cacheConfig['controller'];
		$cache = new $c($cacheConfig);
	}
	if($dataConfig = Config::read('data')) {
		$c = $dataConfig['controller'];
		$data = new $c($dataConfig, $cache);
	}
	if($ormConfig = Config::read('orm')) {
		$c = $ormConfig['mapper'];
		$mapper = new $c($ormConfig, $data, $cache);
	}
	
	$method = $_SERVER['REQUEST_METHOD'];
	if($method == 'GET') {
		$requestData = $_GET;
	} elseif($method == 'POST') {
		$requestData = $_POST;
	} else {
		$requestData = array();
		parse_str(file_get_contents('php://input'), $requestData);
	}
	
	/**
	 * I HATE YOU MAGIC QUOTES
	 */
	if(function_exists('get_magic_quotes_gpc') && get_magic_quotes_gpc()) {
	 	function stripslashes_deep($value) {
			$value = (is_array($value)) ?
				array_map('stripslashes_deep', $value) :
				stripslashes($value);
			return $value;
		}
		
		$requestData = stripslashes_deep($requestData);
		$_COOKIE = stripslashes_deep($_COOKIE);
		// these two lines could be taken out... if they are, accessing
		// form data with $_GET and $_POST will be inconsistent, but it's
		// also unneccessary
		$_GET = stripslashes_deep($_GET);
		$_POST = stripslashes_deep($_POST);
	}
	
	$route = isset($_SERVER['PATH_INFO'])
		? $_SERVER['PATH_INFO']
		: '';
		
	$headers = array();
	if(substr($route, -strlen(AJAX_SUFFIX)) == AJAX_SUFFIX) {
		// hack to get redirected ajax requests working in Firefox
		$headers['X-Requested-With'] = 'XMLHttpRequest';
		$route = substr($route, 0, -strlen(AJAX_SUFFIX));
	} elseif(isset($_SERVER['HTTP_X_REQUESTED_WITH'])) {
		$headers['X-Requested-With'] = $_SERVER['HTTP_X_REQUESTED_WITH'];
	}
	
	echo Request::make($route, $method, $requestData, $headers, $data, $mapper, $cache);