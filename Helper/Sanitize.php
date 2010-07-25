<?php
	namespace Frawst\Helper;
	use \Frawst\Helper,
		\Frawst\Library\Sanitize;
	
	class Sanitize extends Helper {
		public function html($string) {
			return Sanitize::html($string);
		}
		public function slug($string) {
			return Sanitize::slug($string);
		}
	}