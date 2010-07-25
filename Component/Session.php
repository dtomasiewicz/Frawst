<?php
	namespace Frawst\Component;
	use \Frawst\Component,
		\Frawst\Library\Security;
	
	class Session extends Component implements \ArrayAccess {
		private $id;
		private $cookieName = 'Session';
		private $feedback;
		
		public function init() {
			$this->feedback = $this['FEEDBACK'];
			if(is_null($this->feedback)) {
				$this->feedback = array();
			}
			
			$this->start();
		}
		
		public function start() {
			$id = $this->Controller->Cookie[$this->cookieName.'.SESSID'];
			if($id === null) {
				$id = microtime();
				$this->Controller->Cookie[$this->cookieName.'.SESSID'] = $id;
			}
			$this->id = Security::hash($_SERVER['REMOTE_ADDR'].$_SERVER['HTTP_USER_AGENT'].$id);			
		}
		
		public function offsetSet($offset, $value) {
			$this->Controller->Cookie[$this->cookieName.'.'.$offset] = $value;
		}
		public function offsetGet($offset) {
			return $this->Controller->Cookie[$this->cookieName.'.'.$offset];
		}
		public function offsetExists($offset) {
			return isset($this->Controller->Cookie[$this->cookieName.'.'.$offset]);
		}
		public function offsetUnset($offset) {
			unset($this->Controller->Cookie[$this->cookieName.'.'.$offset]);
		}
		
		public function getId() {
			return $this->id;
		}
		
		public function destroy() {
			unset($this->Controller->Cookie[$this->cookieName]);
			$this->start();
		}
		
		public function addFeedback($message, $status = 0) {
			$this->feedback[] = array('message' => $message, 'status' => $status);
			$this['FEEDBACK'] = $this->feedback;
		}
		
		public function getFeedback() {
			unset($this['FEEDBACK']);
			return $this->feedback;
		}
	}