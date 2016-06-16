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
    define( 'K_ADMIN', 1 );

    if ( !defined('K_COUCH_DIR') ) define( 'K_COUCH_DIR', str_replace( '\\', '/', dirname(realpath(__FILE__) ).'/') );
    require_once( K_COUCH_DIR.'header.php' );

    $AUTH->check_access( K_ACCESS_LEVEL_ADMIN, 1 );

    $response = '';
    if( isset($_GET['act']{0}) ){
        if( $_GET['act'] == 'crop' ){
            require_once( K_COUCH_DIR.'includes/timthumb.php' );

            $tpl_id = ( isset($_GET['tpl']) && $FUNCS->is_natural( $_GET['tpl'] ) ) ? (int)$_GET['tpl'] : null;
            $page_id = ( isset($_GET['p']) && $FUNCS->is_natural( $_GET['p']) ) ? (int)$_GET['p'] : null;
            $thumb_id = ( isset($_GET['tb']) ) ? $_GET['tb'] : null;
            $nonce = ( isset($_GET['nonce']) ) ? $_GET['nonce'] : null;
            $crop_pos = ( isset($_GET['cp']) ) ? $_GET['cp'] : 'middle';

            if( $tpl_id && $page_id && $thumb_id && $nonce ){
                $FUNCS->validate_nonce( 'crop_image_' . $thumb_id );

                // create thumbnail
                $PAGE = new KWebpage( $tpl_id, $page_id );
                if( $PAGE->error ){
                    ob_end_clean();
                    die( 'ERROR: ' . $PAGE->err_msg );
                }

                for( $x=0; $x<count($PAGE->fields); $x++ ){
                    $tb = &$PAGE->fields[$x];
                    if( !$tb->system ){
                        if( $tb->k_type == 'thumbnail' && $tb->name==$thumb_id ){
                            // loop again to find the associated thumbnail
                            for( $t=0; $t<count($PAGE->fields); $t++ ){
                                $f = &$PAGE->fields[$t];
                                if( (!$f->system) && $f->k_type=='image' && $tb->assoc_field==$f->name ){

                                    if( extension_loaded('gd') && function_exists('gd_info') ){
                                        $src = $f->get_data();
                                        $pos = strpos( $src, $Config['k_append_url'] );
                                        if( $pos !== false ){
                                            $src = substr( $src, strlen($Config['k_append_url']) );
                                            $pos = strpos( $src, $Config['UserFilesPath'] );
                                            if( $pos !== false ){
                                                $src = substr( $src, strlen($Config['UserFilesPath']) );
                                                $src = $Config['UserFilesAbsolutePath'] . $src;

                                                // create thumbnail
                                                $dest = null;
                                                $w = $tb->width;
                                                $h = $tb->height;
                                                $crop = 1;
                                                $enforce_max = 0;
                                                $quality = $tb->quality;

                                                $thumbnail = k_resize_image( $src, $dest, $w, $h, $crop, $enforce_max, $quality, $crop_pos );
                                                if( $FUNCS->is_error($thumbnail) ){
                                                    die( $thumbnail->err_msg );
                                                }

                                            }
                                        }
                                    }
                                    else{
                                        die( 'No GD image library installed' );
                                    }

                                    // Job done. Exit.
                                    die( 'OK' );
                                }
                                unset( $f );
                            }

                        }

                    }
                    unset( $tb );
                }

                $response = 'OK';
            }
        }
        elseif( $_GET['act'] == 'delete-tpl' ){
            $tpl_id = ( isset($_GET['tpl']) && $FUNCS->is_natural( $_GET['tpl'] ) ) ? (int)$_GET['tpl'] : null;
            $nonce = ( isset($_GET['nonce']) ) ? $_GET['nonce'] : null;
            if( $tpl_id && $nonce ){
                $FUNCS->validate_nonce( 'delete_tpl_' . $tpl_id );

                // get the template
                $rs = $DB->select( K_TBL_TEMPLATES, array('*'), "id='" . $DB->sanitize( $tpl_id ). "' LIMIT 1" );
                if( count($rs) ){
                    $DB->begin();

                    // HOOK: alter_template_delete
                    $rec = $rs[0]; $msg='Unable to delete template';
                    $skip = $FUNCS->dispatch_event( 'alter_template_delete', array($rec, &$msg) );
                    if( $skip ){
                        die( $msg );
                    }

                    // Confirm no cloned pages (except the default-page) exist
                    $rs2 = $DB->select( K_TBL_PAGES, array('*'), "template_id='" . $DB->sanitize( $tpl_id ). "' AND is_master<>'1'" );
                    if( count($rs2) ){
                        die( 'Template has existing cloned pages' );
                    }

                    // delete default page for the template
                    $PAGE = new KWebpage( $tpl_id, null );
                    if( !$PAGE->error ){
                        $PAGE->delete();
                    }

                    // remove template along with the fields and folders defined for it
                    $rs = $DB->delete( K_TBL_TEMPLATES, "id='" . $DB->sanitize( $tpl_id ). "'" );
                    if( $rs==-1 ) die( "ERROR: Unable to delete template from K_TBL_TEMPLATES" );

                    $rs = $DB->delete( K_TBL_FIELDS, "template_id='" . $DB->sanitize( $tpl_id ). "'" );
                    if( $rs==-1 ) die( "ERROR: Unable to delete template data from K_TBL_FIELDS" );

                    $rs = $DB->delete( K_TBL_FOLDERS, "template_id='" . $DB->sanitize( $tpl_id ). "'" );
                    if( $rs==-1 ) die( "ERROR: Unable to delete template data from K_TBL_FOLDERS" );

                    // HOOK: template_deleted
                    $FUNCS->dispatch_event( 'template_deleted', array($rec) );

                    // wrap up
                    $DB->commit( 1 );
                    $FUNCS->invalidate_cache();
                    die( 'OK' );
                }
                else{
                    die( 'Template not found' );
                }
            }
            else{
                die( 'Invalid parameters' );
            }
        }
        elseif( $_GET['act'] == 'delete-field' ){
            $fid = ( isset($_GET['fid']) && $FUNCS->is_natural( $_GET['fid'] ) ) ? (int)$_GET['fid'] : null;
            $nonce = ( isset($_GET['nonce']) ) ? $_GET['nonce'] : null;
            if( $fid && $nonce ){
                $FUNCS->validate_nonce( 'delete_field_' . $fid );

                // get the field
                $rs = $DB->select( K_TBL_FIELDS, array('*'), "id='" . $DB->sanitize( $fid ). "' LIMIT 1" );
                if( count($rs) ){
                    $DB->begin();

                    // HOOK: alter_datafield_delete_for_allpages
                    $rec = $rs[0];
                    $FUNCS->dispatch_event( 'alter_datafield_delete_for_allpages', array($rec) );

                    // If field is udf, intimate it about the impending deletion
                    if( !$FUNCS->is_core_type($rs[0]['k_type']) ){
                        $classname = $FUNCS->udfs[$rs[0]['k_type']]['handler'];
                        $f = new $classname( $rs[0], new KError('dummy'), new KError('dummy') );
                        $f->_delete( -1 );
                    }

                    if( $rs[0]['search_type'] == 'text' ){
                        // remove all instances of this text field
                        $rs = $DB->delete( K_TBL_DATA_TEXT, "field_id='" . $DB->sanitize( $fid ). "'" );
                        if( $rs==-1 ) die( "ERROR: Unable to delete field data from K_TBL_DATA_TEXT" );
                    }
                    else{
                        // remove all instances of this numeric field
                        $rs = $DB->delete( K_TBL_DATA_NUMERIC, "field_id='" . $DB->sanitize( $fid ). "'" );
                        if( $rs==-1 ) die( "ERROR: Unable to delete field data from K_TBL_DATA_NUMERIC" );
                    }

                    // finally remove this field
                    $rs = $DB->delete( K_TBL_FIELDS, "id='" . $DB->sanitize( $fid ). "'" );
                    if( $rs==-1 ) die( "ERROR: Unable to delete field K_TBL_FIELDS" );

                    // HOOK: field_deleted
                    $FUNCS->dispatch_event( 'field_deleted', array($rec) );

                    // wrap up
                    $DB->commit( 1 );
                    $FUNCS->invalidate_cache();
                    die( 'OK' );
                }
                else{
                    die( 'Field not found' );
                }
            }
            else{
                die( 'Invalid parameters' );
            }
        }
        elseif( $_GET['act'] == 'delete-columns' ){
            $fid = ( isset($_GET['fid']) && $FUNCS->is_natural( $_GET['fid'] ) ) ? (int)$_GET['fid'] : null;
            $nonce = ( isset($_GET['nonce']) ) ? $_GET['nonce'] : null;
            if( $fid && $nonce ){
                $FUNCS->validate_nonce( 'delete_column_' . $fid );

                // get the field
                $rs = $DB->select( K_TBL_FIELDS, array('*'), "id='" . $DB->sanitize( $fid ). "' LIMIT 1" );
                if( count($rs) ){
                    if( $rs[0]['k_type'] != '__repeatable' ) die( 'Field not of type repeatable' );

                    // OK to make the changes
                    $DB->begin();
                    // Serialize access for super-admins.. hack of a semaphore
                    $DB->update( K_TBL_SETTINGS, array('k_value'=>K_COUCH_VERSION), "k_key='k_couch_version'" );

                    $custom_params = @$FUNCS->unserialize( $rs[0]['custom_params'] );
                    if( is_array($custom_params) ){
                        $schema = @$FUNCS->unserialize( $custom_params['schema'] );
                        if( is_array($schema) ){
                            $new_schema = array();
                            foreach( $schema as $col ){
                                if( $col['deleted'] ) continue;
                                $new_schema[]=$col;
                            }

                            // save new schema
                            $custom_params['schema'] = $FUNCS->serialize( $new_schema );
                            $custom_params = $FUNCS->serialize( $custom_params );
                            $rs = $DB->update( K_TBL_FIELDS, array('custom_params'=>$custom_params), "id='" . $DB->sanitize( $fid ). "'" );
                            if( $rs==-1 ) die( "ERROR: Unable to save modified schema to field" );
                        }
                    }
                    // wrap up
                    $DB->commit( 1 );
                    echo 'OK';
                    die();
                }
                else{
                    die( 'Field not found' );
                }
            }
            else{
                die( 'Invalid parameters' );
            }
        }
    }

    echo $response;
