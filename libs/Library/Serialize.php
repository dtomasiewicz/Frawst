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
		public static function getSerialClass($serial) {
			$types = array('s' => 'string', 'a' => 'array', 'b' => 'bool', 'i' => 'int', 'd' => 'float', 'N;' => 'null');
			
			$parts = explode(':', $serial, 4);
			return isset($types[$parts[0]]) ? $types[$parts[0]] : trim($parts[2], '"'); 
		}
		
		/**
		 * Encodes the given data to JSON, checking for __toJSON hooks on objects.
		 * @param mixed $data
		 * @param int $opts JSON encoding options (see php.net/json_encode for details)
		 * @param bool $do_encode If true, the returned value will be JSON-encoded. Otherwise,
		 *                        it will be JSON-ready (__toJSON will have been hooked on all
		 *                        objects).
		 * @return string The JSON-encoded data, or the data to be JSON-encoded $do_encode is false
		 */
		public static function toJSON($data, $opts = 0, $do_encode = true) {
			if(is_array($data)) {
				$enc = array();
				foreach($data as $key => $value) {
					$enc[$key] = self::toJSON($value, 0, false);
				}
			} elseif(is_object($data) && $data instanceof JSONEncodable) {
				$enc = $data->toJSON();
			} else {
				$enc = $data;
			}
			
			return $do_encode ? json_encode($enc, $opts) : $enc;
		}
	}