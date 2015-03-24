<?php
/**
 * Sessions be in da database now.
 * Written by Bradley Gill
 * altered effect (http://alteredeffect.com)
 * bradgill@gmail.com
 */
class Session {
	
	// You can play with these
	public static $database = null;
	public static $ttl = 3600; // 1 hour
	private static $cookiename = 'S_SID';
	private static $tablename = 'sessions';
	
	// But ignore these lol
	private static $_instance;
	public $sid;

	
	/**
	 * Return the current session id.
	 * @return The session id or 'Unknown'.
	 */
	public static function id() {
		if ( Session::$_instance != null )
			return Session::$_instance->sid;
		return 'Unknown';
	}
	
	
	/**
	 * Start a session.
	 * @param if you specify an $sid you can force it to open a certain session. You don't have to do this.
	 */
	public static function start( $sid = null ) {
		// If there's already a session open, save it.
		if ( Session::$_instance != null ) {
			Session::$_instance->__destruct();
		}
		Session::$_instance = new Session( $sid );	
	}
	
	
	/**
	 * Destroy the current session.
	 */
	public static function destroy() {
		if ( Session::$_instance != null ) {
			Session::$_instance->destroySession();
		}		
	}
	
	
	
	
	



	// Nothing to see down here!
	
	
	
	
	// Load a session
	public function getSession() {
		global $_SESSION;
		
		
		$query  = 'SELECT * FROM ' . Session::$tablename . ' WHERE sid=:sid' ;
		try {
			$stmt = Session::$database->prepare( $query );
			$stmt->execute( array('sid'=>$this->sid) );
		
			if ( $stmt->errorCode() == 0 ) {
				$obj = $stmt->fetch(PDO::FETCH_ASSOC);
				$_SESSION = json_decode( $obj['data'],true );
			} else {
				$_SESSION = array();
			}
		} catch( PDOException $e ) {
				
			// If the table doesn't exist, create it.
			if ( $e->getCode() == '42S02' ) {
				Session::$database->query( 'CREATE TABLE  `'.Session::$tablename.'` (
					`sid` VARCHAR( 23 ) NOT NULL ,
					`data` TEXT NOT NULL ,
					`updated_at` INT NOT NULL ,
					`remove_at` INT NOT NULL ,
					PRIMARY KEY (  `sid` )
					) ENGINE = MYISAM');
				return true;
			}
		}
		return ( $stmt->errorCode() == 0 );
	}
	
	// Save a session to the db
	public function saveSession() {
		global $_SESSION;
		$data = json_encode( $_SESSION );
		$stmt = Session::$database->prepare( 'INSERT INTO ' . Session::$tablename . ' (sid, data, updated_at, remove_at) VALUES ( :sid, :data, :updated_at, :remove_at )'
		. 'ON DUPLICATE KEY UPDATE sid=VALUES(sid), data=VALUES(data), updated_at=VALUES(updated_at), remove_at=VALUES(remove_at)' );
		try {
			$stmt->execute( array(
				'sid'=>$this->sid,
				'data'=>$data,
				'updated_at'=>time(),
				'remove_at'=>(time()+Session::$ttl)
			));
		} catch (PDOException $e) {}
		return $stmt->errorCode() == 0;
	}
	
	// Delete stale sessions
	public function cleanup() {		
		if ( Session::$database != null )
			try {
				Session::$database->query( 'DELETE FROM ' . Session::$tablename . ' WHERE remove_at < ' . time() );
			} catch (PDOException $e) {}
	}
	
	// Delete the current session
	public function destroySession() {
		if ( Session::$database != null )
			try {
				$stmt = Session::$database->prepare( 'DELETE FROM ' . Session::$tablename . ' WHERE sid =  :sid');	
				$stmt->execute( array('sid'=>$this->sid) );
			} catch (PDOException $e) {}
	}
	
	// Constructor
	private function __construct( $sid ) {
		
		$this->sid = $sid;
		
		if ( $this->sid == null ) {
			// Try to get it from the cookie
			if ( is_array( $_COOKIE ) and array_key_exists( Session::$cookiename, $_COOKIE ) ) {
				$this->sid = $_COOKIE[Session::$cookiename];
			}
		}
		
		if ( strlen( $this->sid ) < 4 ) {
			$this->sid = uniqid( '', true );			
		}
			
		// If database is invalid, default to regular sessions
		
		if ( Session::$database != null) {
			session_start( $this->sid );
		} else {				
			$this->getSession();
		}	
		
		
		
		// Set cookie
		setcookie( Session::$cookiename, $this->sid, time() + Session::$ttl );	
		$this->cleanup();
	}
	
	// Destructor: save the session
	public function __destruct() {
		if ( Session::$database != null ) {
			$this->saveSession();
		}	
	}
	
}

?>