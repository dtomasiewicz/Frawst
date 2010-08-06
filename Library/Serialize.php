<?php
	namespace Frawst\Library;
	
	/**
	 * This houses any serialization-oriented methods.
	 */
	class Serialize {
		/**
		 * Safely determines the type or classname of a serialized entity without
		 * unserializing (or instantiating) it.
		 * @param string $serial The serialized entity
		 * @return string The type or class name the entity would unserialize to
		 */
		public static function getSerialClass ($serial) {
			$types = array('s' => 'string', 'a' => 'array', 'b' => 'bool', 'i' => 'int', 'd' => 'float', 'N;' => 'null');
			
			$parts = explode(':', $serial, 4);
			return isset($types[$parts[0]]) ? $types[$parts[0]] : trim($parts[2], '"'); 
		}
	}