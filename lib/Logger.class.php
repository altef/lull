<?php
/**
 * Log runtime messages to the database.
 * Written by Bradley Gill
 * altered effect (http://alteredeffect.com)
 * bradgill@gmail.com
 *
 * Usage:
 *		Logger::$database = $database; // Tell it where to save
 *		Logger::log( 'hi there' );
 *	
 */
class Logger {

	public static $database = null;
	
	
	/**
	 * Log a string to the database.
	 * @param $string to log.
	 */
	public static function log( $string ) {
		if ( Logger::$_instance == null )
			Logger::$_instance = new Logger();
		// log the string
		Logger::$_instance->_log( $string );
	}

	
	
	
	
	
	
	// Nothing to see down here!
	private static $_instance = null;
	private $id;
	
	// Constructor
	private function __construct() {
		global $config;
		// log the start
		$query = 'INSERT INTO `'.$config['database']['tables']['logger'].'` (`started`, `modified_at`) VALUES (NOW(), NOW())';
		try {
			$this::$database->query( $query );
			$this->id = $this::$database->lastInsertId();
		} catch (PDOException $e) {
			if ( $this::$database->errorCode() == '42S02' ) {
				
				$this::$database->query( 'CREATE TABLE `'.$config['database']['tables']['logger'].'` (
					`started` DATETIME NOT NULL ,
					`modified_at` DATETIME NOT NULL ,
					`complete` TINYINT NOT NULL ,
					`id` INT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY ,
					`log` TEXT NOT NULL
					) ENGINE = MYISAM ;');
				try {
					$this::$database->query( $query );
					$this->id = $this::$database->lastInsertId();
				} catch (PDOException $e) {
					Controller::error( 503, 'Could not start log' );
				}
			} else
				Controller::error( 503, 'Could not start log' );
		}
	}

	// Log the string
	private function _log( $string ) {
		global $config;
		// Log the string
		$stmt = $this::$database->prepare('UPDATE  `'.$config['database']['tables']['logger'].'` SET `log`=CONCAT(`log`, :msg), `modified_at`=NOW() WHERE id=:id' );

		$stmt->execute( array( 'msg'=>$string, 'id'=>$this->id ) );
	}
	
	// Close the log
	public function __destruct() {
		global $config;
		// Log the end
		$stmt = $this::$database->prepare('UPDATE  `'.$config['database']['tables']['logger'].'` SET `complete`=1, `modified_at`=NOW() WHERE id=:id' );
		$stmt->execute( array( 'id'=>$this->id ) );
	}
}
?>