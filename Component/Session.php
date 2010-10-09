<?php
	namespace Frawst\Component;
	use \Frawst\Component,
		\Frawst\Library\Security,
		\Frawst\Library\Session as SessionLib;
	
	class Session extends Component implements \ArrayAccess {
		
		public function offsetSet($offset, $value) {
			SessionLib::set($offset, $value);
		}
		public function offsetGet($offset) {
			return SessionLib::exists($offset) ? SessionLib::get($offset) : null;
		}
		public function offsetExists($offset) {
			return SessionLib::exists($offset);
		}
		public function offsetUnset($offset) {
			SessionLib::delete($offset);
		}
		
		public function id() {
			return SessionLib::id();
		}
		
		public function destroy() {
			SessionLib::destroy();
		}
		
		public function addFeedback($message, $status = 0) {
			$feedback = isset($this['FEEDBACK']) ? unserialize($this['FEEDBACK']) : array();
			$feedback[] = array('message' => $message, 'status' => $status);
			$this['FEEDBACK'] = serialize($feedback);
		}
		
		public function feedback() {
			if (isset($this['FEEDBACK'])) {
				$feedback = unserialize($this['FEEDBACK']);
				unset($this['FEEDBACK']);
				return $feedback;
			} else {
				return array();
			}
		}
	}