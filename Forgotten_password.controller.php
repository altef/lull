<?php
/**
 * Forgotten-password endpoint controller.
 * Ex: /forgotten-password?u=test@example.com
 * Written by Bradley Gill
 * altered effect (http://alteredeffect.com)
 * bradgill@gmail.com
 */
class Forgotten_password extends Controller {
	
	public function get( $chunks ) {
		return $this->reset();
	}
	
	public function post( $chunks ) {
		return $this->reset();
	}

	public function put( $chunks ) {
		Controller::error( 405, 'Not applicable.');
	}
		
	public function delete( $chunks ) {
		Controller::error( 405, 'Not applicable.');
	}	
	
	
	// Initiate the reset procedure.
	private function reset() {
		global $database;
		
		$u = get( $_REQUEST, 'u', '' );		
		$un = new UserManager( $database );
		try {
			if ( $un->forgotPassword( $u ) )
				return 'An email has been sent to you';
		} catch (PDOException $e) {
			Controller::error( 400, 'Invalid user' );
		}
		return false;
	}
}
?>