<?php
	namespace Frawst\Component;
	use \Frawst\Component,
		\Frawst\Library\Cookie as CookieLib;
	
	class Cookie extends Component implements \ArrayAccess {
		public function offsetSet ($offset, $value) {
			$cookie = new CookieLib($offset, $value);
			$cookie->save();
		}
		public function offsetGet ($offset) {
			$cookie = new CookieLib($offset);
			return $cookie->value;
		}
		public function offsetExists ($offset) {
			return CookieLib::exists($offset);
		}
		public function offsetUnset ($offset) {
			$cookie = new CookieLib($offset);
			$cookie->delete();
		}
	}