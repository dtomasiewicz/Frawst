<?php
	namespace Frawst\Component;
	use \Frawst\Component,
		\Frawst\Library\Config;
	
	class Email extends Component {
		private $subject;
		private $message;
		private $headers = array();
		
		function __construct($subject, $message, $headers = array()) {
			$this->subject = $subject;
			$this->message = $message;
			
			if(!isset($headers['From']))
				$headers['From'] = Config::read('General.email');
			if(!isset($headers['Reply-to']))
				$headers['Reply-to'] = Config::read('General.email');
			
			$this->headers = $headers;
		}
		
		function writeHeaders() {
			$headers = '';
			foreach($this->headers as $header => $content)
				$headers .= $header.': '.$content . "\n";
			return $headers;
		}
		
		function send($addresses) {
			
			foreach((array) $addresses as $address) {
				$return = mail($address, $this->subject, $this->message, $this->writeHeaders());
			}
			
			return $return;
		}
	}