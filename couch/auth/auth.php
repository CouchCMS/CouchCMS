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

        function KAuth( $required_access_level=0, $prompt=1 ){
            global $FUNCS;

            $this->hasher = new PasswordHash(8, TRUE);

            $cookie_path = '/';
            if( ($pos = strpos(K_SITE_URL, $_SERVER['HTTP_HOST'])) !==false ){
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

            // get the current user
            $this->user = &$this->authenticate();
            if( !$this->user ){

                if( !$required_access_level ){
                    // create an anonymous user
                    $this->user = new KUser();
                }
                else{
                    if( $prompt ){
                        $this->show_login();
                    }
                    else{
                        ob_end_clean();
                        die();
                    }
                }
            }

            // if logged in user has insufficient priveleges
            if( $this->user->access_level < $required_access_level ){
                $this->show_insufficient_privileges();
            }
        }

        function check_access( $level, $show=0 ){
            if( $this->user->access_level < $level ){
                if( $show ) $this->show_insufficient_privileges();
                return 0;
            }
            return 1;
        }

        function &authenticate(){
            $user = &$this->_authenticate_post();
            if( !$user ){
                $user = &$this->_authenticate_cookie();
            }
            return $user;
        }

        function &_authenticate_cookie(){
            global $DB, $FUNCS;

            if( $_COOKIE[$this->cookie_name] ){
                $cookie = $FUNCS->cleanXSS( $_COOKIE[$this->cookie_name] );
                list( $username, $expiry, $hash ) = explode( ':', $cookie );
                if( time() < $expiry ){
                    if( $cookie == $this->create_cookie($username, $expiry) ){// if cookies match
                        // get user from database
                        $user = new KUser( $username );
                        if( $user->id == -1 ) return;
                        if( $user->disabled ){
                            $this->error = $FUNCS->t('account_disabled'); return;
                        }
                        return $user;
                    }
                }

                // delete invalid cookie
                $this->delete_cookie();
            }
        }

        function &_authenticate_post(){
            global $DB, $FUNCS;

            if( $_POST['k_login'] ){
                $this->error = '';
                $now = time();
                $max_lockout = $now - 15;

                if( isset($_POST['k_cookie_test']) && empty($_COOKIE['couchcms_testcookie']) ){
                    $this->error = $FUNCS->t('prompt_cookies');
                    return;
                }

                $username = $FUNCS->cleanXSS( trim($_POST['k_user_name']) );
                $pwd = $FUNCS->cleanXSS( trim($_POST['k_user_pwd']) );

                if( empty($username) ){
                    $this->error = $FUNCS->t('prompt_username'); return;
                }
                if( empty($pwd) ){
                    $this->error = $FUNCS->t('prompt_password'); return;
                }

                // get user from database
                $user = new KUser( $username );
                if( $user->id == -1 ){
                    $this->error = $FUNCS->t('invalid_credentials'); return;
                }
                //Ensure login attempt not within 15 secs of last failed attempt
                if( $user->last_failed > $max_lockout ){
                    $this->error = $FUNCS->t('invalid_credentials'); return;
                }
                if( $user->disabled ){
                    $this->error = $FUNCS->t('account_disabled'); return;
                }

                // check password
                $check = $this->hasher->CheckPassword( $pwd, $user->password );
                if( !$check ){
                    // Update user record with last_failed_login time here
                    $rs = $DB->update( K_TBL_USERS, array('last_failed'=>$now), "id='" . $DB->sanitize( $user->id ). "'" );
                    if( $rs==-1 ) die( "ERROR: Unable to update data in K_TBL_USERS" );

                    $this->error = $FUNCS->t('invalid_credentials'); return;
                }

                // set an access cookie for future visits of this user
                $this->set_cookie( $username );
            }

            return $user;
        }

        function set_cookie( $username ){
            // create a httpOnly cookie
            $cookie_expiry = time() + (3600 * 12); // 12 hours
            $cookie = $this->create_cookie( $username, $cookie_expiry );
            if( version_compare(phpversion(), '5.2.0', '>=') ) {
                setcookie($this->cookie_name, $cookie, 0, $this->cookie_path, null, null, true);
            }
            else{
                //setcookie($this->cookie_name, $cookie, $cookie_expiry, $this->cookie_path . '; httponly'); //this works too but safe to be explicit
                //$date = gmstrftime("%a, %d-%b-%Y %H:%M:%S", $cookie_expiry ) .' GMT';
                //header("Set-Cookie: ".rawurlencode($this->cookie_name)."=".rawurlencode($cookie)."; expires=$date; path=$this->cookie_path; httpOnly");
                header("Set-Cookie: ".rawurlencode($this->cookie_name)."=".rawurlencode($cookie)."; path=$this->cookie_path; httpOnly");
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
                setcookie( $this->cookie_name, ' ', time() - (3600 * 24 * 365), $this->cookie_path, null, null, true );
            }
            else{
                setcookie( $this->cookie_name, ' ', time() - (3600 * 24 * 365), $this->cookie_path, null, null );
            }
        }

        function show_login(){
            global $FUNCS;

            ob_end_clean();
            header( 'Content-Type: text/html; charset='.K_CHARSET );
            setcookie( 'couchcms_testcookie', 'CouchCMS test cookie', 0, $this->cookie_path, null);

            $err_div = '<div class="error" style="margin-bottom:10px; display:';
            if( $this->error ){
                $err_div .= "block\">";
                $err_div .= $this->error;
            }
            else{
                $err_div .= "none\">&nbsp;";
            }
            $err_div .= '</div>';

            ?>
                <?php echo( $FUNCS->login_header() ); ?>

                    <?php echo $err_div ?>
                    <form name="frm_login" action="" method="post">
                        <p>
                            <label><?php echo $FUNCS->t('user_name'); ?></label><br>
                            <input type="text"  id="k_user_name" name="k_user_name" size="20" autofocus="autofocus" />
                        </p>
                        <p>
                            <label><?php echo $FUNCS->t('password'); ?></label><br>
                            <input type="password" id="k_user_pwd" name="k_user_pwd" size="20"/>
                        </p>
                        <input type="hidden" name="k_cookie_test" value="1" />
                        <input type="submit" name="k_login" value="<?php echo $FUNCS->t('login'); ?>"/>
                    </form>
                    <p>
                        <?php echo '<a href="'.K_ADMIN_URL.'forgotpassword.php">'.$FUNCS->t('forgot_password').'</a>'; ?>
                    </p>

                <?php echo( $FUNCS->login_footer() ); ?>
            <?php
            die();
        }

        function show_insufficient_privileges(){
            global $FUNCS;

            $ret = ob_end_clean();
            header( 'Content-Type: text/html; charset='.K_CHARSET );
            $nonce = $FUNCS->create_nonce( 'logout'.$this->user->id, $this->user->name );
            $redirect = urlencode( $_SERVER["REQUEST_URI"] );
            $logout_link = K_ADMIN_URL.'login.php?act=logout&nonce='.$nonce. '&redirect='.$redirect;
            $logout_link = '<a href="'.$logout_link.'">'.$FUNCS->t('logout').'</a>';
            ?>
            <?php echo( $FUNCS->login_header() ); ?>
            	<div class='wrapper'>
                    <h1 style="margin: 0 0 10px 0; padding: 0pt; font-size: 20px;"><?php echo $FUNCS->t('access_denied'); ?></h1>
                    <p><?php echo $FUNCS->t('insufficient_privileges'); ?></p>
                    <?php echo $logout_link; ?>
                </div>
            <?php echo( $FUNCS->login_footer() ); ?>
            <?php
            die();
        }
    }

