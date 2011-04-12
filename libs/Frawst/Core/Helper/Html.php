<?php
	namespace Frawst\Core\Helper;
	use \Frawst\Core\Sanitize,
		\Frawst\Core\Inflector;
	
	/**
	 * HTML helper. 
	 */
	class Html extends \Frawst\Core\Helper {
		
		private $tags = array(
			'image' => '<img src="%s" %s>',
			'link' => '<a href="%s" %s>%s</a>'
		);
		
		private $js = array();
		private $css = array();
		
		public function image($path, $attrs = array()) {
			return sprintf($this->tags['image'], $this->view()->root($path), $this->parseAttributes($attrs));
		}
		
		public function link($uri, $content, $attrs = array()) {
			return sprintf($this->tags['link'], $uri, $this->parseAttributes($attrs), $content);
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
			$this->js[$file] = $file;
		}
		
		public function js() {
			return $this->js;
		}
		
		public function addCss($file) {
			$this->css[$file] = $file;
		}
		
		public function css() {
			return $this->css;
		}
		
	}