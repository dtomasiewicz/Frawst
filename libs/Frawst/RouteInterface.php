<?php
	namespace Frawst;
	
	interface RouteInterface {
		public function __construct($route);
		public function controller();
		public function param($key);
		public function option($name);
		public function original();
		public function resolved();
		public static function getPath($route);
		public function path();
	}