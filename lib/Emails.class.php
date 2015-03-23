<?php
/** 
 * File: Emails.class.php
 * Written by Bradley Gill
 * altered effect (http://alteredeffect.com)
 * bradgill@gmail.com
 * 
 * This is just going to hold the emails the api will send out.
 */
class Emails {

	/**
	 * Send an email.
	 * @param $type email template to use. ex: 'welcome' resolves to email-templates/welcome.html.
	 * @param $to address to send to.
	 * @param $subject of the email.
	 * @param $fill data to make accessible in the template.
	 */
	public static function send( $type, $to, $subject, $fill=array() ) {
		global $config;
		
		// Figure out if it's a valid type
		$file = $config['email']['templates'] .'/' . $type . '.html';
		if ( !file_exists( $file ) or !is_file( $file ) ) {
			return false;
		}
			
		// Load the file
		$template = file_get_contents( $file );
		
		// Insert data
		foreach( $fill as $k=>$v ) {
			if ( is_scalar( $v ) )
				$template = str_replace( '$'.$k, $v, $template );
		}
		
		// Prepare email
		$headers  = 'MIME-Version: 1.0' . "\r\n";
		$headers .= 'Content-type: text/html; charset=iso-8859-1' . "\r\n";
		$headers .= 'From: '.$config['email']['name'].'<'.$config['email']['address'].'>' . "\r\n";

		// Send
		return mail( $to, $subject, $template, $headers );
	}
	
}

?>