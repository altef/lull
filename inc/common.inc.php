<?php

	/**
	 * Checks if a variable is empty.
	 * @param $value String.
	 * @return boolean.
	 */
	function isEmpty( $value ) {
		return $value==null || strlen($value) == 0;
	}

	
	/**
	 * Retrieves a value from an associative array by key. If that key doesn't exist in the array, it will return a default value specified.
	 * If you only want to allow certain values, you can pass a list of possible values.
	 * @param array Array the associative array to check in.
	 * @param key String the field to look for.
	 * @param default Mixed the default value to return.
	 * @param possible_values Array a list of possible values.  If this is an array, the function will only return one of its elements.
	 * @return mixed.
	 */
	function get($array, $key, $default=null, $possible_values = null) {
		if (array_key_exists($key, $array)) {
			if ($possible_values == null or in_array($array[$key], $possible_values))
				return $array[$key];
		}
		return $default;
	}
	

	/**
	 * Converts a boolean to a user-readable string.
	 * @param b Boolean.
	 * @return String true or false.
	 */
	function bool2str($b) {
		return ($b)?'true':'false';
	}

	
	/**
	 * Replaces spaces with dashes.
	 * @param str String to modify.
	 * @return String fixed string.
	 */
	function despace($str) {
		return str_replace(' ', '-', $str);
	}
	
	
	
	/**
	 * Checks if a thing is like an int.  (like is_int, but works on strings like is_numeric).
	 * @param $n number.
	 * @return Boolean if $n is a valid int.
	 */
	function isAnInt( $n ) {
		return is_numeric($n) and (string)(int)$n == (string)$n;
	}

	
	function sanatize( $str ) {
		if ( preg_match( "/[a-z0-9_\(\)]*/i", $str, $results ) ) {
			return $results[0];
		}
		return '';
	}
?>