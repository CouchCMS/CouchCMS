<?php
    if ( !defined('K_COUCH_DIR') ) die(); // cannot be loaded directly


    class KExtendedComments{
        static function add_template_params( &$attr_custom, $params, $node ){
            global $FUNCS;

            $attr = $FUNCS->get_named_vars(
                array(
                    'comment_masterpage'=>'',
                  ),
                $params
            );
            $attr['comment_masterpage'] = trim( $attr['comment_masterpage'] );

            // merge with existing custom params
            $attr_custom = array_merge( $attr_custom, $attr );

        }

        static function &_get_associated_page( &$comment ){
            global $FUNCS, $DB;

            if( isset($comment->_assoc_page) ) return $comment->_assoc_page;

            // is the 'comment_masterpage' parameter set in the template defining the comment?
            $comment_masterpage = KExtendedComments::_get_comment_masterpage( $comment->tpl_name );
            if( !strlen($comment_masterpage) ){
                $comment->_assoc_page = '';
                return;
            }

            // 'comment_masterpage' parameter is set. Pass it through some sanity checks
            $rs = $DB->select( K_TBL_TEMPLATES, array('id', 'clonable', 'custom_params'), "name='" . $DB->sanitize( $comment_masterpage ). "'" );
            if( !count($rs) ){
                die( "ERROR: Tag 'template' - comment_masterpage '".$FUNCS->cleanXSS($comment_masterpage)."' not found" );
            }
            elseif( !$rs[0]['clonable'] ){
                die( "ERROR: Tag 'template' -  comment_masterpage '".$FUNCS->cleanXSS($comment_masterpage)."' is not clonable" );
            }

            // create the associated page object
            $tpl_id = $rs[0]['id'];
            $page_id = -1;
            if( $comment->id ){ // not a new comment

                // does the corresponding page exist?
                $rs = $DB->select( K_TBL_PAGES, array('id'), "page_name='comment-" . $DB->sanitize( $comment->id ). "' AND template_id='" . $DB->sanitize( $tpl_id ). "'" );
                if( count($rs) ){
                    $page_id = $rs[0]['id'];
                }

            }

            $pg = new KWebpage( $tpl_id, $page_id );
            if( $pg->error ){
                die( "ERROR: in attaching custom page to comment - " . $pg->err_msg );
            }

            // associate the page object to the comment
            $comment->_assoc_page = &$pg;

            return $comment->_assoc_page;
        }

        static function _get_comment_masterpage( $tpl_name ){
            global $FUNCS, $DB;

            if( array_key_exists( $tpl_name, $FUNCS->cached_templates ) ){
                $rec = $FUNCS->cached_templates[$tpl_name];
            }
            else{
                $rs = $DB->select( K_TBL_TEMPLATES, array('*'), "name='" . $DB->sanitize( $tpl_name ). "'" );
                if( !count($rs) ){
                    return;
                }
                $FUNCS->cached_templates[$rs[0]['id']] = $rs[0];
                $FUNCS->cached_templates[$rs[0]['name']] = $rs[0];
                $rec = (array)$rs[0];
            }
            $custom_params = $rec['custom_params'];
            if( strlen($custom_params) ){
                $custom_params = $FUNCS->unserialize($custom_params);
            }
            if( !is_array($custom_params) ) $custom_params=array();

            return $custom_params['comment_masterpage'];
        }

        static function add_custom_comment_fields( &$fields, &$comment ){
            global $FUNCS, $DB, $CTX;


            $pg = KExtendedComments::_get_associated_page( $comment );
            if( !$pg ) return;

            $count = count( $pg->fields );
            for( $x=0; $x<$count; $x++ ){
                $f = &$pg->fields[$x];
                $f->resolve_dynamic_params();

                if( !$f->system ){
                    $comment->fields[] = &$pg->fields[$x];
                }

                unset( $f );

            }

        }

        static function update_custom_comment_fields( &$comment, &$errors ){
            global $FUNCS, $DB;

            $pg = KExtendedComments::_get_associated_page( $comment );
            if( !$pg ) return;

            $title = 'comment-'.$comment->id;
            $pg->_fields['k_page_title']->store_posted_changes( $title );
            $pg->_fields['k_page_name']->store_posted_changes( $title );
            $pg->_fields['k_publish_date']->store_posted_changes( $comment->date );

            $errors = $pg->save();
        }

        // Three ways to save data into custom-fields from the front-end form -
        // 1. Create a form-field with the same name as of the page-field. It will automatically get saved.
        //    i.e. behave as if it was databound.
        // 2. Declare the form as databound (by setting masterpage and 'create' mode) and create inputs with type='bound'.
        //    This will save you the hassle of rendering the fields.
        // 3. Specify a fields value directly as cms:process_comment tag's parameter.
        //
        static function insert_custom_comment_fields( $comment_id, $arr_insert, &$approved, $params, $node ){
            global $FUNCS, $DB, $CTX;

            require_once( K_COUCH_DIR.'comment.php' );

            $FUNCS->remove_event_listener( 'alter_comment_fields_info', array('KExtendedComments', 'add_custom_comment_fields') );
            $comment = new KComment( $comment_id );

            $pg = &$CTX->get_object( 'k_bound_page', 'form' ); // is the form data-bound?
            if( is_null($pg) ){
                $pg = KExtendedComments::_get_associated_page( $comment );
                if( !$pg ) return;
            }
            else{
                // a bit of sanity check for databound forms
                $comment_masterpage = KExtendedComments::_get_comment_masterpage( $comment->tpl_name );
                if( $comment_masterpage!=$pg->tpl_name ){
                    die( "ERROR: form masterpage '".$FUNCS->cleanXSS($pg->tpl_name)."' is not the comment_masterpage" );
                }

                if( $pg->id!='-1' ){ //huh? can only happen if 'mode' of form set to 'edit' (instead of the required 'create')
                    die( "ERROR: 'mode' param of form has to be 'create'" );
                }
            }

            $title = 'comment-'.$comment->id;
            $pg->_fields['k_page_title']->store_posted_changes( $title );
            $pg->_fields['k_page_name']->store_posted_changes( $title );
            $pg->_fields['k_publish_date']->store_posted_changes( $comment->date );

            // gather static values provided as parameters of this tag
            $fields = array();
            $arr_known_params = array( 'k_page_name', 'k_publish_date' );
            foreach( $params as $param ){
                $pname = strtolower( trim($param['lhs']) );
                if( in_array($pname, $arr_known_params) ) continue;
                $fields[$pname]=$param['rhs'];
            }

            for( $x=0; $x<count($pg->fields); $x++ ){
                $f = &$pg->fields[$x];

                if( !$f->system && isset($_POST[$f->name]) ){
                    $f->store_posted_changes( $_POST[$f->name] );
                }

                // explicit param will override submitted values
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
                        $f->store_posted_changes( $fields[$f->name] );
                    }
                }

                unset( $f );
            }

            // Save..
            $errors = $pg->save();

            if( $errors ){
                $sep = '';
                $form_separator = $CTX->get('k_cur_form_separator');

                $str_err = '';
                for( $x=0; $x<count($pg->fields); $x++ ){
                    $f = &$pg->fields[$x];
                    if( $f->err_msg ){
                        $str_err .= $sep . '<b>' . (($f->label) ? $f->label : $f->name) . ':</b> ' . $f->err_msg;
                        $sep = $form_separator;
                    }
                    unset( $f );
                }

                return $str_err;
            }

        }

        static function delete_custom_comment_fields( &$comment ){

            $pg = KExtendedComments::_get_associated_page( $comment );
            if( !$pg || $pg->id==-1 ) return;

            $pg->delete();

            // if we are here, delete was successful (script would have died otherwise)
            $pg->destroy();
            unset( $pg );

        }

        static function set_custom_fields_in_context( $rec, $mode ){
            global $FUNCS, $CTX, $DB;

            if( $mode==2 ){ //Comments
                if( $CTX->get('k_paginated_top') ){
                    $comment_masterpage_id = '';
                    $comment_masterpage = KExtendedComments::_get_comment_masterpage( $rec['tpl_name'] );
                    if( strlen($comment_masterpage) ){
                        if( array_key_exists( $comment_masterpage, $FUNCS->cached_templates ) ){
                            $rs = $FUNCS->cached_templates[$comment_masterpage];
                        }
                        else{
                            $rs = $DB->select( K_TBL_TEMPLATES, array('*'), "name='" . $DB->sanitize( $comment_masterpage ). "'" );
                            if( !count($rs) ){
                                return;
                            }
                            $FUNCS->cached_templates[$rs[0]['id']] = $rs[0];
                            $FUNCS->cached_templates[$rs[0]['name']] = $rs[0];
                            $rs = (array)$rs[0];
                        }
                        $comment_masterpage_id = $rs['id'];
                    }
                    $CTX->set( 'k_comment_masterpage', $comment_masterpage );
                    $CTX->set( 'k_comment_masterpage_id', $comment_masterpage_id );
                }
                else{
                    $comment_masterpage = $CTX->get('k_comment_masterpage', 1);
                    $comment_masterpage_id = $CTX->get('k_comment_masterpage_id', 1);
                }

                if( strlen($comment_masterpage) ){
                    $vars = array();

                    // get the associated page
                    $tpl_id = $comment_masterpage_id;
                    $page_id = -1;
                    $page_name = "comment-" . $DB->sanitize( $rec['id'] );

                    $rs = $DB->select( K_TBL_PAGES, array('id'), "page_name='".$page_name."' AND template_id='" . $DB->sanitize( $tpl_id ). "'" );
                    if( count($rs) ){
                        $page_id = $rs[0]['id'];
                        $vars['k_comment_extended_page_id'] = $page_id;
                        $vars['k_comment_extended_page_name'] = $page_name;
                    }
                    else{
                        $vars['k_comment_extended_page_id'] = '';
                        $vars['k_comment_extended_page_name'] = '';
                    }

                    $pg = new KWebpage( $tpl_id, $page_id );
                    if( !$pg->error ){
                        // set all custom fields in context
                        foreach( $pg->fields as $f ){
                            if( $f->system || $f->deleted ) continue;
                            $vars[$f->name] = $f->get_data( 1 );
                        }

                        $CTX->set_all( $vars );
                    }
                }
            }
        }

    }// end class

    $FUNCS->add_event_listener( 'add_template_params', array('KExtendedComments', 'add_template_params') );
    $FUNCS->add_event_listener( 'alter_comment_fields_info', array('KExtendedComments', 'add_custom_comment_fields') );
    $FUNCS->add_event_listener( 'comment_updated', array('KExtendedComments', 'update_custom_comment_fields') );
    $FUNCS->add_event_listener( 'comment_inserted', array('KExtendedComments', 'insert_custom_comment_fields') );
    $FUNCS->add_event_listener( 'comment_deleted', array('KExtendedComments', 'delete_custom_comment_fields') );
    $FUNCS->add_event_listener( 'alter_page_tag_context', array('KExtendedComments', 'set_custom_fields_in_context') );
