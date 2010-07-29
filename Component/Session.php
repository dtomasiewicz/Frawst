<?php
	namespace Frawst\Component;
	use \Frawst\Component,
		\Frawst\Library\Security;
	
	class Session extends Component implements \ArrayAccess {
		const cookieName = 'Session';
		protected $_id;
		protected $_feedback;
		
		protected function _init() {
			$this->_feedback = isset($this['FEEDBACK'])
				? unserialize($this['FEEDBACK'])
				: array();
			
			$this->start();
		}
		
		public function start() {
			$id = $this->_Controller->Cookie[self::cookieName.'.SESSID'];
			if($id === null) {
				$id = Security::hash(microtime(true));
				$this->_Controller->Cookie[self::cookieName.'.SESSID'] = $id;
			}
			// the session ID here is NOT the same as the one in the cookie. the
			// ID used internally is the cookie ID combined with the remote address,
			// so that a session hijacker will also need to have the same IP
			$this->_id = Security::hash($_SERVER['REMOTE_ADDR'].$id);
		}
		
		public function offsetSet($offset, $value) {
			$this->_Controller->Cookie[self::cookieName.'.'.$offset] = $value;
		}
		public function offsetGet($offset) {
			return $this->_Controller->Cookie[self::cookieName.'.'.$offset];
		}
		public function offsetExists($offset) {
			return isset($this->_Controller->Cookie[self::cookieName.'.'.$offset]);
		}
		public function offsetUnset($offset) {
			unset($this->_Controller->Cookie[self::cookieName.'.'.$offset]);
		}
		
		public function id() {
			return $this->_id;
		}
		
		public function destroy() {
			unset($this->_Controller->Cookie[self::cookieName]);
			$this->start();
		}
		
		public function addFeedback($message, $status = 0) {
			$this->_feedback[] = array('message' => $message, 'status' => $status);
			$this['FEEDBACK'] = serialize($this->_feedback);
		}
		
		public function feedback() {
			unset($this['FEEDBACK']);
			return $this->_feedback;
		}
	}