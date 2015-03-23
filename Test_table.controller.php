<?php
/**
 * An example API endpoint using Generic. 
 * Ex: test-table/ == generic/test/
 * Written by Bradley Gill
 * altered effect (http://alteredeffect.com)
 * bradgill@gmail.com
 */
class Test_table extends Generic {
	public function __construct() {
		global $request;
		$chunks = array_unshift($request, 'test'); // Add the table name back to the front to make this a nice end point
	}
}

?>