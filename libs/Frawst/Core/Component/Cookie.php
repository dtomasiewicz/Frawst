<?php
	namespace Frawst\Core\Component;
	use \Frawst\Core\Cookie as CookieLib;
	
	/**
	 * Provides an array-like interface for setting and deleting cookies
	 * from the controller.
	 */
	class Cookie extends \Frawst\Core\Component implements \ArrayAccess {
		public function offsetSet($offset, $value) {
			CookieLib::set($offset, $value);
		}
		public function offsetGet($offset) {
			return CookieLib::exists($offset) ? CookieLib::get($offset) : null;
		}
		public function offsetExists($offset) {
			return CookieLib::exists($offset);
		}
		public function offsetUnset($offset) {
			CookieLib::delete($offset);
		}
	}