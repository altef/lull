<?php
	
	$config['site'] = array();
	$config['site']['name'] = 'API Framework';
	$config['site']['location'] = 'http://'.$_SERVER['SERVER_NAME'].'/'.str_replace('index.php','', $_SERVER['SCRIPT_NAME']);

	// The name and address the emails will come from, and the template location
	$config['email'] = array();
	$config['email']['name'] = 'Web bot';
	$config['email']['address'] = 'bot@example.com';
	$config['email']['templates'] = 'email-templates/';

	// Database connection
	$config['database'] = array();
	$config['database']['server'] = 'localhost';
	$config['database']['user'] = 'username';
	$config['database']['password'] = 'password';
	$config['database']['database'] = 'api_framework';

	// Table names, in case you want to change them
	$config['database']['tables'] = array();
	$config['database']['tables']['users'] = 'users';
	$config['database']['tables']['reset_links'] = 'reset_links';
	$config['database']['tables']['logger'] = 'logger';

	// User manager settings
	$config['users']=array();
	$config['users']['reset_timeout'] = 4; // How long a reset link is valid, in hours
	
	
	
	
	// Enter all IP addresses where you want $_DEV to be true
	$_DEV = (in_array( $_SERVER['REMOTE_ADDR'], 
		array( 
			'69.172.145.158' 
		)
	));
	
	
	
	
	
	// Autoload classes
	spl_autoload_register( function($class)  {
		if ( file_exists( $class.'.controller.php' ) ) {
			include( $class.'.controller.php' );
		} else if ( file_exists( 'lib/'.$class.'.class.php' ) )
			include( 'lib/'.$class.'.class.php' );
		else if ( file_exists( $class.'.controller.php' ) )
			include( $class.'.controller.php' );
	});
	
	

?>