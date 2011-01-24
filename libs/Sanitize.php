<?php
	namespace Frawst;
	
	class Sanitize {
		
		public static function excerpt($string, $words) {			
			return self::truncateWords(strip_tags($string), $words);		
		}
		
		public static function paragraphs($str) {
			return '<p>'.preg_replace('/\r?\n/', '<br>', preg_replace('/(\r?\n){2,}/', '</p><p>', Sanitize::html($str))).'</p>';
		}
		
		public static function html($str) {			
			return htmlspecialchars($str);			
		}
		
		public static function truncateWords($phrase, $max_words, $ellipsis = 1) {
			$phrase_array = explode(' ',$phrase);
			if (count($phrase_array) > $max_words && $max_words > 0) {
				$phrase = implode(' ',array_slice($phrase_array, 0, $max_words));
			}
			if ($ellipsis && count($phrase_array) > $max_words) {
				$phrase .= '...';
			}
			return $phrase;
		}
	}