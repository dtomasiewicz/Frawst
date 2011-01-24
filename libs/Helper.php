<?php
	namespace Frawst;
	
	abstract class Helper {
		protected $_View;
		
		public function __construct($view) {
			$this->_View = $view;
			$this->_init();
		}
		
		public function __get($name) {
			if ($name == 'View') {
				return $this->_View;
			} else {
				throw new Exception('Invalid View property: '.$name);
			}
		}
		
		protected function _init() {
			
		}
		
		public static function parseAttributes($attrs) {
			$str = '';
			foreach ($attrs as $attr => $value) {
				if ($value !== null) {
					$str .= $attr.'="'.Sanitize::html($value).'" ';
				}
			}
			return trim($str);
		}
	}