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

    class KDataBoundForm{

        // Saves Data Bound Forms ('db_persist' tag also piggy-backs on this)
        static function db_persist_form( $params, $node ){
            global $FUNCS, $DB, $CTX, $AUTH;
            if( $node->name=='db_persist_form' && count($node->children) ) {die("ERROR: Tag \"".$node->name."\" is a self closing tag");}

            // handle params
            $arr_known_params = array( '_invalidate_cache'=>'0', '_auto_title'=>'0', '_token'=>'', '_fields'=>'' );
            if( $node->name=='db_persist' ){
                $arr_known_params = array_merge( $arr_known_params, array('_masterpage'=>'', '_mode'=>'', '_page_id'=>'', '_separator'=>'|', '_set_errors_in_context'=>'0') );
            }
            extract( $FUNCS->get_named_vars(
                        $arr_known_params,
                        $params)
                  );
            $_invalidate_cache = ( $_invalidate_cache==1 ) ? 1 : 0;
            $_auto_title = ( $_auto_title==1 ) ? 1 : 0;
            $_token = trim( $_token  );
            $_set_errors_in_context = ( $_set_errors_in_context==1 ) ? 1 : 0;
            $_fields = ( is_array($_fields) ) ? $_fields : array();

            // get down to business
            if( $node->name=='db_persist_form' ){
                // can only be used used within a data-bound form.. page object wlll be provided by the form
                $pg = &$CTX->get_object( 'k_bound_page', 'form' );
                if( is_null($pg) ){
                    die("ERROR: Tag \"".$node->name."\" needs to be within a Data-bound form");
                }
                $_mode = ( $pg->id==-1 || is_null($pg->id) ) ? 'create' : 'edit';
            }
            else{
                // get the page object
                $_masterpage = trim( $_masterpage );
                if( !$_masterpage ){
                    die( "ERROR: Tag \"".$node->name."\": '_masterpage' attribute missing" );
                }
                $_mode = strtolower( $_mode );
                if( !($_mode=='edit' || $_mode=='create') ){
                    die( "ERROR: Tag \"".$node->name."\" - unknown value for 'mode' parameter (only 'edit' and 'create' supported)" );
                }

                if( $FUNCS->is_spl_template( $_masterpage ) ){
                    $pg = $FUNCS->handle_spl_template( $_masterpage, array($_page_id, '', &$_mode) );
                    if( $pg->error ){
                        die( "ERROR: Tag \"".$node->name."\" - " . $pg->err_msg );
                    }
                }
                else{
                    $rs = $DB->select( K_TBL_TEMPLATES, array('id', 'clonable'), "name='" . $DB->sanitize( $_masterpage ). "'" );
                    if( !count($rs) ){
                        die( "ERROR: Tag \"".$node->name."\" - _masterpage does not exist" );
                    }

                    if( $_mode=='edit' ){
                        $_page_id = ( isset($_page_id) && $FUNCS->is_non_zero_natural($_page_id) ) ? (int)$_page_id : null;
                        if( $rs[0]['clonable'] && !$_page_id ){
                            die( "ERROR: Tag \"".$node->name."\" - _page_id required" );
                        }
                    }
                    else{
                        if( !$rs[0]['clonable'] ){
                            die( "ERROR: Tag \"".$node->name."\" - cannot create page of non-clonable template" );
                        }
                        $_page_id = -1;
                    }

                    $pg = new KWebpage( $rs[0]['id'], $_page_id );
                    if( $pg->error ){
                        die( "ERROR: Tag \"".$node->name."\" - " . $pg->err_msg );
                    }
                }

                $count = count( $pg->fields );
                for( $x=0; $x<$count; $x++ ){
                    $f = &$pg->fields[$x];
                    $f->resolve_dynamic_params();
                    unset( $f );
                }
            }

            // gather static values provided as parameters of this tag
            $fields = count( $_fields ) ? $_fields : array();
            foreach( $params as $param ){
                $pname = strtolower( trim($param['lhs']) );
                if( array_key_exists($pname, $arr_known_params) ) continue;
                $fields[$pname]=$param['rhs'];
            }

            // HOOK: db_xxx_alter_fields
            if( $_token ){
                $FUNCS->dispatch_event( $node->name.'_alter_fields_'.$_token, array(&$pg, &$fields, $_mode, $params, $node) );
            }

            if( count($fields) ){
                if( $pg instanceof KWebpage ){
                    foreach( $fields as $key=>$val ){
                        if( array_key_exists($key, $pg->_fields) ){
                            $f = &$pg->_fields[$key];
                            if( $f->k_type== 'checkbox' ){
                                // supplied static checkbox values are supposed to be comma-separated -
                                // this needs to be changed to match the separator expected by page-field
                                $separator = ( $f->k_separator ) ? $f->k_separator : '|';
                                $sep = '';
                                $str_val = '';
                                $val = explode(',', $val);
                                foreach( $val as $v ){
                                    $str_val .= $sep . trim( $v );
                                    $sep = $separator;
                                }
                                $f->store_posted_changes( $str_val );
                            }
                            else{
                                $f->store_posted_changes( $val, $node->name );
                            }
                        }
                    }
                }
                else{
                    for( $x=0; $x<count($pg->fields); $x++ ){
                        $f = &$pg->fields[$x];
                        if( isset($fields[$f->name]) ){
                            if( $f->k_type== 'checkbox' ){
                                // supplied static checkbox values are supposed to be comma-separated -
                                // this needs to be changed to match the separator expected by page-field
                                $separator = ( $f->k_separator ) ? $f->k_separator : '|';
                                $sep = '';
                                $str_val = '';
                                $fields[$f->name] = explode(',', $fields[$f->name]);
                                foreach( $fields[$f->name] as $v ){
                                    $str_val .= $sep . trim( $v );
                                    $sep = $separator;
                                }
                                $f->store_posted_changes( $str_val );
                            }
                            else{
                                $f->store_posted_changes( $fields[$f->name], $node->name );
                            }
                        }
                        unset( $f );
                    }
                }
            }

            if( $pg instanceof KWebpage ){
                // _auto_title
                // if creating a new page and both title and name not set, create a random title
                // This will also create a random name using the title when the page is saved
                if( $_mode=='create' && $_auto_title ){
                    if( trim($pg->_fields['k_page_name']->get_data())=='' ){ // name
                        $f = &$pg->_fields['k_page_title']; // title
                        if( trim($f->get_data())=='' ){
                            $f->store_posted_changes( md5($AUTH->hasher->get_random_bytes(16)) );
                        }
                        unset( $f );
                    }
                }

                $f = &$pg->_fields['k_publish_date']; // k_publish_date
                if( !$f->get_data() ){
                    $f->store_posted_changes( $FUNCS->get_current_desktop_time() );
                }
                unset( $f );
            }

            // HOOK: db_xxx_presave
            if( $_token ){
                $FUNCS->dispatch_event( $node->name.'_presave_'.$_token, array(&$pg, $_mode, $params, $node) );
            }

            // Save..
            $errors = $pg->save( $node->name );

            // HOOK: db_xxx_postsave
            if( $_token ){
                $FUNCS->dispatch_event( $node->name.'_postsave_'.$_token, array(&$pg, $_mode, &$errors, $params, $node) );
            }

            if( $errors ){
                $sep = '';
                $form_separator = ( $node->name=='db_persist_form' ) ? $CTX->get('k_cur_form_separator') : $_separator;

                $str_err = '';
                for( $x=0; $x<count($pg->fields); $x++ ){
                    $f = &$pg->fields[$x];
                    if( $f->err_msg ){
                        if( $node->name=='db_persist_form' || ($node->name=='db_persist' && $_set_errors_in_context) ){
                            $CTX->set( 'k_error_'.$f->name, $f->err_msg );
                        }
                        $str_err .= $sep . '<b>' . (($f->label) ? $f->label : $f->name) . ':</b> ' . $f->err_msg;
                        $sep = $form_separator;
                    }
                    unset( $f );
                }
                $CTX->set( 'k_success', '' );
                $CTX->set( 'k_error', $str_err );
                $CTX->set( 'k_error_count', $errors );
                $CTX->set( 'k_persist_error', $str_err );
                $CTX->set( 'k_persist_error_count', $errors );

                // HOOK: db_xxx_savefailed
                if( $_token ){
                    $FUNCS->dispatch_event( $node->name.'_savefailed_'.$_token, array(&$pg, $_mode, &$errors, $params, $node) );
                }
            }
            else{
                if( $_invalidate_cache ){
                    $FUNCS->invalidate_cache();
                }

                // report success
                $CTX->set( 'k_success', '1' );
                $CTX->set( 'k_error', '' );
                $CTX->set( 'k_error_count', '0' );
                $CTX->set( 'k_persist_error_count', '0' );
                if( $_mode=='create' ){
                    $CTX->set( 'k_last_insert_id', $pg->id );
                    $CTX->set( 'k_last_insert_page_name', $pg->page_name );
                }

                // HOOK: db_xxx_savesuccess
                if( $_token ){
                    $FUNCS->dispatch_event( $node->name.'_savesuccess_'.$_token, array(&$pg, $_mode, $params, $node) );
                }
            }

            if( $node->name=='db_persist' ){ $pg->destroy(); unset( $pg ); }

            // call the children
            foreach( $node->children as $child ){
                $html .= $child->get_HTML();
            }
            return $html;
        }

        // Creates new page or Updates existing one
        static function db_persist( $params, $node ){
            // delegate to 'db_persist_form' tag
            return KDataBoundForm::db_persist_form( $params, $node );
        }

        // Same as db_persist above but has no context of its own
        static function db_persist_ex( $params, $node ){

            if( count($node->children) ) {die("ERROR: Tag \"".$node->name."\" is a self closing tag");}

            // delegate to 'db_persist_form' tag
            $node->name='db_persist';
            return KDataBoundForm::db_persist_form( $params, $node );
        }

        // Deletes page
        static function db_delete( $params, $node ){
            global $FUNCS, $DB, $CTX;
            if( count($node->children) ) {die("ERROR: Tag \"".$node->name."\" is a self closing tag");}

            // handle params
            extract( $FUNCS->get_named_vars(
                        array(
                              'masterpage'=>'',
                              'page_id'=>'',
                              'invalidate_cache'=>'0'
                              ),
                        $params)
                   );
            $masterpage = trim( $masterpage );
            if( !$masterpage ){
                die( "ERROR: Tag \"".$node->name."\": 'masterpage' attribute missing" );
            }
            $page_id = ( isset($page_id) && $FUNCS->is_non_zero_natural($page_id) ) ? (int)$page_id : null;
            if( !$page_id ){
                die( "ERROR: Tag \"".$node->name."\": 'page_id' required" );
            }

            // get down to business
            if( $FUNCS->is_spl_template( $masterpage ) ){
                $mode = 'delete';
                $pg = $FUNCS->handle_spl_template( $masterpage, array($page_id, '', &$mode) );
                if( $pg->error ){
                    die( "ERROR: Tag \"".$node->name."\" - " . $pg->err_msg );
                }
            }
            else{
                $rs = $DB->select( K_TBL_TEMPLATES, array('id', 'clonable'), "name='" . $DB->sanitize( $masterpage ). "'" );
                if( !count($rs) ){
                    die( "ERROR: Tag \"".$node->name."\" - masterpage does not exist" );
                }

                if( !$rs[0]['clonable'] ){
                    die( "ERROR: Tag \"".$node->name."\" - cannot delete non-clonable template" );
                }

                $pg = new KWebpage( $rs[0]['id'], $page_id );
                if( $pg->error ){
                    die( "ERROR: Tag \"".$node->name."\" - " . $pg->err_msg );
                }
            }

            // delete..
            $pg->delete();

            // if we are here, delete was successful (script would have died otherwise)
            $pg->destroy();
            unset( $pg );
            if( $invalidate_cache ){
                $FUNCS->invalidate_cache();
            }
        }

        // Deletes page bound to the form
        static function db_delete_form( $params, $node ){
            global $FUNCS, $DB, $CTX;
            if( count($node->children) ) {die("ERROR: Tag \"".$node->name."\" is a self closing tag");}

            // handle params
            extract( $FUNCS->get_named_vars(
                        array(
                              'invalidate_cache'=>'0'
                              ),
                        $params)
                   );

            // get the page object bound to the form
            $pg = &$CTX->get_object( 'k_bound_page', 'form' );
            if( is_null($pg) ){
                die( "ERROR: Tag \"".$node->name."\" needs to be within a Data-bound form" );
            }

            if( $pg->id==-1 || is_null($pg->id) ){
                die( "ERROR: Tag \"".$node->name."\" - mode of Data-bound form needs to be 'edit'" );
            }

            if( $pg instanceof KWebpage ){
                if( !$pg->tpl_is_clonable ){
                    die( "ERROR: Tag \"".$node->name."\" - cannot delete non-clonable template" );
                }
            }

            // delete..
            $pg->delete();

            // if we are here, delete was successful (script would have died otherwise)
            $pg->destroy();
            unset( $pg );
            if( $invalidate_cache ){
                $FUNCS->invalidate_cache();
            }

        }

        // Begins transaction
        static function db_begin_trans( $params, $node ){
            global $DB;
            if( count($node->children) ) {die("ERROR: Tag \"".$node->name."\" is a self closing tag");}

            $DB->begin();
        }

        // Commits transaction
        static function db_commit_trans( $params, $node ){
            global $DB, $FUNCS;
            if( count($node->children) ) {die("ERROR: Tag \"".$node->name."\" is a self closing tag");}

            extract( $FUNCS->get_named_vars(
                        array(
                              'force'=>'0'
                              ),
                        $params)
                   );
            $force = ( $force==1 ) ? 1 : 0;

            $DB->commit( $force );
        }

        // Rollbacks transaction
        static function db_rollback_trans( $params, $node ){
            global $DB, $FUNCS;
            if( count($node->children) ) {die("ERROR: Tag \"".$node->name."\" is a self closing tag");}

            extract( $FUNCS->get_named_vars(
                        array(
                              'force'=>'1'
                              ),
                        $params)
                   );
            $force = ( trim($force)==='0' ) ? 0 : 1;

            $DB->rollback( $force );
        }

        // Provides meta-info about all fields in a template
        static function db_fields( $params, $node ){
            global $FUNCS, $PAGE, $DB, $CTX;

            extract( $FUNCS->get_named_vars(
                        array(
                               'masterpage'=>'',
                               'page_name'=>'',
                               'names'=>'', /*name(s) of fields to fetch. Can have negation*/
                               'types'=>'', /*type(s) of fields to fetch. Can have negation*/
                               'skip_system'=>'1',
                               'skip_deleted'=>'1',
                               'bound'=>'0', /*use bound page*/
                               'render_display'=>'0',
                              ),
                        $params)
                   );

            // sanitize params
            $masterpage = trim( $masterpage );
            $page = trim( $page_name );
            $names = trim( $names );
            $types = trim( $types );
            $skip_system = ( $skip_system==0 ) ? 0 : 1;
            $skip_deleted = ( $skip_deleted==0 ) ? 0 : 1;
            $bound = ( $bound==1 ) ? 1 : 0;
            $render_display = ( $render_display==1 ) ? 1 : 0;

            if( !$bound ){
                if( !$masterpage ){
                    die( "ERROR: Tag \"".$node->name."\": 'masterpage' attribute missing" );
                }

                if( $FUNCS->is_spl_template( $masterpage ) ){
                    $mode = 'edit';
                    $pg = $FUNCS->handle_spl_template( $masterpage, array('', $page, &$mode) );
                }
                else{
                    $rs = $DB->select( K_TBL_TEMPLATES, array('id', 'clonable'), "name='" . $DB->sanitize( $masterpage ). "'" );
                    if( !count($rs) ) return;

                    $pg = new KWebpage( $rs[0]['id'], 0, $page );
                }
            }
            else{
                $pg = &$CTX->get_object( 'k_bound_page' );
                if( is_null($pg) ) return;
            }

            if( !$pg->error ){
                if( $names ){
                    // Negation?
                    $neg = 0;
                    $pos = strpos( strtoupper($names), 'NOT ' );
                    if( $pos!==false && $pos==0 ){
                        $neg = 1;
                        $names = trim( substr($names, strpos($names, ' ')) );
                    }
                    $arr_names = array_map( "trim", explode( ',', $names ) );
                }

                if( $types ){
                    // Negation?
                    $neg_types = 0;
                    $pos = strpos( strtoupper($types), 'NOT ' );
                    if( $pos!==false && $pos==0 ){
                        $neg_types = 1;
                        $types = trim( substr($types, strpos($types, ' ')) );
                    }
                    $arr_types = array_map( "trim", explode( ',', $types ) );
                }

                $count = count( $pg->fields );
                for( $x=0; $x<$count; $x++ ){
                    $f = &$pg->fields[$x];
                    if( ($skip_system && $f->system) || ($skip_deleted && $f->deleted) ){
                        unset( $f );
                        continue;
                    }

                    if( $arr_types ){
                        if( $neg_types ){
                            if( in_array($f->k_type, $arr_types) ){
                                unset( $f );
                                continue;
                            }
                        }
                        else{
                            if( !in_array($f->k_type, $arr_types) ){
                                unset( $f );
                                continue;
                            }
                        }
                    }

                    if( $arr_names ){
                        if( $neg ){
                            if( in_array($f->name, $arr_names) ){
                                unset( $f );
                                continue;
                            }
                        }
                        else{
                            if( !in_array($f->name, $arr_names) ){
                                unset( $f );
                                continue;
                            }
                        }
                    }

                    $f->resolve_dynamic_params();

                    $CTX->reset();
                    $vars = array();
                    $vars['id'] = $f->id;
                    $vars['template_id'] = $f->template_id;
                    $vars['name'] = $f->name;
                    $vars['label'] = $f->label;
                    $vars['desc'] = $f->k_desc;
                    $vars['type'] = $f->k_type;
                    $vars['hidden'] = $f->hidden;
                    $vars['searchable'] = $f->searchable;
                    $vars['search_type'] = $f->search_type;
                    $vars['order'] = $f->k_order;
                    if( !$pg->tpl_is_clonable || ($pg->tpl_is_clonable && ($page || $bound)) ){
                        $vars['data'] = $f->get_data( 1 );
                    }
                    else{
                        $vars['data'] = $f->default_data;
                    }
                    $vars['default_data'] = $f->default_data;
                    $vars['required'] = $f->required;
                    $vars['deleted'] = $f->deleted;
                    $vars['validator'] = $f->validator;
                    $vars['validator_msg'] = $f->validator_msg;
                    $vars['separator'] = $f->k_separator;
                    $vars['val_separator'] = $f->val_separator;
                    $vars['opt_values'] = $f->opt_values;
                    $vars['opt_selected'] = $f->opt_selected;
                    $vars['toolbar'] = $f->toolbar;
                    $vars['custom_toolbar'] = $f->custom_toolbar;
                    $vars['css'] = $f->css;
                    $vars['custom_styles'] = $f->custom_styles;
                    $vars['maxlength'] = $f->maxlength;
                    $vars['height'] = $f->height;
                    $vars['width'] = $f->width;
                    $vars['group'] = $f->k_group;
                    $vars['assoc_field'] = $f->assoc_field;
                    $vars['crop'] = $f->crop;
                    $vars['enforce_max'] = $f->enforce_max;
                    $vars['quality'] = $f->quality;
                    $vars['show_preview'] = $f->show_preview;
                    $vars['preview_width'] = $f->preview_width;
                    $vars['preview_height'] = $f->preview_height;
                    $vars['no_xss_check'] = $f->no_xss_check;
                    $vars['rtl'] = $f->rtl;
                    $vars['body_id'] = $f->body_id;
                    $vars['body_class'] = $f->body_class;
                    $vars['_html'] = $f->_html;
                    $vars['dynamic'] = $f->dynamic;
                    $vars['system'] = $f->system;
                    $vars['udf'] = $f->udf;
                    $vars['modified'] = (int)$f->modified;
                    $vars['orig_data'] = $f->orig_data;

                    // udf params
                    if( strlen($f->custom_params) ){
                        $arr_params = $FUNCS->unserialize($f->custom_params);
                        if( is_array($arr_params) && count($arr_params) ){
                            foreach( $arr_params as $k=>$v ){
                                $vars[$k] = $v;
                            }
                        }
                    }
                    $vars['k_caption'] = $f->label ? $f->label : $f->name;
                    $CTX->set_all( $vars );

                    if( $render_display ){
                        $display_html = $FUNCS->render( 'display_field_'.$f->k_type, $f );
                        if( is_null($display_html) ) $display_html = $vars['data'];
                        $CTX->set('k_display_html', $display_html);
                    }
                    unset( $f );

                    // call the children
                    foreach( $node->children as $child ){
                        $html .= $child->get_HTML();
                    }
                }
            }
            return $html;
        }


        ////////////////////////////////////////////////////////////////////////
        // Utility functions related to security nonces
        ////////////////////////////////////////////////////////////////////////

        // Given an 'action', returns a nonce for it
        static function create_nonce( $params, $node ){
            global $FUNCS;
            if( count($node->children) ) {die("ERROR: Tag \"".$node->name."\" is a self closing tag");}

            extract( $FUNCS->get_named_vars(
                        array(
                              'action'=>''
                              ),
                        $params)
                   );
            $action = trim( $action );
            if( !strlen($action) ) {die("ERROR: Tag \"".$node->name."\" requires an 'action' parameter");}

            $html = $FUNCS->create_nonce( $action );

            return $html;
        }

        // Given an 'action' and its purported 'nonce', verifies if the nonce tallies with the action.
        // If verification fails the script is summarily terminated.
        // If 'nonce' not provided, looks for a GPC parameter named 'nonce'.
        static function validate_nonce( $params, $node ){
            global $FUNCS;
            if( count($node->children) ) {die("ERROR: Tag \"".$node->name."\" is a self closing tag");}

            extract( $FUNCS->get_named_vars(
                        array(
                              'action'=>'',
                              'nonce'=>''
                              ),
                        $params)
                   );
            $nonce = trim( $nonce );
            $action = trim( $action );
            if( !strlen($action) ) {die("ERROR: Tag \"".$node->name."\" requires an 'action' parameter");}

            $FUNCS->validate_nonce( $action, $nonce );
        }

        // Given an 'action' and its purported 'nonce', verifies if the nonce tallies with the action.
        // Returns '1' if verification suceeds else returns '0'.
        // If 'nonce' not provided, looks for a GPC parameter named 'nonce'.
        static function check_nonce( $params, $node ){
            global $FUNCS;
            if( count($node->children) ) {die("ERROR: Tag \"".$node->name."\" is a self closing tag");}

            extract( $FUNCS->get_named_vars(
                        array(
                              'action'=>'',
                              'nonce'=>''
                              ),
                        $params)
                   );
            $nonce = trim( $nonce );
            $action = trim( $action );
            if( !strlen($action) ) {die("ERROR: Tag \"".$node->name."\" requires an 'action' parameter");}

            $html = ( $FUNCS->check_nonce($action, $nonce) ) ? '1' : '0';

            return $html;
        }

    }// end class

    $FUNCS->register_tag( 'db_persist_form', array('KDataBoundForm', 'db_persist_form') );
    $FUNCS->register_tag( 'db_persist', array('KDataBoundForm', 'db_persist'), 1 );
    $FUNCS->register_tag( 'db_persist_ex', array('KDataBoundForm', 'db_persist_ex') );
    $FUNCS->register_tag( 'db_delete', array('KDataBoundForm', 'db_delete') );
    $FUNCS->register_tag( 'db_delete_form', array('KDataBoundForm', 'db_delete_form') );
    $FUNCS->register_tag( 'db_begin_trans', array('KDataBoundForm', 'db_begin_trans') );
    $FUNCS->register_tag( 'db_commit_trans', array('KDataBoundForm', 'db_commit_trans') );
    $FUNCS->register_tag( 'db_rollback_trans', array('KDataBoundForm', 'db_rollback_trans') );
    $FUNCS->register_tag( 'db_fields', array('KDataBoundForm', 'db_fields'), 1, 1 );
    $FUNCS->register_tag( 'create_nonce', array('KDataBoundForm', 'create_nonce') );
    $FUNCS->register_tag( 'validate_nonce', array('KDataBoundForm', 'validate_nonce') );
    $FUNCS->register_tag( 'check_nonce', array('KDataBoundForm', 'check_nonce') );

    require_once( K_ADDONS_DIR.'data-bound-form/securefile.php' );
    require_once( K_ADDONS_DIR.'data-bound-form/throttle.php' );
    require_once( K_ADDONS_DIR.'data-bound-form/datetime.php' );
    require_once( K_ADDONS_DIR.'data-bound-form/checkspam.php' );
