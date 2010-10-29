<?php
	namespace Frawst\Helper;
	use \Frawst\Library\Matrix,
	    \Frawst\Library\Sanitize;
	
	class Form extends \Frawst\Helper {
		protected $_Form;
		
		public function __get($name) {
			if ($name == 'Form') {
				return $this->_Form;
			} else {
				return parent::__get($name);
			}
		}
		
		public function open($formName, $action = null, $attrs = array()) {
			if (!($form = $this->_View->Response->Request->form($formName))) {
				$class = 'Frawst\\Form\\'.$formName;
				$form = new $class();
			}
			$this->_Form = $form;
			
			$attrs['action'] = $this->_View->path($action);
			$attrs['method'] = $this->_Form->method();
			
			$out = '<form '.$this->parseAttributes($attrs).'>';
			
			if($attrs['method'] != 'GET') {
				$out .= '<input type="hidden" name="___METHOD" value="'.$attrs['method'].'">';
			}
			
			if($token = $this->_Form->makeToken()) {
				$class = get_class($this->_Form);
				$out .= '<input type="hidden" name="'.$class::TOKEN_NAME.'" value="'.$token.'">';
			}
			
			return $out;
		}
		
		public function populate($data = array()) {
			$this->_Form->populate($data);
		}
		
		public function close() {
			$this->_Form = null;
			return '</form>';
		}
		
		public function errors($field) {
			$errors = $this->_Form->errors($field);
			
			$out = '<ul class="fieldErrors fieldErrors-'.str_replace('.', '-', $field).'">';
			foreach ($errors as $message) {
				$out .= '<li>'.$message.'</li>';
			}
			$out .= '</ul>';
			
			return $out;
		}
		
		public function input($name, $attrs = array()) {
			$attrs['name'] = Matrix::dotToBracket($name);
			$attrs['value'] = $this->_Form->get($name);
			return '<input '.$this->parseAttributes($attrs).'>';
		}
		
		/**
		 * Hidden
		 */
		public function hidden($name, $attrs = array()) {
			$attrs['type'] = 'hidden';
			return $this->input($name, $attrs);
		}
		
		/**
		 * Text
		 */
		public function text($name, $attrs = array()) {
			$attrs['type'] = 'text';
			return $this->input($name, $attrs);
		}
		
		/**
		 * Password
		 */
		public function password($name, $attrs = array()) {
			$attrs['type'] = 'password';
			return $this->input($name, $attrs);
		}
		
		/**
		 * Checkbox
		 */
		public function checkbox($name, $attrs = array()) {
			$attrs['name'] = Matrix::dotToBracket($name);
			$attrs['type'] = 'checkbox';
			$attrs['checked'] = $this->_Form->get($name) !== null
				? 'checked'
				: null;
			$attrs['value'] = isset($attrs['value']) ? $attrs['value'] : 1;
			return '<input '.$this->parseAttributes($attrs).'>';
		}
		
		/**
		 * Radio
		 * @todo make this array-proof
		 */
		public function radio($name, $value, $attrs = array()) {
			$attrs['name'] = Matrix::dotToBracket($name);
			$attrs['type'] = 'radio';
			$attrs['value'] = $value;
			$attrs['checked'] = $value == $this->_Form[$name]
				? 'checked'
				: null;
			return '<input '.$this->parseAttributes($attrs).'>';
		}
		
		/**
		 * Textarea
		 */
		public function textarea($name, $attrs = array()) {
			$attrs['name'] = Matrix::dotToBracket($name);
			$value = $this->_Form->get($name);
			return '<textarea '.$this->parseAttributes($attrs).'>'.Sanitize::html($value).'</textarea>';
		}
		
		/**
		 * Select
		 */
		public function select($name, $options, $attrs = array()) {
			$attrs['name'] = Matrix::dotToBracket($name);
			$out = '<select '.$this->parseAttributes($attrs).'>';
			
			$selected = $this->_Form->get($name);
			foreach ($options as $value => $content) {
				$out .= '<option value="'.$value.'"';
				if ($selected == $value) {
					$out .= ' selected="selected"';
				}
				$out .= '>'.Sanitize::html($content).'</option>';
			}
			return $out.'</select>';
		}
		
		public function submit($value = null, $name = null, $attrs = array()) {
			$attrs['type'] = 'submit';
			$attrs['value'] = $value;
			if($name !== null) {
				$attrs['name'] = Matrix::dotToBracket($name);
			}
			return '<input '.$this->parseAttributes($attrs).'>';
		}
		
		/**
		 * Select box for 24 hours
		 */
		public function selectHour24($name, $attrs = array()) {
			$hours = array(
				0 => '00', 1 => '01', 2 => '02', 3 => '03', 4 => '04', 5 => '05', 6 => '06', 7 => '07', 8 => '08', 9 => '09', 10 => '10', 11 => '11',
				12 => '12', 13 => '13', 14 => '14', 15 => '15', 16 => '16', 17 => '17', 18 => '18', 19 => '19', 20 => '20', 21 => '21', 22 => '22', 23 => '23'
			);
			return $this->select($name, $hours, $attrs);
		}
		
		/**
		 * Select box for 60 minutes
		 */
		public function selectMinute($name, $attrs = array()) {
			$minutes = array(
				0 => '00', 1 => '01', 2 => '02', 3 => '03', 4 => '04', 5 => '05', 6 => '06', 7 => '07', 8 => '08', 9 => '09', 10 => '10', 11 => '11',
				12 => '12', 13 => '13', 14 => '14', 15 => '15', 16 => '16', 17 => '17', 18 => '18', 19 => '19', 20 => '20', 21 => '21', 22 => '22', 23 => '23',
				24 => '24', 25 => '25', 26 => '26', 27 => '27', 28 => '28', 29 => '29', 30 => '30', 31 => '31', 32 => '32', 33 => '33', 34 => '34', 35 => '35',
				36 => '36', 37 => '37', 38 => '38', 39 => '39', 40 => '40', 41 => '41', 42 => '42', 43 => '43', 44 => '44', 45 => '45', 46 => '46', 47 => '47',
				48 => '48', 49 => '49', 50 => '50', 51 => '51', 52 => '52', 53 => '53', 54 => '54', 55 => '55', 56 => '56', 57 => '57', 58 => '58', 59 => '59'
			);
			return $this->select($name, $minutes, $attrs);
		}
		
		/**
		 * Yes/no select box (returns 1 for yes, 0 for no)
		 */
		public function selectYesNo($name, $attrs = array()) {
			$opts = array(
				0 => 'No',
				1 => 'Yes'
			);
			return $this->select($name, $opts, $attrs);
		}
		
		public function __call($method, $args) {
			return call_user_func_array(array($this->_Form, $method), $args);
		}
	}