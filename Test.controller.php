<?php
/**
 * An example API endpoint controller. 
 * Ex: test/
 * Written by Bradley Gill
 * altered effect (http://alteredeffect.com)
 * bradgill@gmail.com
 */
class Test extends Controller {
	
	public function __construct() {}
	
	public function get( $chunks ) {
		return $_GET;
	}
	
	public function put( $chunks ) {
		$data = Controller::getData('put');
		return $data;
	}
	
	public function post( $chunks ) {
		return $_POST;
	}
	
	
	public function delete( $chunks ) {
		Controller::error( 405, 'Not applicable.');
	}	
}
?>