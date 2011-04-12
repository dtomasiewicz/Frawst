<?php
	/**
	 * Frawst Framework Version 0.1 (development)
	 * Author: Daniel Tomasiewicz < www.fourstaples.com >
	 * 
	 * This file will setup, execute, and send a Frawst request/response
	 * sequence for the HTTP request in which it was included.
	 */
	
	namespace Frawst;
	use Frawst\Core\Module;
	
	require 'bootstrap.php';
	
	/**
	 * HANDLE HTTP REQUEST
	 */
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
	$allowedMethods = array('GET', 'POST', 'PUT', 'DELETE');
	if ($method == 'POST' && isset($data['___METHOD']) && in_array($m = strtoupper($data['___METHOD']), $allowedMethods)) {
		$method = $m;
		unset($data['___METHOD']);
	}
	
	// I HATE YOU, MAGIC QUOTES
	if (function_exists('get_magic_quotes_gpc') && get_magic_quotes_gpc()) {
	 	function stripslashes_deep($value) {
			$value = (is_array($value)) ?
				array_map('Frawst\stripslashes_deep', $value) :
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
	$route = isset($_SERVER['PATH_INFO'])
		? ltrim($_SERVER['PATH_INFO'], '/')
		: '';
	
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
	
	$addr = array_key_exists('REMOTE_ADDR', $_SERVER) ? $_SERVER['REMOTE_ADDR'] : null;
	$port = array_key_exists('REMOTE_PORT', $_SERVER) ? $_SERVER['REMOTE_PORT'] : null;
	Module::factory('Main', null, null, $addr, $port)
		->request($route, $data, $method, $headers)->execute()->send();