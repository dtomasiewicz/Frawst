<?php
	namespace Frawst;
	
	interface ControllerInterface {
		public function __construct(ResponseInterface $response);
		
		/**
		 * @return Request The request object this controller is acting for
		 */
		public function request();
		
		/**
		 * @return Response The response object this controller is acting on
		 */
		public function response();
		
		/**
		 * Executes the controller logic and returns the response data
		 * @return mixed Response data
		 */
		public function execute();
		
		/**
		 * Attempts to create or return a component with the given name
		 * @param string $name The name of the component (e.g. Session)
		 * @return ComponentInterface The component object or null on failure
		 */
		public function component($name);
		
		public static function factory($name, ResponseInterface $response);
		
		/**
		 * Determines whether or not a controller with the given name exists
		 * @param string $controller Name of the controller, correctly capitalized with
		 *                           forward slashes 
		 * @return bool
		 */
		public static function exists($name);
		
		/**
		 * Determines if the specified controller is abstract or not. An abstract
		 * controller cannot be used as a request controller, but a sub-controller
		 * may be.
		 * @param string $controller Name of the controller, correctly capitalized with
		 *                           forward slashes
		 * @return bool
		 */
		public static function isAbstract($name);
	}