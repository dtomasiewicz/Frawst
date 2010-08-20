<?php
	/**
	 * Frawst Framework Version 0.1 (development)
	 * Author: Daniel Tomasiewicz < www.fourstaples.com >
	 * 
	 * This is the main bootstrap file, through which everything happens.
	 */
	
	namespace {
		use \Frawst\Loader,
			\Frawst\Exception;
			
		error_reporting(E_ALL);
		define('Frawst\\VERSION', '0.1dev');
		define('Frawst\\SCRIPT_START', microtime(true));
		
		/**
		 * Environment constants
		 */
		defined('Frawst\\APP_NAME')
			or define('Frawst\\APP_NAME', basename(dirname($_SERVER['SCRIPT_NAME'])));
		defined('Frawst\\DOMAIN')
			or define('Frawst\\DOMAIN', $_SERVER['HTTP_HOST']);
		defined('Frawst\\ROOT')
			or define('Frawst\\ROOT', dirname(__FILE__));	
		defined('Frawst\\APP_ROOT')
			or define('Frawst\\APP_ROOT', dirname(Frawst\ROOT).DIRECTORY_SEPARATOR.Frawst\APP_NAME);
		defined('Frawst\\WEB_ROOT')
			or define('Frawst\\WEB_ROOT', rtrim(dirname($_SERVER['SCRIPT_NAME']), '/'));
		defined('Frawst\\AJAX_SUFFIX')
			or define('Frawst\\AJAX_SUFFIX', '___AJAX___');
		
		if(!defined('Frawst\\URL_REWRITE')) {
			if(function_exists('apache_get_modules') && in_array('mod_rewrite', apache_get_modules())) {
				define('Frawst\\URL_REWRITE', true);
			} else {
				define('Frawst\\URL_REWRITE', false);
			}
		}
		
		require 'Loader.php';
		spl_autoload_register('Frawst\\Loader::import');
		
		function error_handler($code, $message, $file, $line) {
			throw new Exception\Language($message, 0, $code, $file, $line);
		}
		set_error_handler('error_handler');
	}
	
	namespace Frawst {
		/**
		 * Paths in which libraries can be held.
		 */
		Loader::addPath(ROOT, 'Frawst', 'core');
		Loader::addPath(ROOT.DIRECTORY_SEPARATOR.'vendor', '*', 'core');
		Loader::addPath(APP_ROOT, 'Frawst', 'app');
		Loader::addPath(APP_ROOT.DIRECTORY_SEPARATOR.'vendor', '*', 'app');
		
		/**
		 * App-specific bootstrapping
		 */
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
		
		/**
		 * I HATE YOU MAGIC QUOTES
		 */
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
		
		/**
		 * Pull request route, method, and variables from $_SERVER
		 */
		$headers = array();
		$route = isset($_SERVER['PATH_INFO'])
			? $_SERVER['PATH_INFO']
			: '';
		
		/**
		 * Extract HTTP headers from the ugly $_SERVER array
		 */
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
		
		$request = new Request($route, $data, $method, $headers);
		$request->execute()->send();
	}