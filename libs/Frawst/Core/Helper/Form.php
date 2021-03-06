<?php
	namespace Frawst\Core\Helper;
	use \Frawst\Core\Matrix,
	    \Frawst\Core\Security,
	    \Frawst\Core\Sanitize;
	
	/**
	 * Form helper. Used to create forms based on definitions (extensions
	 * of Frawst\Form)
	 * 
	 * Features:
	 *  - automatically generates security tokens to prevent CSRF
	 *  - will automatically populate fields with (in order of priority)
	 *    1. data already submitted to this request by the same form
	 *    2. values provided using the populate() method
	 *    3. defaults from the form definition
	 *  - dot-path field naming
	 */
	class Form extends \Frawst\Core\Helper {
		
		/**
		 * @var Frawst\Form An instance of the form definition
		 */
		private $Form;
		
		public function object() {
			return $this->Form;
		}
		
		/**
		 * Opens a form. Loads the form definition and returns mark-up for the
		 * opening tag and any necessary hidden tags.
		 * @param string $formName The name of the form. There should be a form
		 *                         definition class called Frawst\Form\$formName
		 * @param string $action The action for the form. Expects an application route.
		 * @param array $attrs Any additional attributes/values for the FORM tag
		 * @return string HTML for the form opening
		 */
		public function open($formName, $attrs = array()) {
			$class = \Frawst\Core\Form::className($this->Module->name(), $formName);
			
			$this->Form = $this->Request->form($formName);
			
			$attrs['action'] = isset($attrs['action']) ? $attrs['action'] : $this->View->path();
			
			if(isset($attrs['method'])) {
				$method = strtoupper($attrs['method']);
				$attrs['method'] = $method == 'GET'
					? 'GET'
					: 'POST';
			} else {
				$method = $attrs['method'] = 'POST';
			}
			
			$out = '<form '.$this->parseAttributes($attrs).'>';
			
			// store the method in a hidden field for browsers that don't support
			// methods other than GET and POST
			if($attrs['method'] != 'GET') {
				$out .= '<input type="hidden" name="___METHOD" value="'.$method.'">';
			}
			
			$out .= '<input type="hidden" name="'.$class::tokenName().'" value="'.$class::makeToken().'">';
			
			return $out;
		}
		
		/**
		 * Closes the form
		 * @return string The form closing tag
		 */
		public function close() {
			$this->Form = null;
			return '</form>';
		}
		
		public function repopulate($field, $default = null) {
			if($this->Form) {
				if(null !== $value = $this->Form->get($field)) {
					return $value;
				}
			}
			
			return $default;
		}
		
		/**
		 * Returns mark-up for a list of errors on the given field
		 * @param string $field The name of the field (dot-path)
		 */
		public function errors($field) {
			if($this->Form && count($errors = $this->Form->errors($field))) {
				$out = '<ul class="fieldErrors fieldErrors-'.str_replace('.', '-', $field).'">';
				foreach ($errors as $message) {
					$out .= '<li>'.$message.'</li>';
				}
				return $out.'</ul>';
			}
			return '';
		}
		
		protected function input($name, $default, $attrs = array()) {
			$attrs['name'] = Matrix::dotToBracket($name);
			$attrs['value'] = $this->repopulate($name, $default);
			return '<input '.$this->parseAttributes($attrs).'>';
		}
		
		/**
		 * Hidden
		 */
		public function hidden($name, $default = '', $attrs = array()) {
			$attrs['type'] = 'hidden';
			return $this->input($name, $default, $attrs);
		}
		
		/**
		 * Text
		 */
		public function text($name, $default = '', $attrs = array()) {
			$attrs['type'] = 'text';
			return $this->input($name, $default, $attrs);
		}
		
		/**
		 * Password
		 */
		public function password($name, $default = '', $attrs = array()) {
			$attrs['type'] = 'password';
			return $this->input($name, $default, $attrs);
		}
		
		/**
		 * Checkbox
		 */
		public function checkbox($name, $defaultChecked = false, $attrs = array()) {
			if($this->repopulate($name, $defaultChecked)) {
				$attrs['checked'] = 'checked';
			}
			
			$attrs['name'] = Matrix::dotToBracket($name);
			$attrs['type'] = 'checkbox';
			$attrs['value'] = isset($attrs['value']) ? $attrs['value'] : 1;
			return '<input '.$this->parseAttributes($attrs).'>';
		}
		
		/**
		 * Radio
		 */
		public function radio($name, $value, $default = null, $attrs = array()) {
			$attrs['name'] = Matrix::dotToBracket($name);
			$attrs['type'] = 'radio';
			$attrs['value'] = $value;
			if($value === $this->repopulate($name, $default)) {
				$attrs['checked'] = 'checked';
			}
			return '<input '.$this->parseAttributes($attrs).'>';
		}
		
		/**
		 * Textarea
		 */
		public function textarea($name, $default = '', $attrs = array()) {
			$attrs['name'] = Matrix::dotToBracket($name);
			$value = $this->repopulate($name, $default);
			return '<textarea '.$this->parseAttributes($attrs).'>'.htmlspecialchars($value).'</textarea>';
		}
		
		/**
		 * Select
		 */
		public function select($name, $options, $selected = null, $attrs = array()) {
			$attrs['name'] = Matrix::dotToBracket($name);
			$out = '<select '.$this->parseAttributes($attrs).'>';
			
			$selected = $this->repopulate($name, $selected);
			foreach ($options as $value => $content) {
				$out .= '<option value="'.$value.'"';
				if ($selected == $value) {
					$out .= ' selected="selected"';
				}
				$out .= '>'.htmlspecialchars($content).'</option>';
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
		public function selectHour24($name, $selected = null, $attrs = array()) {
			$hours = array(
				0 => '00', 1 => '01', 2 => '02', 3 => '03', 4 => '04', 5 => '05', 6 => '06', 7 => '07', 8 => '08', 9 => '09', 10 => '10', 11 => '11',
				12 => '12', 13 => '13', 14 => '14', 15 => '15', 16 => '16', 17 => '17', 18 => '18', 19 => '19', 20 => '20', 21 => '21', 22 => '22', 23 => '23'
			);
			return $this->select($name, $hours, $selected, $attrs);
		}
		
		/**
		 * Select box for 60 minutes
		 */
		public function selectMinute($name, $selected = null, $attrs = array()) {
			$minutes = array(
				0 => '00', 1 => '01', 2 => '02', 3 => '03', 4 => '04', 5 => '05', 6 => '06', 7 => '07', 8 => '08', 9 => '09', 10 => '10', 11 => '11',
				12 => '12', 13 => '13', 14 => '14', 15 => '15', 16 => '16', 17 => '17', 18 => '18', 19 => '19', 20 => '20', 21 => '21', 22 => '22', 23 => '23',
				24 => '24', 25 => '25', 26 => '26', 27 => '27', 28 => '28', 29 => '29', 30 => '30', 31 => '31', 32 => '32', 33 => '33', 34 => '34', 35 => '35',
				36 => '36', 37 => '37', 38 => '38', 39 => '39', 40 => '40', 41 => '41', 42 => '42', 43 => '43', 44 => '44', 45 => '45', 46 => '46', 47 => '47',
				48 => '48', 49 => '49', 50 => '50', 51 => '51', 52 => '52', 53 => '53', 54 => '54', 55 => '55', 56 => '56', 57 => '57', 58 => '58', 59 => '59'
			);
			return $this->select($name, $minutes, $selected, $attrs);
		}
		
		/**
		 * Yes/no select box (returns 1 for yes, 0 for no)
		 */
		public function selectYesNo($name, $selected = null, $attrs = array()) {
			$opts = array(
				0 => 'No',
				1 => 'Yes'
			);
			return $this->select($name, $opts, $selected, $attrs);
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
	}