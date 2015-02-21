<?php
    /*
    CouchCMS
    Copyright(c) 2009 - 2010 kksidd@gmail.com

    THIS IS COPYRIGHTED SOFTWARE
    PLEASE READ THE LICENSE AGREEMENT
    */

    ob_start();

    if ( !defined('K_COUCH_DIR') ) define( 'K_COUCH_DIR', str_replace( '\\', '/', dirname(realpath(__FILE__) ).'/') );
    $get = $_GET['redirect']; // get it before header.php sanitizes and converts '&' to '&amp;';
    require_once( K_COUCH_DIR.'header.php' );
    $_GET['redirect'] = $get; // can bypass sanitization because we'll sanitize URL ourselves later on.

    session_start();
    if( isset($_GET['lang']) && strlen($_GET['lang']) ){
        $_SESSION['lang'] = $_GET['lang'];
    }

    if( isset($_GET['redirect']) ){
        $ref = $FUNCS->sanitize_url( trim($_GET['redirect']) );

        if( strpos(strtolower($ref), 'http')===0 ){ // we don't allow redirects external to our site
            $ref = K_SITE_URL;
        }
    }
    else{
        $ref = K_SITE_URL;
    }

    header("Location: ".$ref);
    die();
