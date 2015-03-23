<?php
/**
 * A users API endpoint controller. 
 * Written by Bradley Gill
 * altered effect (http://alteredeffect.com)
 * bradgill@gmail.com
 */
class Users extends Controller {
	
	/**
	 * Return your user data.
	 * Ex: GET users/
	 * Ex: GET users/[yourid]
	 */
	public function get( $chunks ) {
		global $database, $userid;
		if ( $userid == null ) {
			Controller::error(401, 'Please login.');
		}
		$id = array_shift( $chunks );		
		if ( isEmpty( $id ) ) {
			// Could return a list of users here.
			$id = $userid;
		} 
				
		if ($userid == null || $id != $userid ) {
			Controller::error(401, 'Can only retrieve your own user info.');
		}
		
		$um = new UserManager( $database );
		$u = $um->get( $id );
		return $u;
	}
	
	
	
	/** 
	 * Update your user data. Either whatever data is passed, or just a certain field.
	 * Ex: PUT users/
	 * Ex: PUT users/[yourid]/
	 * Ex: PUT users/[fieldname]/
	 * Ex: PUT users/[yourid]/[fieldname]/
	 */
	public function put( $chunks ) {
		global $database, $userid, $config;
		if ( $userid == null ) {
			Controller::error(401, 'Please login.');
		}
		$id = array_shift( $chunks );		
		if ( isEmpty( $id ) ) {
			$id = $userid;
		}
		if (!isAnInt( $id ) ) {
			$field = $id;
			$id = $userid;
		} else {
			$field = array_shift( $chunks );
		}
		
		if ($userid == null || $id != $userid ) {
			Controller::error(401, 'Can only update your own user info.');
		}
		
		$data = Controller::getData('put');
		
		// Check for field;
		if ( !isEmpty( $field ) ) {
			if ( !array_key_exists( $field, $data ) )
				Controller::error( 406, 'You need to pass in a data for field `'.$field.'`');
			$fields = array( $field=>$data[$field] ); // Only update one
		} else $fields = $data;
		
		$um = new UserManager( $database );
		$u = $um->updateUser( $id, $fields );
	
		if ( $u !== false ) {
			return "Updated.";
		} else {
			return "Could not update user.";
		}
	}
	
	
	/**
	 * Add a user. Expects a username and password in the POST array.
	 * Ex: POST users/
	 */
	public function post( $chunks ) {
		global $database, $userid;
		
		$data = Controller::getData('post');
		$in = array();
		$e = get( $data, 'email' );
		if ( isEmpty( $e ) )
			Controller::error( 406, 'Field \'email\' required.');
		$in['email'] = $e;
		$e = get( $data, 'password' );
		if ( isEmpty( $e ) )
			Controller::error( 406, 'Field \'password\' required.');
		$in['password'] = $e;
		
		$um = new UserManager( $database );
		$u = $um->createUser( $in['email'], $in['password'] );
		if ( $u === false ) {
			Controller::error( 400, 'Could not create user');
		} else
			return 'User created.';
	}
		
	
	/**
	 * Deletes your user.
	 * Ex: DELETE users/
	 * Ex: DELETE /users/[yourid]/
	 */
	public function delete( $chunks ) {
		global $database,$config,$userid;
		
		if ( $userid == null ) {
			Controller::error(401, 'Please login.');
		}
		$id = array_shift( $chunks );		
		
		if ( isEmpty( $id ) ) {
			$id = $userid;
		}
		
		if ($userid == null || $id != $userid ) {
			Controller::error(401, 'Can only delete your own user info.');
		}
				
		$data = Controller::getData('delete');
		$in = array();
		
		
		$q = 'DELETE FROM `'.$config['database']['tables']['users'].'` WHERE `id`=:key';
		$in['key'] = $id;
		
		try {
			$stmt = $database->prepare( $q );
			$stmt->execute( $in );
		} catch (PDOException $e) {
			Controller::error(400,'Bad query');
		}
		return $stmt->errorCode() == 0;
	}		
}
?>