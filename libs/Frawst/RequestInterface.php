<?php
	namespace Frawst;
	
	interface RequestInterface {
		/**
		 * Creates a request object.
		 * @param string|RouteInterface $route The request route
		 * @param array $data The request data
		 * @param string $method The request method
		 * @param array $headers The request headers
		 * @param array $persist Any persistent data the request should have access to
		 * @return RequestInterface The request object
		 */
		public static function factory($route, array $data, $method, array $headers, array $persist);
		
		/**
		 * Creates an AJAX sub-request of the current one, with identical headers
		 * aside from X-Requested-With, and the same persistent data.
		 * @param string|RouteInterface $route The route of the sub-request
		 * @param array $data The request data
		 * @param string $method The request method
		 * @return RequestInterface The sub-request
		 */
		public function subRequest($route, array $data, $method);
		
		/**
		 * Executes the request, returing a response object
		 * @return ResponseInterface The generated response object
		 */
		public function execute();
		
		/**
		 * Retrieve a request header
		 * @param string $name The name of the header, or null to retrieve
		 *                     all set headers as an associative array.
		 * @return string|array The value of the header, an array of all headers,
		 *                      or null if the header is not set.
		 */
		public function header($name);
		
		/**
		 * Determine if this request is an AJAX request. AJAX requests have
		 * an X-Requested-With header with value 'xmlhttprequest' (case-insensitive). 
		 * @return bool True if it is ajax, otherwise false
		 */
		public function isAjax();
		
		/**
		 * @return string The request method
		 */
		public function method();
		
		/**
		 * @return RouteInterface The request route
		 */
		public function route();
		
		/**
		 * @return string|array The request data at the given index (dot paths supported)
		 */
		public function data($key);
		
		/**
		 * Get persistent data
		 * @param string $key
		 */
		public function persist($key);
		
		/**
		 * Set persistent data
		 * @param string $key
		 * @param mixed $value
		 */
		public function setPersist($key, $value);
	}