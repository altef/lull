<?php
/**
 * Manage users. Handles creating new users and password resets.
 * Written by Bradley Gill
 * altered effect (http://alteredeffect.com)
 * bradgill@gmail.com
 * Usage:
 *		$um = new UserManager( $database );
 *		$um->createUser('someone@gmail.com', 'password');
 *	
 */
class UserManager {
	
	private $database;
	
	/**
	 * Constructor.
	 * @param $db a PDO database object.
	 */
	public function __construct( $db ) {
		global $config;	
		$this->database = $db;
		
		try {
			// Cleanup old reset links
			$this->database->query( 'DELETE FROM `'.$config['database']['tables']['reset_links'].'` WHERE created_at < DATE_SUB( NOW(), INTERVAL '.$config['users']['reset_timeout'].' HOUR )');
		} catch (PDOException $e) {
			// If the tables don't exist, make them
			$this->error_actions( $this->database->errorCode() );
		}
	}
	
	
	/**
	 * Retrieve a user object.
	 * @param user's $id.
	 * @return the user object or false if none found.
	 */
	public function get( $id ) {
		global $config;
		$stmt = $this->database->prepare( 'SELECT * FROM `'.$config['database']['tables']['users'].'` WHERE id=:id' );
		$result = $stmt->execute( array('id'=> $id ) );
		
		if ( $stmt->errorCode() == 0 && $stmt->rowCount() == 1 ) {
			return $data = $stmt->fetch(PDO::FETCH_ASSOC);			
		}		
		return false;
	}
	
	
	/**
	 * Verifies a user's login is correct.
	 * @param $username email address.
	 * @param $password user's password.
	 * @return user id or false if login is invalid.
	 */
	public function verifyLogin( $username, $password ) {
		global $config;
		$stmt = $this->database->prepare( 'SELECT * FROM `'.$config['database']['tables']['users'].'` WHERE email=:email' );
		$result = $stmt->execute( array('email'=> $username ) );
		
		$this->error_actions( $stmt->errorCode() );
		if ( $stmt->errorCode() == 0 && $stmt->rowCount() == 1 ) {
			$data = $stmt->fetch(PDO::FETCH_ASSOC);
			// Check password
			if (password_verify( $password, $data['password'] )) {
				return $data['id'];
			}			
		}		
		return false;
	}
	
	
	/**
	 * Inserts a new user, and sends the user a welcome email.
	 * @param $username email address.
	 * @param $password. A random password will be chosen if unspecified.
	 * @return Boolean.
	 */
	public function createUser( $username, $password=null ) {
		global $config;
		if ( $password == null )
			$password = $this->generatePass();
		
		$data = array(
			'email'=>$username
		);
		$data['hash'] = password_hash( $password, PASSWORD_DEFAULT);
		
		$query = 'INSERT INTO `'.$config['database']['tables']['users'].'` SET email=:email, password=:hash';
		
		$stmt = $this->database->prepare( $query );
		try {
			$stmt->execute( $data );
		
			if ( $stmt->errorCode() == 0 && $stmt->rowCount() == 1 ) {
				Emails::send( 'welcome', $username, 'Welcome to '.$config['site']['name'], array_merge( $config, array( 'email'=>$username, 'password'=>$password )) );
				return true;
			} 
		} catch (PDOException $e) {
			
		}
		return false;
	}
	
	
	/**
	 * Updates a user row.
	 * @param $id user to update.
	 * @param $data an associative array of fields (keys) to update (with their values). ex: array('email'=>'test@example.com')
	 * @return Boolean.
	 */
	public function updateUser( $id, $data ) {
		global $config;
		
		// If you add fields to the users table, add them here.
		$fields = array('email', 'password');
		
		$in = array();
		foreach( $data as $k=>$v ) {
			if ( in_array( $k, $fields ) ) {
				if ( $k == 'password' ) {
					$in['password'] = password_hash( $v, PASSWORD_DEFAULT );
				} else {
					$in[$k] = $v;
				}
				
			}
		}
		$q = 'UPDATE `'.$config['database']['tables']['users'].'` SET ';
		foreach( $in as $k=>$v ) {
			$q .= "`$k`=:$k,";
		}
		$q = trim( $q, ',');
		$q .= ' WHERE id=:id';
		$in['id'] = $id;
		
		$stmt = $this->database->prepare( $q );
		try {
			$stmt->execute( $in );
		} catch (PDOException $e) {
			return false;
		}
		return $stmt->errorCode() == 0;
	}
	
	
	/**
	 * Starts the password reset process.
	 * Sends an email to the user with a reset code.
	 * @param $email user's email.
	 * @return Boolean.
	 */
	public function forgotPassword($email) {
		global $config;
		$key = $this->hash( 64, 2 );
		$data = array( 'email'=>$email );
		$stmt = $this->database->prepare( 'DELETE FROM `'.$config['database']['tables']['reset_links'].'` WHERE `user`=( SELECT `id` FROM '.$config['database']['tables']['users'].' WHERE email=:email) ');
		$stmt->execute( $data );
		
		$query = 'INSERT INTO `'.$config['database']['tables']['reset_links'].'` (`key`, `user`) VALUES ( :key, ( SELECT `id` FROM '.$config['database']['tables']['users'].' WHERE email=:email))'; 
		$stmt = $this->database->prepare( $query );
		$data['key'] = $key;
		$stmt->execute( $data );
		
		$this->error_actions( $stmt->errorCode() );
		if (  $stmt->errorCode() == 0 && $stmt->rowCount() == 1 ) {			
			Emails::send( 'reset-password', $email, $config['site']['name']." Password reset link", array_merge( $config, array('code'=>$key) ) );
			return true;
		}
		return false;
	}
	
	
	/**
	 * Completes the password reset process.
	 * @param $key emailed to the user in the password reset stage.
	 * @param new $password.
	 * @return Boolean.
	 */
	public function resetPassword( $key, $password ) {
		global $config;
		$data = array(
			'key'=>$key,
		);
		$data['hash'] = password_hash( $password, PASSWORD_DEFAULT );
		$query = 'UPDATE `'.$config['database']['tables']['users'].'` SET password=:hash WHERE `id`=(SELECT `user` FROM `'.$config['database']['tables']['reset_links'].'` WHERE `key`=:key)';
		$stmt = $this->database->prepare( $query );
		$stmt->execute( $data );
		if (  $stmt->errorCode() == 0 && $stmt->rowCount() == 1 ) {			
			$stmt = $this->database->prepare( 'DELETE FROM `'.$config['database']['tables']['reset_links'].'` WHERE `key`=:key' );
			$stmt->execute( array('key'=>$key) );		
			return true;
		} 
		return false;
	}
	
	
	
	
	
	
	
	// String generation functions
	public function generatePass($length=9) {
		return $this->hash( $length, 4 );
	}
	
	
	private function hash( $length=22, $sets=1 ) {
		$range = array( array( 65, 90 ), array( 97, 122 ), array(48,57), array( 40,47 ), array( 91, 95 ) );
		$pass = '';
		if ( $sets > count( $range ) )
			$sets = count( $range )-1;
		for($i=0; $i< $length; $i++) {
			$list = rand( 0, $sets );
			$pass .= chr(rand($range[$list][0],$range[$list][1]));
		}
		return $pass;
	}
	

	
	// If the table doesn't exist, create it.
	private function error_actions( $code ) {
		global $config;
		if ( $code == '42S02' ) {
			$this->database->query( 'CREATE TABLE IF NOT EXISTS `'.$config['database']['tables']['users'].'` (
				  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
				  `email` varchar(255) NOT NULL,
				  `password` varchar(255) NOT NULL,
				  PRIMARY KEY (`id`),
				  UNIQUE KEY `email` (`email`)
				) ENGINE=MyISAM DEFAULT CHARSET=utf8 AUTO_INCREMENT=1 ;');
				
			$this->database->query( 'CREATE TABLE IF NOT EXISTS `'.$config['database']['tables']['reset_links'].'` (
				  `key` varchar(63) NOT NULL,
				  `user` int(10) unsigned NOT NULL,
				  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
				  PRIMARY KEY (`key`)
				) ENGINE=MyISAM DEFAULT CHARSET=utf8;');
		}
	}	
}

?>