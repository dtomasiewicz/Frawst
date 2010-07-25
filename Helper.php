<?php
	namespace Frawst;
	use \Frawst\Library\Sanitize;
	
	class Helper {
		protected $View;
		
		public function __construct($view) {
			$this->View = $view;
		}
		
		protected function parseAttributes($attrs) {
			$str = '';
			foreach($attrs as $attr => $value) {
				if(!is_null($value)) {
					$str .= $attr.'="'.Sanitize::html($value).'" ';
				}
			}
			return trim($str);
		}
	}