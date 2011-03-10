<?php
	namespace Frawst\Helper;
	use \Frawst\Helper,
		\Frawst\Sanitize,
		\Frawst\Inflector;
	
	/**
	 * HTML helper. 
	 */
	class Html extends Helper {
		
		private $__tags = array(
			'image' => '<img src="%s" %s>',
			'link' => '<a href="%s" %s>%s</a>'
		);
		
		private $__js = array();
		private $__css = array();
		
		public function image($path, $attrs = array()) {
			return sprintf($this->__tags['image'], $this->view()->webroot('public/images/'.$path), $this->parseAttributes($attrs));
		}
		
		public function link($uri, $content, $attrs = array()) {
			return sprintf($this->__tags['link'], $uri, $this->parseAttributes($attrs), $content);
		}
		
		public function appLink($route, $content, $attrs = array()) {
			return $this->link($this->view()->path($route), $content, $attrs);
		}
		
		public function sanitize($string) {
			return htmlspecialchars($string);
		}
		
		public static function parseAttributes($attrs) {
			$str = '';
			if(is_array($attrs)) {
				foreach ($attrs as $attr => $value) {
					if ($value !== null) {
						$str .= $attr.'="'.htmlspecialchars($value).'" ';
					}
				}
			}
			return trim($str);
		}
		
		public function addJs($file) {
			$this->__js[$file] = $file;
		}
		
		public function js() {
			return $this->__js;
		}
		
		public function addCss($file) {
			$this->__css[$file] = $file;
		}
		
		public function css() {
			return $this->__css;
		}
		
	}