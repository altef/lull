<?php
	require_once('inc/config.inc.php');
	require_once( 'inc/common.inc.php' );

	
	// Error reporting
	if ( $_DEV ) {
		ini_set('display_errors', 1 );
		error_reporting( E_ALL | E_NOTICE );
	} else
		error_reporting( E_ERROR );

	
	// Valid controllers. These are your API endpoints.
	$loggedin_controllers = array( 'Users' );
	$anonymous_controllers = array('Test', 'Forgotten_password', 'Reset_password', 'Login', 'Users');
	
	
	
	
	// Get the verb & controller
	$http_verb = strtolower($_SERVER['REQUEST_METHOD']);
	
	$request =  str_replace( str_replace('index.php','', $_SERVER['SCRIPT_NAME']), '', $_SERVER['REQUEST_URI'] );
	$request = explode('/', trim($request, '/'));
	$controller = str_replace('-', '_', ucfirst( array_shift( $request ) ));
	list($controller) = explode('?', $controller);
	
	// Connect to db
	try {
		$database = new PDO('mysql:dbname='.$config['database']['database'].';host='.$config['database']['server'].';charset=utf8', $config['database']['user'], $config['database']['password']);
		$database->setAttribute(PDO::ATTR_EMULATE_PREPARES, false);
		$database->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
	} catch (PDOException $e) {
		Controller::error( 503, 'Database cannot be reached' );
	}

	
	// Login stuff
	session_start();
	$userid = @$_SESSION['userid'];
	
	
	
	// Pass along to the appropriate controller
	if ( in_array( $controller, array_merge( $loggedin_controllers, $anonymous_controllers ) ) ) {
		// Check the login
		if ( !array_key_exists('userid', $_SESSION ) ) {
			if ( !in_array( $controller, $anonymous_controllers ) )
				Controller::error(401, 'Please login.');
		}
		
		$controllerInstance = new $controller();
		if ( !method_exists( $controllerInstance, $http_verb ) )
			Controller::error( 405, 'Method not implemented.');
		$r = $controllerInstance->$http_verb( $request );

		// If you want to encode as something other than JSON, do it here
		Controller::json_out( $r );
		
	} else {
		Controller::error( 404, 'Controller not found' );
	}
?>