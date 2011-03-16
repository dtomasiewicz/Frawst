<?php
	namespace Frawst;
	
	interface ViewInterface {
		public function render($data);

		public function isAjax();
		
		public function path($route);
		
		public function webroot($resource);
 		
 		public function helper($name);
 		
 		/**
 		 * @return string The current layout
 		 */
 		public function layout();
 		
 		/**
 		 * @param string $layout The name of the layout file to be used when
 		 *                       rendering. null for no layout.
 		 */
 		public function setLayout($layout);
 		
 		public static function factory(ResponseInterface $response);
 		
 		/**
 		 * Determines if the given content file exists.
 		 * @param string $view The content path (usually a lower-cased route)
 		 * @return bool
 		 */
 		public static function exists($name);
	}