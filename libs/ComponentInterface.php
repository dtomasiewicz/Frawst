<?php
	namespace Frawst;
	
	interface ComponentInterface {
		public function __construct(ControllerInterface $controller);
		public function setup();
		public function teardown();
		public function controller();
	}