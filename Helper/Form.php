<?php
	namespace Frawst\Helper;
	use \Frawst\Helper,
		\Frawst\Library\Matrix,
		\Frawst\Library\Sanitize;
	
	/**
	 * Frawst FormHelper Class
	 *
	 * Simplifies form creation and handles retrieving of errors.
	 */
	class Form extends Helper {
		private $data;
		private $errors;
		
		private function value($field, $default = null) {
			if(Matrix::pathExists($this->data, $field)) {
				return Matrix::pathGet($this->data, $field);
			} else {
				return $default;
			}
		}
		
		/**
		 * Creates a form. data, method, attributes, action
		 */
		public function create($errors = array(), $attrs = array()) {
			$this->data = $this->View->Request->data();
			$this->errors = $errors;
			
			$attrs['method'] = isset($attrs['method']) ? strtoupper($attrs['method']) : 'POST';
			$attrs['action'] = isset($attrs['action'])
				? $attrs['action']
				: $this->View->Request->path();
			
			$out = '<form '.$this->parseAttributes($attrs).'>';
			// hack for browsers that don't support PUT or DELETE
			if($attrs['method'] == 'PUT' || $attrs['method'] == 'DELETE') {
				$out .= $this->hidden('___METHOD', $attrs['method']);
			}
			
			return $out;
		}
		
		public function hidden($name, $value = null, $attrs = array()) {
			$attrs['type'] = 'hidden';
			$attrs['name'] = Matrix::dotToBracket($name);
			$attrs['value'] = $this->value($name, $value);
			
			return $this->input($attrs);
		}
		
		/**
		 * Text field.
		 */
		public function text($name, $value = null, $attrs = array()) {
			$attrs['type'] = 'text';
			$attrs['name'] = Matrix::dotToBracket($name);
			$attrs['value'] = $this->value($name, $value);
			
			return $this->input($attrs);
		}
		
		public function input($attrs = array()) {
			return '<input '.$this->parseAttributes($attrs).'>';
		}
		
		/**
		 * Textarea
		 */
		public function textarea($name, $value = null, $attrs = array()) {
			$attrs['name'] = Matrix::dotToBracket($name);
			$value = $this->value($name, $value);
			if(!isset($attrs['rows']))
				$attrs['rows'] = 5;
			if(!isset($attrs['cols']))
				$attrs['cols'] = 40;
			
			return '<textarea '.$this->parseAttributes($attrs).'>'.Sanitize::html($value) .'</textarea>';
		}
		
		/**
		 * Password field. Note that password input is NOT cached.
		 */
		public function password($name, $value = null, $attrs = array()) {
			$attrs['type'] = 'password';
			$attrs['name'] = Matrix::dotToBracket($name);
			$attrs['value'] = $value;
			
			return $this->input($attrs);
		}
		
		/**
		 * Select field.
		 */
		public function select($name, $options = array(), $selected = null, $multiple = false, $attrs = array()) {
			if($options instanceof ModelSet) {
				$options->indexByPrimaryKey();
			}
			if($selected instanceof Model || $selected instanceof ModelSet) {
				$selected = $selected->primaryKey();
			}
			
			$out = '';
			$attrs['name'] = Matrix::dotToBracket($name);
			$selected = $this->value($name, $selected);
			if($multiple) {
				// for multiple-selects, add a hidden 0 field so it still gets submitted
				$attrs['name'] .= '[]';
				$attrs['multiple'] = 'multiple';
				$attrs['size'] = $multiple;
				$out .= '<input type="hidden" value="0" name="'.Matrix::dotToBracket($name).'">';
			}
			
			$out .= '<select '.$this->parseAttributes($attrs).'>';
			foreach($options as $value => $text) {
				$out .= '<option';
				if($value == $selected || ($multiple && is_array($selected) && in_array($value, $selected))) {
					$out .= ' selected="selected"';
				}
				$out .= ' value="'.Sanitize::html($value).'">'.Sanitize::html($text).'</option>';
			}
			return $out.'</select>';
		}
		
		/**
		 * Checkbox
		 */
		public function checkbox($name, $checked = null, $attrs = array()) {
			$attrs['type'] = 'checkbox';
			$attrs['checked'] = $this->value($name, $checked);
			
			return $this->input($attrs);
		}
		
		/**
		 * Submit button
		 */
		public function submit($value = null, $name = null, $attrs = array()) {
			$attrs['value'] = $value;
			$attrs['name'] = $name;
			$attrs['type'] = 'submit';
			
			return $this->input($attrs);
		}
		
		public function close() {
			return '</form>';
		}
		
		public function errors($field, $attrs = array()) {
			if(Matrix::pathExists($this->errors, $field)) {
				$errors = Matrix::pathGet($this->errors, $field);
				
				if(!isset($attrs['class'])) {
					$attrs['class'] = 'errors';
				}
				
				$str = '<ul '.$this->parseAttributes($attrs).'>';
				foreach($errors as $error) {
					$str .= '<li>'.$error.'</li>';
				}
				return $str.'</ul>';
			} else {
				return '';
			}
		}
	}