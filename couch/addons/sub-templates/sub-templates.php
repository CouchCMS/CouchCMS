<?php
    if ( !defined('K_COUCH_DIR') ) die(); // cannot be loaded directly

    class KSubTemplates{
        const subtpl_selector = '_sub_template'; // name of the relational selector field.

        static function add_template_params( &$attr_custom, $params, $node ){
            global $CTX, $FUNCS, $PAGE, $DB;

            $attr = $FUNCS->get_named_vars(
                array(
                    'has_subtemplates'=>'0',
                    'subtpl_selector_label'=>'',
                    'subtpl_selector_is_advanced'=>'0',
                  ),
                $params
            );
            extract( $attr );

            $attr['has_subtemplates'] = ( $has_subtemplates==1 ) ? 1 : 0;
            $subtpl_selector_label = trim( $subtpl_selector_label );
            $attr['subtpl_selector_label'] = $subtpl_selector_label;
            $attr['subtpl_selector_is_advanced'] = ( $subtpl_selector_is_advanced==1 ) ? 1 : 0;

            // merge with existing custom params
            $attr_custom = array_merge( $attr_custom, $attr );

            // if template has sub-templates ..
            if( $has_subtemplates ){

                // get the aux template (containing sub-template names as pages) ..
                $aux_tpl_name = self::_get_aux_tpl_name( $PAGE->tpl_name );
                $rs = $DB->select( K_TBL_TEMPLATES, array('*'), "name='" . $DB->sanitize( $aux_tpl_name ). "'" );
                if( !count($rs) ){
                    $rs = $DB->insert( K_TBL_TEMPLATES, array('name'=>$aux_tpl_name, 'description'=>'', 'clonable'=>1, 'executable'=>0, 'hidden'=>2) );
                    $rs = $DB->select( K_TBL_TEMPLATES, array('*'), "name='" . $DB->sanitize( $aux_tpl_name ). "'" );
                    if( !count($rs) ) die( "ERROR: Tag \"".$node->name."\" (".$name.") cannot create record in K_TBL_TEMPLATES for aux subtemplate template" );
                }
                $id_aux_tpl = $rs[0]['id'];
                $custom_params = $rs[0]['custom_params'];
                if( strlen($custom_params) ){
                    $custom_params = $FUNCS->unserialize($custom_params);
                }
                if( !is_array($custom_params) ) $custom_params=array();

                // get all pages of aux (these represent sub-templates)
                $arr_subtemplates = array();
                $rs = $DB->select( K_TBL_PAGES, array('id', 'page_name', 'page_title', 'k_order', 'file_meta'), "template_id='" . $DB->sanitize( $id_aux_tpl ). "'" );
                if( count($rs) ){
                    foreach( $rs as $rec ){
                        $arr_subtemplates[$rec['page_name']] =  array( 'id'=>$rec['id'], 'label'=>$rec['page_title'], 'order'=>$rec['k_order'], 'file_meta'=>$rec['file_meta'], 'processed'=>0 );
                    }
                }

                // check for special subtemplates..
                foreach( array('@common'/*, '@deleted'*/) as $sp_tpl ){
                    $var_name = 'id_'.substr( $sp_tpl, 1 );

                    if( !array_key_exists($sp_tpl, $arr_subtemplates) ){
                        $res = self::_create_sub_template( $id_aux_tpl, $sp_tpl, $sp_tpl, 1 );
                        if( $FUNCS->is_error($res) ) die( "ERROR: Tag \"".$node->name."\" (".$name.") error creating sub-template: ".$res->err_msg );
                        $arr_subtemplates[$sp_tpl] = array( 'id'=>$res, 'label'=>$sp_tpl, 'order'=>0, 'processed'=>1 );
                        $$var_name = $res;
                    }
                    else{
                        $arr_subtemplates[$sp_tpl]['processed'] = 1;
                        $$var_name = $arr_subtemplates[$sp_tpl]['id'];
                    }
                }

                // the selector field..
                $html = "
                <cms:editable name='".self::subtpl_selector."' label='".$subtpl_selector_label."'
                    type='relation'
                    masterpage='".$aux_tpl_name."'
                    has='one'
                    advanced_gui='".$subtpl_selector_is_advanced."'
                    order='-10000'
                    orderby='weight'
                    order_dir='asc'
                    required='1'
                    validator='ksubtemplates::validate_new'
                />
                ";
                $parser = new KParser( $html, $node->line_num, 0, '', $node->ID );
                $dom = $parser->get_DOM();
                $node->children = array_merge( $dom->children, $node->children ); // prepend code

                // prepare data to be eventually persisted into custom_params of aux template
                $data = array(
                    'orig' => $custom_params,
                    'curr' => array(
                        'id_aux_tpl' => $id_aux_tpl,
                        'id_common' => $id_common,
                        'id_selector' => null, /* will be filled on tag end */
                    )
                );
                $node->_subtpl_data = $data;

                // set data for child <cms:sub_template> tags
                $data2 = array(
                    'id_aux_tpl'=>$id_aux_tpl,
                    'id_common' => $id_common,
                    'arr_subtemplates'=>$arr_subtemplates
                );
                $CTX->set_object( '__subtpl_data', $data2, 'global' );

            } // end has_subtemplates
        }

        static function template_tag_end( $params, $node ){
            global $FUNCS, $PAGE, $CTX, $DB;

            $data = $node->_subtpl_data;
            if( !is_array($data) ){ return; }

            $f = $PAGE->_fields[self::subtpl_selector];
            if( !$f ){ return; }

            $data['curr']['id_selector'] =  $f->id;
            if( $data['orig'] != $data['curr'] ){
                $custom_params = $FUNCS->serialize($data['curr']);
                $rs = $DB->update( K_TBL_TEMPLATES, array('custom_params'=>$custom_params), "id='" . $DB->sanitize( $data['curr']['id_aux_tpl'] ). "'" );
                if( $rs==-1 ) die( "ERROR: Tag: '.$node->name.' Unable to save modified aux template custom params" );
            }

            // check for unprocessed sub-templates..
            $data2 = &$CTX->get_object( '__subtpl_data', '__ROOT__' );
            if( is_array($data2) ){
                foreach( $data2['arr_subtemplates'] as $subtpl ){
                    if( !$subtpl['processed'] ){
                        // before deleting, make sure the sub-template has no child fields
                        $rs = $DB->select( K_TBL_SUB_TEMPLATES, array('*'), "template_id = '".$PAGE->tpl_id."' AND sub_template_id = '".$subtpl['id']."'" );
                        if( count($rs) ){
                            $names = self::_get_names( $subtpl['id'] );
                            die( "ERROR: Tag 'sub_template': cannot delete stub '".$names['subtpl_name']."' as it still contains fields.<br>Delete all fields within it before deleting the sub-template." );
                        }

                        // remove page
                        $rs = $DB->delete( K_TBL_PAGES, "id='" . $DB->sanitize( $subtpl['id'] ). "'" );
                        if( $rs==-1 ) die( "ERROR: Tag: '.$node->name.' Unable to delete page from K_TBL_PAGES" );

                        // remove data
                        $rs = $DB->delete( K_TBL_SUB_TEMPLATES, "template_id = '".$PAGE->tpl_id."' AND sub_template_id = '".$subtpl['id']."'" );
                        if( $rs==-1 ) die( "ERROR: Tag: '.$node->name.' Unable to delete record from K_TBL_SUB_TEMPLATES" );

                        // remove relations
                        $rs = $DB->delete( K_TBL_RELATIONS, "cid='" . $DB->sanitize( $subtpl['id'] ). "'" );
                        if( $rs==-1 ) die( "ERROR: Tag: '.$node->name.' Unable to delete records from K_TBL_RELATIONS" );
                    }
                }
            }
        }

        static function sub_template_tag_handler( $params, $node ){
            global $CTX, $FUNCS, $PAGE, $AUTH, $DB;
            if( $AUTH->user->access_level < K_ACCESS_LEVEL_SUPER_ADMIN || defined('K_ADMIN') ){ return; } // nop within admin panel

            $data = &$CTX->get_object( '__subtpl_data', '__ROOT__' );
            if( !is_array($data) ){ return; }

            $attr = $FUNCS->get_named_vars(
                array(
                    'name'=>'',
                    'label'=>'',
                    'order'=>'0',
                ),
                $params
            );
            extract( $attr );

            $arr_update = array();
            $name = trim( $name );
            if( !$name ) {die("ERROR: Tag \"".$node->name."\" needs a 'name' attribute");}
            if( $name!=='@common' && !$FUNCS->is_title_clean($name) ){
                die( "ERROR: Tag \"".$node->name."\": 'name' attribute ({$name}) contains invalid characters. (Only lowercase[a-z], numerals[0-9], hyphen and underscore permitted.)" );
            }
            $label = trim( $label );
            if( !strlen($label) ){ $label = $name; }
            $order = $FUNCS->is_int( $order ) ? intval( $order ) : 0;

            // find self..
            if( !array_key_exists($name, $data['arr_subtemplates']) ){
                $res = self::_create_sub_template( $data['id_aux_tpl'], $name, $label, 0, $order );
                if( $FUNCS->is_error($res) ) die( "ERROR: Tag \"".$node->name."\" (".$name.") error creating sub-template page: ".$res->err_msg );
                $data['arr_subtemplates'][$name] = array( 'id'=>$res, 'label'=>$label, 'order'=>$order, 'file_meta'=>'', 'processed'=>1 );
            }
            else{
                // check if params modified
                if( $name!=='@common' ){
                    $self = $data['arr_subtemplates'][$name];
                    if( $self['label']!=$label ){ $arr_update['page_title'] = $label; }
                    if( $self['order']!=$order ){ $arr_update['k_order'] = $order; }

                    // flag as processed
                    $data['arr_subtemplates'][$name]['processed'] = 1;
                }
            }
            $subtpl_id = $data['arr_subtemplates'][$name]['id'];
            $encoded_data = $data['arr_subtemplates'][$name]['file_meta'];
            $id_common = $data['id_common'];
            $tpl_id = $PAGE->tpl_id;
            $id_selector = null;
            $id_selector = ( $PAGE->_fields[self::subtpl_selector] ) ? $PAGE->_fields[self::subtpl_selector]->id : null;

            // fetch ids of fields within this sub-template
            $items_selected =  self::_get_child_fields( $tpl_id, $subtpl_id, 1 /*as_keys*/ );

            // setup listeners for getting ids of fields being processed in child nodes
            {
                $items_posted = array();
                $listener_existing_fields = function(&$modified, &$prev_value, &$prev_udf_values, $f) use(&$items_posted, $tpl_id){
                    if( $f->template_id == $tpl_id ){
                        $items_posted[$f->id] = null;
                    }
                };
                $FUNCS->add_event_listener( 'alter_editable_modifications', $listener_existing_fields); // existing fields

                $listener_new_fields = function(&$f) use(&$items_posted, $tpl_id){
                    if( $f->template_id == $tpl_id ){
                        $items_posted[$f->id] = null;
                    }
                };
                $FUNCS->add_event_listener( 'field_inserted', $listener_new_fields ); // newly created

                $listener_stub_fields = function($f, $order, $filter) use(&$items_posted, $tpl_id, $name){
                    if( $f->template_id == $tpl_id ){
                        if( array_key_exists($f->id, $items_posted) ){
                            die( "ERROR: Tag 'sub_template': Dupicate stub! '".$f->name."' already exists in sub-template '".$name."'" );
                        }
                        $items_posted[$f->id] = array( 'order'=>$order, 'filter'=>$filter );
                    }
                };
                $FUNCS->add_event_listener( 'stub_field_found', $listener_stub_fields ); // stub

                $listener_get_pages_to_add = function(&$rs, &$f, $to_table, $prev_failed) use($subtpl_id, $tpl_id, $id_selector, $name){
                    if( ($f->template_id==$tpl_id) && $id_selector ){
                        if( $name!='@common' ){

                            // return back only pages that belong to this sub-template (if not @common)
                            $rs = self::_get_pages( $tpl_id, $subtpl_id, $f->id, $id_selector, $to_table, $prev_failed, 0 );
                        }
                        return 1; // stop propogation
                    }
                };
                $FUNCS->add_event_listener( 'get_pages_to_add_field_db', $listener_get_pages_to_add );
            }

            // setup listeners to handle child cms:config_form_view and cms:config_list_view tags
            {
                // replace cms:config_list_view tag
                $listener_config_list_view = function($tag_name, &$params, &$node, &$html){
                    die( "ERROR: Tag cms:config_list_view cannot be used within cms:sub_template" ); // for now..
                };
                $FUNCS->add_event_listener( 'alter_tag_config_list_view_execute', $listener_config_list_view );

                // replace cms:config_form_view tag
                $listener_config_form_view = function($tag_name, &$params, &$node, &$html) use($subtpl_id, $encoded_data, &$arr_update){
                    global $FUNCS, $DB, $CTX;

                    $data = @unserialize( base64_decode($encoded_data) );
                    if( !is_array($data) ){
                        $data = array( 'config_list'=>'', 'config_form'=>'' );
                    }

                    // setup objects to be filled by child cms:field, cms:script, cms:style, cms:html, cms:persist tags
                    $arr_config = array( 'arr_fields'=>array(), 'js'=>'', 'css'=>'', 'html'=>'', 'params'=>'' );
                    $CTX->set_object( '__config', $arr_config );

                    // invoke child tags
                    foreach( $node->children as $child ){
                        $child->get_HTML();
                    }

                    // if array modified, save it
                    $data['config_form'] = $arr_config;
                    $mod_encoded_data = base64_encode( serialize($data) );
                    if( $encoded_data !== $mod_encoded_data ){
                        $arr_update['file_meta'] = $mod_encoded_data;
                    }

                    return 1; // skip original tag code
                };
                $FUNCS->add_event_listener( 'alter_tag_config_form_view_execute', $listener_config_form_view );
            }

            foreach( $node->children as $child ){
                $child->get_HTML();
            }

            $FUNCS->remove_event_listener( 'alter_editable_modifications', $listener_existing_fields );
            $FUNCS->remove_event_listener( 'field_inserted', $listener_new_fields );
            $FUNCS->remove_event_listener( 'stub_field_found', $listener_stub_fields );
            $FUNCS->remove_event_listener( 'alter_tag_config_form_view_execute', $listener_config_form_view );
            $FUNCS->remove_event_listener( 'alter_tag_config_list_view_execute', $listener_config_list_view );

            // check if data modified..
            if( count($arr_update) ){
                $rs = $DB->update( K_TBL_PAGES, $arr_update, "id='" . $DB->sanitize( $subtpl_id ). "'" );
                if( $rs==-1 ) die( "ERROR: sub-templates - Unable to save data in K_TBL_PAGES" );
            }

            $items_common = array_intersect_key( $items_selected, $items_posted );
            foreach( $items_common as $field_id=>$old_val ){
                $new_val = $items_posted[$field_id];
                if( $old_val!=$new_val || (is_null($old_val['filter']) && !is_null($new_val['filter'])) || (!is_null($old_val['filter']) && is_null($new_val['filter'])) ){
                    $sql = "UPDATE ". K_TBL_SUB_TEMPLATES."\r\n";

                    if( is_null($new_val) ){
                        $sql .= "SET is_stub = NULL, filter_type = NULL \r\n";
                    }
                    else{
                        $sql .= "SET is_stub = '".$DB->sanitize($new_val['order'])."', filter_type = ";
                        $sql .= ( is_null($new_val['filter']) ) ? "NULL" : "'".$DB->sanitize($new_val['filter'])."'";
                        $sql .= "\r\n";
                    }

                    $sql .= "WHERE template_id = '".$tpl_id."' AND sub_template_id = '".$subtpl_id."' AND field_id='".$DB->sanitize($field_id)."'";
                    $DB->_query( $sql );
                    $rs = $DB->rows_affected = mysql_affected_rows( $DB->conn );
                    if( $rs==-1 ) die( "ERROR: Unable to save modified value of is_stub" );
                }
            }

            $items_deleted = array_diff_key( $items_selected, $items_posted );
            $items_inserted = array_diff_key( $items_posted, $items_selected );
            if( count($items_deleted) || count($items_inserted) ){
                $is_common = ( $name=='@common' ) ? 1 : 0;
                self::_update_child_fields( $tpl_id, $subtpl_id, $name, $items_deleted, $items_inserted, $id_selector, $id_common, $is_common );
            }
            $FUNCS->remove_event_listener( 'get_pages_to_add_field_db', $listener_get_pages_to_add );
        }

        static function stub_tag_handler( $params, $node ){
            global $CTX, $FUNCS, $PAGE,  $AUTH;
            if( $AUTH->user->access_level < K_ACCESS_LEVEL_SUPER_ADMIN || defined('K_ADMIN') ){ return; } // nop within admin panel

            $attr = $FUNCS->get_named_vars(
                array(
                    'name'=>'',
                    'order'=>'',
                    'filter'=>null,
                ),
                $params
            );
            extract( $attr );

            $name = trim( $name );
            if( !$name ) {die("ERROR: Tag \"".$node->name."\" needs a 'name' attribute");}
            $order = $FUNCS->is_int( $order ) ? intval( $order ) : '';
            if( !is_null($filter) ){
                if( !is_numeric($filter) ){
                    $filter = strtolower( trim($filter) );
                    $known_types = array( 'dropdown'=>0, 'checkbox'=>1, 'slider'=>2 );
                    $filter = array_key_exists( $filter, $known_types ) ? $known_types[$filter] : null;
                }
                $filter = is_numeric( $filter ) ? floatval( $filter ) : null;
            }

            // get the field indicated by name ..
            if( isset($PAGE->_fields[$name]) ){
                $field = $PAGE->_fields[$name];
                if( $order=='' ){ $order = $field->k_order; }

                // HOOK: stub_field_found
                $FUNCS->dispatch_event( 'stub_field_found', array($field, $order, $filter) );
            }
        }

        static function get_custom_fields_info_db( &$rs2, &$pg ){
            global $FUNCS, $DB, $AUTH;
            if( !$pg->tpl_has_subtemplates || ($pg->accessed_via_browser && $AUTH->user->access_level >= K_ACCESS_LEVEL_SUPER_ADMIN) ){ return; /* full set */}

            $aux_tpl_name = KSubTemplates::_get_aux_tpl_name( $pg->tpl_name );
            $rs = $DB->select( K_TBL_TEMPLATES, array('id','custom_params'), "name='" . $DB->sanitize( $aux_tpl_name ). "'" );
            if( !count($rs) ){ return ;}

            $aux_tpl_id = $rs[0]['id'];
            $custom_params = $rs[0]['custom_params'];
            if( strlen($custom_params) ){
                $custom_params = $FUNCS->unserialize($custom_params);
            }
            if( !is_array($custom_params) ){ return; }

            $tpl_id = $pg->tpl_id;
            $sub_template_id = null;
            $is_nested = null;
            if( $pg->accessed_via_browser && $pg->tpl_nested_pages ){
                if( is_null($pg->id) && is_null($pg->page_name) && isset($_GET['fname']) && $FUNCS->is_title_clean($_GET['fname']) ){
                    $rs = $DB->select( K_TBL_PAGES, array('id'), "page_name='" . $DB->sanitize( trim($_GET['fname']) ). "' AND template_id='" . $DB->sanitize( $pg->tpl_id ). "'" );
                    if( count($rs) ){
                        $pg->id = $rs[0]['id'];
                        $is_nested = 1;
                    }
                }
            }
            if( !$pg->id && !is_null($pg->page_name) ){
                $rs = $DB->select( K_TBL_PAGES, array('id'), "page_name='" . $DB->sanitize( $pg->page_name ). "' AND template_id='" . $DB->sanitize( $pg->tpl_id ). "'" );
                if( count($rs) ){
                    $pg->id = $rs[0]['id'];
                }
            }

            if( $pg->id ){
                if( $pg->id==-1 ){
                    // try fo figure out the sub-template..
                    $tmp_id = null;
                    $set_field = 0;

                    // HOOK: get_sub_template_of_new_page
                    $FUNCS->dispatch_event( 'get_sub_template_of_new_page', array(&$tmp_id) );

                    if( !$tmp_id ){
                        if( $_POST ){
                            $input = ( $pg->tpl_subtpl_selector_is_advanced ) ? 'f_'.self::subtpl_selector : 'f_'.self::subtpl_selector.'_chk';
                            if( isset($_POST[$input]) ){
                                $tmp_id = $_POST[$input];
                            }
                        }
                        else{
                            if( defined('K_ADMIN') && isset($_GET[self::subtpl_selector]) ){
                                $tmp_id = $_GET[self::subtpl_selector];
                                $set_field = 1;
                            }
                        }
                    }

                    if( $tmp_id && $FUNCS->is_non_zero_natural($tmp_id) ){
                        $tmp_id = (int)$tmp_id;
                        $rs = $DB->select( K_TBL_PAGES, array('id'), "id='".$DB->sanitize($tmp_id)."' AND template_id='".$DB->sanitize($aux_tpl_id)."'" );
                        if( count($rs) ){
                            $sub_template_id = $rs[0]['id'];

                            if( $set_field ){ // set id into the selector field too (a later event handler will do this)
                                $pg->__tmp_sub_template_id = $sub_template_id;
                            }
                        }
                    }
                }
                else{
                    // get selected sub_template..
                    $rs = $DB->select( K_TBL_RELATIONS, array('cid'), "pid='".$DB->sanitize( $pg->id )."' AND fid='".$DB->sanitize( $custom_params['id_selector'] )."'" );
                    if( count($rs) ){
                        $sub_template_id = $rs[0]['cid'];
                    }
                }
                if( $is_nested ){
                    $pg->id = null;
                }
            }

            // get the fields..
            $arr_ids = array( $custom_params['id_selector'] );
            $arr_ids_common = $arr_ids_sub = array();
            if( $sub_template_id ){
                $arr_ids_common =  self::_get_child_fields( $tpl_id, $custom_params['id_common'] );
                $arr_ids_sub =  self::_get_child_fields( $tpl_id, $sub_template_id );
            }
            $arr_ids = array_merge( $arr_ids, $arr_ids_common, $arr_ids_sub );
            $str_ids = implode( ",", $arr_ids );

            // return the limited set..
            $rs2 = $DB->select( K_TBL_FIELDS, array('*'), "template_id='" . $DB->sanitize( $pg->tpl_id ). "' AND id IN (".$str_ids.") ORDER BY k_group, k_order, id" );
        }

        static function get_cached_fields( &$cached_fields, &$pg ){
            global $FUNCS, $DB;
            if( !$pg->tpl_has_subtemplates ){ return; }

            if( $pg->id && is_array($FUNCS->cached_subtpl_fields) ){
                $rs = $DB->select( K_TBL_FIELDS.' f INNER JOIN '.K_TBL_TEMPLATES.' t ON f.template_id = t.id INNER JOIN '.K_TBL_RELATIONS.' r ON r.fid=f.id', array('r.cid'), "t.id='".$DB->sanitize( $pg->tpl_id )."' AND f.name='".$DB->sanitize( self::subtpl_selector )."' AND r.pid='".$DB->sanitize( $pg->id )."'" );
                if( count($rs) ){
                    $sub_tpl_id = $rs[0]['cid'];

                    if( array_key_exists( $sub_tpl_id, $FUNCS->cached_subtpl_fields ) ){
                        $cached_fields = $FUNCS->cached_subtpl_fields[$sub_tpl_id];
                    }
                }
            }
        }

        static function _get_aux_tpl_name( $tpl ){
            return  $tpl . '__subtpl';
        }

        static function _create_sub_template( $tpl_id, $name, $title, $unpublished=0, $order=0, $value='' ){
            global $FUNCS, $DB;

            // create page representing the sub-template
            $arr_insert = array( 'template_id'=>$tpl_id, 'page_title'=>$title, 'page_name'=>$name, 'k_order'=>$order, 'publish_date'=>(!$unpublished)? $FUNCS->get_current_desktop_time():'0000-00-00 00:00:00' );
            if( $name=='@common'){ $arr_insert['is_master']=1; }
            $rs = $DB->insert( K_TBL_PAGES, $arr_insert );
            if( $rs!=1 ) return $FUNCS->raise_error( "Failed to insert record in K_TBL_PAGES" );
            $page_id = $DB->last_insert_id;

            return $page_id;
        }

        static function _get_sub_template_data( $pid, $decode=0 ){
            global $FUNCS, $DB;

            $sql = "id='".$DB->sanitize( $pid )."'";
            $rs = $DB->select( K_TBL_PAGES, array('file_meta'), $sql );
            if( count($rs) ){
                $data = $rs[0]['file_meta'];
                if( $decode ){
                    $data = @unserialize( base64_decode($data) );
                }
            }

            return $data;
        }

        static function _get_child_fields( $tpl_id, $subtpl_id, $as_keys=0 ){
            global $DB;

            $sql = "SELECT field_id, is_stub, filter_type FROM ".K_TBL_SUB_TEMPLATES." WHERE template_id = '".$DB->sanitize($tpl_id)."' AND sub_template_id = '".$DB->sanitize($subtpl_id)."'";
            $result = @mysql_query( $sql, $DB->conn );
            if( !$result ){
                ob_end_clean();
                die( "Could not successfully run query: " . mysql_error( $DB->conn ) );
            }

            $items_selected = array();
            if( $as_keys ){
                while( $row=mysql_fetch_row($result) ){
                    $items_selected[$row[0]] = is_null($row[1]) ? null : array( 'order'=>$row[1], 'filter'=>$row[2] );
                }
            }
            else{
                while( $row=mysql_fetch_row($result) ){
                    $items_selected[] = $row[0];
                }
            }

            return $items_selected;
        }

        static function _update_child_fields( $tpl_id, $subtpl_id, $subtpl_name, $items_deleted, $items_inserted, $id_selector, $id_common, $is_common ){
            global $DB, $PAGE;

            $DB->begin();

            // delete the removed items ($stub would be null for normal fields)
            if( count($items_deleted) ){
                foreach( $items_deleted as $field_id=>$stub ){
                    if( !is_null($stub) ){
                        // delete data fields in existing pages of this sub-template..
                        self::_process_stub_fields( $tpl_id, $subtpl_id, $field_id, $id_selector, $is_common, 1 );

                        $rs = $DB->delete( K_TBL_SUB_TEMPLATES, "template_id = '".$tpl_id."' AND sub_template_id = '".$subtpl_id."' AND field_id='".$DB->sanitize( $field_id )."'" );
                        if( $rs==-1 ) die( "ERROR: Unable to delete record from K_TBL_SUB_TEMPLATES" );
                    }
                    else{
                        // for normal fields, wait for core's 'field_deleted' event to delete record from K_TBL_SUB_TEMPLATES
                    }
                }
            }

            // insert new items ($stub would be null for normal fields)
            if( count($items_inserted) ){
                foreach( $items_inserted as $field_id=>$stub ){
                    if( !is_null($stub) && !$is_common ){
                        // check that the original field or another stub of the same field doesn't exist in the same sub-template and also not in @common..
                        $arr_subtpls = array( $id_common, $subtpl_id );
                        foreach( $arr_subtpls as $id_subtpl ){
                            $rs = $DB->select( K_TBL_SUB_TEMPLATES, array('*'), "template_id = '".$tpl_id."' AND sub_template_id = '".$id_subtpl."' AND field_id='".$DB->sanitize( $field_id )."'" );
                            if( count($rs) ){
                                $names = self::_get_names( $id_subtpl, $field_id );
                                die( "ERROR: Tag 'sub_template':<br>Cannot add stub '".$names['field_name']."' to '".$subtpl_name."' as it already exists in '".$names['subtpl_name']."' sub-template." );
                            }
                        }
                    }
                    else{
                        // check that field (or stub in @common) does not already exist in any other sub-template..
                        $rs = $DB->select( K_TBL_SUB_TEMPLATES, array('*'), "field_id='" . $DB->sanitize( $field_id ). "'" );
                        if( count($rs) ){
                            $names = self::_get_names( $rs[0]['sub_template_id'], $rs[0]['field_id'] );
                            die( "ERROR: Tag 'sub_template':<br>Cannot add '".$names['field_name']."' to '".$subtpl_name."' as it already exists in '".$names['subtpl_name']."' sub-template.<br>Please delete the existing field first before adding it to another sub-template." );
                        }
                    }

                    if( !is_null($stub) ){
                        // create data fields in existing pages of this sub-template..
                        self::_process_stub_fields( $tpl_id, $subtpl_id, $field_id, $id_selector, $is_common );
                    }

                    // insert record
                    $vals = array(
                        'template_id'=>$tpl_id,
                        'sub_template_id'=>$subtpl_id,
                        'field_id'=>$field_id,
                    );
                    if( !is_null($stub) ){
                        $vals['is_stub']=$stub['order'];
                        if( !is_null($stub['filter']) ){ $vals['filter_type']=$stub['filter']; }
                    }
                    $rs = $DB->insert( K_TBL_SUB_TEMPLATES, $vals );
                    if( $rs!=1 ) die( "ERROR: Failed to insert record in K_TBL_SUB_TEMPLATES" );
                }
            }

            $DB->commit();
        }

        static function _get_names( $sub_template_id, $field_id=null  ){
            global $DB;

            $names = array();

            if( $field_id ){
                $rs = $DB->select( K_TBL_FIELDS, array('name'), "id='".$DB->sanitize( $field_id )."'" );
                $names['field_name'] = ( count($rs) ) ? $rs[0]['name'] : '';
            }

            if( $sub_template_id ){
                $rs = $DB->select( K_TBL_PAGES, array('page_name'), "id='".$DB->sanitize( $sub_template_id )."'" );
                $names['subtpl_name'] = $subtpl_name = ( count($rs) ) ? $rs[0]['page_name'] : '';
            }

            return $names;
        }

        static function _process_stub_fields( $tpl_id, $subtpl_id, $field_id, $id_selector, $is_common, $delete=0 ){
            global $FUNCS, $DB, $AUTH, $CTX, $PAGE;

            // check for failure in the past run of this routine
            $lock_file = K_COUCH_DIR . 'cache/$$stub'.$delete.'-'.$tpl_id.'-'.$subtpl_id.'-'.$field_id;
            $prev_failed = file_exists( $lock_file ); // presence of this file indicates previous failure
            if( !$prev_failed ){
                // leave a tell-tale sign in case this routine fails mid-way processing the code to follow
                $fp = @fopen( $lock_file, 'x+b' );
            }

            // get the real field
            $rs = $DB->select( K_TBL_FIELDS, array('name'), "id='".$DB->sanitize($field_id)."' LIMIT 1" );
            if( count($rs) && isset($PAGE->_fields[$rs[0]['name']]) ){
                $f = $PAGE->_fields[$rs[0]['name']];
                $is_udf = ( array_key_exists($f->k_type, $FUNCS->udfs) ) ? 1 : 0;

                // ** Following portion of the code can cause the script to timeout if there are too many existing pages to add the data fields to **

                // Create this field's storage for each existing page of the sub-template..
                $to_table = ( $f->search_type=='text' ) ? K_TBL_DATA_TEXT : K_TBL_DATA_NUMERIC;

                @set_time_limit( 0 ); // make server wait
                $start_time = time();

                // query for pages to process..
                $rs = self::_get_pages( $PAGE->tpl_id, $subtpl_id, $field_id, $id_selector, $to_table, $prev_failed, $delete, $is_common );

                if( count($rs) ){
                    foreach( $rs as $rec ){
                        if( $delete ){
                            if( $f->udf ){
                                $f->_delete( $rec['id'] );
                            }

                            $rs = $DB->delete( $to_table, "page_id='".$DB->sanitize($rec['id'])."' AND field_id='".$DB->sanitize($field_id)."'" );
                            if( $rs==-1 ) die( "ERROR: Unable to delete record stub from " . $to_table );
                        }
                        else{
                            $arr_to_fields = array('page_id'=>$rec['id'],
                                        'field_id'=>$field_id,
                                        'value'=>''
                                        );
                            if( $f->search_type=='text' ){
                                $arr_to_fields['search_value'] = '';
                            }

                            $rs2 = $DB->insert( $to_table, $arr_to_fields );
                            if( $rs2==-1 ) die( "ERROR: Failed to insert record for stub in" . $to_table );

                            if( $is_udf ){
                                // Call udf to do something for 'create' event
                                $f->_create( $rec['id'] );
                            }
                        }

                        $cur_time = time();
                        if( $cur_time + 25 > $start_time ){
                            header( "X-Dummy: wait" ); // make browser wait
                            $start_time = $cur_time;
                        }
                    }

                    if( $prev_failed ){
                        // end script and refresh current page to process next batch of records in a staggered manner
                        ob_end_clean();
                        $DB->commit( 1 );

                        $cnt = ( isset($_GET['__cnt__']) && $FUNCS->is_non_zero_natural($_GET['__cnt__']) ) ? (int)$_GET['__cnt__'] : 0;
                        $dst = $CTX->get( 'k_page_link' );
                        $sep = ( strpos($dst, '?')===false ) ? '?' : '&';
                        $dst = $dst . $sep . '__cnt__=' . ++$cnt;
                        $html="
                            <script language=\"JavaScript\" type=\"text/javascript\">
                                window.setTimeout( 'location.href=\"".$dst."\";', 100 );
                            </script>
                            Modifying schema. Could take some time. Please wait...(processed: ".$cnt*$stagger_limit." pages)
                        ";
                        die( $html );
                    }
                }
            }

            if( $prev_failed ){
                @unlink( $lock_file );
            }
            else{
                if( $fp ){
                    fclose( $fp );
                    @unlink( $lock_file );
                }
            }
        }

        static function _get_pages( $tpl_id, $subtpl_id, $field_id, $id_selector, $to_table, $prev_failed, $delete, $is_common=0 ){
            global $DB;

            $stagger_limit = 500; // number of pages processed in a single run. Might require tweaking if script still times out
            if( $is_common ){
                if( $prev_failed ){
                    $rs = $DB->select( K_TBL_PAGES . " p LEFT OUTER JOIN " . $to_table . " d ON (p.id = d.page_id AND d.field_id='". $DB->sanitize( $field_id ) ."')", array('p.*'), "p.template_id = '". $DB->sanitize($tpl_id). "' AND p.is_master<>'1' AND d.page_id IS NULL LIMIT 0, ".$stagger_limit );
                }
                else{
                    $rs = $DB->select( K_TBL_PAGES, array('*'), "template_id='" . $DB->sanitize($tpl_id). "' AND is_master<>'1'" );
                }
            }
            else{
                if( $prev_failed ){
                    if( $delete ){
                        $rs = $DB->select( K_TBL_PAGES." p INNER JOIN ".K_TBL_RELATIONS." r ON p.id = r.pid INNER JOIN ".$to_table." d ON (p.id = d.page_id AND d.field_id='".$DB->sanitize($field_id)."')", array('p.*'), "p.template_id = '".$DB->sanitize($tpl_id). "' AND r.fid='".$DB->sanitize($id_selector)."' AND r.cid='".$DB->sanitize($subtpl_id)."' LIMIT 0, ".$stagger_limit );
                    }
                    else{
                        $rs = $DB->select( K_TBL_PAGES." p INNER JOIN ".K_TBL_RELATIONS." r ON p.id = r.pid LEFT OUTER JOIN ".$to_table." d ON (p.id = d.page_id AND d.field_id='".$DB->sanitize($field_id)."')", array('p.*'), "p.template_id = '".$DB->sanitize($tpl_id). "' AND r.fid='".$DB->sanitize($id_selector)."' AND r.cid='".$DB->sanitize($subtpl_id)."' AND d.page_id IS NULL LIMIT 0, ".$stagger_limit );
                    }
                }
                else{
                    $rs = $DB->select( K_TBL_PAGES." p INNER JOIN ".K_TBL_RELATIONS." r ON p.id = r.pid", array('p.*'), "template_id='".$DB->sanitize($tpl_id)."' AND r.fid='".$DB->sanitize($id_selector)."' AND r.cid='".$DB->sanitize($subtpl_id)."'" );
                }
            }

            return $rs;
        }

        static function validate_new( $field ){
            if( $field->page->id != -1 && (count($field->items_deleted) || count($field->items_inserted)) ){
                return KFuncs::raise_error( "Sub-template cannot be changed once the page has been saved" );
            }
        }

        static function alter_fields_info( &$_fields, &$pg, &$skip_cache, &$fields ){
            global $FUNCS, $DB;

            if( $pg->tpl_has_subtemplates ){
                if( $pg->__tmp_sub_template_id ){
                    $f = &$pg->_fields[self::subtpl_selector];
                    if( $f ){
                        $f->store_posted_changes( $pg->__tmp_sub_template_id );
                    }
                    unset( $pg->__tmp_sub_template_id );
                }

                // handle cache..
                if( $pg->id ){
                    // get sub_template id
                    if( array_key_exists(self::subtpl_selector, $pg->_fields) ){
                        $fid = $pg->_fields[self::subtpl_selector]->id;

                        $rs = $DB->select( K_TBL_RELATIONS, array('cid'), "pid='".$DB->sanitize( $pg->id )."' AND fid='".$DB->sanitize( $fid )."'" );
                        if( count($rs) ){
                            $sub_template_id = $rs[0]['cid'];

                            if( !is_array($FUNCS->cached_subtpl_fields) ){ $FUNCS->cached_subtpl_fields=array(); }
                            $FUNCS->cached_subtpl_fields[$sub_template_id] = $fields;
                        }
                    }
                }
                $skip_cache=1;
            }
        }

        static function get_pages_to_add_field_db( &$rs, &$f ){ // this gets called when <cms:editable> is used outside of <cms:sub_template>
            global $PAGE;
            if( !$PAGE->tpl_has_subtemplates ){ return; }

            if( $f->template_id==$PAGE->tpl_id ){

                // return back an empty set
                $rs = array();
            }
        }

        static function alter_pages_form_default_fields(&$arr_default_fields, &$obj){
            global $PAGE, $FUNCS, $CTX, $DB;
            if( !$PAGE->tpl_has_subtemplates ){ return; }

            if( !is_array($obj->arr_config) ){ $obj->arr_config = array(); }
            if( !is_array($obj->arr_config['arr_fields']) ){ $obj->arr_config['arr_fields'] = array(); }

            $sub_template_name = $id_sub_template = $id_common = '';
            $f = $PAGE->_fields[self::subtpl_selector];
            if( $f ){
                if( $PAGE->id == -1 ){
                    if( $_POST ){
                        $input = ( $PAGE->tpl_subtpl_selector_is_advanced ) ? 'f_'.self::subtpl_selector : 'f_'.self::subtpl_selector.'_chk';
                        if( isset($_POST[$input]) ){
                            $tmp_id = $_POST[$input];
                        }
                    }
                    else{
                        if( defined('K_ADMIN') && isset($_GET[self::subtpl_selector]) ){
                            $tmp_id = $_GET[self::subtpl_selector];
                        }
                    }
                }
                else{
                    $tmp_id = $f->items_selected[0];
                }

                if( $FUNCS->is_non_zero_natural($tmp_id) ){
                    $tmp_id = (int)$tmp_id;
                    $aux_tpl_name = self::_get_aux_tpl_name( $PAGE->tpl_name );
                    $rs = $DB->select( K_TBL_PAGES." p INNER JOIN ".K_TBL_TEMPLATES." t ON p.template_id =  t.id", array('p.page_title, p.page_name, t.custom_params'), "p.id='".$DB->sanitize($tmp_id)."' AND t.name='".$DB->sanitize($aux_tpl_name)."'" );
                    if( count($rs) ){
                        $id_sub_template = $tmp_id;

                        $sub_template_name = trim( $rs[0]['page_title'] );
                        if( $sub_template_name=='' ){ $sub_template_name = $rs[0]['page_name']; }

                        $custom_params = $rs[0]['custom_params'];
                        if( strlen($custom_params) ){
                            $custom_params = $FUNCS->unserialize($custom_params);
                        }
                        if( !is_array($custom_params) ) $custom_params=array();
                        $id_common = $custom_params['id_common'];

                        // set order of fields
                        $rs2 = $DB->select( K_TBL_SUB_TEMPLATES." st INNER JOIN ".K_TBL_FIELDS." f ON st.field_id = f.id", array('st.is_stub, f.name'), "st.template_id = '".$DB->sanitize($PAGE->tpl_id)."' AND (st.sub_template_id = '".$DB->sanitize($id_sub_template)."' || st.sub_template_id = '".$DB->sanitize($id_common)."')" );
                        if( count($rs2) ){
                            foreach( $rs2 as $rec ){
                                $fname = $rec['name'];
                                $order = $rec['is_stub'];
                                if( !is_null($order) && array_key_exists($fname, $arr_default_fields) ){
                                    $arr_default_fields[$fname]['order'] = $rec['is_stub'];
                                }
                            }
                        }
                    }
                }
            }

            // set custom config
            foreach( array('common', 'sub_template') as $sub_tpl ){
                $var_name = 'id_'.$sub_tpl;
                $id_sub_tpl = $$var_name;

                if( $id_sub_tpl ){
                    $arr_config_subtpl = null;
                    $data_subtpl = self::_get_sub_template_data( $id_sub_tpl, 1 );
                    if( is_array($data_subtpl) ){ $arr_config_subtpl=$data_subtpl['config_form']; }

                    if( is_array($arr_config_subtpl) ){
                        if( is_array($arr_config_subtpl['arr_fields']) ){
                            foreach( $arr_config_subtpl['arr_fields'] as $k=>$v ){
                                $obj->arr_config['arr_fields'][$k] = $v;
                            }
                        }

                        foreach( array('js', 'css', 'html', 'params') as $k ){
                            if( is_array($arr_config_subtpl[$k]) ){
                                if( !is_array($obj->arr_config[$k]) ){ $obj->arr_config[$k] = array(); }

                                foreach( $arr_config_subtpl[$k] as $v ){
                                    $obj->arr_config[$k][] = $v;
                                }
                            }
                        }
                    }
                }
            }

            // set selector dropdown
            if( $PAGE->id == -1 ){
                $obj->arr_config['arr_fields']['my_refresh_form'] = array(
                    'name'=>'my_refresh_form',
                    'no_wrapper'=>'1',
                    'content'=>'<input type="hidden" id="k_refresh_form" name="k_refresh_form" value="">'
                );

                $FUNCS->add_event_listener( 'form_alter_posted_data_'.$CTX->get('k_cur_token'), function(&$form, &$refresh_form, &$refresh_errors, &$pg){
                    if( $pg->tpl_has_subtemplates ){
                        if( isset($_POST['k_refresh_form'][0]) ){
                            $refresh_form=1;
                        }
                    }
                });

                ob_start();
                ?>
                $(function(){
                    var $field = $('#k_element_<?php echo(self::subtpl_selector); ?>');
                    var $list = $field.find('select');
                    var $input = $field.find('[name="f_<?php echo(self::subtpl_selector); ?>"]');

                    if($input.length){
                        var id;
                        $list.bind( '_refresh', function(){
                            var $opts = $(this).find('option');
                            if($opts.length){
                                id = $opts.first().val();
                                $input.val(id);
                            }
                            else{
                                // nothing selected
                                id='';
                            }
                            $input.val(id);
                            refresh();
                        });
                    }
                    else{
                        $list.on( 'change', function(){
                            refresh();
                        });
                    }

                    function refresh(){
                        var $form = $('#k_admin_frm');
                        $form.find('#btn_submit').trigger('my_submit');
                        $form.find('#k_refresh_form').val('1');
                        $('#k_overlay').css('display', 'block');
                        $form.submit();
                        return false;
                    }
                });
                <?php
                $js = ob_get_contents();
                ob_end_clean();
                $FUNCS->add_js( $js );
            }
            else{
                $obj->arr_config['arr_fields'][self::subtpl_selector] = array(
                    'name'=>self::subtpl_selector,
                    'content'=>$sub_template_name
                );

                $FUNCS->add_css( '#k_element_'.self::subtpl_selector.' .labels{ display:none; }' );
            }
        }

        static function field_deleted( $rec ){
            global $DB;

            $field_id = $rec['id'];

            $rs = $DB->delete( K_TBL_SUB_TEMPLATES, "field_id='".$DB->sanitize( $field_id )."'" );
            if( $rs==-1 ) die( "ERROR: Unable to delete record from K_TBL_SUB_TEMPLATES" );
        }

        static function delete_aux_template( $tpl ){
            global $DB, $FUNCS;

            $custom_params = $tpl['custom_params'];
            if( strlen($custom_params) ){
                $custom_params = $FUNCS->unserialize($custom_params);
                if( is_array($custom_params) && $custom_params['has_subtemplates'] ){

                    $DB->delete( K_TBL_SUB_TEMPLATES, "template_id = '".$DB->sanitize( $tpl['id'] )."'" );

                    // get the aux template
                    $tpl_name = self::_get_aux_tpl_name( $tpl['name'] );

                    $rs = $DB->select( K_TBL_TEMPLATES, array('*'), "name='".$DB->sanitize( $tpl_name )."' AND hidden='2' LIMIT 1" );
                    if( count($rs) ){

                        // mark template as deleted
                        $DB->update( K_TBL_TEMPLATES, array('deleted'=>1), "id='".$DB->sanitize( $rs[0]['id'] )."'" );

                        // HOOK: st_aux_template_deleted
                        $FUNCS->dispatch_event( 'st_aux_template_deleted', array( $tpl_name, $tpl['name'], $rs[0]) );

                        // signal to GC (can piggyback on existing gc logic of mosaic)
                        $FUNCS->set_setting( 'gc_mosaic_is_dirty', 1 );
                    }
                }
            }
        }

        static function page_prevalidate( &$fields, &$pg ){
            if( !$pg->tpl_has_subtemplates ){ return; }

            if( $pg->id != -1 ){
                $f = &$pg->_fields[self::subtpl_selector];
                if( $f && !count($f->items_selected) ){
                    // if at this point page does not have a sub-template, it was saved before the addon took effect
                    $f->k_inactive = 1;
                }
            }
        }

        static function alter_page_context( &$vars, &$pg ){
            global $FUNCS, $DB;
            static $cache = array();

            if( $vars['k_template_has_subtemplates'] && ($vars['k_is_page'] || $vars['k_is_list_page']) && ($id = intval($vars['_sub_template'])) ){
                if( isset($cache[$id]) ){
                    $vars['k_sub_template_name'] = $cache[$id];
                }
                else{
                    $rs = $DB->select( K_TBL_PAGES, array('page_name'), "id='".$DB->sanitize($id)."'" );
                    if( count($rs) ){
                        $cache[$id] = $vars['k_sub_template_name'] = $rs[0]['page_name'];
                    }
                }
                $vars['k_sub_template'] = $id;
            }
        }

    }// end class

    // register custom tags
    $FUNCS->register_tag( 'sub_template', array('KSubTemplates', 'sub_template_tag_handler') );
    $FUNCS->register_tag( 'stub', array('KSubTemplates', 'stub_tag_handler') );

    // hook events
    $FUNCS->add_event_listener( 'add_template_params', array('KSubTemplates', 'add_template_params') );
    $FUNCS->add_event_listener( 'template_tag_end', array('KSubTemplates', 'template_tag_end') );
    $FUNCS->add_event_listener( 'get_custom_fields_info_db', array('KSubTemplates', 'get_custom_fields_info_db') );
    $FUNCS->add_event_listener( 'get_cached_fields', array('KSubTemplates', 'get_cached_fields') );
    $FUNCS->add_event_listener( 'field_deleted', array('KSubTemplates', 'field_deleted') );
    $FUNCS->add_event_listener( 'alter_fields_info', array('KSubTemplates', 'alter_fields_info') );
    $FUNCS->add_event_listener( 'get_pages_to_add_field_db', array('KSubTemplates', 'get_pages_to_add_field_db'), -10 );
    $FUNCS->add_event_listener( 'page_prevalidate', array('KSubTemplates', 'page_prevalidate') );
    $FUNCS->add_event_listener( 'template_deleted', array('KSubTemplates', 'delete_aux_template') );
    $FUNCS->add_event_listener( 'alter_page_set_context', array('KSubTemplates', 'alter_page_context') );
    if( defined('K_ADMIN') ){
        $FUNCS->add_event_listener( 'alter_pages_form_default_fields', array('KSubTemplates', 'alter_pages_form_default_fields') );
    }

    require_once( K_COUCH_DIR.'addons/sub-templates/filtered_search.php' );
