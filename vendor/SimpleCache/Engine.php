<?php
	namespace SimpleCache;
	
	abstract class Engine {
		abstract public function read($name);
		abstract public function write($name, $value);
	}