<?php
	namespace Frawst;
	
	interface RequestInterface {
		public function __construct(RouteInterface $route, $data, $method, $headers);
		public function header($name);
		public function isAjax();
		public function subRequest(RouteInterface $route, $data, $method);
		public function execute();
		public function method();
		public function param($index);
		public function option($name);
		public function data($key);
	}