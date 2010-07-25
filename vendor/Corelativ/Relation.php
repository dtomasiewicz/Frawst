<?php
	namespace Corelativ\Factory;
	
	class Relation extends Corelativ\Factory {
		protected $Subject;
		
		public function __construct($config, $data) {
			
		}
		
		public function setSubject($subject) {
			$this->Subject = $subject;
		}
	}