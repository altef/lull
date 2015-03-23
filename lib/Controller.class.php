<?php
/**
 * An abstract Controller class for endpoint controllers to extend.
 * Plus some static helper functions.
 * Written by Bradley Gill
 * altered effect (http://alteredeffect.com)
 * bradgill@gmail.com
 */
abstract class Controller {
	
	// Mandatory HTTP verbs to implement
	abstract protected function get( $chunks );
	abstract protected function put( $chunks );
	abstract protected function post( $chunks );
	abstract protected function delete( $chunks );		
	
	
	/**
	 * Something has gone wrong! Terminate the script.
	 * @param HTTP error $code.
	 * @param Text to write in the body.
	 */
	static function error( $code, $desc ) {
		$codes = array();
		$codes[400] = 'Bad Request';
		$codes[401] = 'Unauthorized';
		$codes[403] = 'Forbidden';
		$codes[404] = 'Not Found';
		$codes[405] = 'Method Not Allowed';
		$codes[406] = 'Not Acceptable';
		$codes[501] = 'Not Implemented';
		$codes[503] = 'Service unavailable';
		
		header('Content-Type: text/html');
		header( 'HTTP/1.1 '. $code . ' '. @$codes[$code] );
		die( $desc );
	}
	
	
	/**
	 * Send out some JSON! Terminate the script.
	 * @param $obj object to JSON encode.
	 */
	static function json_out( $obj ) {
		header('Content-Type: application/json');
		die( json_encode( $obj ) );
	}
	
	
	/**
	 * Retrieve the data passed in.
	 * @param HTTP $verb. ex: one of 'get', 'put', 'post', or 'delete'.
	 * @return an associative array of data.
	 */
	static function getData( $verb ) {
		if ( in_array( $verb, array( 'put', 'delete' ) ) ) {
			return json_decode(file_get_contents("php://input"), true );				
		} else
			return $_REQUEST;
	}	
}

?>