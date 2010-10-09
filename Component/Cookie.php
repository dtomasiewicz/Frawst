<?php
	namespace Frawst\Component;
	use \Frawst\Component,
		\Frawst\Library\Cookie as CookieLib;
	
	class Cookie extends Component implements \ArrayAccess {
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