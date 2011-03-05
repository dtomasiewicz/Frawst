<?php
	namespace Frawst;
	
	interface ViewInterface {
		
		public function __construct(ResponseInterface $response);
		
		public function render($data);
		
		public function partial($partial, $data);

		public function isAjax();
		
		public function path($route);
		
		public function webroot($resource);

		public function ajax($route, $data, $method);
 		
 		public function helper($name);
 		
 		public function layout($layout);
	}