<?php
	namespace Frawst;
	
	interface RouteInterface {
		public function param($key);
		public function option($name);
		public function original();
		public static function resolve($route);
		public function resolved();
		public static function getPath($route);
		public function path();
		
		/**
		 * @return string The name of the controller for the request. Must exist.
		 */
		public function controller();
		
		/**
		 * @return string The name of the content view for rendering. Must exist.
		 */
		public function template();
	}