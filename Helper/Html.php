<?php
	namespace Frawst\Helper;
	use \Frawst\Helper,
		\Frawst\Library\Sanitize,
		\Frawst\Library\Inflector;
	
	class Html extends Helper {
		public $tags = array(
			'image' => '<img src="%s" %s>',
			'link' => '<a href="%s" %s>%s</a>'
		);
		
		public function image($path, $attrs = array()) {
			return sprintf($this->tags['image'], $this->View->path('public/images/'.$path), $this->parseAttributes($attrs));
		}
		
		public function link($uri, $content, $attrs = array()) {
			return sprintf($this->tags['link'], $uri, $this->parseAttributes($attrs), $content);
		}
		
		public function appLink($route, $content, $attrs = array()) {
			return $this->link($this->View->path($route), $content, $attrs);
		}
		
		public function sanitize($string) {
			return Sanitize::html($string);
		}
		
		public function slug($string) {
			return Inflector::slug($string);
		}
		
		public function paragraphs($string) {
			return Sanitize::paragraphs($string);
		}
	}