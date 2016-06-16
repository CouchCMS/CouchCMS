<?php
    ob_start();
    k_timer_start();
    define( 'K_ADMIN', 1 );

    if ( !defined('K_COUCH_DIR') ) define( 'K_COUCH_DIR', str_replace( '\\', '/', dirname(realpath(__FILE__) ).'/') );
    require_once( K_COUCH_DIR.'header.php' );

    $AUTH->check_access( K_ACCESS_LEVEL_ADMIN );
    $FUNCS->dispatch_event( 'admin_init' );

    // gather routes definitions
    $FUNCS->dispatch_event( 'register_admin_routes' );
    define( 'K_REGISTER_ROUTES_DONE', '1' );
    $FUNCS->dispatch_event( 'alter_admin_routes', array(&$FUNCS->routes) );

    // set context ..
    $CTX->push( '__ROOT__' );
    $FUNCS->set_userinfo_in_context();
    $FUNCS->dispatch_event( 'add_render_vars' );
    if( K_THEME_NAME ){
        $FUNCS->dispatch_event( K_THEME_NAME.'_add_render_vars' );
    }

    // and process the current request
    if( isset($_GET['o']{0}) ){
        $html = $FUNCS->process_route( $_GET['o'], $_GET['q'] );
    }
    else{
        // if no route specified in request, redirect to the first registered route (or show welcome msg if no route available)
        $html = $FUNCS->render( 'default_route' );
        $FUNCS->set_admin_title( $FUNCS->t('welcome') );
    }

    if( $FUNCS->is_error($html) ){
        if( $html->err_msg===ROUTE_ACCESS_DENIED ){
            header( 'HTTP/1.1 403 Forbidden' );
            $html = 'Access forbidden!';
        }
        else{
            header( 'HTTP/1.1 404 Not Found' );
            header( 'Status: 404 Not Found' );
            if( $html->err_msg===ROUTE_NOT_FOUND ){
                $html = 'Page not found';
            }
            else{
                $html = $html->err_msg;
            }
        }
    }

    if( !$FUNCS->route_fully_rendered ){
        $html = $FUNCS->render( 'main', $html );
    }

    if( defined('K_IS_MY_TEST_MACHINE') ){
        $html .= "<!-- in: ".k_timer_stop()." Queries: ".$DB->queries;
        if( $DB->debug ){ $html .= " (in ".k_format_time($DB->query_time).")"; }
        $html .= " -->";
    }

    // final output
    $CTX->pop();
    header( 'Content-Type: '.$FUNCS->route_content_type.'; charset='.K_CHARSET );
    die( $html );


    ////////////////////////////////////////////////////////////////////////////
    function k_get_time(){
        list ($msec, $sec) = explode(' ', microtime());
        $microtime = (float)$msec + (float)$sec;
        return $microtime;
    }

    function k_timer_start(){
        global $k_time_start;
        $k_time_start = k_get_time();
        return true;
    }

    function k_timer_stop( $echo = 0 ){
        global $k_time_start, $k_time_end;
        $k_time_end = k_get_time();
        $diff = k_format_time( $k_time_end - $k_time_start );
        if ( $echo ){ echo $diff; }
        return $diff;
    }

    function k_format_time( $microtime, $echo = 0 ){
        $microtime = number_format( $microtime, 3 ) . ' sec';
        if ( $echo ){ echo $microtime; }
        return $microtime;
    }
