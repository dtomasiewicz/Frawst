<?php
	namespace Frawst\Component;
	use \Frawst\Component,
		\Frawst\Library\Security,
		\Frawst\Library\Cookie as CookieLib;
	
	class Session extends Component implements \ArrayAccess {
		const cookieName = 'Session';
		protected $_id;
		
		protected function _init() {
			$this->start();
		}
		
		public function start() {
			$cookie = new CookieLib(self::cookieName.'.SESSID');
			if(isset($cookie->value)) {
				$id = $cookie->value;
			} else {
				$id = Security::hash(microtime(true));
				$cookie->value = $id;
				$cookie->save();
			}
			// the session ID here is NOT the same as the one in the cookie. the
			// ID used internally is the cookie ID combined with the remote address,
			// so that a session hijacker will also need to have the same IP
			$this->_id = Security::hash($_SERVER['REMOTE_ADDR'].$id);
		}
		
		public function offsetSet($offset, $value) {
			$cookie = new CookieLib(self::cookieName.'.'.$offset, $value);
			$cookie->save();
		}
		public function offsetGet($offset) {
			$cookie = new CookieLib(self::cookieName.'.'.$offset);
			return $cookie->value;
		}
		public function offsetExists($offset) {
			return CookieLib::exists(self::cookieName.'.'.$offset);
		}
		public function offsetUnset($offset) {
			$cookie = new CookieLib(self::cookieName.'.'.$offset);
			$cookie->delete();
		}
		
		public function id() {
			return $this->_id;
		}
		
		public function destroy() {
			unset($this['SESSID']);
			$this->start();
		}
		
		public function addFeedback($message, $status = 0) {
			$feedback = isset($this['FEEDBACK']) ? unserialize($this['FEEDBACK']) : array();
			$feedback[] = array('message' => $message, 'status' => $status);
			$this['FEEDBACK'] = serialize($feedback);
		}
		
		public function feedback() {
			if(isset($this['FEEDBACK'])) {
				$feedback = unserialize($this['FEEDBACK']);
				unset($this['FEEDBACK']);
				return $feedback;
			} else {
				return array();
			}
		}
	}