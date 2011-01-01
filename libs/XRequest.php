<?php
	namespace Frawst;
	
	/**
	 * External Request class
	 * 
	 * This class immitates behaviours of the standard Request class, but applies
	 * to external requests to sources outside of the current application.
	 */

	class XRequest {
		/**
		 * @var resource The cURL handle
		 */
		protected $_handle;
		
		/**
		 * @var array Request data
		 */
		protected $_data;
		
		/**
		 * @var array Request headers
		 */
		protected $_headers;
		
		/**
		 * @var string The request method
		 */
		protected $_method;
		
		/**
		 * @var Frawst\XResponse Response to this request
		 */
		protected $_Response;
		
		/**
		 * Constructor.
		 * @param string $uri The URI to which the request is made
		 * @param array $data The data to send in the request
		 * @param string $method The request method
		 * @param array $headers Request headers
		 */
		public function __construct($uri, $data = array(), $method = 'GET', $headers = array()) {
			$this->_uri = $uri;
			$this->_data = $data;
			$this->_method = strtoupper($method);
			$this->_headers = $headers;
		}
		
		/**
		 * Immitate read-only properties
		 * @param string $name
		 * @return mixed
		 */
		public function __get($name) {
			switch ($name) {
				case 'Response':
					return $this->_Response;
				default:
					throw new Exception('Trying to access undeclared property Request::$'.$name);
			}
		}
		
		/**
		 * Executes the request.
		 * @return mixed False if the request fails, or a Frawst\XResponse object if it's
		 *               successful.
		 */
		public function execute() {
			$fields = http_build_query($this->_data);
			
			$headers = array('Content-Length: '.strlen($fields));
			foreach($this->_headers as $header => $value) {
				$headers[] = $header.': '.$value;
			}
			
			
			$this->_handle = curl_init($this->_uri);
			curl_setopt_array($this->_handle, array(
				CURLOPT_CUSTOMREQUEST => $this->_method,
				CURLOPT_POSTFIELDS => $fields,
				CURLOPT_HTTPHEADER => $headers,
				CURLOPT_FOLLOWLOCATION => true,
				CURLOPT_RETURNTRANSFER => true
			));
			
			$this->_Response = new XResponse($this);
			
			if(false !== $responseData = curl_exec($this->_handle)) {
				$this->_Response->pullInfo($responseData);
				return $this->_Response;
			} else {
				return false;
			}
		}
		
		/**
		 * Returns the curl file handle used for this request. Only available
		 * after execute() is invoked.
		 * @return resource
		 */
		public function handle() {
			return $this->_handle;
		}
	}