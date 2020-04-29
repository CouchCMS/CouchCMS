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

    // UDF for secure file upload
    class SecureFile extends KUserDefinedField{

        function __construct( $row, &$page, &$siblings ){
            global $FUNCS;

            // call parent
            parent::__construct( $row, $page, $siblings );

            $this->orig_data = $this->data = array();
            $this->requires_multipart = 1;
        }

        static function handle_params( $params ){
            global $AUTH;
            if( $AUTH->user->access_level < K_ACCESS_LEVEL_SUPER_ADMIN ) return;

            return SecureFile::_handle_params( $params );
        }

        static function _handle_params( $params ){
            global $FUNCS;

            // Default values for params
            $default_allowed_ext = array(
                                 '7z', 'aiff', 'asf', 'avi', 'bmp', 'csv', 'doc', 'docx', 'fla', 'flv', 'gif', 'gz', 'gzip', 'jpeg', 'jpg',
                                 'mid', 'mov', 'mp3', 'mp4', 'mpc', 'mpeg', 'mpg', 'ods', 'odt', 'pdf', 'png', 'ppt', 'pptx', 'pxd', 'qt',
                                 'ram', 'rar', 'rm', 'rmi', 'rmvb', 'rtf', 'sdc', 'sitd', 'svg', 'swf', 'sxc', 'sxw', 'tar', 'tgz', 'tif',
                                 'tiff', 'txt', 'vsd', 'wav', 'webm', 'wma', 'wmv', 'xls', 'xlsx', 'xml', 'zip'
                                );
            $default_max_size = 512; // in KB
            $default_max_width = 2048;
            $default_max_height = 2048;

            // get supplied params
            extract( $FUNCS->get_named_vars(
                        array(
                                'allowed_ext'=>'', /* e.g. jpeg,jpg,gif,png */
                                'max_size'=>'0',    /* in KB */
                                'max_width'=>'0',
                                'max_height'=>'0',
                                'thumb_width'=>'',
                                'thumb_height'=>'',
                                'thumb_enforce_max'=>'0',
                                'thumb_quality'=>'80',
                                'use_thumb_for_preview'=>'1',
                                'delete_caption'=>'',
                                'submit_caption'=>'',
                                'show_submit'=>'0',
                              ),
                        $params)
                   );

            // sanitize params
            $allowed_ext = trim( $allowed_ext );
            $max_size = trim( $max_size );
            $max_width = trim( $max_width );
            $max_height = trim( $max_height );
            $thumb_width = abs( (int)$thumb_width );
            $thumb_height = abs( (int)$thumb_height );
            $thumb_enforce_max = ( $thumb_enforce_max==1 ) ? 1 : 0;
            $thumb_quality = (int)$thumb_quality;
            if( $thumb_quality<=0 ){ $thumb_quality='80'; } elseif( $thumb_quality>100 ){ $thumb_quality='100'; }
            $use_thumb_for_preview = ( $use_thumb_for_preview==0 ) ? 0 : 1;
            $delete_caption = trim( $delete_caption );
            if( !strlen($delete_caption) ) $delete_caption='Delete file';
            $submit_caption = trim( $submit_caption );
            if( !strlen($submit_caption) ) $submit_caption='Upload file';
            $show_submit = ( $show_submit==1 ) ? 1 : 0;

            $max_size = ( $FUNCS->is_non_zero_natural($max_size) ) ? $max_size : $default_max_size;
            $max_width = ( $FUNCS->is_non_zero_natural($max_width) ) ? $max_width : $default_max_width;
            $max_height = ( $FUNCS->is_non_zero_natural($max_height) ) ? $max_height : $default_max_height;
            $allowed_extensions = array();
            $arr_ext = array_map( "trim", explode( ',', $allowed_ext ) );
            foreach( $arr_ext as $ext ){
                if( $ext && in_array($ext, $default_allowed_ext) ) $allowed_extensions[]=$ext;
            }
            if( !count($allowed_extensions) ) $allowed_extensions = $default_allowed_ext;

            // return back params
            $attr = array();
            $attr['allowed_ext'] = implode( ',', $allowed_extensions );
            $attr['max_size'] = $max_size;
            $attr['max_width'] = $max_width;
            $attr['max_height'] = $max_height;
            $attr['thumb_width'] = $thumb_width;
            $attr['thumb_height'] = $thumb_height;
            $attr['thumb_enforce_max'] = $thumb_enforce_max;
            $attr['thumb_quality'] = $thumb_quality;
            $attr['use_thumb_for_preview'] = $use_thumb_for_preview;
            $attr['delete_caption'] = $delete_caption;
            $attr['submit_caption'] = $submit_caption;
            $attr['show_submit'] = $show_submit;

            return $attr;
        }

        // Load from database
        function store_data_from_saved( $data ){
            global $FUNCS;
            $this->data = $FUNCS->unserialize( $data );
            if( !is_array($this->data) ) $this->data=array();
            $this->orig_data = $this->data;
        }

        // Output to admin panel
        function _render( $input_name, $input_id, $extra='', $dynamic_insertion=0 ){
            global $FUNCS, $CTX;

            if( $this->data['file_id'] ){
                $delete_caption = $this->delete_caption;
                if( $this->show_preview && $this->_is_image($this->data['file_ext']) ){
                    $use_thumb = ( $this->use_thumb_for_preview && ($this->thumb_width || $this->thumb_height) ) ? 1 : 0;
                    $data = $this->data['file_id'] . '|' . $use_thumb . '|0|0|0|0|0|0';
                    $link = K_ADMIN_URL . 'download.php?auth=' . urlencode( $data . '|' . $FUNCS->hash_hmac($data, $FUNCS->hash_hmac($data, $FUNCS->get_secret_key())) );

                    $html .= '<div class="secure_file_preview_wrapper">';
                    $html .= '<img id="file_preview_' . $input_id . '" name="file_preview_'. $input_name .'" src="'.$link.'" ';
                    if( !$use_thumb ){
                        $html .= ( $this->preview_width ) ? 'width="'.$this->preview_width.'" ': '';
                        $html .= ( $this->preview_height ) ? 'height="'.$this->preview_height.'" ': '';
                    }
                    $html .= 'class="secure_file_preview" >';
                    $html .= '</div>';
                }

                if( defined('K_ADMIN') ){
                    $data = $this->data['file_id'] . '|0|0|0|0|2|0|0';
                    $link = K_ADMIN_URL . 'download.php?auth=' . urlencode( $data . '|' . $FUNCS->hash_hmac($data, $FUNCS->hash_hmac($data, $FUNCS->get_secret_key())) );
                    $html .= '<span class="sf_filename" id="file_name_' . $input_id . '" name="file_name_'. $input_name .'"><a href="'.$link.'">'.$this->data['file_name'].'</a></span>&nbsp;';
                }
                else{
                    $html .= '<span class="sf_filename" id="file_name_' . $input_id . '" name="file_name_'. $input_name .'">'.$this->data['file_name'].'</span>&nbsp;';
                }
                $html .= '<input type="submit" class="sf_btn" name="delete_'.$input_name.'" value="'.$delete_caption.'" />';
                $html .= '<input type="hidden" name="secure_file_id_'.$input_name.'" value="'.$this->data['file_id'].'" />';
                $html .= '<input type="hidden" name="secure_file_name_'.$input_name.'" value="'.$this->data['file_name'].'" />';
                $html .= '<input type="hidden" name="secure_file_ext_'.$input_name.'" value="'.$this->data['file_ext'].'" />';
                $html .= '<input type="hidden" name="secure_file_size_'.$input_name.'" value="'.$this->data['file_size'].'" />';

                // add a nonce to prevent tampering
                $data = $this->data['file_id'] . ':' . $this->data['file_name'] . ':' . $this->data['file_ext'] . ':' . $this->data['file_size'];
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

        // Output to front-end via $CTX
        function get_data( $for_ctx=0 ){
            global $CTX;

            if( $for_ctx ){
                // Data not a simple string hence
                // we'll store it into '_obj_' of CTX directly
                // to be used by the auxilally tag which knows how to display it
                $CTX->set_object( $this->name, $this->data );
            }

            // and return only status for the normal context
            return ( count($this->data) ? 1 : 0 );
        }

        // Handle posted data
        function store_posted_changes( $post_val ){
            global $FUNCS;
            if( $this->deleted || $this->k_inactive ) return; // no need to store

            $secure_file_id = $this->_get_input_name( 'secure_file_id' );
            if( isset($_POST[$secure_file_id]) ){ // existing attachment
                if( isset($_POST[$this->_get_input_name( 'delete' )]) ){
                    $this->data = array();
                    $this->refresh_form = 1;
                }
                else{
                    $file_id = $_POST[$secure_file_id];
                    $file_name = $_POST[$this->_get_input_name( 'secure_file_name' )];
                    $file_ext = $_POST[$this->_get_input_name( 'secure_file_ext' )];
                    $file_size = $_POST[$this->_get_input_name( 'secure_file_size' )];
                    $file_nonce = $_POST[$this->_get_input_name( 'secure_file_nonce' )];

                    // verify nonce before accepting submitted values
                    if( $file_nonce == $this->_get_nonce( $file_id . ':' . $file_name . ':' . $file_ext . ':' . $file_size ) ){
                        $this->data['file_id'] = $file_id;
                        $this->data['file_name'] = $file_name;
                        $this->data['file_ext'] = $file_ext;
                        $this->data['file_size'] = $file_size;
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
                }
                else{
                    $this->data = array();
                }
            }

            // modified?
            $this->modified = ( $this->orig_data['file_id'] == $this->data['file_id'] ) ? false : true;

        }

        function validate(){
            global $FUNCS;
            if( $this->deleted || $this->k_inactive ) return true;

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

        // Save to database.
        function get_data_to_save(){
            global $FUNCS, $DB, $Config;

            $processed = 1;
            // process the new file being attached
            if( $this->data['file_id'] ){
                // verify that a record exists and is indeed 'orphan' before accepting
                $rs = $DB->select( K_TBL_ATTACHMENTS, array('attach_id'), "attach_id='" . $DB->sanitize( $this->data['file_id'] ). "' AND is_orphan='1'" );
                if( count($rs) ){
                    // 'un-orphan' the record
                    $DB->update( K_TBL_ATTACHMENTS, array('is_orphan'=>'0'), "attach_id='" . $DB->sanitize( $this->data['file_id'] ). "'" );
                }
                else{
                    $this->data = $this->orig_data;
                    $processed = 0;
                }
            }

            // process the old file being replaced
            if( $this->orig_data['file_id'] && $processed ){
                // 'orphan' the existing record
                $DB->update( K_TBL_ATTACHMENTS, array('is_orphan'=>'1'), "attach_id='" . $DB->sanitize( $this->orig_data['file_id'] ). "'" );
            }

            // take the opportunity to remove orphan files older than 6 hours
            $threshold = time() - (6 * 60 * 60);
            $rs = $DB->select( K_TBL_ATTACHMENTS, array('*'), "file_time<'" . $threshold . "' AND is_orphan='1'" );
            if( count($rs) ){
                $dest_folder = $Config['UserFilesAbsolutePath'] . 'attachments/';
                foreach( $rs as $rec ){
                    // delete physical file
                    @unlink( $dest_folder . $rec['file_disk_name'] . '.' . $rec['file_extension'] );
                    // also associated thumbnail, if any
                    @unlink( $dest_folder . $rec['file_disk_name'] . '_t.' . $rec['file_extension'] );
                }
                $DB->delete( K_TBL_ATTACHMENTS, "file_time<'" . $threshold . "' AND is_orphan='1'" );
            }

            return $FUNCS->serialize( $this->data );
        }

        // Search value
        function get_search_data(){
            return '';
        }

        function _process_upload( $input_name ){
            global $FUNCS, $DB, $AUTH, $Config;

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

            // check if destination folder exists and is writable
            if( !file_exists($Config['UserFilesAbsolutePath']) ){
                return $FUNCS->raise_error( 'Destination folder for uploads does not exist' );
            }
            $dest_folder = $Config['UserFilesAbsolutePath'] . 'attachments';
            if( !file_exists($dest_folder) ){
                $oldumask = umask( 0 );
                if( !@mkdir($dest_folder, 0777) ){
                    umask( $oldumask );
                    return $FUNCS->raise_error( 'Destination folder for attachments does not exist' );
                }
                umask( $oldumask );
            }
            if( !@is_writable($dest_folder) ){
                return $FUNCS->raise_error( 'Destination folder for attachments is not writable' );
            }

            // move file
            $disk_file_name = md5( $AUTH->hasher->get_random_bytes(16) );
            $dest_file_path = $dest_folder . '/' . $disk_file_name . '.' . $file_ext;
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

                // all ok
                $process_image = 1;
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

            // if we reach here, everything is ok.. can persist new file's info into database
            $rs = $DB->insert( K_TBL_ATTACHMENTS,
                                array(
                                'file_real_name'=>$file_name,
                                'file_disk_name'=>$disk_file_name,
                                'file_extension'=>$file_ext,
                                'file_size'=>$file_size,
                                'file_time'=>time(),
                                'creation_ip'=>trim( $FUNCS->cleanXSS(strip_tags($_SERVER['REMOTE_ADDR'])) ),
                                'is_orphan'=>1
                                )
                            );
            if( $rs!=1 ) return $FUNCS->raise_error( "Failed to insert record in K_TBL_ATTACHMENTS" );
            $id = $DB->last_insert_id;

            // if upload was an image, see if it requires some processing
            // We are doing this after adding an entry into the database because this processing
            // could possibly cause a 'white-out' due to insufficient memory etc.
            if( $process_image ){
                // the main image
                $res = $this->_process_image( $dest_file_path );
                if( !$FUNCS->is_error($res) ){
                    $new_size = @filesize( $dest_file_path );
                    if( $new_size!==false && $new_size!=$file_size ){
                        $DB->update( K_TBL_ATTACHMENTS, array('file_size'=>$new_size), "attach_id='" . $DB->sanitize( $id ). "'" );
                        $file_size = $new_size;
                    }
                }

                // its thumbnail
                if( $this->thumb_width || $this->thumb_height ){
                    $thumb_file_path = $dest_folder . '/' . $disk_file_name . '_t.' . $file_ext;
                    $res = $this->_process_image( $dest_file_path, $thumb_file_path );
                }
            }

            // return new values
            $this->data['file_id'] = $id;
            $this->data['file_name'] = $file_name;
            $this->data['file_ext'] = $file_ext;
            $this->data['file_size'] = $file_size;
            return true;
        }

        function _process_image( $src, $dest=null ){
            global $FUNCS;

            if( extension_loaded('gd') && function_exists('gd_info') ){
                require_once( K_COUCH_DIR.'includes/timthumb.php' );

                if( !$dest ){ // main image
                    $dest = $src;
                    $w = $this->width;
                    $h = $this->height;
                    $crop = $this->crop;
                    $enforce_max = ( $crop ) ? 0 : $this->enforce_max; // make crop and enforce_max mutually exclusive
                    $quality = $this->quality;
                }
                else{ // thumbnail
                    $w = $this->thumb_width;
                    $h = $this->thumb_height;
                    $crop = !$this->thumb_enforce_max;
                    $enforce_max = $this->thumb_enforce_max;
                    $quality = $this->thumb_quality;
                }
                return k_resize_image( $src, $dest, $w, $h, $crop, $enforce_max, $quality );
            }
        }

        function _get_nonce( $data ){
            global $FUNCS;

            $key = $FUNCS->hash_hmac( $data, $FUNCS->_get_nonce_secret_key() );
            $nonce = $FUNCS->hash_hmac( $data, $key );

            return $nonce;
        }

        // To handle discrepency in names of inputs rendered in admin-panel and front-end
        function _get_input_name( $input_name='' ){
            if( $input_name ) $input_name .= '_';
            return $input_name .'f_'.$this->name;
        }

        static function _is_image( $file_ext ){
            return in_array( $file_ext, array('jpg', 'jpeg', 'png', 'gif') );
        }

        //////
        // Handles 'cms:show_securefile' tag
        static function show_handler( $params, $node ){
            global $FUNCS, $CTX, $DB;
            if( !count($node->children) ) return;

            extract( $FUNCS->get_named_vars(
                array(
                    'var'=>'',
                    'with_hits'=>'0'
                ),
                $params)
            );
            $var = trim( $var );
            $with_hits = ( $with_hits==1 ) ? 1 : 0;

            if( $var ){
                // get the data array from CTX
                $obj = &$CTX->get_object( $var );

                if( $obj ){
                    // set component values as $CTX variables
                    $CTX->set( 'file_id', $obj['file_id'] );
                    $CTX->set( 'file_name', $obj['file_name'] );
                    $CTX->set( 'file_ext', $obj['file_ext'] );
                    $is_image = ( SecureFile::_is_image($obj['file_ext']) ) ? 1 : 0;
                    $CTX->set( 'file_is_image', $is_image );
                    $CTX->set( 'file_size', $obj['file_size'] );

                    // hit count
                    $hit_count = '0';
                    if( $with_hits ){
                        $rs = $DB->select( K_TBL_ATTACHMENTS, array('hit_count'), "attach_id='" . $DB->sanitize( $obj['file_id'] )."'" );
                        if( count($rs) ){
                            $hit_count = $rs[0]['hit_count'];
                        }
                    }
                    $CTX->set( 'file_hit_count', $hit_count );

                    // and call the children tags
                    foreach( $node->children as $child ){
                        $html .= $child->get_HTML();
                    }
                }

                return $html;
            }
        }

        // Handles 'cms:securefile_link' tag
        static function link_handler( $params, $node ){
            global $FUNCS, $DB, $Config;
            if( count($node->children) ) {die("ERROR: Tag \"".$node->name."\" is a self closing tag");}

            extract( $FUNCS->get_named_vars(
                        array(
                              'id'=>'',
                              'thumbnail'=>'0',
                              'physical_path'=>'0',
                              ),
                        $params)
                   );

            $id = trim( $id );
            if( !$FUNCS->is_non_zero_natural($id) ) return;
            $is_thumb = ( $thumbnail==1 ) ? 1 : 0;
            $physical_path = ( $physical_path==1 ) ? 1 : 0;

            $link = '';
            $rs = $DB->select( K_TBL_ATTACHMENTS, array('file_real_name', 'file_disk_name','file_extension'), "attach_id='" . $DB->sanitize( $id ). "'" );
            if( count($rs) ){
                $file_name = $rs[0]['file_disk_name'];
                if( $is_thumb ) $file_name .= '_t';
                $file_name .= '.' . $rs[0]['file_extension'];

                if( $physical_path ){
                    $link = $Config['UserFilesAbsolutePath'] . 'attachments/';
                }
                else{
                    $link = $Config['k_append_url'] . $Config['UserFilesPath'] . 'attachments/';
                }

                $link .= $file_name;
            }

            return $link;
        }
    }
    $FUNCS->register_udf( 'securefile', 'SecureFile', 0/*repeatable*/ );
    $FUNCS->register_tag( 'show_securefile', array('SecureFile', 'show_handler'), 1, 0 ); // The helper tag that shows the variables via CTX
    $FUNCS->register_tag( 'securefile_link', array('SecureFile', 'link_handler'), 0, 0 ); // outputs link to the physical file
