<?php
/**
 * An generic table updater API endpoint controller. 
 * Only works on tables with a primary key on a single field.
 * Written by Bradley Gill
 * altered effect (http://alteredeffect.com)
 * bradgill@gmail.com
 */
class Generic extends Controller {
	
	private $tables = array('test'); // A white-list of tables generic can update
									
	/**
	 * Return a row, or all rows.
	 * ex: GET generic/tablename
	 * ex: GET generic/tablename/keyvalue
	 */
	public function get( $chunks ) {
		global $database;
		
		$table = $this->getTable( $chunks );
		$pri = $this->pri( $this->fields( $table ) );		
		
		// Return all, or if a key is specified return that
		$data = array();
		$id = array_shift( $chunks );		
		
		if ( isEmpty( $id ) ) {
			// Get all
			$stmt = $database->query( 'SELECT * FROM `' . $table.'`' );
			return $stmt->fetchAll(PDO::FETCH_ASSOC);
		} else {
			
			// Get one
			$stmt = $database->prepare( 'SELECT * FROM `' . $table . '` WHERE `'.$pri['Field'].'`=:value' );
			$stmt->execute( array('value'=>$id) );
			return $stmt->fetchAll(PDO::FETCH_ASSOC);
		}
	}
	
	
	/**
	 * Update an entry.
	 * ex: PUT generic/tablename/keyvalue, updates any fields passed in the JSON encoded payload
	 * ex: PUT generic/tablename/keyvalue/fieldname, only updates fieldname
	 */
	public function put( $chunks ) {
		global $database;
		
		$table = $this->getTable( $chunks );
		$fields = $this->fields( $table );
		$pri = $this->pri( $fields );
		$id = array_shift( $chunks );		
		if ( isEmpty( $id ) ) {
			Controller::error(400, 'Row unspecified.');
		}
		
		$data = Controller::getData('put');
		$in = array();
		
		// Check for field;
		$field = array_shift( $chunks );
		if ( !isEmpty( $field ) ) {
			if ( $field == $pri['Field'] ) {
				Controller::error(406, 'Can\'t update the primary key');
			}
			$fields = array( array('Field'=>$field,'Key'=>'')); // Only update one
		}
		
		$q = 'UPDATE `'.$table.'` SET ';
		foreach( $fields as $f ) {
			if ( $f['Key'] != 'PRI' and array_key_exists( $f['Field'], $data )) {
				$q .= '`'.$f['Field'].'`=:'.$f['Field'].',';
				$in[$f['Field']] = $data[$f['Field']];
			}
		}
		$q = trim( $q,',' );
		$q .= ' WHERE `' . $pri['Field'].'`=:key';
		$in['key'] = $id;
		
		try {
			$stmt = $database->prepare( $q );
			$stmt->execute( $in );
		} catch (PDOException $e) {
			echo $q;
			Controller::error(400,'Bad query');
		}
		return $stmt->errorCode() == 0;
	}
	
	
	/**
	 * Adds a new row.
	 * ex: POST generic/tablename
	 */ 
	public function post( $chunks ) {
		// Add an entry
		global $database;
		
		$table = $this->getTable( $chunks );
		$fields = $this->fields( $table );

		
		$data = Controller::getData('post');
		$in = array();
		
		$q = 'INSERT INTO `'.$table.'` SET ';
		foreach( $fields as $f ) {
			if ( array_key_exists( $f['Field'], $data )) {
				$q .= '`'.$f['Field'].'`=:'.$f['Field'].',';
				$in[$f['Field']] = $data[$f['Field']];
			}
		}
		$q = trim( $q,',' );
		
		try {
			$stmt = $database->prepare( $q );
			$stmt->execute( $in );
		} catch (PDOException $e) {
			Controller::error(400,'Bad query');
		}
		return $stmt->errorCode() == 0;
	}
			
	
	/** 
	 * Deletes a row.
	 * ex: DELETE generic/tablename/keyvalue
	 */
	public function delete( $chunks ) {
		// Delete an entry
		global $database;
		
		$table = $this->getTable( $chunks );
		$pri = $this->pri( $this->fields( $table ) );
		$id = array_shift( $chunks );		
		if ( isEmpty( $id ) ) {
			Controller::error(400, 'Row unspecified.');
		}
		
		$data = Controller::getData('delete');
		$in = array();
		
		
		$q = 'DELETE FROM `'.$table.'` WHERE `' . $pri['Field'].'`=:key';
		$in['key'] = $id;
		
		try {
			$stmt = $database->prepare( $q );
			$stmt->execute( $in );
		} catch (PDOException $e) {
			Controller::error(400,'Bad query');
		}
		return $stmt->errorCode() == 0;
	}	
	
	
	
	
	
	
	
	/**
	 * Figures out the table name.
	 */
	private function getTable( &$chunks ) {
		$table = array_shift( $chunks );
		if ( $table == null || !in_array( $table, $this->tables )) {
			Controller::error( 400, 'Specify a valid table.' );
		}
		return $table;
	}
	
	
	/**
	 * Returns the field entry for the primary key
	 * @param $fields list (from $this->fields(...))
	 */
	private function pri( $fields ) {		
		$pri = null;
		foreach( $fields as $f ) {
			if ( $f['Key'] == 'PRI' ) {
				if ( $pri != null ) {
					Controller::error( 501, 'Invalid table - must have one primary key.' );
				}
				$pri = $f;
			}
		}
		return $pri;
	}
	
	
	/**
	 * Returns a list of field objects.
	 * @param $table name.
	 */
	private function fields( $table ) {
		global $database;
		$stmt = $database->prepare( 'SHOW COLUMNS FROM `'.$table.'`' );		
		try {
			$stmt->execute(  );
		} catch( PDOException $e ) {
			Controller::error( 503, 'Cannot access table '. $table );
		}
		
		$desc = $stmt->fetchAll(PDO::FETCH_ASSOC);
		return $desc;
	}
}


?>