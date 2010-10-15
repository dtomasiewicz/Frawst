<?php
	namespace Frawst\Library;
	
	/**
	 * This interface can be implemented to give more flexible JSON encoding to
	 * objects. An implementing class supplies a toJSON function that will return
	 * data to be json_encode'd instead of the object itself. This can be particularly
	 * useful when protected/private properties need to be available in the encoded
	 * JSON.
	 */
	interface JSONEncodable {
		/**
		 * @return mixed The data to be JSON-encoded
		 */
		public function toJSON();
	}