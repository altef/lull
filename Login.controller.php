<?php
/**
 * An API endpoint controller for logging in and out. 
 * Written by Bradley Gill
 * altered effect (http://alteredeffect.com)
 * bradgill@gmail.com
 */
class Login extends Controller {

	public function __construct() {}

	// Login
	public function get( $chunks ) {
		return $this->login();
	}
	
	// Login
	public function post( $chunks ) {
		return $this->login();
	}	
	
	// Logout
	public function delete( $chunks ) {
		unset( $_SESSION['userid'] );
		return 'Logged out.';
	}	

	
	public function put( $chunks ) {
		Controller::error( 405, 'Not applicable.');
	}
	
	
	private function login() {
		global $database;

		$u = get( $_REQUEST, 'u', '' );
		$p = get( $_REQUEST, 'p', '' );
		
		$un = new UserManager( $database );
		$id = $un->verifyLogin( $u, $p );
		if ( $id !== false ) {
			$_SESSION['userid'] = $id;
			return 'Logged in.';
		}
		Controller::error( 401, 'Invalid login.' );
	}
}


?>