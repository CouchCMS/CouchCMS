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
        error_reporting(E_ALL & ~(E_NOTICE | E_STRICT)); // Report all errors except notices and strict standard warnings
    }
    else{
        error_reporting(E_ALL & ~E_NOTICE); // Report all errors except notices
    }
    // Since PHP 5.1.0 every call to a date/time function generates a E_NOTICE if the timezone isn't valid,
    if( version_compare( phpversion(), '5.1.0', '>=' ) ){
        date_default_timezone_set("America/New_York");
    }



    if( !defined('K_COUCH_DIR') ) die(); // cannot be loaded directly

    define( 'K_COUCH_VERSION', '1.4.7' ); // Changes with every release
    define( 'K_COUCH_BUILD', '20151124' ); // YYYYMMDD - do -

    if( file_exists(K_COUCH_DIR.'config.php') ){
        require_once( K_COUCH_DIR.'config.php' );
    }
    else{
        die( '<h3>"config.php" not found. Perhaps you forgot to rename the "config.example.php" file to "config.php" after editing it?</h3>' );
    }
    if( function_exists('mb_internal_encoding') ) mb_internal_encoding( K_CHARSET );

    // Check license
    // Ultra-simplified now that there is no IonCube involved :)
    if( !defined('K_PAID_LICENSE') ) define( 'K_PAID_LICENSE', 0 );
    if( !defined('K_REMOVE_FOOTER_LINK') ) define( 'K_REMOVE_FOOTER_LINK', 0 );
    if( !K_PAID_LICENSE ){
        require_once( K_COUCH_DIR.'logo.php' );
        if( !defined('K_COUCH_LOGO_FILE') ){ die( '<h3>Invalid logo.php.</h3>' ); }
    }

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
            $k_cache_dir = K_COUCH_DIR . 'cache/';
            if( is_writable($k_cache_dir) ){
                $k_cache_file = $k_cache_dir . md5($_SERVER['REQUEST_URI']) . '.dat';
                if( file_exists($k_cache_file) ){

                    // Check if the cache has not expired
                    $file_time = @filemtime( $k_cache_file );
                    $cache_invalidated_time = @filemtime( $k_cache_dir . 'cache_invalidate.dat' );

                    if( $file_time > $cache_invalidated_time ){
                        // Send back the cached contents
                        $pg = @unserialize( file_get_contents($k_cache_file) );
                        if( $pg ){
                            if( $pg['redirect_url'] ){
                                header( "Location: ".$pg['redirect_url'], TRUE, 301 );
                                die();
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

    if( version_compare( '5.0.0', phpversion(), '>' ) ) {
        die( 'You are using PHP version '. phpversion().' but the CMS requires at least 5.0' );
    }
    if( version_compare(phpversion(), '5.0') < 0 ){
        /*
         // hack for making PHP4 understand 'clone'
        eval('
        function clone($object) {
          return $object;
        }
        ');
        */
        define( 'K_PHP_4', 1 );
    }

    // Refuse to run on IIS
    $web_server = strtolower( $_SERVER['SERVER_SOFTWARE'] );
    if( (strpos($web_server, 'microsoft')!==false) && (strpos($web_server, 'iis')!==false) ){
        die( 'Microsoft-IIS Web Server not supported by CMS' );
    }

    if( !extension_loaded('mysql') ){
        die( 'MySQL extension missing from your host\'s PHP installation' );
    }

    if ( !defined('K_SITE_DIR') ) define( 'K_SITE_DIR', dirname( K_COUCH_DIR ) . '/' );
    if ( !defined('K_HTTPS') ) define( 'K_HTTPS', (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS']=='on') ? 1 : 0 );

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
            if( $path{strlen($path)-1}=='/' ){
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
                if( $path{strlen($path)-1}=='/' ){
                    $path = substr( $path, 0, strlen($path)-1);
                }

                $path2 = str_replace( '\\', '/', realpath('./') );
                if( $path2{strlen($path2)-1}=='/' ){
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

    if( get_magic_quotes_gpc() ){
        $_GET = $FUNCS->stripslashes_deep( $_GET );
        $_POST = $FUNCS->stripslashes_deep( $_POST );
        $_COOKIE = $FUNCS->stripslashes_deep( $_COOKIE );
    }
    $_GET = $FUNCS->sanitize_deep( $_GET );

    require_once( K_COUCH_DIR.'db.php' );

    $DB = new KDB();
    if( !$DB->connect() ){
        die( '<h1>Could not connect to database</h1>' . mysql_error() );
    }
    // get Couch's version
    $_rs = @mysql_query( "select k_value from ".K_TBL_SETTINGS." where k_key='k_couch_version'", $DB->conn );
    if( $_rs && ($_row = mysql_fetch_row( $_rs )) ){

        $_ver = $_row[0];
        if( version_compare(K_COUCH_VERSION, $_ver, ">") ){ // Upgrade required

            $DB->begin();

            // will move the queries to a separate file later
            // upgrade to 1.0.2 (unreleased .. merged with 1.1)
            if( version_compare("1.0.2", $_ver, ">") ){
                // dynamic folders
                $_sql = "ALTER TABLE `".K_TBL_TEMPLATES."` ADD `dynamic_folders` int(1) DEFAULT '0';";
                $DB->_query( $_sql );

                $_sql = "ALTER TABLE `".K_TBL_TEMPLATES."` MODIFY `name` varchar(255) NOT NULL;";
                $DB->_query( $_sql );

                $_sql = "ALTER TABLE `".K_TBL_FOLDERS."` ADD `image` text;";
                $DB->_query( $_sql );

                $_sql = "CREATE INDEX ".K_TBL_FOLDERS."_Index01 ON ".K_TBL_FOLDERS." (template_id, id);";
                $DB->_query( $_sql );

                $_sql = "CREATE INDEX ".K_TBL_FOLDERS."_Index02 ON ".K_TBL_FOLDERS." (template_id, name(255));";
                $DB->_query( $_sql );

            }

            // upgrade to 1.1
            if( version_compare("1.1.0", $_ver, ">") ){
                // drafts
                $_sql = "ALTER TABLE `".K_TBL_PAGES."` ADD `parent_id` int DEFAULT '0';";
                $DB->_query( $_sql );

                $_sql = "CREATE INDEX `".K_TBL_PAGES."_Index11` ON `".K_TBL_PAGES."` (`template_id`, `parent_id`, `modification_date`);";
                $DB->_query( $_sql );

                $_sql = "CREATE INDEX `".K_TBL_PAGES."_Index12` ON `".K_TBL_PAGES."` (`parent_id`, `modification_date`);";
                $DB->_query( $_sql );
            }

            // upgrade to 1.2 //actually RC1 (will be considered < 1.2.0)
            if( version_compare("1.2", $_ver, ">") ){
                // nested pages
                $_sql = "ALTER TABLE `".K_TBL_TEMPLATES."` ADD `nested_pages` int(1) DEFAULT '0';";
                $DB->_query( $_sql );

                $_sql = "ALTER TABLE `".K_TBL_PAGES."` ADD `nested_parent_id` int DEFAULT '-1';";
                $DB->_query( $_sql );

                $_sql = "ALTER TABLE `".K_TBL_PAGES."` ADD `weight` int DEFAULT '0';";
                $DB->_query( $_sql );

                $_sql = "ALTER TABLE `".K_TBL_PAGES."` ADD `show_in_menu` int(1) DEFAULT '1';";
                $DB->_query( $_sql );

                $_sql = "ALTER TABLE `".K_TBL_PAGES."` ADD `menu_text` varchar(255);";
                $DB->_query( $_sql );

                $_sql = "ALTER TABLE `".K_TBL_PAGES."` ADD `is_pointer` int(1) DEFAULT '0';";
                $DB->_query( $_sql );

                $_sql = "ALTER TABLE `".K_TBL_PAGES."` ADD `pointer_link` text;";
                $DB->_query( $_sql );

                $_sql = "ALTER TABLE `".K_TBL_PAGES."` ADD `pointer_link_detail` text;";
                $DB->_query( $_sql );

                $_sql = "ALTER TABLE `".K_TBL_PAGES."` ADD `open_external` int(1) DEFAULT '0';";
                $DB->_query( $_sql );

                $_sql = "ALTER TABLE `".K_TBL_PAGES."`  ADD `masquerades` int(1) DEFAULT '0';";
                $DB->_query( $_sql );

                $_sql = "ALTER TABLE `".K_TBL_PAGES."`  ADD `strict_matching` int(1) DEFAULT '0';";
                $DB->_query( $_sql );
            }
            // upgrade to 1.2.0RC2
            if( version_compare("1.2.0RC2", $_ver, ">") ){
                $_sql = "CREATE INDEX `".K_TBL_PAGES."_Index13` ON `".K_TBL_PAGES."` (`template_id`, `is_pointer`, `masquerades`, `pointer_link_detail`(255));";
                $DB->_query( $_sql );
            }
            // upgrade to 1.2.0 //release
            if( version_compare("1.2.0", $_ver, ">") ){

            }
            // upgrade to 1.2.5RC1
            if( version_compare("1.2.5RC1", $_ver, ">") ){
                $_sql = "ALTER TABLE `".K_TBL_TEMPLATES."` ADD `gallery` int(1) DEFAULT '0';";
                $DB->_query( $_sql );

                $_sql = "ALTER TABLE `".K_TBL_PAGES."` ADD `file_name` varchar(260);";
                $DB->_query( $_sql );

                $_sql = "ALTER TABLE `".K_TBL_PAGES."` ADD `file_ext` varchar(20);";
                $DB->_query( $_sql );

                $_sql = "ALTER TABLE `".K_TBL_PAGES."` ADD `file_size` int DEFAULT '0';";
                $DB->_query( $_sql );

                $_sql = "ALTER TABLE `".K_TBL_PAGES."` ADD `file_meta` text;";
                $DB->_query( $_sql );

                $_sql = "CREATE INDEX `".K_TBL_PAGES."_Index14` ON `".K_TBL_PAGES."` (`template_id`, `file_name`(255));";
                $DB->_query( $_sql );

                $_sql = "CREATE INDEX `".K_TBL_PAGES."_Index15` ON `".K_TBL_PAGES."` (`template_id`, `page_folder_id`, `file_name`(255));";
                $DB->_query( $_sql );

                $_sql = "CREATE INDEX `".K_TBL_PAGES."_Index16` ON `".K_TBL_PAGES."` (`template_id`, `file_ext`(20), `file_name`(255));";
                $DB->_query( $_sql );

                $_sql = "CREATE INDEX `".K_TBL_PAGES."_Index17` ON `".K_TBL_PAGES."` (`template_id`, `page_folder_id`, `file_ext`(20), `file_name`(255));";
                $DB->_query( $_sql );

                $_sql = "CREATE INDEX `".K_TBL_PAGES."_Index18` ON `".K_TBL_PAGES."` (`template_id`, `file_size`);";
                $DB->_query( $_sql );

                $_sql = "CREATE INDEX `".K_TBL_PAGES."_Index19` ON `".K_TBL_PAGES."` (`template_id`, `page_folder_id`, `file_size`);";
                $DB->_query( $_sql );
            }
            // upgrade to 1.3RC1
            if( version_compare("1.3RC1", $_ver, ">") ){
                $_sql = "ALTER TABLE `".K_TBL_FIELDS."` ADD `custom_params` text;";
                $DB->_query( $_sql );

                $_sql = "CREATE TABLE `".K_TBL_RELATIONS."` (
                    `pid`     int NOT NULL,
                    `fid`     int NOT NULL,
                    `cid`     int NOT NULL,
                    `weight`  int DEFAULT '0',
                    PRIMARY KEY (`pid`, `fid`, `cid`)
                ) ENGINE = InnoDB
                CHARACTER SET `utf8` COLLATE `utf8_general_ci`;";
                $DB->_query( $_sql );

                $_sql = "CREATE INDEX `".K_TBL_RELATIONS."_Index01` ON `".K_TBL_RELATIONS."` (`pid`, `fid`, `weight`);";
                $DB->_query( $_sql );

                $_sql = "CREATE INDEX `".K_TBL_RELATIONS."_Index02` ON `".K_TBL_RELATIONS."` (`fid`, `cid`, `weight`);";
                $DB->_query( $_sql );

                $_sql = "CREATE INDEX `".K_TBL_RELATIONS."_Index03` ON `".K_TBL_RELATIONS."` (`cid`);";
                $DB->_query( $_sql );
            }
            // upgrade to 1.4RC1
            if( version_compare("1.4RC1", $_ver, ">") ){
                $_sql = "ALTER TABLE `".K_TBL_PAGES."` ADD `creation_IP` varchar(45);";
                $DB->_query( $_sql );

                $_sql = "CREATE INDEX `".K_TBL_PAGES."_Index20` ON `".K_TBL_PAGES."` (`creation_IP`, `creation_date`);";
                $DB->_query( $_sql );

                $_sql = "CREATE TABLE `".K_TBL_ATTACHMENTS."` (
                    `attach_id`       bigint(11) UNSIGNED AUTO_INCREMENT NOT NULL,
                    `file_real_name`  varchar(255) NOT NULL,
                    `file_disk_name`  varchar(255) NOT NULL,
                    `file_extension`  varchar(255) NOT NULL,
                    `file_size`       int(20) UNSIGNED NOT NULL DEFAULT '0',
                    `file_time`       int(10) UNSIGNED NOT NULL DEFAULT '0',
                    `is_orphan`       tinyint(1) UNSIGNED DEFAULT '1',
                    `hit_count`       int(10) UNSIGNED DEFAULT '0',
                    PRIMARY KEY (`attach_id`)
                    ) ENGINE = InnoDB CHARACTER SET `utf8` COLLATE `utf8_general_ci`;";
                $DB->_query( $_sql );

                $_sql = "CREATE INDEX `".K_TBL_ATTACHMENTS."_Index01` ON `".K_TBL_ATTACHMENTS."` (`is_orphan`);";
                $DB->_query( $_sql );

                $_sql = "CREATE INDEX `".K_TBL_ATTACHMENTS."_Index02` ON `".K_TBL_ATTACHMENTS."` (`file_time`);";
                $DB->_query( $_sql );

                $_sql = "CREATE INDEX `".K_TBL_ATTACHMENTS."_Index03` ON `".K_TBL_ATTACHMENTS."` (`is_orphan`, `file_time`);";
                $DB->_query( $_sql );
            }
            // upgrade to 1.4.5RC1
            if( version_compare("1.4.5RC1", $_ver, ">") ){
                $_sql = "ALTER TABLE `".K_TBL_ATTACHMENTS."` ADD `creation_ip` varchar(45);";
                $DB->_query( $_sql );

                $_sql = "CREATE INDEX `".K_TBL_ATTACHMENTS."_index04` ON `".K_TBL_ATTACHMENTS."` (`creation_ip`, `file_time`);";
                $DB->_query( $_sql );

                $_sql = "ALTER TABLE `".K_TBL_TEMPLATES."` ADD `handler` text;";
                $DB->_query( $_sql );

                $_sql = "ALTER TABLE `".K_TBL_TEMPLATES."` ADD `custom_params` text;";
                $DB->_query( $_sql );

                $_sql = "ALTER TABLE `".K_TBL_USERS."` ADD `password_reset_key` varchar(64);";
                $DB->_query( $_sql );

                $_sql = "ALTER TABLE `".K_TBL_USERS."` ADD `failed_logins` int DEFAULT '0';";
                $DB->_query( $_sql );

                $_sql = "CREATE INDEX `".K_TBL_USERS."_password_reset_key` ON `".K_TBL_USERS."` (`password_reset_key`);";
                $DB->_query( $_sql );

                $_sql = "ALTER TABLE `".K_TBL_USERS."` MODIFY `name` varchar(255) NOT NULL;";
                $DB->_query( $_sql );
            }
            // upgrade to 1.4.5
            if( version_compare("1.4.5", $_ver, ">") ){
                $_sql = "ALTER TABLE `".K_TBL_FIELDS."` ADD `searchable` int(1) DEFAULT '1';";
                $DB->_query( $_sql );
            }

            // Finally update version number
            $_rs = $DB->update( K_TBL_SETTINGS, array('k_value'=>K_COUCH_VERSION), "k_key='k_couch_version'" );
            if( $_rs==-1 ) die( "ERROR: Unable to update version number" );
            $DB->commit( 1 );
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
    if ( defined('K_USE_ALTERNATIVE_MTA') && K_USE_ALTERNATIVE_MTA ){
        require_once( K_COUCH_DIR.'includes/email.php' );
    }

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

    $TAGS = new KTags();
    $CTX = new KContext();
    $PAGE; // Current page being handled

    // addons to 1.3
    require_once( K_COUCH_DIR . 'addons/nicedit/nicedit.php' );
    require_once( K_COUCH_DIR . 'addons/repeatable/repeatable.php' );
    require_once( K_COUCH_DIR . 'addons/relation/relation.php' );

    // include custom functions/addons if any
    if( file_exists(K_COUCH_DIR . 'addons/kfunctions.php') ){
        include_once( K_COUCH_DIR.'addons/kfunctions.php' );
    }
    if( file_exists(K_SITE_DIR . 'kfunctions.php') ){
        include_once( K_SITE_DIR . 'kfunctions.php' );
    }

    // Current user's authentication info
    $AUTH = new KAuth( );

    // All addons loaded at this point
    $FUNCS->dispatch_event( 'init' );
