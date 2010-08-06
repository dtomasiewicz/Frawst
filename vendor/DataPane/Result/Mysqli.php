<?php
	namespace DataPane\Result;
	use DataPane\Result;
	
	class Mysqli extends Result {
		public function __construct (\MySQLi_Result $results) {
			while ($fields = $results->fetch_assoc()) {
				$this[] = $fields;
			}
			
			// free results
			$results->close();
		}
	}