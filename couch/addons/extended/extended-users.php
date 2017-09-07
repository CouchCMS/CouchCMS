<?php
    if ( !defined('K_COUCH_DIR') ) die(); // cannot be loaded directly

    class KExtendedUsers{

        var $secret_key;

        var $t = array();
        var $users_tpl;
        var $login_tpl;
        var $lost_password_tpl;
        var $registration_tpl;

        function __construct(){
            $this->populate_config();
        }

        function populate_config(){

            $t = array();
            if( file_exists(K_ADDONS_DIR.'extended/config.php') ){
                require_once( K_ADDONS_DIR.'extended/config.php' );
            }
            else{/*
                die(
                      "<h3>Members module: 'config.php' not found. <br/>
                      Perhaps you forgot to rename the 'config.example.php' file in 'couch/addons/extended' to 'config.php'?
                      </h3>"
                   );
                 */
            }
            $this->t = array_map( "trim", $t );
            unset( $t );

            $this->users_tpl = $this->t['users_tpl'];
            $this->login_tpl = ( K_SITE_OFFLINE ) ? '' : $this->t['login_tpl'];
            $this->lost_password_tpl = $this->t['lost_password_tpl'];
            $this->registration_tpl = $this->t['registration_tpl'];
        }

        function &_get_associated_page( &$user, $id_only=0 ){
            global $FUNCS, $DB;

            if( isset($user->_assoc_page) ) return $user->_assoc_page;

            if( !strlen($this->users_tpl) ){
                $user->_assoc_page = '';
                return;
            }

            // custom template is set. Pass it through some sanity checks
            $rs = $DB->select( K_TBL_TEMPLATES, array('id', 'clonable'), "name='" . $DB->sanitize( $this->users_tpl ). "'" );
            if( !count($rs) ){
                die( "ERROR: Extended Users module - users_tpl '".$FUNCS->cleanXSS($this->users_tpl)."' not found" );
            }
            elseif( !$rs[0]['clonable'] ){
                die( "ERROR: Extended Users module -  users_tpl '".$FUNCS->cleanXSS($this->users_tpl)."' is not clonable" );
            }

            // create the associated page object
            $tpl_id = $rs[0]['id'];
            $page_id = -1;
            if( $user->id ){ // not a new user

                // does the corresponding page exist?
                $rs = $DB->select( K_TBL_FIELDS, array('id'), "name='extended_user_id' AND template_id='" . $DB->sanitize( $tpl_id ). "'" );
                if( count($rs) ){
                    $field_id = $rs[0]['id'];
                }
                else{
                    return;
                    //die( "ERROR: Extended Users module -  please visitx '".$FUNCS->cleanXSS($this->users_tpl)."' as super-admin to define all required fields" );
                }

                $rs = $DB->select( K_TBL_DATA_NUMERIC, array('page_id'), "field_id='".$FUNCS->cleanXSS($field_id)."' AND value='" . $DB->sanitize( $user->id ). "'" );
                if( count($rs) ){
                    $page_id = $rs[0]['page_id'];
                }
            }

            if( $id_only ) return $page_id;

            $pg = new KWebpage( $tpl_id, $page_id );
            if( $pg->error ){
                die( "ERROR: (Extended Users module) in attaching custom page to user - " . $pg->err_msg );
            }

            // associate the page object to the folder
            $user->_assoc_page = &$pg;

            return $user->_assoc_page;
        }

        function add_custom_user_fields( &$fields, &$user ){
            global $FUNCS, $DB;

            $pg = $this->_get_associated_page( $user );
            if( !$pg ) return;

            $arr_hidden_fields = array( 'extended_user_id', 'extended_user_email', 'extended_user_password', 'extended_user_password_repeat' );

            $count = count( $pg->fields );
            for( $x=0; $x<$count; $x++ ){
                $f = &$pg->fields[$x];
                $f->resolve_dynamic_params();

                if( !$f->system ){
                    if( in_array($f->name, $arr_hidden_fields) ){
                        $f->no_render=1;
                    }

                    $user->fields[] = &$pg->fields[$x];
                }

                unset( $f );

            }

        }

        function save_custom_user_fields( &$user, $action ){
            global $FUNCS, $DB, $KUSER;

            $pg = $this->_get_associated_page( $user );
            if( !$pg ) return;

            $title = $user->title;
            $name = $user->name;
            $date = ( $user->disabled ) ? '0000-00-00 00:00:00' : $user->registration_date;

            $pg->_fields['k_page_title']->store_posted_changes( $title );
            $pg->_fields['k_page_name']->store_posted_changes( $name );
            $pg->_fields['k_publish_date']->store_posted_changes( $date );
            $pg->_fields['extended_user_id']->store_posted_changes( $user->id );
            $pg->_fields['extended_user_email']->store_posted_changes( $user->email );

            $FUNCS->remove_event_listener( 'page_validate', array($KUSER, 'sync_user_to_page') );
            $pg->save();
            $FUNCS->add_event_listener( 'page_validate', array($KUSER, 'sync_user_to_page') );
        }

        function delete_custom_user_fields( &$user ){
            global $FUNCS, $KUSER;

            $pg = $this->_get_associated_page( $user );
            if( !$pg || $pg->id==-1 ) return;

            $FUNCS->remove_event_listener( 'page_deleted', array($KUSER, 'delete_user_account') );
            $pg->delete();
            $FUNCS->add_event_listener( 'page_deleted', array($KUSER, 'delete_user_account') );

            // if we are here, delete was successful (script would have died otherwise)
            $pg->destroy();
            unset( $pg );

        }

        function delete_user_account( &$pg ){
            global $FUNCS, $KUSER;

            if( $pg->tpl_name!=$this->users_tpl ){ return; }

            $user_id = trim( $pg->_fields['extended_user_id']->get_data() );
            if( $FUNCS->is_non_zero_natural($user_id) && $user_id != -1 ){
                $user = new KUser( $user_id, 1 );
                if( $user->access_level <= K_ACCESS_LEVEL_AUTHENTICATED ){

                    $FUNCS->remove_event_listener( 'user_deleted', array($KUSER, 'delete_custom_user_fields') );
                    $user->delete();
                    $FUNCS->add_event_listener( 'user_deleted', array($KUSER, 'delete_custom_user_fields') );
                }
            }
        }

        function set_custom_fields_in_context(){
            global $AUTH, $CTX;

            if( !$AUTH->user->disabled ){
                $pg_id = $this->_get_associated_page( $AUTH->user, 1 );
                if( !$pg_id ) return;

                $CTX->set( 'k_extended_user_id', $AUTH->user->id );
                $CTX->set( 'k_user_id', $pg_id );
            }

            $CTX->set( 'k_user_template', $this->users_tpl );
            $CTX->set( 'k_user_login_template', $this->login_tpl );
            $CTX->set( 'k_user_lost_password_template', $this->lost_password_tpl );
            $CTX->set( 'k_user_registration_template', $this->registration_tpl );

        }

        function add_hidden_fields( &$attr_custom, $params, $node ){
            global $FUNCS, $PAGE;

            if( !strlen($this->users_tpl) ) return;

            // take the opportunity to add the hidden fields
            if( $PAGE->tpl_name==$this->users_tpl ){
                $html="
                <cms:ignore>
                <cms:editable name='extended_user_css' type='message'>
                <style type=\"text/css\">
                    #k_element_extended_user_id,
                    #k_element_extended_user_email
                    { display:none; }
                </style>
                </cms:editable>
                </cms:ignore>
                <cms:editable label='Extended-User ID' name='extended_user_id' search_type='integer' type='text'>0</cms:editable>
                <cms:editable label='Extended-User Email' name='extended_user_email' type='text' searchable='0' />
                <cms:editable label='New Password' name='extended_user_password' type='dummy_password' />
                <cms:editable label='Repeat New Password' name='extended_user_password_repeat' type='dummy_password' />
                ";
                $parser = new KParser( $html, $node->line_num, 0, '', $node->ID );
                $dom = $parser->get_DOM();

                foreach( $dom->children as $child_node ){
                    if( $child_node->type==K_NODE_TYPE_CODE ){
                       $node->children[] = $child_node;
                    }
                }
            }

        }

        function hide_fields( &$pg ){
            global $FUNCS;

            if( !strlen($this->users_tpl) ) return;

            if( $pg->tpl_name==$this->users_tpl ){
                $f = $pg->_fields['extended_user_id'];
                if( !$f ){
                    die( "ERROR: Extended Users module -  please visit '".$FUNCS->cleanXSS($pg->tpl_name)."' as super-admin to define all required fields" );
                }

                $f->no_render=1;
            }
        }

        function sanitize_title( &$pg ){return;
            global $FUNCS;
            if( !strlen($this->users_tpl) ) return;

            if( $pg->tpl_name==$this->users_tpl ){
                $f = $pg->_fields['k_page_title'];
                $title = trim( $f->get_data() );
                $title = $FUNCS->unhtmlentities( $title, K_CHARSET );
                $title = strip_tags( $title );
                $f->store_posted_changes( $title );
            }
        }

        function sync_user_to_page( &$fields, &$errors, &$pg ){
            global $FUNCS, $KUSER;

            if( !strlen($this->users_tpl) ) return;

            if( $pg->tpl_name==$this->users_tpl && !$errors ){

                // get the corresponding user object
                $f = $pg->_fields['extended_user_id'];
                if( !$f ){
                    die( "ERROR: Extended Users module -  please visit '".$FUNCS->cleanXSS($pg->tpl_name)."' as super-admin to define all required fields" );
                }

                $user_id = ( $pg->id == -1 ) ? '' : ( $f->modified ? $f->orig_data: $f->data );

                // remove hooks temporarily
                $FUNCS->remove_event_listener( 'alter_user_fields_info', array($KUSER, 'add_custom_user_fields') );
                $FUNCS->remove_event_listener( 'user_saved', array($KUSER, 'save_custom_user_fields') );

                $user = new KUser( $user_id, 1 );
                $user->populate_fields();

                // sync data from page to user
                $title = trim( $pg->_fields['k_page_title']->get_data() );
                $name = trim( $pg->_fields['k_page_name']->get_data() );
                $email = trim( $pg->_fields['extended_user_email']->get_data() );
                $date = trim( $pg->_fields['k_publish_date']->get_data() );
                $pwd = trim( $pg->_fields['extended_user_password']->get_data() );
                $pwd2 = trim( $pg->_fields['extended_user_password_repeat']->get_data() );

                $user->fields[0]->store_posted_changes( $name );
                $user->fields[1]->store_posted_changes( $title );
                $user->fields[2]->store_posted_changes( $email );
                if( $user->id == -1 ){
                    $user->fields[3]->store_posted_changes( K_ACCESS_LEVEL_AUTHENTICATED );
                }
                if( $date == '0000-00-00 00:00:00' ){
                    if( $user->access_level<K_ACCESS_LEVEL_SUPER_ADMIN ){
                        $user->fields[4]->store_posted_changes( 1 ); // disable user
                    }
                    else{
                        $pg->_fields['k_publish_date']->store_posted_changes( $user->registration_date );
                    }
                }
                else{
                    $user->fields[4]->store_posted_changes( 0 ); // enable user
                }
                $user->fields[5]->store_posted_changes( $pwd );
                $user->fields[6]->store_posted_changes( $pwd2 );

                // save..
                $user_errors = $user->save();

                // restore hooks
                $FUNCS->add_event_listener( 'alter_user_fields_info', array($KUSER, 'add_custom_user_fields') );
                $FUNCS->add_event_listener( 'user_saved', array($KUSER, 'save_custom_user_fields') );

                if( $user_errors ){
                    // pass on the user-object error messages to page
                    $arr_map = array(
                        'k_name'=>'k_page_name',
                        'k_title'=>'k_page_title',
                        'k_email'=>'extended_user_email',
                        'k_password'=>'extended_user_password',
                        'k_password2'=>'extended_user_password_repeat',
                    );

                    for( $x=0; $x<count($user->fields); $x++ ){
                        $f = &$user->fields[$x];
                        if( $f->err_msg ){
                            $pg->_fields[$arr_map[$f->name]]->err_msg = $f->err_msg;
                        }
                        unset( $f );
                    }

                    $errors = $user_errors;
                    return;
                }

                // attach the page to the user
                $pg->_fields['extended_user_id']->store_posted_changes( $user->id );

            }
        }

        function activation_link_handler( $params, $node ){
            global $FUNCS, $DB, $AUTH;

            if( count($node->children) ) {die("ERROR: Tag \"".$node->name."\" is a self closing tag");}

            extract( $FUNCS->get_named_vars(
                array(
                    'user'=>'',
                    'processor'=>'',
                ),
                $params)
            );
            $user = trim( $user );
            $processor = trim( $processor );

            $tpl = ( $processor ) ? $processor : $this->registration_tpl;
            if( !strlen($tpl) ) return;

            $link = '';
            if( $user ){
                $field = ( strpos($user, '@')!==false ) ? 'email' : 'name';

                $rs = $DB->select( K_TBL_USERS, array('name', 'activation_key'), $field."='" . $DB->sanitize( $user ). "'" );
                if( count($rs) ){
                    $name = trim( $rs[0]['name'] );
                    $key = trim( $rs[0]['activation_key'] );
                    if( $key ){
                        $hash = $AUTH->get_hash( $name, $key, time() + 86400 /* 24 hrs */ );
                        $link = $FUNCS->get_link( $tpl ) . "?act=activate&key=" . urlencode( $hash );
                    }
                }
            }

            return $link;
        }

        function process_activation_handler( $params, $node ){
            global $FUNCS, $CTX, $PAGE;

            if( count($node->children) ) {die("ERROR: Tag \"".$node->name."\" is a self closing tag");}
            $PAGE->no_cache = 1;

            $res = $this->_process_activation();

            if( $FUNCS->is_error($res) ){
                $CTX->set( 'k_success', '' );
                $CTX->set( 'k_error', $res->err_msg );
            }
            else{
                $CTX->set( 'k_success', '1' );
                $CTX->set( 'k_error', '' );
            }

        }

        function _process_activation(){
            global $FUNCS, $DB, $AUTH;

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

            // finally check if activation key still exists for the user
            // get the user with this activation key
            $rs = $DB->select( K_TBL_USERS, array('id'), "name='" . $DB->sanitize( $user )."' AND activation_key='".$DB->sanitize( $key )."'" );
            if( !count($rs) ){
                return $FUNCS->raise_error( $FUNCS->t('invalid_key') );
            }
            else{
                $user = new KUser( $rs[0]['id'], 1 );
                $user->populate_fields();
                $user->fields[4]->store_posted_changes( 0 ); // enable user
                $access_level = $AUTH->user->access_level;
                $AUTH->user->access_level = K_ACCESS_LEVEL_AUTHENTICATED +  1; // to allow an unlogged visitor activate his account
                $errors = $user->save();
                if( $errors ){
                    return $FUNCS->raise_error( 'Activation failed' );
                }
                $AUTH->user->access_level = $access_level;
            }

        }

        function process_forgot_password_handler( $params, $node ){
            global $FUNCS, $CTX, $PAGE;

            if( count($node->children) ) {die("ERROR: Tag \"".$node->name."\" is a self closing tag");}
            $PAGE->no_cache = 1;

            extract( $FUNCS->get_named_vars(
                array(
                    'username'=>'',
                    'send_mail'=>'1'
                ),
                $params)
            );
            $username = trim( $username );
            $send_mail = ( $send_mail==0 ) ? 0 : 1;

            $res = $this->_forgot_password( $username, $send_mail );

            if( $FUNCS->is_error($res) ){
                $CTX->set( 'k_success', '' );
                $CTX->set( 'k_error', $res->err_msg );
                $CTX->set( 'k_forgot_password_error', $res->err_msg );
            }
            else{
                $CTX->set( 'k_success', '1' );
                $CTX->set( 'k_error', '' );
                $CTX->set( 'k_forgot_password_success', '1' );
            }
        }

        function _forgot_password( $username, $send_mail ){
            global $DB, $FUNCS, $AUTH, $CTX;

            $username = strlen( $username ) ? $username : $_POST['k_user_name'];
            $username = $FUNCS->cleanXSS( trim($username) );
            if( empty($username) ){
                return $FUNCS->raise_error( $FUNCS->t('submit_error') ); //Please enter your username or email address.
            }

            $user = $AUTH->reset_key( $username );
            if( $FUNCS->is_error($user) ){
                return $user;
            }

            // set info in context for other tags to use (specifically cms:send_mail)
            $CTX->set( 'k_user_id', $user->id );
            $CTX->set( 'k_user_name', $user->name );
            $CTX->set( 'k_user_title', $user->title );
            $CTX->set( 'k_user_email', $user->email );
            $CTX->set( 'k_user_access_level', $user->access_level );

            // extended info
            $pg_id = $this->_get_associated_page( $user, 1 );
            if( $pg_id ){
                $CTX->set( 'k_extended_user_id', $user->id );
                $CTX->set( 'k_user_id', $pg_id );
            }
            $processor = ( strlen($this->lost_password_tpl) ) ? $FUNCS->get_link($this->lost_password_tpl) : K_ADMIN_URL .'forgotpassword.php';
            $hash = $AUTH->get_hash( $user->name , $user->password_reset_key, time() + 86400 /* 24 hrs */ );
            $reset_link = $processor . "?act=reset&key=" . urlencode( $hash );
            $CTX->set( 'k_reset_password_link', $reset_link );

            if( $send_mail ){
                $to = $user->email;
                $from = K_EMAIL_FROM;
                $subject = $FUNCS->t( 'reset_req_email_subject' );

                $msg = $FUNCS->t( 'reset_req_email_msg_0' ) . ": \r\n";
                $msg .= K_SITE_URL . "\r\n";
                $msg .= $FUNCS->t( 'user_name' ) .': ' . $user->name . "\r\n\r\n";
                $msg .= $FUNCS->t( 'reset_req_email_msg_1' ) . "\r\n";
                $msg .= $reset_link ."\r\n";

                $headers = array();
                $headers['MIME-Version']='1.0';
                $headers['Content-Type']='text/plain; charset='.K_CHARSET;
                $rs = $FUNCS->send_mail( $from, $to, $subject, $msg, $headers );
                if( !$rs ){
                    return $FUNCS->raise_error( $FUNCS->t('email_failed') );
                }
            }

        }

        function process_reset_password_handler( $params, $node ){
            global $FUNCS, $CTX, $PAGE;

            if( count($node->children) ) {die("ERROR: Tag \"".$node->name."\" is a self closing tag");}
            $PAGE->no_cache = 1;

            extract( $FUNCS->get_named_vars(
                array(
                    'send_mail'=>'1'
                ),
                $params)
            );
            $send_mail = ( $send_mail==0 ) ? 0 : 1;

            $res = $this->_reset_password( $send_mail );

            if( $FUNCS->is_error($res) ){
                $CTX->set( 'k_success', '' );
                $CTX->set( 'k_error', $res->err_msg );
                $CTX->set( 'k_reset_password_error', $res->err_msg );
            }
            else{
                $CTX->set( 'k_success', '1' );
                $CTX->set( 'k_error', '' );
                $CTX->set( 'k_reset_password_success', '1' );
            }

        }

        function _reset_password( $send_mail ){
            global $DB, $FUNCS, $AUTH, $CTX;

            $data = $_GET['key'];
            $data = str_replace( ' ', '+', $data );
            list( $name, $key, $expiry, $hash ) = explode( '|', $data );

            // check if link has not expired
            if( time() > $expiry ){
                return $FUNCS->raise_error( $FUNCS->t('invalid_key') );
            }

            // next verify hash to make sure the data has not been tampered with.
            if( $data !== $AUTH->get_hash($name, $key, $expiry) ){
                return $FUNCS->raise_error( $FUNCS->t('invalid_key') );
            }

            // get the user with this reset key
            $user = new KUser( $name );

            if( $user->id==-1 || $user->disabled || $user->password_reset_key!==$key ){
                return $FUNCS->raise_error( $FUNCS->t( 'invalid_key' ) );
            }

            // generate a new password for the user
            $password = $FUNCS->generate_key( 12 );
            $hash = $AUTH->hasher->HashPassword( $password );

            // update record
            $rs = $DB->update( K_TBL_USERS, array('password'=>$hash, 'password_reset_key'=>''), "id='" . $DB->sanitize( $user->id ). "'" );
            if( $rs==-1 ) die( "ERROR: Unable to update K_TBL_USERS" );

            // set info in context for other tags to use (specifically cms:send_mail)
            $CTX->set( 'k_user_id', $user->id );
            $CTX->set( 'k_user_name', $user->name );
            $CTX->set( 'k_user_title', $user->title );
            $CTX->set( 'k_user_email', $user->email );
            $CTX->set( 'k_user_access_level', $user->access_level );
            $CTX->set( 'k_user_new_password', $password );

            // extended info
            $pg_id = $this->_get_associated_page( $user, 1 );
            if( $pg_id ){
                $CTX->set( 'k_extended_user_id', $user->id );
                $CTX->set( 'k_user_id', $pg_id );
            }

            if( $send_mail ){
                $subject = $FUNCS->t( 'reset_email_subject' );

                $msg = $FUNCS->t( 'reset_email_msg_0' ) . ": \r\n";
                $msg .= K_SITE_URL . "\r\n";
                $msg .= $FUNCS->t( 'user_name' ) .': ' . $name . "\r\n\r\n";
                $msg .= $FUNCS->t( 'new_password' ) .': ' . $password . "\r\n\r\n";
                $msg .= $FUNCS->t( 'reset_email_msg_1' ) . "\r\n";

                $from = K_EMAIL_FROM;
                $to = $user->email;

                $headers = array();
                $headers['MIME-Version']='1.0';
                $headers['Content-Type']='text/plain; charset='.K_CHARSET;
                $rs = $FUNCS->send_mail( $from, $to, $subject, $msg, $headers );
                if( !$rs ){
                    return $FUNCS->raise_error( $FUNCS->t( 'email_failed' ) );
                }
            }
        }

        function process_login_handler( $params, $node ){
            global $FUNCS, $CTX, $PAGE, $AUTH;

            if( count($node->children) ) {die("ERROR: Tag \"".$node->name."\" is a self closing tag");}
            $PAGE->no_cache = 1;

            extract( $FUNCS->get_named_vars(
                array(
                    'username'=>'',
                    'password'=>'',
                    'remember'=>'',
                    'redirect'=>'' /* can be '0', '1', '2' or a link */
                ),
                $params)
            );
            $username = trim( $username );
            $password = trim( $password );
            $remember = trim( $remember );
            $redirect = trim( $redirect );
            if( !strlen($redirect) ) $redirect='2'; // default expects a querystring param named 'redirect'

            $res = $AUTH->login( $username, $password, $remember );

            if( $FUNCS->is_error($res) ){
                $CTX->set( 'k_success', '' );
                $CTX->set( 'k_error', $res->err_msg );
                $CTX->set( 'k_login_error', $res->err_msg );
            }
            else{
                // which kind of redirection requested?
                if( $redirect=='0' ){ // no redirection
                    return;
                }
                elseif( $redirect=='1' ){ // redirect to current page
                    $dest = $_SERVER["REQUEST_URI"];
                }
                elseif( $redirect=='2' ){ // link supplied as querystring parameter (this is default behaviour)
                    $dest = $_GET['redirect'];
                    $dest = $FUNCS->unhtmlentities( $dest, K_CHARSET );
                }
                else{ // link supplied as parameter to this tag
                    $dest = $redirect;
                }

                $AUTH->redirect( $dest );
            }

        }

        function process_logout_handler( $params, $node ){
            global $FUNCS, $CTX, $PAGE, $AUTH;

            if( count($node->children) ) {die("ERROR: Tag \"".$node->name."\" is a self closing tag");}
            $PAGE->no_cache = 1;

            if( $AUTH->user->id != -1 ){
                extract( $FUNCS->get_named_vars(
                    array(
                        'nonce'=>'',
                        'redirect'=>'' /* can be '0', '1', '2' or a link */
                    ),
                    $params)
                );
                $nonce = trim( $nonce );
                $redirect = trim( $redirect );
                if( !strlen($redirect) ) $redirect='2'; // default expects a querystring param named 'redirect'

                $AUTH->logout( $nonce );

                // which kind of redirection requested?
                if( $redirect=='0' ){ // no redirection
                    return;
                }
                elseif( $redirect=='1' ){ // redirect to current page
                    $dest = $_SERVER["REQUEST_URI"];
                }
                elseif( $redirect=='2' ){ // link supplied as querystring parameter (this is default behaviour)
                    $dest = $_GET['redirect'];
                    $dest = $FUNCS->unhtmlentities( $dest, K_CHARSET );
                }
                else{ // link supplied as parameter to this tag
                    $dest = $redirect;
                }

                $AUTH->redirect( $dest );
            }
        }

        function login_link_handler( $params, $node ){
            global $FUNCS;

            if( count($node->children) ) {die("ERROR: Tag \"".$node->name."\" is a self closing tag");}

            extract( $FUNCS->get_named_vars(
                array(
                    'redirect'=>''
                ),
                $params)
            );
            $redirect = trim( $redirect );

            if( $node->name=='login_link' ){
                $link = $FUNCS->get_login_link( $redirect );
            }
            else{ //logout_link
                $link = $FUNCS->get_logout_link( $redirect );
            }

            return $link;
        }


        function add_test_login_cookie( &$html, &$PAGE ){
            if( strlen($this->login_tpl) && $PAGE->tpl_name==$this->login_tpl ){
                global $AUTH;
                setcookie( 'couchcms_testcookie', 'CouchCMS test cookie', 0, $AUTH->cookie_path, null, K_HTTPS ? true : null );
            }
        }

        function alter_login_link( &$link, &$redirect ){
            global $FUNCS, $AUTH;

            if( strlen($this->login_tpl) ){
                if( $AUTH->user->id == -1 ){
                    if( !strlen($redirect) ){ $redirect = $_SERVER["REQUEST_URI"]; }
                    $link = $FUNCS->get_link( $this->login_tpl ).'?redirect='.urlencode( $redirect );
                }
                else{
                    $link = 'javascript:void(0)';
                }
            }
        }

        function alter_logout_link( &$link, &$redirect ){
            global $FUNCS, $AUTH;

            if( strlen($this->login_tpl) ){
                if( $AUTH->user->id != -1 ){
                    $nonce = $FUNCS->create_nonce( 'logout'.$AUTH->user->id, $AUTH->user->name );
                    if( !strlen($redirect) ){ $redirect = $_SERVER["REQUEST_URI"]; }
                    $link = $FUNCS->get_link( $this->login_tpl ).'?act=logout&nonce='.$nonce. '&redirect='.urlencode( $redirect );
                }
                else{
                    $link = 'javascript:void(0)';
                }
            }
        }

    }// end class

    $KUSER = new KExtendedUsers();

    // register custom tags
    $FUNCS->register_tag( 'activation_link', array($KUSER, 'activation_link_handler') );
    $FUNCS->register_tag( 'process_activation', array($KUSER, 'process_activation_handler') );
    $FUNCS->register_tag( 'process_login', array($KUSER, 'process_login_handler') );
    $FUNCS->register_tag( 'process_logout', array($KUSER, 'process_logout_handler') );
    $FUNCS->register_tag( 'login_link', array($KUSER, 'login_link_handler') );
    $FUNCS->register_tag( 'logout_link', array($KUSER, 'login_link_handler') );
    $FUNCS->register_tag( 'process_forgot_password', array($KUSER, 'process_forgot_password_handler') );
    $FUNCS->register_tag( 'process_reset_password', array($KUSER, 'process_reset_password_handler') );

    // hook events
    $FUNCS->add_event_listener( 'alter_user_fields_info', array($KUSER, 'add_custom_user_fields') );
    $FUNCS->add_event_listener( 'user_saved', array($KUSER, 'save_custom_user_fields') );
    $FUNCS->add_event_listener( 'user_deleted', array($KUSER, 'delete_custom_user_fields') );
    $FUNCS->add_event_listener( 'alter_user_set_context', array($KUSER, 'set_custom_fields_in_context') );

    $FUNCS->add_event_listener( 'add_template_params', array($KUSER, 'add_hidden_fields') );
    $FUNCS->add_event_listener( 'page_presave', array($KUSER, 'sanitize_title') );
    $FUNCS->add_event_listener( 'page_validate', array($KUSER, 'sync_user_to_page') );
    $FUNCS->add_event_listener( 'edit_page_prerender', array($KUSER, 'hide_fields') );
    $FUNCS->add_event_listener( 'page_deleted', array($KUSER, 'delete_user_account') );

    $FUNCS->add_event_listener( 'alter_final_page_output', array($KUSER, 'add_test_login_cookie') );
    $FUNCS->add_event_listener( 'get_login_link', array($KUSER, 'alter_login_link') );
    $FUNCS->add_event_listener( 'get_logout_link', array($KUSER, 'alter_logout_link') );


    // UDF for dummy password fields
    class KDummyPassword extends KUserDefinedField{

        function _render( $input_name, $input_id, $extra='', $dynamic_insertion=0 ){
            $this->k_type = 'password';
            return KField::_render( $input_name, $input_id, $extra, $dynamic_insertion ); // Calling grandparent statically! Not a bug: https://bugs.php.net/bug.php?id=42016
            return $html;
        }

        // Save to database
        function get_data_to_save(){
            return ''; // nothing
        }
    }

    $FUNCS->register_udf( 'dummy_password', 'KDummyPassword', 0/*repeatable*/, 0/*searchable*/ );
