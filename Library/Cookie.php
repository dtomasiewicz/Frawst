<?php
	namespace Frawst\Library;
	use \Frawst\Library\Matrix;
	
	class Cookie {
		protected $name;
		public $value;
		public $expires;
		public $path;
		public $domain;
		
		public function __construct($name, $value = null, $expires = 0, $path = null, $domain = null) {
			$this->name = $name;
			$this->value = is_null($value) && Matrix::pathExists($_COOKIE, $this->name)
				? unserialize(Matrix::pathGet($_COOKIE, $this->name))
				: $value;
			$this->expires = $expires;
			$this->path = is_null($path) ? \Frawst\WEB_ROOT : $path;
			$this->domain = is_null($domain) ? \Frawst\DOMAIN : $domain;
		}
		
		public function save() {
			setcookie(Matrix::dotToBracket($this->name), serialize($this->value), $this->expires, $this->path, $this->domain);
			Matrix::pathSet($_COOKIE, $this->name, serialize($this->value));
		}
		
		public function delete() {
			setcookie(Matrix::dotToBracket($this->name), '', time()-3600, $this->path, $this->domain);
			Matrix::pathUnset($_COOKIE, $this->name);
		}
		
		public static function exists($name) {
			return Matrix::pathExists($_COOKIE, $name);
		}
	}