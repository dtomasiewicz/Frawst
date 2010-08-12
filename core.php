<?php
	/**
	 * Frawst Framework Version 0.1 (development)
	 * Author: Daniel Tomasiewicz < www.fourstaples.com >
	 * 
	 * This is the main bootstrap file, through which everything happens.
	 */
	
	namespace {
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
			or define('Frawst\\APP_ROOT', dirname(Frawst\ROOT).DIRECTORY_SEPARATOR.Frawst\APP_NAME);
		defined('Frawst\\WEB_ROOT')
			or define('Frawst\\WEB_ROOT', dirname($_SERVER['SCRIPT_NAME']));
		defined('Frawst\\AJAX_SUFFIX')
			or define('Frawst\\AJAX_SUFFIX', '___AJAX___');
		
		if(!defined('Frawst\\URL_REWRITE')) {
			if(function_exists('apache_get_modules') && in_array('mod_rewrite', apache_get_modules())) {
				define('Frawst\\URL_REWRITE', true);
			} else {
				define('Frawst\\URL_REWRITE', false);
			}
		}
			
		require('Loader.php');
		
		use \Frawst\Loader,
			\Frawst\Exception;
		
		function __autoload($class) {
			Loader::import($class);
		}
		
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
		//@todo this probably should be in an app-specific bootstrap since it's specific to Corelativ
		Loader::addPath(APP_ROOT.DIRECTORY_SEPARATOR.'Model', 'Corelativ\Model', 'app');
		
		/**
		 * App-specific bootstrapping
		 */
		if (file_exists($file = APP_ROOT.DIRECTORY_SEPARATOR.'bootstrap.php')) {
			require($file);
		}
		
		date_default_timezone_set(Config::read('general.timezone'));
		setlocale(LC_ALL, Config::read('general.locale'));
		
		$method = $_SERVER['REQUEST_METHOD'];
		if ($method == 'GET') {
			$data = $_GET;
		} elseif ($method == 'POST') {
			$data = $_POST;
		} else {
			$data = array();
			parse_str(file_get_contents('php://input'), $data);
		}
		
		// REST hack for browsers that don't support all methods. only works if the
		// originating script passes this magic parameter, of course
		if (isset($data['___METHOD'])) {
			$method = $data['___METHOD'];
			unset($data['___METHOD']);
		}
		
		/**
		 * I HATE YOU MAGIC QUOTES
		 */
		if (function_exists('get_magic_quotes_gpc') && get_magic_quotes_gpc()) {
		 	function stripslashes_deep($value) {
				$value = (is_array($value)) ?
					array_map('stripslashes_deep', $value) :
					stripslashes($value);
				return $value;
			}
			
			$requestData = stripslashes_deep($data);
			$_COOKIE = stripslashes_deep($_COOKIE);
			// the following two lines could be taken out... if they are, accessing
			// form data with $_GET and $_POST will be inconsistent, but it's
			// also unneccessary. but really, if you're the type of person who
			// has magic quotes turned on at all, saving a few microseconds isn't
			// going to help you, i'm afraid.
			$_GET = stripslashes_deep($_GET);
			$_POST = stripslashes_deep($_POST);
		}
		
		/**
		 * Pull request route, method, and variables from $_SERVER
		 */
		
		$route = isset($_SERVER['PATH_INFO'])
			? $_SERVER['PATH_INFO']
			: '';
			
		$headers = array();
		if (substr($route, -strlen(AJAX_SUFFIX)) == AJAX_SUFFIX) {
			// hack to get redirected ajax requests working in Firefox
			$headers['X-Requested-With'] = 'XMLHttpRequest';
			$route = substr($route, 0, -strlen(AJAX_SUFFIX));
		} elseif (isset($_SERVER['HTTP_X_REQUESTED_WITH'])) {
			$headers['X-Requested-With'] = $_SERVER['HTTP_X_REQUESTED_WITH'];
		}
		
		\DataPane\Data::init(Config::read('data'));
		\SimpleCache\Cache::init(Config::read('cache'));
		\Corelativ\Mapper::init(Config::read('orm'));
		
		/**
		 * And finally, the request itself.
		 */
		$request = new Request($route, $data, $method, $headers);
		$request->execute()->send();
	}