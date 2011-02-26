<?php
	namespace Frawst;
	
	interface HelperInterface {
		public function __construct(ViewInterface $view);
		public function setup();
		public function teardown();
		public function view();
	}