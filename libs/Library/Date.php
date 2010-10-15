<?php
	namespace Frawst\Library;
	
	class Date {		
		const SECOND = 1;
		const MINUTE = 60;
		const HOUR = 3600;
		const DAY = 86400;
		const MONTH = 2629744;
		const YEAR = 31556926;
		
		/**
		 * Returns Today if the specified time falls during the current day,
		 * otherwise formats as specified.
		 */
		public static function nice($date, $format = 'F j, Y') {
			$today = mktime(0, 0, 0);
			$tomorrow = $today+86400;
			return ($date >= $today && $date < $tomorrow) ? 'Today' : date($format, $date);
		}
		
		/**
		 * Formats a time span.
		 * 
		 * @param	seconds		The number of seconds in the time span
		 * @param	precision	Smallest unit included
		 * @param	cutoff		If the span is >= this, only a single unit will be represented
		 * @param	formats		Formattings of each part
		 */
		public static function niceSpan($seconds, $precision = self::SECOND, $cutoff = self::DAY, $formats = array(), $glue = ' ') {
			$formats += array(
				'year' => array('%d year', '%d years'),
				'month' => array('%d month', '%d months'),
				'day' => array('%d day', '%d days'),
				'hour' => array('%d hour', '%d hours'),
				'minute' => array('%d minute', '%d minutes'),
				'second' => array('%d second', '%d seconds'),
				
			);
			
			$years = floor($seconds / self::YEAR);
			$seconds %= self::YEAR;
			
			$months = floor($seconds / self::MONTH);
			$seconds %= self::MONTH;
			
			$days = floor($seconds / self::DAY);
			$seconds %= self::DAY;
			
			$hours = floor($seconds / self::HOUR);
			$seconds %= self::HOUR;
			
			$minutes = floor($seconds / self::MINUTE);
			$seconds %= self::MINUTE;
			
			$string = '';
			$cut = false;
			
			if ($years > 0) {
				$format = is_array($formats['year'])
					? (isset($formats['year'][1]) && $years != 1 ? $formats['year'][1] : $formats['year'][0])
					: $formats['year'];
				
				$string .= sprintf($format, $years).$glue;
				
				if (YEAR >= $cutoff) {
					$cut = true;
				}
			}
			
			if (!$cut && ((self::MONTH >= $precision && $months > 0) || (self::MONTH == $precision && strlen($string) == 0))) {
				$format = is_array($formats['month'])
					? (isset($formats['month'][1]) && $months != 1 ? $formats['month'][1] : $formats['month'][0])
					: $formats['month'];
				
				$string .= sprintf($format, $months).$glue;
				
				if (self::MONTH >= $cutoff) {
					$cut = true;
				}
			}
			
			if (!$cut && ((self::DAY >= $precision && $days > 0) || (self::DAY == $precision && strlen($string) == 0))) {
				$format = is_array($formats['month'])
					? (isset($formats['month'][1]) && $months != 1 ? $formats['month'][1] : $formats['month'][0])
					: $formats['month'];
				
				$string .= sprintf($format, $days).$glue;
				
				if (self::DAY >= $cutoff) {
					$cut = true;
				}
			}
			
			if (!$cut && ((self::HOUR >= $precision && $hours > 0) || (self::HOUR == $precision && strlen($string) == 0))) {
				$format = is_array($formats['hour'])
					? (isset($formats['hour'][1]) && $hours != 1 ? $formats['hour'][1] : $formats['hour'][0])
					: $formats['hour'];
				
				$string .= sprintf($format, $hours).$glue;
				
				if (self::HOUR >= $cutoff) {
					$cut = true;
				}
			}
			
			if (!$cut && ((self::MINUTE >= $precision && $minutes > 0) || (self::MINUTE == $precision && strlen($string) == 0))) {
				$format = is_array($formats['minute'])
					? (isset($formats['minute'][1]) && $minutes != 1 ? $formats['minute'][1] : $formats['minute'][0])
					: $formats['minute'];
				
				$string .= sprintf($format, $minutes).$glue;
				
				if (self::MINUTE >= $cutoff) {
					$cut = true;
				}
			}
			
			if (!$cut && (self::SECOND == $precision && ($seconds > 0 || strlen($string) == 0))) {
				$format = is_array($formats['second'])
					? (isset($formats['second'][1]) && $seconds != 1 ? $formats['second'][1] : $formats['second'][0])
					: $formats['second'];
				
				$string .= sprintf($format, $seconds).$glue;
			}
			
			return substr($string, 0, strlen($glue)*-1);
		}
	}