<?php
    if ( !defined('K_COUCH_DIR') ) die(); // cannot be loaded directly

    class KPHPMailer{

        var $config = array();

        function __construct(){
            global $FUNCS;

            // hook on to event thrown by $FUNCS->send_mail() function
            $FUNCS->add_event_listener( 'alter_send_mail', array($this, 'send_mail_handler') );

            $this->populate_config();
            $FUNCS->register_tag( 'attachment', array('KPHPMailer', 'attachment_handler'), 1, 0 );
            $FUNCS->register_tag( 'alt_body', array('KPHPMailer', 'alt_body_handler'), 1, 0 );
        }

        function populate_config(){
            $cfg = array();
            if( file_exists(K_COUCH_DIR.'addons/phpmailer/config.php') ){
                require_once( K_COUCH_DIR.'addons/phpmailer/config.php' );
            }

            $this->config = array_map( "trim", $cfg );
            unset( $cfg );
        }

        function send_mail_handler( &$from, &$to, &$subject, &$text, &$headers, &$result, &$arr_config, $debug ){
            global $FUNCS, $CTX;

            require_once( K_COUCH_DIR.'addons/phpmailer/PHPMailer/PHPMailerAutoload.php' );
            $mail = new PHPMailer;

            $mail->XMailer = 'PHPMailer';

            switch( strtolower($this->config['method']) ){
                case 'smtp':
                    $mail->IsSMTP();
                        if( $this->config['host'] ){   $mail->Host = $this->config['host']; }
                        if( $this->config['port'] ){   $mail->Port = $this->config['port']; }
                        if( $this->config['secure'] ){ $mail->SMTPSecure = $this->config['secure']; }
                        if( $this->config['authentication_required'] ){
                            $mail->SMTPAuth = true;
                            $mail->Username = $this->config['username'];
                            $mail->Password = $this->config['password'];
                        }
                    break;
                case 'mail':    $mail->isMail(); break;
                case 'sendmail':$mail->isSendmail(); break;
                case 'qmail':   $mail->isQmail();
            }

            // from
            $arr_addr = $mail->parseAddresses( $FUNCS->_rsc($from) );
            $mail->SetFrom( $arr_addr[0]['address'], $arr_addr[0]['name'] );

            // to
            $arr_addr = $mail->parseAddresses( $FUNCS->_rsc($to) );
            for( $x=0; $x<count($arr_addr); $x++ ){
                $mail->addAddress( $arr_addr[$x]['address'], $arr_addr[$x]['name'] );
            }

            // cc, bcc, reply-to
            if( is_array($headers) ){
                foreach( $headers as $k=>$v ){
                    $k = $FUNCS->_rsc( $k );
                    $v = $FUNCS->_rsc( $v );

                    switch( $k ){
                        case 'Cc':
                            $arr_addr = $mail->parseAddresses( $v );
                            for( $x=0; $x<count($arr_addr); $x++ ){
                                $mail->addCC( $arr_addr[$x]['address'], $arr_addr[$x]['name'] );
                            }
                            break;
                        case 'Bcc':
                            $arr_addr = $mail->parseAddresses( $v );
                            for( $x=0; $x<count($arr_addr); $x++ ){
                                $mail->addBCC( $arr_addr[$x]['address'], $arr_addr[$x]['name'] );
                            }
                            break;
                        case 'Reply-To':
                            $arr_addr = $mail->parseAddresses( $v );
                            for( $x=0; $x<count($arr_addr); $x++ ){
                                $mail->AddReplyTo( $arr_addr[$x]['address'], $arr_addr[$x]['name'] );
                            }
                            break;
                        case 'Sender':
                            $arr_addr = $mail->parseAddresses( $v );
                            $mail->Sender = $arr_addr[0]['address'];
                            break;
                        case 'Return-Path':
                        case 'MIME-Version':
                            break;
                        case 'Content-Type':
                            if( ($pos=strripos($v, 'charset=')) !== false ){
                                // expects string in the format 'text/html; charset=utf-8' as dispatched by cms:send_mail tag
                                $charset = substr( $v, $pos + 8);
                                $mail->CharSet = $charset;
                            }

                            if( stripos($v, 'html') !== false ){
                                $mail->isHTML( true );

                                // plain text version of message
                                $plain = ( is_array($arr_config) && !is_null($arr_config['alt_body']) ) ? $arr_config['alt_body'] : $text;
                                $plain = $FUNCS->unhtmlentities( $plain, K_CHARSET );
                                $plain = trim( strip_tags(preg_replace('/<(head|title|style|script)[^>]*>.*?<\/\\1>/si', '', $plain)) );
                                if( !strlen($plain) ){
                                    $plain = 'To view this email message, open it in a program that understands HTML!' . "\r\n" . "\r\n";
                                }

                                $mail->AltBody = $mail->normalizeBreaks( $plain );
                            }
                            break;
                        default:
                            $mail->addCustomHeader( $k, $v );
                            break;
                    }
                }
            }

            // subject and body
            $mail->Subject = $FUNCS->_rsc( $subject );
            $mail->Body    = $text;

            // attachments
            if( is_array($arr_config) ){
                foreach( $arr_config['att'] as $att ){
                    if( !$att['encoding'] ) $att['encoding']='base64';

                    if( $att['is_file'] ){ // file from file-system
                        if( $att['cid'] ){ // inline
                            $mail->addEmbeddedImage( $att['data'], $att['cid'], $att['name'], $att['encoding'], $att['mime_type'] );
                        }
                        else{
                            $mail->addAttachment( $att['data'], $att['name'], $att['encoding'], $att['mime_type'] );
                        }
                    }
                    else{ // stringified file
                        if( $att['cid'] ){ // inline
                            $mail->addStringEmbeddedImage( $att['data'], $att['cid'], $att['name'], $att['encoding'], $att['mime_type'] );
                        }
                        else{
                            // 'name' in this case needs to be a proper file name (e.g. 'logo.gif')
                            $mail->addStringAttachment( $att['data'], $att['name'], $att['encoding'], $att['mime_type'] );
                        }
                    }
                }
            }

            // debug?
            if( $this->config['debug'] || $debug ){
                $mail->SMTPDebug = 3;
                $mail->Debugoutput = array( $this, 'log_debug' );
                ob_start();
            }

            // HOOK: alter_phpmailer
            $FUNCS->dispatch_event( 'alter_phpmailer', array(&$mail) );

            @set_time_limit( 0 );

            // aaand send it!
            $result = $mail->send();

            if( $this->config['debug'] || $debug ){
                $log = ob_get_contents();
                ob_end_clean();
                $FUNCS->log( $log );
            }

            return 1; // tell $FUNCS to skip any further processing of the function call
        }

        function log_debug( $str, $level ){
            global $FUNCS;

            $str = preg_replace('/(\r\n|\r|\n)/ms', "\n", $str);
            echo str_replace(
                "\n",
                "\n                   \t                  ",
                trim($str)
            ) . "\n";
        }

        // Handles 'cms:attachment' tag
        static function attachment_handler( $params, $node ){
            global $FUNCS, $CTX;
            static $count = 0;

            // search for the parent cms:send_mail tag for passing on the attachments ..
            $arr_config = &$CTX->get_object( '__config', 'send_mail' );
            if( !is_array($arr_config) ){ return; }

            extract( $FUNCS->get_named_vars(
                        array(
                              'field'=>'',
                              'file'=>'',
                              'name'=>'',
                              'encoding'=>'',  /* options: "8bit", "7bit", "binary", "base64", and "quoted-printable" */
                              'mime_type'=>'', /* e.g. 'image/jpeg', 'image/gif' */
                              'inline'=>'0',   /* mail needs to be html for inline to work */
                              'base_encoded'=>'0',
                              ),
                        $params)
                   );
            $field = trim( $field );
            $file = trim( $file );
            $name = trim( $name );
            $encoding = trim( $encoding );
            $mime_type = trim( $mime_type );
            $inline = ( $inline==1 ) ? 1 : 0;
            $base_encoded = ( $base_encoded==1 ) ? 1 : 0;

            $data = '';
            $is_file = 1;
            $cid = ( $inline ) ? 'attach-'.++$count : '0'; // inline attachments require $cid


            // There could be three different sources of the attachment -
            // 1. enclosed content of this tag,
            // 2. file uploaded through field of type 'uploadfile' or
            // 3. any file on disk
            if( count($node->children) ){ // if tag used as a 'tag-pair', then enclosed contents are the attachment

                // this 'stringified' attachment will finally be presented as a standard file through the email.
                // Therefore, the 'name' (e.g. 'logo.gif') becomes mandatory ..
                if( /*!$inline &&*/ $name=='' ){
                    die( "ERROR: Tag \"".$node->name."\" - 'name' is empty. A proper file-name (e.g. 'logo.gif') is mandatory for 'stringified' attachments" );
                }

                foreach( $node->children as $child ){
                    $data .= $child->get_HTML();
                }
                $data = trim( $data );

                if( $base_encoded ){
                    $data = base64_decode( substr($data, strpos($data, ",")) );
                }

                $is_file = 0;
            }
            else if( $field!='' ){ // next check field (type 'uploadfile') ..

                // get the data array from CTX
                $obj = &$CTX->get_object( $field );
                if( !$obj ) return;

                $data = $FUNCS->decrypt(base64_decode($obj['file_id']), $obj['file_key']);
                if( $name=='' ){
                    $name = $obj['file_name'];
                }
            }
            else if( $file!='' ){ // finally, check any specified file ..

                // expects a full file path but exception is made for local site's images/files where URL can be given.
                // If URL of current site given, convert it to local path ..
                if( strpos($file, K_SITE_URL)===0 ){ // if local file
                    $file = str_replace( K_SITE_URL, K_SITE_DIR, $file );
                    if( !is_file ($file) ) return;
                }

                $data = $file;
            }

            // pass on the extracted data to cms:send_mail
            $arr_config['att'][] = array( 'name'=>$name, 'data'=>$data, 'is_file'=>$is_file, 'cid'=>$cid, 'encoding'=>$encoding, 'mime_type'=>$mime_type );

            $html = '';
            if( $inline ){
                $html = 'cid:'.$cid;
            }

            return $html;
        }


        // Handles 'cms:alt_body' tag
        // Sets the plain-text message body that can be read by mail clients that do not have HTML email
        static function alt_body_handler( $params, $node ){
            global $FUNCS, $CTX;

            // search for the parent cms:send_mail tag for passing on the data ..
            $arr_config = &$CTX->get_object( '__config', 'send_mail' );
            if( !is_array($arr_config) ){ return; }

            $data =  '';
            foreach( $node->children as $child ){
                $data .= $child->get_HTML();
            }
            $arr_config['alt_body'] = $data;

            return;
        }

    }// end class

    $KPHPMailer = new KPHPMailer();

    ////////////////////////////////////////////////////////////////////////////
    // Form Input field for uploading mail attachments
    class KUploadFile extends KUserDefinedFormField{

        function __construct( $fields, &$siblings ){

            // call parent
            parent::__construct( $fields, $siblings );

            $this->data = array();
            $this->requires_multipart = 1;
        }

        static function handle_params( $params, $node ){
            // piggyback on securefile ..
            $attr = SecureFile::_handle_params( $params );

            // remove parameters no applicable ..
            $nop = array( 'thumb_width', 'thumb_height', 'thumb_enforce_max', 'thumb_quality', 'use_thumb_for_preview' );
            foreach( $nop as $v ) unset( $attr[$v] );

            return $attr;
        }

        function _render( $input_name, $input_id, $extra='', $dynamic_insertion=0 ){
            global $FUNCS;

            if( $this->data['file_id'] ){
                $delete_caption = $this->delete_caption;

                $html .= '<span class="sf_filename" id="file_name_' . $input_id . '" name="file_name_'. $input_name .'">'.$this->data['file_name'].'</span>&nbsp;';
                $html .= '<input type="submit" class="sf_btn" name="delete_'.$input_name.'" value="'.$delete_caption.'" />';
                $html .= '<input type="hidden" name="secure_file_id_'.$input_name.'" value="'.$this->data['file_id'].'" />';
                $html .= '<input type="hidden" name="secure_file_name_'.$input_name.'" value="'.$this->data['file_name'].'" />';
                $html .= '<input type="hidden" name="secure_file_ext_'.$input_name.'" value="'.$this->data['file_ext'].'" />';
                $html .= '<input type="hidden" name="secure_file_size_'.$input_name.'" value="'.$this->data['file_size'].'" />';
                $html .= '<input type="hidden" name="secure_file_key_'.$input_name.'" value="'.$this->data['file_key'].'" />';

                // add a nonce to prevent tampering
                $data = $this->data['file_id'] . ':' . $this->data['file_key'] . ':' .$this->data['file_name'] . ':' . $this->data['file_ext'] . ':' . $this->data['file_size'];
                $html .= '<input type="hidden" name="secure_file_nonce_'.$input_name.'" value="'.$this->_get_nonce($data).'" />';
            }
            else{
                $submit_caption = $this->submit_caption;
                $style = '';
                $html .= '<input id="' . $input_id . '" name="'. $input_name .'" style="'.$style.'" '.$extra.' type="file" class="sf_fileinput" />';
                $html .= '<input type="submit" class="sf_btn" name="submit_'.$input_name.'" value="'.$submit_caption.'"';
                if( !$this->show_submit )  $html .= ' style="display:none;"';
                $html .= ' />';
            }

            return $html;
        }

        function get_data(){
            global $CTX;

            // Data not a simple string hence
            // we'll store it into '_obj_' of CTX directly
            // to be used by the auxiliary tag that knows how to display it
            $CTX->set_object( $this->name, $this->data );

            // and return only status for the normal context
            return ( count($this->data) ? 1 : 0 );
        }

        function store_posted_changes( $post_val ){
            global $FUNCS;
            if( $this->k_inactive ) return; // no need to store

            $secure_file_id = $this->_get_input_name( 'secure_file_id' );
            if( isset($_POST[$secure_file_id]) ){ // existing attachment
                if( isset($_POST[$this->_get_input_name( 'delete' )]) ){
                    $this->data = array();
                    $this->refresh_form = 1;
                }
                else{
                    $file_id = str_replace( ' ', '+', $_POST[$secure_file_id] );
                    $file_name = $_POST[$this->_get_input_name( 'secure_file_name' )];
                    $file_ext = $_POST[$this->_get_input_name( 'secure_file_ext' )];
                    $file_size = $_POST[$this->_get_input_name( 'secure_file_size' )];
                    $file_nonce = $_POST[$this->_get_input_name( 'secure_file_nonce' )];
                    $file_key = $_POST[$this->_get_input_name( 'secure_file_key' )];

                    // verify nonce before accepting submitted values
                    if( $file_nonce == $this->_get_nonce( $file_id . ':' . $file_key . ':' .$file_name . ':' . $file_ext . ':' . $file_size ) ){
                        $this->data['file_id'] = $file_id;
                        $this->data['file_name'] = $file_name;
                        $this->data['file_ext'] = $file_ext;
                        $this->data['file_size'] = $file_size;
                        $this->data['file_key'] = $file_key;
                    }
                }
            }
            else{ // no existing attachment.. perhaps one attached now
                if( isset($_POST[$this->_get_input_name( 'submit' )]) ) $this->refresh_form = 1;

                $file = $this->_get_input_name();
                if( $_FILES[$file]['name'] ){
                    $res = $this->_process_upload( $file );
                    if( $FUNCS->is_error($res) ){
                        $this->err_msg = $this->err_msg_refresh = $res->err_msg;
                        @unlink( $_FILES[$file]['tmp_name'] );
                    }

                    // encrypt file name
                    $this->data['file_key'] = $FUNCS->generate_key( 32 );
                    $this->data['file_id'] = base64_encode( $FUNCS->encrypt($this->data['file_id'], $this->data['file_key']) );
                }
                else{
                    $this->data = array();
                }
            }
        }

        function validate(){
            global $FUNCS;
            if( $this->k_inactive ) return true;

            if( $this->err_msg_refresh ){
                $this->err_msg = $this->err_msg_refresh;
                return false;
            }

            if( $this->required && !$this->data['file_id'] ){
                $this->err_msg = $FUNCS->t('required_msg');
                return false;
            }
            return true;
        }

        function _process_upload( $input_name ){
            global $FUNCS, $AUTH;

            $file = $_FILES[$input_name];
            if( !is_uploaded_file($file['tmp_name']) ){

                // Check for POST errors in uploading
                $err = ( $file['error'] !== UPLOAD_ERR_OK ) ? $file['error'] : UPLOAD_ERR_NO_FILE;
                switch( $err ){
                    case UPLOAD_ERR_INI_SIZE:
                        $err_msg = 'The uploaded file exceeds the upload_max_filesize directive in php.ini';
                        break;
                    case UPLOAD_ERR_FORM_SIZE:
                        $err_msg = 'The uploaded file exceeds the MAX_FILE_SIZE directive that was specified in the HTML form';
                        break;
                    case UPLOAD_ERR_PARTIAL:
                        $err_msg = 'The uploaded file was only partially uploaded';
                        break;
                    case UPLOAD_ERR_NO_FILE:
                        $err_msg = 'No file was uploaded';
                        break;
                    break;
                        case UPLOAD_ERR_NO_TMP_DIR:
                        $err_msg = 'Missing a temporary folder';
                    break;
                        case UPLOAD_ERR_CANT_WRITE:
                        $err_msg = 'Failed to write file to disk';
                    break;
                        case UPLOAD_ERR_EXTENSION:
                        $err_msg = 'File upload stopped by extension';
                        break;
                    default:
                        $err_msg = 'Unknown upload error';
                }
                return $FUNCS->raise_error( $err_msg );
            }

            // sanitize file name
            $file_name = $file['name'];
            $file_ext = $ext = '';
            $pos = strrpos( $file_name, '.' );
            if( $pos!==false ){
                $ext = strtolower( trim(substr($file_name, $pos+1)) );
                if( $ext ){
                    $file_ext = $ext;
                    $ext = '.' . $ext;
                    $file_name = substr( $file_name, 0, $pos );
                }
            }
            $file_name = $FUNCS->get_clean_url( $file_name );
            $file_name .= $ext;
            $file_size = $file['size'];

            // validate file extension
            $allowed_ext = explode( ',', $this->allowed_ext );
            if( $file_ext=='' || !in_array($file_ext, $allowed_ext) ){
                return $FUNCS->raise_error( 'File extension not allowed' );
            }

            // validate file size
            $size_uploaded_file = $file_size/1024; // bytes to KB
            if( $size_uploaded_file > $this->max_size ){
                return $FUNCS->raise_error( 'The uploaded file exceeds the '.$this->max_size.' KB size permitted' );
            }

            $dest_folder = $FUNCS->get_tmp_dir();
            if( !$dest_folder ){
                return $FUNCS->raise_error( 'Temporary folder for uploads is not available' );
            }

            // move file
            $disk_file_name = md5( $AUTH->hasher->get_random_bytes(16) );
            $dest_file_path = $dest_folder . $disk_file_name . '.' . $file_ext;
            if( !@copy($file['tmp_name'], $dest_file_path) ){
                if( !@move_uploaded_file($file['tmp_name'], $dest_file_path) ){
                    return $FUNCS->raise_error( 'Failed to move file' );
                }
            }
            $oldumask = umask( 0 );
            @chmod( $dest_file_path, 0777 );
            umask( $oldumask );

            // Some further checks on the uploaded file before truly accepting it
            // if the moved file 'claims' to be an image..
            if( $this->_is_image($file_ext) ){
                // ..verify that it is indeed so
                $info = @getimagesize($dest_file_path);
                if( $info===false || !in_array($info[2], array(IMAGETYPE_GIF, IMAGETYPE_JPEG, IMAGETYPE_PNG)) ){
                    @unlink( $dest_file_path );
                    return $FUNCS->raise_error( 'Invalid file content' );
                }
                // ..check the permissible dimensions
                if( $info[0]>$this->max_width || $info[1]>$this->max_height ){
                    @unlink( $dest_file_path );
                    return $FUNCS->raise_error( 'The dimensions of image exceed the permitted '.$this->max_width.'px X '.$this->max_height.'px' );
                }
            }
            else{
                // if a non HTML file..
                if( !in_array($file_ext, array('html', 'htm', 'xml', 'xsd', 'txt', 'js')) ){
                    // ..make sure it does not contain any HTML
                    $fp = @fopen( $dest_file_path, 'rb' );
                    if( $fp !== false ){
                        $content = trim( strtolower(fread($fp, 1024)) );
                        fclose( $fp );
                        if( $content ){
                            $forbidden_tags = array( 'body', 'head', 'html', 'img', 'pre', 'script', 'table', 'title', 'plaintext', 'a href' );
                            foreach( $forbidden_tags as $tag ){
                                if( strpos($content, '<' . $tag)!==false ){
                                    @unlink( $dest_file_path );
                                    return $FUNCS->raise_error( 'Invalid file content' );
                                }
                            }
                        }
                    }
                }
            }

            // if we reach here, everything is ok.. return new values
            $this->data['file_id'] = $dest_file_path;
            $this->data['file_name'] = $file_name;
            $this->data['file_ext'] = $file_ext;
            $this->data['file_size'] = $file_size;
            return true;
        }

        function _get_nonce( $data ){
            global $FUNCS;

            $key = $FUNCS->hash_hmac( $data, $FUNCS->_get_nonce_secret_key() );
            $nonce = $FUNCS->hash_hmac( $data, $key );

            return $nonce;
        }

        function _get_input_name( $input_name='' ){
            if( $input_name ) $input_name .= '_';
            return $input_name . $this->name;
        }

        static function _is_image( $file_ext ){
            return in_array( $file_ext, array('jpg', 'jpeg', 'png', 'gif') );
        }

        //////
        // Handles 'cms:show_uploadfile' tag
        static function show_handler( $params, $node ){
            global $FUNCS, $CTX;
            if( !count($node->children) ) return;

            extract( $FUNCS->get_named_vars(
                array(
                    'var'=>'',
                ),
                $params)
            );
            $var = trim( $var );

            if( $var ){
                // get the data array from CTX
                $obj = &$CTX->get_object( $var );

                if( $obj ){
                    // set component values as $CTX variables
                    $CTX->set( 'file_path', $FUNCS->decrypt(base64_decode($obj['file_id']), $obj['file_key']) );
                    $CTX->set( 'file_name', $obj['file_name'] );
                    $CTX->set( 'file_ext', $obj['file_ext'] );
                    $is_image = ( KUploadFile::_is_image($obj['file_ext']) ) ? 1 : 0;
                    $CTX->set( 'file_is_image', $is_image );
                    $CTX->set( 'file_size', $obj['file_size'] );

                    // and call the children tags
                    foreach( $node->children as $child ){
                        $html .= $child->get_HTML();
                    }
                }

                return $html;
            }
        }
    }

    $FUNCS->register_udform_field( 'uploadfile', 'KUploadFile' );
    $FUNCS->register_tag( 'show_uploadfile', array('KUploadFile', 'show_handler'), 1, 0 ); // The helper tag that shows the variables via CTX
