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
    elseif( isset($_GET['act'][0]) && $_GET['act'] == 'reset' ){
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
        global $FUNCS, $DB, $AUTH;

        $val = $FUNCS->cleanXSS( trim($_POST['k_user_name']) );
        if( $val && is_string( $val ) ){

            $user = $AUTH->reset_key( $val );
            if( $FUNCS->is_error($user) ){
                return $user;
            }

            // Send confirmation email to the user
            $name = $user->name;
            $to = $user->email;
            $key = $user->password_reset_key;
            $hash = $AUTH->get_hash( $name , $key, time() + 86400 /* 24 hrs */ );
            $reset_link = K_ADMIN_URL . "forgotpassword.php?act=reset&key=" . urlencode( $hash );

            $subject = $FUNCS->t( 'reset_req_email_subject' );

            $msg = $FUNCS->t( 'reset_req_email_msg_0' ) . ": \r\n";
            $msg .= K_SITE_URL . "\r\n";
            $msg .= $FUNCS->t( 'user_name' ) .': ' . $name . "\r\n\r\n";
            $msg .= $FUNCS->t( 'reset_req_email_msg_1' ) . "\r\n";
            $msg .= $reset_link ."\r\n";

            $from = K_EMAIL_FROM;

            $headers = array();
            $headers['MIME-Version']='1.0';
            $headers['Content-Type']='text/plain; charset='.K_CHARSET;
            $rs = $FUNCS->send_mail( $from, $to, $subject, $msg, $headers );
            if( $rs ){
                return;
            }
            $err_msg = $FUNCS->t( 'email_failed' );

        }
        else{
            $err_msg = $FUNCS->t( 'submit_error' ); //Please enter your username or email address.
        }
        return $FUNCS->raise_error( $err_msg );
    }

    function reset_password(){
        global $FUNCS, $DB, $AUTH;

        //?act=reset&key=xxxx%7Ch5D8jruI61wwncdEmNxGKbWJMapnb6pI%7C1410647383%7C6274dd9452c643d527e5ff8e995d12ee
        $data = $_GET['key'];
        $data = str_replace( ' ', '+', $data );
        list( $user, $key, $expiry, $hash ) = explode( '|', $data );

        // check if link has not expired
        if( time() > $expiry ){
            return $FUNCS->raise_error( $FUNCS->t('invalid_key') );
        }

        // next verify hash to make sure the data has not been tampered with.
        if( $data !== $AUTH->get_hash($user, $key, $expiry) ){
            return $FUNCS->raise_error( $FUNCS->t('invalid_key') );
        }

        // get the user with this activation key
        $rs = $DB->select( K_TBL_USERS, array('id', 'name', 'email'), "name='" . $DB->sanitize( $user )."' AND password_reset_key='".$DB->sanitize( $key )."'" );
        if( !count($rs) ){
            return $FUNCS->raise_error( $FUNCS->t('invalid_key') );
        }
        else{
            $id = $rs[0]['id'];
            $name = $rs[0]['name'];
            $to = $rs[0]['email'];

            // generate a new password for the user
            $password = $FUNCS->generate_key( 12 );
            $hash = $AUTH->hasher->HashPassword( $password );

            // update record
            $rs = $DB->update( K_TBL_USERS, array('password'=>$hash, 'password_reset_key'=>''), "id='" . $DB->sanitize( $id ). "'" );
            if( $rs==-1 ) die( "ERROR: Unable to update K_TBL_USERS" );

            // send the new password to the user
            $subject = $FUNCS->t( 'reset_email_subject' );

            $msg = $FUNCS->t( 'reset_email_msg_0' ) . ": \r\n";
            $msg .= K_SITE_URL . "\r\n";
            $msg .= $FUNCS->t( 'user_name' ) .': ' . $name . "\r\n\r\n";
            $msg .= $FUNCS->t( 'new_password' ) .': ' . $password . "\r\n\r\n";
            $msg .= $FUNCS->t( 'reset_email_msg_1' ) . "\r\n";

            $from = K_EMAIL_FROM;

            $headers = array();
            $headers['MIME-Version']='1.0';
            $headers['Content-Type']='text/plain; charset='.K_CHARSET;
            $rs = $FUNCS->send_mail( $from, $to, $subject, $msg, $headers );
            if( !$rs ){
                return $FUNCS->raise_error( $FUNCS->t( 'email_failed' ) );
            }
        }

    }

    function show_form( $msg, $msg_class, $msgonly=0 ){
        global $FUNCS;

        $html = $FUNCS->render( 'forgot_password', $msg, $msg_class, $msgonly );
        echo $html;
    }
