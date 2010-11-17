<?php
	namespace Frawst\Component;
	use \Frawst\Component,
		\Frawst\Library\Session as SessionLib;
	
	/**
	 * Provides an array-style interface for setting and deleting session
	 * data from the controller.
	 */
	class Session extends Component implements \ArrayAccess {
		const FEEDBACK_OK = 0;
		const FEEDBACK_NOTICE = 1;
		const FEEDBACK_ERROR = 2;
		
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
		
		/**
		 * Stores a "feedback" message in the session. These messages are useful
		 * for displaying one-time messages to the user (success messages, error messages,
		 * etc) and will be automatically deleted upon being accessed with feedback().
		 * @param string $message
		 * @param int $status The type of feedback
		 */
		public function addFeedback($message, $status = self::FEEDBACK_OK) {
			$feedback = isset($this['FEEDBACK']) ? unserialize($this['FEEDBACK']) : array();
			$feedback[] = array('message' => $message, 'status' => $status);
			$this['FEEDBACK'] = serialize($feedback);
		}
		
		/**
		 * Returns all stored feedback messages as an array and deletes them from
		 * the session. Each feedback message is a hash with two keys: message and
		 * status.
		 * @return array
		 */
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