<?php
/**
 * Reset-password API endpoint controller. 
 * Ex: reset-password?c=[code from email]&p=[new password]
 * Written by Bradley Gill
 * altered effect (http://alteredeffect.com)
 * bradgill@gmail.com
 */
class Reset_password extends Controller {
	
	// Reset the password
	public function get( $chunks ) {
		return $this->reset();
	}
	
	// Reset the password
	public function post( $chunks ) {
		return $this->reset();
	}

	
	
	public function put( $chunks ) {
		Controller::error( 405, 'Not applicable.');
	}
		
	public function delete( $chunks ) {
		Controller::error( 405, 'Not applicable.');
	}	
	
	
	private function reset() {
		global $database;
		
		$c = get( $_REQUEST, 'c', '' );
		$p = get( $_REQUEST, 'p', '' );
		
		
		$un = new UserManager( $database );
		if ( $un->resetPassword( $c, $p ) ) {
			return 'Your password has been reset.';
		}
		Controller::error( 400, 'Invalid reset request' );
	}
}
?>