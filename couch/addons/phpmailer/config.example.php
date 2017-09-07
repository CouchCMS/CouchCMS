<?php
	
	if ( !defined('K_COUCH_DIR') ) die(); // cannot be loaded directly
	
    // 1. Method used for sending emails.
    //
    // Four valid options available:
    // 'smtp'       - Send messages using SMTP (e.g. GMail).
    // 'mail'       - Send messages using PHP's mail() function.
    // 'sendmail'   - Send messages using $Sendmail.
    // 'qmail'      - Send messages using qmail.
    //
    $cfg['method'] = 'smtp';
    
    
    // 2. SMTP settings: required only if $cfg['method'] above is set to 'smtp'.
    $cfg['host']   = 'smtp.gmail.com';      // Address of the SMTP server. If left empty, defaults to 'localhost'.
    $cfg['port']   = '587';                 // Port of the SMTP server. If left empty, defaults to '25'.   
    $cfg['secure'] = 'tls';                 // encryption to use on the SMTP connection. Valid options are '', 'ssl' or 'tls'.
    $cfg['authentication_required'] = '1';  // set this to '0' if your SMTP server does not require authentication
    
        // If 'authentication_required' above is set to '1', the following credentials will be required
        $cfg['username'] = 'your_email@gmail.com';
        $cfg['password'] = 'your_password';
 

    // 3. Debug. If set to '1', will log all debug output in 'log.txt' at site's root.
    $cfg['debug'] = '0';          
    
    

