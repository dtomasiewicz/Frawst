<?php
	namespace Frawst\Core;
	
	/**
	 * Classes can implement this interface to define methods to be used
	 * for comparison among instances by Frawst's built-in sorting routines.
	 */
	interface Comparable {
		public function equals(Comparable $b);
		public function compareTo(Comparable $b);
	}