<?php
	namespace Frawst;
	use \Frawst\Exception;
	
   /**
    * Handles response to external requests.
    */
	class XResponse {
		
		/**
		 * The Request object to which this Response is responding to.
		 * @var Request
		 */
		protected $_Request;
		
		/**
		 * @var array Response data
		 */
		protected $_data;
		
		/**
		 * @var array Associative array of response headers. Never sent if the response
		 * is simply "rendered" (as a sub-request).
		 */
		protected $_headers = array();
		
		/**
		 * Constructor.
		 * @param Frawst\XRequest Request that is being responded to
		 */
		public function __construct($request) {
			$this->_Request = $request;
		}
		
		/**
		 * Read-only immitation.
		 * @param string $name
		 * @return mixed
		 */
		public function __get($name) {
			switch ($name) {
				case 'Request':
					return $this->_Request;
				default:
					throw new Exception\Frawst('Invalid Response property: '.$name);
			}
		}
		
		/**
		 * Pulls response info from the Request.
		 */
		public function pullInfo($data) {
			$this->_data = $data;
			
			$handle = $this->_Request->handle();
			$this->_headers['Content-Type'] = curl_getinfo($handle, CURLINFO_CONTENT_TYPE);
		}
		
		/**
		 * Returns the response data.
		 * @return mixed Response data
		 */
		public function data() {
			return $this->_data;
		}
		
		/**
		 * @return array Associative array of response headers
		 */
		public function headers() {
			return $this->_headers;
		}
		
		/**
		 * Returns the value of a response header.
		 * @param string $name The name of the header
		 * @return string The response header value or null if not set
		 */
		public function header($name) {
			return isset($this->_headers[$name])
				? $this->_headers[$name]
				: null;
		}
		
		/**
		 * Convenience method for getting the response Content-Type.
		 * @param bool $encoding If false, the content encoding will not be returned
		 * @return string The response Content-Type
		 */
		public function contentType($encoding = false) {
			$cT = $this->header('Content-Type');
			
			if(!$encoding) {
				$cT = explode(';', $cT);
				return $cT[0];
			} else {
				return $cT;
			}
		}
	}