<?php
	namespace Frawst;
	
	interface HelperInterface {
		public function __construct(ViewInterface $view);
		public function setup();
		public function teardown();
		public function view();
		public static function factory($name, ViewInterface $view);
		public static function exists($name);
	}