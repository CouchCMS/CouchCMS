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

    if ( !defined('K_COUCH_DIR') ) die(); // cannot be loaded directly
    require_once( K_COUCH_DIR.'auth/PasswordHash.php' );

    define( 'K_MIN_PASSWORD_LEN', 5 );
    define( 'K_ACCESS_LEVEL_SUPER_ADMIN', 10 );
    define( 'K_ACCESS_LEVEL_ADMIN', 7 );
    define( 'K_ACCESS_LEVEL_AUTHENTICATED_SPECIAL', 4 );
    define( 'K_ACCESS_LEVEL_AUTHENTICATED', 2 );
    define( 'K_ACCESS_LEVEL_UNAUTHENTICATED', 0 );

    require_once( K_COUCH_DIR.'auth/user.php' );

    class KAuth{
        var $hasher;
        var $cookie_name;
        var $cookie_path;
        var $cookie_domain;
        var $secret_key;
        var $error = '';
        var $user; //current user

        function __construct( $required_access_level=0, $prompt=1 ){
            global $FUNCS;

            $this->hasher = new PasswordHash(8, TRUE);

            $cookie_path = '/';
            if( !empty($_SERVER['HTTP_HOST']) && (($pos = strpos(K_SITE_URL, $_SERVER['HTTP_HOST'])) !==false) ){
                $given_site_dir = substr( K_SITE_URL, $pos + strlen($_SERVER['HTTP_HOST']) );
                if( substr(K_SITE_DIR, -(strlen($given_site_dir))) == $given_site_dir ){
                    $cookie_path = $given_site_dir;
                }
            }
            $this->cookie_name = 'couchcms_'. md5( K_SITE_URL );
            $this->cookie_path = $cookie_path;
            $this->cookie_domain = $_SERVER['HTTP_HOST'];
            //$this->cookie_domain = preg_replace( '|^www\.(.*)$|', '.\\1', $_SERVER['HTTP_HOST'] ); //for all sub-domains too.

            $this->secret_key = $FUNCS->get_secret_key();

            // get current user, if any
            $this->user = &$this->_authenticate_cookie();
            if( !$this->user ){
                $this->user = new KUser();
            }

            // for backward compatibility .. deprecated now
            if( $required_access_level ){
                $this->check_access( $required_access_level, !$prompt );
            }
        }

        function check_access( $required_access_level=0, $kill=0 ){
            global $FUNCS;

            if( $this->user->access_level < $required_access_level ){
                if( $kill ){
                    ob_end_clean();
                    die();
                }
                else{
                    // if user is authenticated but with insufficient privileges
                    if( $this->user->id != -1 ){
                        $this->show_insufficient_privileges();
                    }
                    else{
                        // prompt for login
                        $url = $FUNCS->get_login_link();
                        $this->redirect( $url );
                    }

                }
            }
        }

        function &_authenticate_cookie(){
            global $DB, $FUNCS;

            if( $_COOKIE[$this->cookie_name] ){
                $cookie = $FUNCS->cleanXSS( $_COOKIE[$this->cookie_name] );
                list( $username, $expiry, $hash ) = explode( ':', $cookie );
                if( time() < $expiry ){
                    if( $cookie === $this->create_cookie($username, $expiry) ){// if cookies match
                        // get user from database
                        $user = new KUser( $username );
                        if( $user->id != -1 && !$user->disabled ){
                            return $user;
                        }
                    }
                }

                // delete invalid cookie
                $this->delete_cookie();
            }
        }

        function login( $username='', $pwd='', $remember='' ){
            global $DB, $FUNCS;

            if( isset($_POST['k_cookie_test']) && empty($_COOKIE['couchcms_testcookie']) ){
                return $FUNCS->raise_error( $FUNCS->t('prompt_cookies') );
            }

            $now = time();
            $max_lockout = $now - 20;

            $username = strlen( trim($username) ) ? $username : $_POST['k_user_name'];
            $pwd = strlen( trim($pwd) ) ? $pwd : $_POST['k_user_pwd'];
            $remember = strlen( trim($remember) ) ? $remember : $_POST['k_user_remember'][0];

            $username = trim( $username );
            $pwd = trim( $pwd );
            if( strlen($username)>1024 || strlen($pwd)>255 ){ // hack attempt?
                return $FUNCS->raise_error( $FUNCS->t('invalid_credentials') );
            }

            $username = $FUNCS->cleanXSS( $username );
            $pwd = $FUNCS->cleanXSS( $pwd );
            $remember = ( trim($remember)==='1' ) ? 1 : 0;

            if( empty($username) ){
                return $FUNCS->raise_error( $FUNCS->t('prompt_username') );
            }
            if( empty($pwd) ){
                return $FUNCS->raise_error( $FUNCS->t('prompt_password') );
            }

            // get user from database
            $user = new KUser( $username );
            if( $user->id == -1 ){
                return $FUNCS->raise_error( $FUNCS->t('invalid_credentials') );
            }

            // ensure no more than 3 failed login attempts within 20 seconds
            if( ($user->failed_logins >= 3) && ($user->last_failed > $max_lockout) ){
                return $FUNCS->raise_error( $FUNCS->t('invalid_credentials') );
            }

            if( $user->disabled ){
                return $FUNCS->raise_error( $FUNCS->t('account_disabled') );
            }

            // check password
            $check = $this->hasher->CheckPassword( $pwd, $user->password );
            if( !$check ){

                // Update user record with last_failed_login time and number of failed attempts
                $sql = "UPDATE ".K_TBL_USERS." SET last_failed='".$now."', failed_logins=failed_logins+1 WHERE id='".$DB->sanitize( $user->id )."'";
                $DB->_query( $sql );

                return $FUNCS->raise_error( $FUNCS->t('invalid_credentials') );
            }

            // All OK .. user can login.
            // reset failed login counter for this user
            if( $user->failed_logins ){
                $sql = "UPDATE ".K_TBL_USERS." SET last_failed='0', failed_logins='0' WHERE id='".$DB->sanitize( $user->id )."'";
                $DB->_query( $sql );
            }

            $this->user = &$user;

            // set an access cookie for future visits of this user
            $this->set_cookie( $username, $remember );

        }

        function logout( $nonce='' ){
            global $FUNCS;

            $FUNCS->validate_nonce( 'logout'.$this->user->id, $nonce );
            $this->delete_cookie();

        }

        function set_cookie( $username, $remember=0 ){
            // create a httpOnly cookie
            $days_valid = ( $remember ) ? 14 : 1;
            $cookie_expiry = time() + (3600 * 24 * $days_valid);
            $cookie = $this->create_cookie( $username, $cookie_expiry );
            if( version_compare(phpversion(), '5.2.0', '>=') ) {
                if( $remember ){
                    setcookie( $this->cookie_name, $cookie, $cookie_expiry, $this->cookie_path, null, K_HTTPS ? true : null, true );
                }
                else{
                    setcookie( $this->cookie_name, $cookie, 0, $this->cookie_path, null, K_HTTPS ? true : null, true );
                }
            }
            else{
                if( $remember ){
                    $date = gmstrftime("%a, %d-%b-%Y %H:%M:%S", $cookie_expiry ) .' GMT';
                    header( "Set-Cookie: ".rawurlencode($this->cookie_name)."=".rawurlencode($cookie)."; expires=$date; path=$this->cookie_path; httpOnly".(K_HTTPS ? "; Secure" : "") );
                }
                else{
                    header( "Set-Cookie: ".rawurlencode($this->cookie_name)."=".rawurlencode($cookie)."; path=$this->cookie_path; httpOnly".(K_HTTPS ? "; Secure" : "") );
                }
            }
        }

        function create_cookie( $username, $cookie_expiry ){
            global $FUNCS;

            // implementation of 'A Secure Cookie Protocol - Alex X. liu'
            $data = $username . ':' . $cookie_expiry;
            $key = $FUNCS->hash_hmac( $data, $this->secret_key );
            $hash = $FUNCS->hash_hmac( $data, $key );
            return $data . ':' . $hash;
        }

        function delete_cookie(){
            if( version_compare(phpversion(), '5.2.0', '>=') ) {
                setcookie( $this->cookie_name, ' ', time() - (3600 * 24 * 365), $this->cookie_path, null, K_HTTPS ? true : null, true );
            }
            else{
                setcookie( $this->cookie_name, ' ', time() - (3600 * 24 * 365), $this->cookie_path, null, K_HTTPS ? true : null );
            }
        }

        function redirect( $dest ){
            global $FUNCS, $DB;

            // sanity checks
            $default_dest = ( $this->user->access_level < K_ACCESS_LEVEL_ADMIN ) ? K_SITE_URL : K_ADMIN_URL . K_ADMIN_PAGE;
            $dest = $FUNCS->sanitize_url( $dest, $default_dest, 1  );

            $DB->commit( 1 );
            header( "Location: ".$dest );
            die();
        }

        function show_login( $res ){
            global $FUNCS;

            ob_end_clean();
            header( 'Content-Type: text/html; charset='.K_CHARSET );
            setcookie( 'couchcms_testcookie', 'CouchCMS test cookie', 0, $this->cookie_path, null, K_HTTPS ? true : null );

            $html = $FUNCS->render( 'login', $res );
            echo $html;

            die();
        }

        function show_insufficient_privileges(){
            global $FUNCS;

            ob_end_clean();
            header( 'Content-Type: text/html; charset='.K_CHARSET );

            $html = $FUNCS->render( 'insufficient_privileges' );
            echo $html;

            die();
        }

        // utilty functions shared with 'extended users'
        function reset_key( $username ){
            global $FUNCS, $DB;

            $user = new KUser( $username );

            if( $user->id==-1 ){
                return $FUNCS->raise_error( $FUNCS->t( 'no_such_user' ) );
            }
            if( $user->disabled ){
                return $FUNCS->raise_error( $FUNCS->t( 'account_disabled' ) );
            }

            $id = $user->id;
            $key = $user->password_reset_key;

            if( empty($key) ){
                $key = $FUNCS->generate_key( 32 );
                $rs = $DB->update( K_TBL_USERS, array('password_reset_key'=>$key), "id='" . $DB->sanitize( $id ). "'" );
                if( $rs==-1 ) die( "ERROR: Unable to update K_TBL_USERS" );

                $user->password_reset_key = $key;
            }

            return $user;

        }

        function get_hash( $user, $value, $expiry ){
            global $FUNCS;

            $data = $user . '|' . $value . '|' . $expiry;
            $key = $FUNCS->hash_hmac( $data, $FUNCS->_get_nonce_secret_key() );
            $hash = $FUNCS->hash_hmac( $data, $key );

            return $data . '|' . $hash;
        }
    }
