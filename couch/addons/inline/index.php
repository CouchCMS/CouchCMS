<?php

    ob_start();
    define( 'K_ADMIN', 1 );

    if ( defined('K_COUCH_DIR') ) die( 'inline/index.php is meant to be invoked directly' );
    define( 'K_COUCH_DIR', str_replace( '\\', '/', dirname(dirname(dirname(realpath(__FILE__)))).'/') );

    require_once( K_COUCH_DIR.'header.php' );
    header( 'Content-Type: text/html; charset='.K_CHARSET );

    $AUTH->check_access( K_ACCESS_LEVEL_ADMIN, 1 );

    // at this point we have a logged in user with appropriate priveleges

    require_once( K_COUCH_DIR.'addons/inline/inline_ex.php' );
    new InlineEx();
