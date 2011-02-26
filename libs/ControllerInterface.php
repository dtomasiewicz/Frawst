<?php
	namespace Frawst;
	
	interface ControllerInterface {
		public function __construct(RequestInterface $request, ResponseInterface $response);
		public function request();
		public function response();
		public function execute();
		public function component($name);
	}