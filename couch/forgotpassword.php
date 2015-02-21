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

    ob_start();

    if ( !defined('K_COUCH_DIR') ) define( 'K_COUCH_DIR', str_replace( '\\', '/', dirname(realpath(__FILE__) ).'/') );
    require_once( K_COUCH_DIR.'header.php' );
    header( 'Content-Type: text/html; charset='.K_CHARSET );

    $AUTH = new KAuth();
    if( $AUTH->user->id != -1 ){
        // if already logged-in, why are you here?
        header("Location: ".rawurldecode(K_SITE_URL));
        die;
    }

    $msg = "";
    $msg_class = 'notice';
    if( $_POST['k_submit'] ){
        $rs = request_confirmation();
        if( $FUNCS->is_error($rs) ){
            $msg = $rs->err_msg;
            $msg_class = 'error';
        }
        else{
            $msg = $FUNCS->t( 'reset_req_email_confirm' );
            $showonlymsg = 1;
        }
    }
    elseif( isset($_GET['act']{0}) && $_GET['act'] == 'reset' ){
        $rs = reset_password();
        if( $FUNCS->is_error($rs) ){
            $msg = $rs->err_msg;
            $msg_class = 'error';
        }
        else{
            $msg = $FUNCS->t( 'reset_email_confirm' );
        }
        $showonlymsg = 1;
    }
    show_form( $msg, $msg_class, $showonlymsg );

    ////////////////////////////////////////////////////////////////////////////
    function request_confirmation(){
        global $FUNCS, $DB;

        $val = $FUNCS->cleanXSS( trim($_POST['k_user_name']) );
        if( $val && is_string( $val ) ){

            $user = new KUser( $val );

            if( $user->id==-1 ){
                $err_msg = $FUNCS->t( 'no_such_user' );
            }
            else{
                $id = $user->id;
                $name = $user->name;
                $to = $user->email;
                $key = $user->activation_key;

                if( empty($key) ){
                    $key = $FUNCS->generate_key( 32 );
                    $rs = $DB->update( K_TBL_USERS, array('activation_key'=>$key), "id='" . $DB->sanitize( $id ). "'" );
                    if( $rs==-1 ) die( "ERROR: Unable to update K_TBL_USERS" );
                }
                // Send confirmation email to the user
                $subject = $FUNCS->t( 'reset_req_email_subject' );

                $msg = $FUNCS->t( 'reset_req_email_msg_0' ) . ": \r\n";
                $msg .= K_SITE_URL . "\r\n";
                $msg .= $FUNCS->t( 'user_name' ) .': ' . $name . "\r\n\r\n";
                $msg .= $FUNCS->t( 'reset_req_email_msg_1' ) . "\r\n";
                $msg .= K_ADMIN_URL . "forgotpassword.php?act=reset&name=" . rawurlencode( $name ) . "&key=".$key ."\r\n";

                $site = preg_replace('|^(?:www\.)?(.*)$|', '\\1', $_SERVER['SERVER_NAME']);
                $from = 'admin@' . $site;

                $rs = $FUNCS->send_mail( $from, $to, $subject, $msg );
                if( $rs ){
                    return;
                }
                $err_msg = $FUNCS->t( 'email_failed' );
            }

        }
        else{
            $err_msg = $FUNCS->t( 'submit_error' ); //Please enter your username or email address.
        }
        return $FUNCS->raise_error( $err_msg );
    }

    function reset_password(){
        global $FUNCS, $DB;

        //?act=reset&name=johndoe&key=11uBfS3TTvIbbKq4OWGF2Wqxy58NAdM1
        $name = $FUNCS->cleanXSS( $_GET['name'] );
        $key = $FUNCS->cleanXSS( $_GET['key'] );

        if( !$name || !$FUNCS->is_title_clean($name) ){
            return $FUNCS->raise_error( $FUNCS->t('invalid_key') );
        }
        if( !$key || !$FUNCS->is_alphanumeric($key) ){
            return $FUNCS->raise_error( $FUNCS->t('invalid_key') );
        }

        // get the user with this activation key
        $rs = $DB->select( K_TBL_USERS, array('id', 'name', 'email'), "name='" . $DB->sanitize( $name )."' AND activation_key='".$DB->sanitize( $key )."'" );
        if( !count($rs) ){
            return $FUNCS->raise_error( $FUNCS->t('invalid_key') );
        }
        else{
            $id = $rs[0]['id'];
            $name = $rs[0]['name'];
            $to = $rs[0]['email'];

            // generate a new password for the user
            $AUTH = new KAuth( 0 );
            $password = $FUNCS->generate_key( 12 );
            $hash = $AUTH->hasher->HashPassword( $password );

            // update record
            $rs = $DB->update( K_TBL_USERS, array('password'=>$hash, 'activation_key'=>''), "id='" . $DB->sanitize( $id ). "'" );
            if( $rs==-1 ) die( "ERROR: Unable to update K_TBL_USERS" );

            // send the new password to the user
            $subject = $FUNCS->t( 'reset_email_subject' );

            $msg = $FUNCS->t( 'reset_email_msg_0' ) . ": \r\n";
            $msg .= K_SITE_URL . "\r\n";
            $msg .= $FUNCS->t( 'user_name' ) .': ' . $name . "\r\n\r\n";
            $msg .= $FUNCS->t( 'new_password' ) .': ' . $password . "\r\n\r\n";
            $msg .= $FUNCS->t( 'reset_email_msg_1' ) . "\r\n";

            $site = preg_replace('|^(?:www\.)?(.*)$|', '\\1', $_SERVER['SERVER_NAME']);
            $from = 'admin@' . $site;

            $rs = $FUNCS->send_mail( $from, $to, $subject, $msg );
            if( !$rs ){
                return $FUNCS->raise_error( $FUNCS->t( 'email_failed' ) );
            }
        }

    }

    function show_form( $msg, $msg_class, $msgonly=0 ){
        global $FUNCS;

        if( empty($msg) ){
            $msg = $FUNCS->t('recovery_prompt');
        }
        $msg_div = '<div class="'.$msg_class.'" style="margin-bottom:10px; display:';
        if( $msg ){
            $msg_div .= "block\">";
            $msg_div .= $msg;
        }
        else{
            $msg_div .= "none\">&nbsp;";
        }
        $msg_div .= '</div>';
        ?>
        <?php echo( $FUNCS->login_header() ); ?>

            <?php echo $msg_div ?>
            <?php if(!$msgonly ){ ?>
            <form name="frm_forgotpass" action="" method="post">
                <p>
                    <label><?php echo $FUNCS->t('name_or_email'); ?>:<br />
                    <input type="text" id="k_user_name" name="k_user_name" size="20" autofocus="autofocus" /></label>
                </p>
                <input type="submit" name="k_submit" value="<?php echo $FUNCS->t('submit'); ?>"/>
            </form>
            <?php } ?>

        <?php echo( $FUNCS->login_footer() ); ?>
    <?php
    }
    ?>
