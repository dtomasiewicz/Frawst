<?php
	namespace Frawst\Library;
	
	interface Comparable {
		public function equals(Comparable $b);
		public function compareTo(Comparable $b);
	}