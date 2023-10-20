<?php
    /*
    The contents of this file are subject to the Common Public Attribution License
    Version 1.0 (the "License"); you may not use this file except in compliance with
    the License. You may obtain a copy of the License at
    http://www.couchcms.com/cpal.html. The License is based on the Mozilla
    Public License Version 1.1 but Sections 14 and 15 have been added to cover use
    of software over a computer network and provide for limited attribution for the
    Original Developer. In addition, Exhibit A has been modified to be consistent with
    Exhibit B.

    Software distributed under the License is distributed on an "AS IS" basis, WITHOUT
    WARRANTY OF ANY KIND, either express or implied. See the License for the
    specific language governing rights and limitations under the License.

    The Original Code is the CouchCMS project.

    The Original Developer is the Initial Developer.

    The Initial Developer of the Original Code is Kamran Kashif (kksidd@couchcms.com).
    All portions of the code written by Initial Developer are Copyright (c) 2009, 2010
    the Initial Developer. All Rights Reserved.

    Contributor(s):

    Alternatively, the contents of this file may be used under the terms of the
    CouchCMS Commercial License (the CCCL), in which case the provisions of
    the CCCL are applicable instead of those above.

    If you wish to allow use of your version of this file only under the terms of the
    CCCL and not to allow others to use your version of this file under the CPAL, indicate
    your decision by deleting the provisions above and replace them with the notice
    and other provisions required by the CCCL. If you do not delete the provisions
    above, a recipient may use your version of this file under either the CPAL or the
    CCCL.
    */

    if( defined('E_STRICT') ){
        error_reporting(E_ALL & ~(E_NOTICE | E_STRICT | E_WARNING | E_DEPRECATED)); // Report all errors except notices and strict standard warnings
    }
    else{
        error_reporting(E_ALL & ~E_NOTICE); // Report all errors except notices
    }
    // Since PHP 5.1.0 every call to a date/time function generates a E_NOTICE if the timezone isn't valid,
    if( !ini_get('date.timezone') || !date_default_timezone_set(ini_get('date.timezone')) ){
        date_default_timezone_set( "America/New_York" );
    }


    if( !defined('K_COUCH_DIR') ) die(); // cannot be loaded directly

    define( 'K_COUCH_VERSION', '2.4' ); // Changes with every release
    define( 'K_COUCH_BUILD', '20231006' ); // YYYYMMDD - do -

    if( file_exists(K_COUCH_DIR.'config.php') ){
        require_once( K_COUCH_DIR.'config.php' );
    }
    else{
        die( '<h3>"config.php" not found. Perhaps you forgot to rename the "config.example.php" file to "config.php" after editing it?</h3>' );
    }
    if( function_exists('mb_internal_encoding') ) mb_internal_encoding( K_CHARSET );
    define( 'K_CACHE_OPCODES', '1' );
    define( 'K_CACHE_SETTINGS', '0' );
    if( !defined('K_CACHE_DIR') ) define( 'K_CACHE_DIR', K_COUCH_DIR . 'cache/' );

    // Check license
    // Ultra-simplified now that there is no IonCube involved :)
    if( !defined('K_PAID_LICENSE') ) define( 'K_PAID_LICENSE', 0 );
    if( !defined('K_REMOVE_FOOTER_LINK') ) define( 'K_REMOVE_FOOTER_LINK', 0 );

    if( !defined('K_HTTPS') ) define( 'K_HTTPS', (
        ( isset($_SERVER['HTTPS']) && (strtolower($_SERVER['HTTPS'])=='on' || strval($_SERVER['HTTPS'])=='1') ) ||
        ( isset($_SERVER['SERVER_PORT']) && $_SERVER['SERVER_PORT']=='443' ) ||
        ( isset($_SERVER['HTTP_X_FORWARDED_PROTO']) && $_SERVER['HTTP_X_FORWARDED_PROTO']=='https' ) ||
        ( isset($_SERVER['HTTP_X_FORWARDED_SSL']) && strtolower($_SERVER['HTTP_X_FORWARDED_SSL'])=='on')
    ) ? 1 : 0 );

    // Check if a cached version of the requested page may be used
    if ( !K_SITE_OFFLINE && !defined('K_ADMIN') && K_USE_CACHE && $_SERVER['REQUEST_METHOD']!='POST' ){

        $auth = 0; $no_cache = 0;
        foreach( $_COOKIE as $k=>$v ){
            if( preg_match("/^couchcms_\w{11,}/", $k) ){
                $auth = 1;
                break;
            }
        }

        if( isset($_GET['nc']) && trim($_GET['nc'])=='1' ){
            $no_cache = 1;
        }

        // Authenticated users will always be served dynamically generated pages
        // Same if no_cache explicity asked for.
        if( !$auth && !$no_cache ){
            $k_cache_dir = K_CACHE_DIR;
            if( is_writable($k_cache_dir) ){

                $k_cache_url = 'http' . ((K_HTTPS) ? 's://' : '://') . $_SERVER['HTTP_HOST'] .
                                (($_SERVER['SERVER_PORT']!='80' && $_SERVER['SERVER_PORT']!='443' && (strpos($_SERVER['HTTP_HOST'], ':')===false)) ? ':' . $_SERVER['SERVER_PORT'] : '') .
                                $_SERVER['REQUEST_URI'];

                $k_cache_file = $k_cache_dir . md5($k_cache_url) . '.dat';
                if( file_exists($k_cache_file) ){

                    // Check if the cache has not expired
                    $file_time = @filemtime( $k_cache_file );
                    $cache_invalidated_time = @filemtime( $k_cache_dir . 'cache_invalidate.dat' );

                    if( $file_time > $cache_invalidated_time ){
                        // Send back the cached contents
                        $pg = @unserialize( file_get_contents($k_cache_file) );
                        if( $pg ){
                            if( $pg['redirect_url'] ){
                                if( $pg['redirect_url']===$k_cache_url ){ // corner case
                                    @unlink( $k_cache_file );
                                }
                                else{
                                    header( "Location: ".$pg['redirect_url'], TRUE, 301 );
                                    die();
                                }
                            }
                            else{
                                $html = $pg['cached_html'];
                                $mime_type = $pg['mime_type'];
                                $res_404 = $pg['res_404'];
                                if( strlen(trim($html)) ){
                                    if( $res_404 ){
                                        header('HTTP/1.1 404 Not Found');
                                        header('Status: 404 Not Found');
                                    }

                                    header( $mime_type );
                                    echo $html;
                                    //echo 'Cached copy: ' . k_timer_stop();
                                    flush();

                                    // Check if time for the periodic purging of stale pages in cache
                                    if( K_CACHE_PURGE_INTERVAL ){
                                        $cache_purge_interval = K_CACHE_PURGE_INTERVAL * 60 * 60;
                                        $max_cache_age = K_MAX_CACHE_AGE * 60 * 60;
                                        $cache_purged_time = @filemtime( $k_cache_dir . 'cache_purge.dat' ); //last purge
                                        $cur_time = time();

                                        if( $cur_time - $cache_purged_time > $cache_purge_interval ){
                                            // Mark the cache as purged
                                            $file = $k_cache_dir . 'cache_purge.dat';
                                            if( file_exists($file) ) @unlink( $file );
                                            @fclose( @fopen($file, 'a') );

                                            // delete all stale files
                                            $dir = @opendir( $k_cache_dir );
                                            if( $dir ){
                                                while( $file = readdir($dir) ){
                                                    if( $file == '.' || $file == '..' ) continue;
                                                    $file = $k_cache_dir . $file;
                                                    $file_time = @filemtime( $file );
                                                    if( $file_time < $cache_invalidated_time || ($cur_time - $file_time > $max_cache_age) ){
                                                        @unlink( $file );
                                                    }
                                                }
                                                closedir( $dir );
                                            }

                                        }
                                    }

                                    die();
                                }
                            }
                        }
                    }
                    else{
                        // Delete the stale cached copy
                        @unlink( $k_cache_file );
                    }
                }
            }
        }
    }
    if( !defined('K_HTML4_SELFCLOSING_TAGS') ) define( 'K_HTML4_SELFCLOSING_TAGS', 0 );

    if( version_compare( '5.3.0', phpversion(), '>' ) ) {
        die( 'You are using PHP version '. phpversion().' but the CMS requires at least 5.3.0' );
    }

    // Refuse to run on IIS
    $web_server = strtolower( $_SERVER['SERVER_SOFTWARE'] );
    if( (strpos($web_server, 'microsoft')!==false) && (strpos($web_server, 'iis')!==false) ){
        die( 'Microsoft-IIS Web Server not supported by CMS' );
    }

    if( !extension_loaded('mysql') ){
        include_once( K_COUCH_DIR . 'includes/mysql2i/mysql2i.class.php' );
    }

    if ( !defined('K_SITE_DIR') ) define( 'K_SITE_DIR', dirname( K_COUCH_DIR ) . '/' );

    //unset($_SERVER['DOCUMENT_ROOT']); //testing
    if ( !defined('K_SITE_URL') ){
        $url = 'http';
        if( K_HTTPS ){
            $url .= 's';
        }
        $url .= '://';
        $port = '';
        if( $_SERVER['SERVER_PORT']!='80' && $_SERVER['SERVER_PORT']!='443' ){
            $port = ':' . $_SERVER['SERVER_PORT'];
        }

        // find sub-domain
        $subdomain = '';
        if( !isset($_SERVER['DOCUMENT_ROOT']) ){
            unset( $subdomain );
        }
        else{
            $path = str_replace( '\\', '/', $_SERVER['DOCUMENT_ROOT'] );
            if( $path[strlen($path)-1]=='/' ){
                $path = substr( $path, 0, strlen($path)-1);
            }

            // subdomain = K_SITE_DIR - DOCUMENT_ROOT
            // Assumption is that K_SITE_DIR will be below DOCUMENT_ROOT. This fails in NFS.
            if( strpos(K_SITE_DIR, $path) === 0 ){
                $subdomain = substr( K_SITE_DIR, strlen($path) );
            }
            else{
                unset( $subdomain );
            }
        }

        if( !isset($subdomain) ){
            // try the alternative method of finding sub-domain
            if( $_SERVER['SCRIPT_NAME'] ){
                if( strpos($_SERVER['SCRIPT_NAME'], '.')!==false ){
                    $path = dirname( $_SERVER['SCRIPT_NAME'] );
                }
                else{
                    $path = $_SERVER['SCRIPT_NAME'];
                }
                $path = str_replace( '\\', '/', trim($path) );
                if( $path[strlen($path)-1]=='/' ){
                    $path = substr( $path, 0, strlen($path)-1);
                }

                $path2 = str_replace( '\\', '/', realpath('./') );
                if( $path2[strlen($path2)-1]=='/' ){
                    $path2 = substr( $path2, 0, strlen($path2)-1);
                }

                if( $path ){
                    $path2 = str_replace( $path, '', $path2 );
                }

                //$path2 should be equivalent to DOCUMENT_ROOT here
                if( strpos(K_SITE_DIR, $path2) === 0 ){
                    $subdomain = substr( K_SITE_DIR, strlen($path2) );
                }
            }
        }

        if( !isset($subdomain) ){
            die( 'Please define your website\'s URL in config.php' );
        }

        $url .= $_SERVER['HTTP_HOST'];
        if( strpos($_SERVER['HTTP_HOST'], ':')===false ) $url .= $port;
        $url .= $subdomain;

        define( 'K_SITE_URL', $url );
    }

    // Dreamhost PHP-CGI problem
    if( strpos($_SERVER['SCRIPT_FILENAME'], 'php.cgi') !== false ){
        $_SERVER['SCRIPT_FILENAME'] = $_SERVER['PATH_TRANSLATED'];
    }
    if( strpos($_SERVER['SCRIPT_NAME'], 'php.cgi') !== false ){ //unused in Couch
        $_SERVER['SCRIPT_NAME'] = $_SERVER['PHP_SELF'];
    }

    if ( !defined('K_ADMIN_URL') ) define( 'K_ADMIN_URL', K_SITE_URL . basename(K_COUCH_DIR). '/' );
    if ( !defined('K_ADMIN_PAGE') ) define( 'K_ADMIN_PAGE', '' );
    if ( !defined('K_GMT_OFFSET') ) define( 'K_GMT_OFFSET', +5.5 );
    define( 'K_SYSTEM_THEME_DIR', K_COUCH_DIR . 'theme/_system/' );
    define( 'K_SYSTEM_THEME_URL', K_ADMIN_URL . 'theme/_system/' );

    require_once( K_COUCH_DIR.'functions.php' );
    $FUNCS = new KFuncs();

    // Language file
    global $t;
    $t = array();
    require_once( K_COUCH_DIR.'lang/EN.php' );
    $FUNCS->_t = $t;
    if( K_ADMIN_LANG != 'EN' && file_exists(K_COUCH_DIR . 'lang/' . K_ADMIN_LANG . '.php') ){
        $t = array();
        require_once( K_COUCH_DIR . 'lang/' . K_ADMIN_LANG . '.php' );
        $FUNCS->_t = array_merge( $FUNCS->_t, $t );
    }
    unset( $t );

    if( version_compare(phpversion(), "5.4.0", "<") ){
        if( get_magic_quotes_gpc() ){
            $_GET = $FUNCS->stripslashes_deep( $_GET );
            $_POST = $FUNCS->stripslashes_deep( $_POST );
            $_COOKIE = $FUNCS->stripslashes_deep( $_COOKIE );
        }
    }
    $__GET = $_GET; // save a pristine copy of $_GET before sanitizing the values
    $_GET = $FUNCS->sanitize_deep( $_GET );

    require_once( K_COUCH_DIR.'db.php' );

    $DB = new KDB();
    if( !$DB->connect() ){
        die( '<h1>Could not connect to database</h1>' . mysql_error() );
    }

    // get Couch's version
    try{
        $_rs = @mysql_query( "select k_value from ".K_TBL_SETTINGS." where k_key='k_couch_version'", $DB->conn );
    }
    catch( Exception $e ){}
    if( $_rs && ($_row = mysql_fetch_row( $_rs )) ){
        $_ver = $_row[0];
        if( version_compare(K_COUCH_VERSION, $_ver, ">") ){ // Upgrade required
            require_once( K_COUCH_DIR.'upgrade.php' );
        }
    }
    else{
        // Couch not yet installed or is the database corrupt?
        // Invoke installation routine and it will fail if any table already exists (i.e. database corrupt)
        define( 'K_INSTALLATION_IN_PROGRESS', 1 );
        require_once( K_COUCH_DIR.'install.php' );
        die();
    }

    require_once( K_COUCH_DIR.'auth/auth.php' );

    // set paths for uploaded images
    global $Config;

    if( defined('K_UPLOAD_DIR') ){
        $k_upload_dir = K_UPLOAD_DIR;
        $k_upload_dir = trim( $k_upload_dir, "/\\" );
        $k_upload_dir = str_replace( '\\', '/', $k_upload_dir );
        $Config['UserFilesPath'] = $k_upload_dir . '/';

        //real path
        $site_dir = K_SITE_DIR;
        $Config['UserFilesAbsolutePath'] = $site_dir . $k_upload_dir . '/';

        $Config['k_append_url'] = K_SITE_URL;

    }
    else{
        $Config['UserFilesPath'] = 'uploads' . '/';

        $couch_dir = K_COUCH_DIR;
        $Config['UserFilesAbsolutePath'] = $couch_dir . 'uploads' . '/';

        $Config['k_append_url'] = K_ADMIN_URL;
    }

    if( !defined('K_PRETTY_URLS') ) define( 'K_PRETTY_URLS', 0 );
    if( !defined('K_EXTRACT_EXIF_DATA') ) define( 'K_EXTRACT_EXIF_DATA', 0 );
    define( 'K_MASQUERADE_ON', (K_PRETTY_URLS && extension_loaded('curl')) );

    // full boot of core
    require_once( K_COUCH_DIR.'page.php' );
    require_once( K_COUCH_DIR.'tags.php' );
    require_once( K_COUCH_DIR.'route.php' );

    $TAGS = new KTags();
    $CTX = new KContext();
    $PAGE; // Current page being handled

    // addons to 1.3
    define( 'K_ADDONS_DIR',  K_COUCH_DIR . 'addons/' );
    require_once( K_ADDONS_DIR . 'nicedit/nicedit.php' );
    require_once( K_ADDONS_DIR . 'repeatable/repeatable.php' );
    require_once( K_ADDONS_DIR . 'relation/relation.php' );
    require_once( K_ADDONS_DIR . 'cart/session.php' );
    require_once( K_ADDONS_DIR . 'data-bound-form/data-bound-form.php' );

    // addons to 2.0
    require_once( K_ADDONS_DIR . 'recaptcha/recaptcha.php' );
    if ( defined('K_USE_ALTERNATIVE_MTA') && K_USE_ALTERNATIVE_MTA ){
        require_once( K_ADDONS_DIR . 'phpmailer/phpmailer.php' );
    }
    require_once( K_COUCH_DIR.'addons/mosaic/mosaic.php' );

    // Current user's authentication info
    $AUTH = new KAuth( );

    require_once( K_SYSTEM_THEME_DIR . 'register.php' );
    if( defined('K_ADMIN_THEME') ){
        $k_admin_theme = trim( K_ADMIN_THEME, " /\\" );

        if( $k_admin_theme ){
            if( !KFuncs::is_title_clean($k_admin_theme) ){
                die( "K_ADMIN_THEME setting in config.php contains invalid characters" );
            }
            if( is_dir(K_COUCH_DIR . 'theme/' . $k_admin_theme) ){
                define( 'K_THEME_NAME', $k_admin_theme );
                define( 'K_THEME_DIR', K_COUCH_DIR . 'theme/' . $k_admin_theme . '/' );
                define( 'K_THEME_URL', K_ADMIN_URL . 'theme/' . $k_admin_theme . '/' );
            }
        }
    }
    if( !defined('K_THEME_NAME') ) define( 'K_THEME_NAME', '' );
    if( !defined('K_THEME_DIR') ) define( 'K_THEME_DIR', '' );
    if( !defined('K_THEME_URL') ) define( 'K_THEME_URL', '' );

    // include custom functions/addons if any
    if( file_exists(K_ADDONS_DIR . 'kfunctions.php') ){
        include_once( K_ADDONS_DIR . 'kfunctions.php' );
    }
    if( file_exists(K_SITE_DIR . 'kfunctions.php') ){
        include_once( K_SITE_DIR . 'kfunctions.php' );
    }
    if( K_THEME_DIR && file_exists(K_THEME_DIR . 'kfunctions.php') ){
        include_once( K_THEME_DIR . 'kfunctions.php' );
    }
    if( K_THEME_DIR && file_exists(K_THEME_DIR . 'icons.php') ){ // translated icon names used by theme
        global $t;
        $t = array();
        include_once( K_THEME_DIR . 'icons.php' );
        $FUNCS->_ti =  array_filter( array_map("trim", $t) );
        unset( $t );
    }

    // initialize theming (for the admin panel we'll defer this till the current route is selected)
    if( !defined('K_ADMIN') ){
        $FUNCS->init_render();
    }

    // All addons loaded at this point
    $FUNCS->dispatch_event( 'init' );
