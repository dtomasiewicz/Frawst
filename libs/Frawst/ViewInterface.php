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
 		
 		/**
 		 * @return string The current layout
 		 */
 		public function layout();
 		
 		/**
 		 * @param string $layout The name of the layout file to be used when
 		 *                       rendering. null for no layout.
 		 */
 		public function setLayout($layout);
 		
 		/**
 		 * Determines if the given content file exists.
 		 * @param string $view The content path (usually a lower-cased route)
 		 * @return bool
 		 */
 		public static function contentExists($content);
	}