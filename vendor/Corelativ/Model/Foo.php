<?php
	namespace Corelativ\Model;
	
	class Foo extends \Corelativ\Model {
		protected $displayField = 'name';
		protected $related = array(
			'Child' => array('model' => 'Foo')
		);
	}