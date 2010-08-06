<?php
	namespace SimpleCache;
	
	abstract class Engine {
		abstract public function exists ($name);
		abstract public function expire ($name);
		abstract public function get ($name);
		abstract public function set ($name, $value);
	}