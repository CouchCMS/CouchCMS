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

    class Relation extends KUserDefinedField{

        var $items_selected = array();
        var $items_deleted = array();
        var $items_inserted = array();

        static function handle_params( $params ){
            global $FUNCS, $AUTH;
            if( $AUTH->user->access_level < K_ACCESS_LEVEL_SUPER_ADMIN ) return;

            $attr = $FUNCS->get_named_vars(
                array(  'masterpage'=>'',
                    'has'=>'', /* one, many */
                    'reverse_has'=>'', /*  -do-  */
                    'folder'=>'',
                    'include_subfolders'=>'1',
                    'orderby'=>'', /* publish_date, page_title, page_name, weight */
                    'order_dir'=>'', /* desc, asc */
                    'no_gui'=>'0', /* for setting values only programmatically */
                    'advanced_gui'=>'0',
                  ),
                $params
            );
            $attr['masterpage'] = trim( $attr['masterpage'] );
            if( !strlen($attr['masterpage']) ) die("ERROR: Editable 'relation' type requires a 'masterpage' parameter.");
            $attr['has'] = strtolower( trim($attr['has']) );
            if( $attr['has']!='one' && $attr['has']!='many' ) $attr['has'] = 'many';
            $attr['reverse_has'] = strtolower( trim($attr['reverse_has']) );
            if( $attr['reverse_has']!='one' && $attr['reverse_has']!='many' ) $attr['reverse_has'] = 'many';
            $attr['folder'] = trim( $attr['folder'] );
            $attr['orderby'] = strtolower( trim($attr['orderby']) );
            $attr['order_dir'] = strtolower( trim($attr['order_dir']) ); // 'order' is a 'core parameter' and will be stripped off
            $attr['no_gui'] = ( $attr['no_gui']==1 ) ? 1 : 0;
            $attr['advanced_gui'] = ( $attr['advanced_gui']==1 ) ? 1 : 0;

            return $attr;
        }

        // Load from database
        function store_data_from_saved( $data ){
            global $DB;

            $pid = $this->page->id;
            $fid = $this->id;

            $sql = "SELECT cid FROM ".K_TBL_RELATIONS." WHERE pid = '".$pid."' AND fid = '".$fid."'";
            $result = @mysql_query( $sql, $DB->conn );
            if( !$result ){
                ob_end_clean();
                die( "Could not successfully run query: " . mysql_error( $DB->conn ) );
            }

            $this->items_selected = array();
            while( $row=mysql_fetch_row($result) ){
                $this->items_selected[] = $row[0];
            }
        }

        // Show in admin panel
        function _render( $input_name, $input_id, $extra='', $dynamic_insertion=0 ){
            global $FUNCS;

            if( $this->no_gui ){
                $rows = $this->items_selected;
                if( $this->has=='one' && count($rows)>1 ){
                    array_splice( $rows, 1 );
                }
                foreach( $rows as $row_id ){
                    $html .= '<input type="hidden" name="'.$input_name.'_chk[]" value="'.$row_id.'" />';
                }

                return $html;
            }

            $renderable = ( $this->advanced_gui ) ? 'field_relation_advanced' : 'field_relation';
            $html = $FUNCS->render( $renderable, $this, $input_name, $input_id, $extra, $dynamic_insertion );

            return $html;
        }

        // Called from both _render and store_posted_changes
        function _get_rows( $arr_posted=null, $for_render=null ){
            global $FUNCS, $DB;

            $rs = $DB->select( K_TBL_TEMPLATES, array('id'), "name='" . $DB->sanitize( $this->masterpage ). "'" );
            if( !count($rs) ) return $FUNCS->raise_error( 'Error: masterpage: "'.$this->masterpage.'" not found.' );
            $tpl_id = $rs[0]['id'];

            $rows = array();
            $str_posted_ids = '';
            if( is_array($arr_posted) ){
                // called from store_posted_changes or advanced gui render
                $select_fields = ( $for_render )? 'id, page_title' : 'id';

                $arr_posted_sanitized = array();
                foreach( $arr_posted as $id ){
                    if( $FUNCS->is_non_zero_natural($id) ) $arr_posted_sanitized[]=(int)$id;
                }
                $str_posted_ids = trim( implode(',', $arr_posted_sanitized) );

                if( !strlen($str_posted_ids) ){
                    // No valid rows posted. Return.
                    return $rows;
                }
            }
            else{
                // called from _render
                $select_fields = 'id, page_title';
            }

            // folder?
            $fsql = $this->_get_folder_sql( $tpl_id );

            // reverse has one?
            if( $this->reverse_has=='one' ){
                // show only pages that are not already selected by others of the same relation field
                $rsql = $this->_get_reverse_has_one_sql();
            }

            $sql = "SELECT " . $select_fields . "\r\n";
            $sql .= "FROM ".K_TBL_PAGES." p \r\n";
            $sql .= "WHERE p.template_id='".$tpl_id."'"."\r\n";
            $sql .= $rsql;
            $sql .= $fsql;
            $sql .= "AND NOT p.publish_date = '0000-00-00 00:00:00'"."\r\n";

            if( is_array($arr_posted) ){
                $sql .= "AND p.id IN (".$str_posted_ids.")"."\r\n";
            }
            if( !is_array($arr_posted) || $for_render ){
                // order & orderby
                $orderby = trim( $this->orderby );
                if( $orderby!='publish_date' && $orderby!='page_title' && $orderby!='page_name' && $orderby!='weight' ) $orderby = 'publish_date';
                if( $orderby == 'weight' ){ $orderby = 'k_order'; }
                $order = trim( $this->order_dir );
                if( $order!='desc' && $order!='asc' ) $order = 'desc';
                $sql .= "ORDER BY p.".$orderby." ".$order."\r\n";
            }

            $result = @mysql_query( $sql, $DB->conn );
            if( !$result ){
                ob_end_clean();
                die( "Could not successfully run query: " . mysql_error( $DB->conn ) );
            }

            if( is_array($arr_posted) && !$for_render ){
                while( $row=mysql_fetch_row($result) ){
                    $rows[] = $row[0];
                }
            }
            else{
                while( $row=mysql_fetch_row($result) ){
                    $rows[$row[0]] = $row[1];
                }
            }
            mysql_free_result( $result );

            return $rows;
        }

        function _get_folder_sql( $tpl_id ){
            global $FUNCS, $DB;

            $folder = trim( $this->folder );
            $include_subfolders = ( $this->include_subfolders==0 ) ? 0 : 1;
            if( $folder!='' ){

                $arr_folders = array();
                // get all the folders of the masterpage
                $folders = &$FUNCS->get_folders_tree( $tpl_id, $this->masterpage );

                // Negation?
                $neg = 0;
                $pos = strpos( strtoupper($folder), 'NOT ' );
                if( $pos!==false && $pos==0 ){
                    $neg = 1;
                    $folder = trim( substr($folder, strpos($folder, ' ')) );
                }

                // multiple folders specified?
                $arr_parent_folders = array_map( "trim", explode( ',', $folder ) );
                foreach( $arr_parent_folders as $parent_folder ){
                    if( $parent_folder ){
                        // locate the folder
                        $f = &$folders->find( $parent_folder );
                        if( $f ){
                            if( $include_subfolders ){
                                // get all the child folders of it
                                $sub_folders = $f->get_children(); //includes the parent folder too
                                foreach( $sub_folders as $sf ){
                                    if( !array_key_exists( $sf->name, $arr_folders ) ){
                                        $arr_folders[$sf->name] = $sf->id;
                                    }
                                }
                            }
                            else{
                                if( !array_key_exists( $f->name, $arr_folders ) ){
                                    $arr_folders[$f->name] = $f->id;
                                }
                            }
                        }
                    }
                }

                if( count($arr_folders) ){
                    $fsql = "AND p.page_folder_id ";
                    if( $neg ) $fsql .= "NOT ";
                    $fsql .= "IN (";
                    $sep = "";
                    foreach( $arr_folders as $k=>$v ){
                        $fsql .= $sep . "'" . $DB->sanitize( $v )."'";
                        $sep = ", ";
                    }
                    $fsql .= ") ";
                }
            }
            else{
                if( !$include_subfolders ){
                    $fsql .= "AND p.page_folder_id='-1' ";
                }
            }

            return $fsql;
        }

        function _get_reverse_has_one_sql(){
            $pid = ( $this->page->parent_id ) ? $this->page->parent_id : $this->page->id;
            $fid = $this->id;

            $sql = "AND p.id NOT IN"."\r\n";
            $sql .= "(SELECT rel.cid FROM ".K_TBL_RELATIONS." rel WHERE rel.fid = '".$fid."' AND rel.pid <> '".$pid."'"."\r\n";

            // take drafts into consideration ..
            $sql .= "AND rel.pid NOT IN (SELECT p2.id FROM ".K_TBL_PAGES." p2 WHERE p2.parent_id = '".$pid."'))"."\r\n";

            return $sql;
        }

        // Handle posted data
        function store_posted_changes( $post_val ){
            global $FUNCS;
            if( $this->deleted || $this->k_inactive ) return; // no need to store

            $input_name = 'f_'.$this->name.'_chk';
            $arr_posted = array();
            if( $post_val ){
                // check if '+' or '-' specified
                $post_val = trim( $post_val );
                $op = $post_val[0];
                if( $op=='+' || $op=='-' ){
                    $post_val = substr( $post_val, 1 );
                }

                $arr_posted = array_map( "trim", explode( ',', $post_val ) );

                if( $op=='+' ){
                    $arr_posted = array_merge( $this->items_selected, array_diff($arr_posted, $this->items_selected) );
                }
                elseif( $op=='-' ){
                    $arr_posted = array_diff( $this->items_selected, $arr_posted );
                }
            }
            else{
                if( isset($_POST[$input_name]) ){
                    if( !is_array($_POST[$input_name]) ){ // has='one'
                        if( $_POST[$input_name]!='-' ) $arr_posted[] = $_POST[$input_name];
                    }
                    else{
                        $arr_posted = $_POST[$input_name];
                    }
                }
            }

            $arr_posted_sanitized = $this->_get_rows( $arr_posted ); // accept only valid values
            if( $FUNCS->is_error($arr_posted_sanitized) ) return;

            $arr_posted = $arr_posted_sanitized;
            if( $this->has=='one' && count($arr_posted)>1 ){
                array_splice( $arr_posted, 1 );
            }

            $this->items_deleted = array_diff( $this->items_selected, $arr_posted );
            $this->items_inserted = array_diff( $arr_posted, $this->items_selected );
            if( count($this->items_deleted) || count($this->items_inserted) ){
                $this->modified = true;
            }
            $this->items_selected = $arr_posted;
        }

        // before save
        function validate(){ // for now only checking for 'required'
            global $FUNCS;
            if( $this->deleted || $this->k_inactive ) return true;

            if( $this->required && !count($this->items_selected) ){
                $this->err_msg = $FUNCS->t('required_msg');
                return false;
            }
            return true;
        }

        function get_data( $for_ctx=0 ){
            if( $for_ctx ){
                return implode( ',', $this->items_selected );
            }
        }

        // Save to database.
        function get_data_to_save(){
            global $DB;

            $pid = $this->page->id;
            $fid = $this->id;

            // delete the removed items
            if( count($this->items_deleted) ){
                foreach( $this->items_deleted as $cid ){
                    $rs = $DB->delete( K_TBL_RELATIONS, "pid = '".$pid."' AND fid = '".$fid."' AND cid='".$DB->sanitize( $cid )."'" );
                    if( $rs==-1 ) die( "ERROR: Unable to delete record from K_TBL_RELATIONS" );
                }
            }

            // insert new items
            if( count($this->items_inserted) ){
                foreach( $this->items_inserted as $cid ){
                    $weight = 0; //TODO
                    $rs = $DB->insert( K_TBL_RELATIONS, array(
                        'pid'=>$pid,
                        'fid'=>$fid,
                        'cid'=>$cid,
                        'weight'=>$weight
                        )
                    );
                    if( $rs!=1 ) die( "ERROR: Failed to insert record in K_TBL_RELATIONS" );
                }
            }
            return;
        }

        // Called from 'cms:editable' when this type of field gets modified in a template (i.e. its parameters)
        function _update_schema( $orig_values ){
            global $DB;

            // if 'masterpage' (i.e. related template) changed, remove all previous relation records for this field as they are now defunct
            if( array_key_exists('masterpage', $orig_values) ){
                $masterpage = $this->masterpage;
                // ..but first make sure the new template exists
                $rs = $DB->select( K_TBL_TEMPLATES, array('id'), "name='" . $DB->sanitize( $masterpage ). "'" );
                if( !count($rs) ){
                    ob_end_clean();
                    die( 'Error: Tag "editable" type="relation" - masterpage: "'.$masterpage.'" not found.' );
                }
                //$rs = $DB->delete( K_TBL_RELATIONS, "fid='" . $this->id . "'" );
                //if( $rs==-1 ) die( "ERROR: Unable to delete records from K_TBL_RELATIONS" );
            }
            return;
        }

        // Called either from a page being deleted
            // or when this field's definition gets removed from a template (in which case the $page_id param would be '-1' )
        function _delete( $page_id ){
            global $DB;

            if( $page_id==-1 ){
                // Remove all records from the relation table for this field
                $rs = $DB->delete( K_TBL_RELATIONS, "fid='" . $this->id . "'" );
                if( $rs==-1 ) die( "ERROR: Unable to delete records from K_TBL_RELATIONS" );
            }
            else{
                // Remove all records from the relation table for the page being deleted
                $rs = $DB->delete( K_TBL_RELATIONS, "pid='" . $DB->sanitize( $page_id ). "' AND fid='".$this->id."'" );
                if( $rs==-1 ) die( "ERROR: Unable to delete records from K_TBL_RELATIONS" );

                $rs = $DB->delete( K_TBL_RELATIONS, "cid='" . $DB->sanitize( $page_id ). "' AND fid='".$this->id."'" );
                if( $rs==-1 ) die( "ERROR: Unable to delete records from K_TBL_RELATIONS" );
            }
            return;
        }

        function _clone( $cloned_page_id, $cloned_page_title ){
            global $DB;

            $pid = $this->page->id;
            $fid = $this->id;

            $rs = $DB->select( K_TBL_RELATIONS, array('cid'), "pid='" . $DB->sanitize( $pid ). "' AND fid='" . $DB->sanitize( $fid ). "'" );
            if( count($rs) ){
                foreach( $rs as $row ){
                    $cid = $row['cid'];
                    $weight = 0; //TODO
                    $rs2 = $DB->insert( K_TBL_RELATIONS, array(
                        'pid'=>$cloned_page_id,
                        'fid'=>$fid,
                        'cid'=>$cid,
                        'weight'=>$weight
                        )
                    );
                    if( $rs2!=1 ) die( "ERROR: Failed to insert record in K_TBL_RELATIONS" );
                }
            }
        }

        function _unclone( &$cloned_field ){
            global $DB;

            // Prepare for the impending get_data_to_save() via $PAGE->save()
            $pid = $cloned_field->page->id;
            $fid = $this->id;

            $sql = "SELECT cid FROM ".K_TBL_RELATIONS." WHERE pid = '".$pid."' AND fid = '".$fid."'";
            $result = @mysql_query( $sql, $DB->conn );
            if( !$result ){
                ob_end_clean();
                die( "Could not successfully run query: " . mysql_error( $DB->conn ) );
            }

            $arr_recs = array();
            while( $row=mysql_fetch_row($result) ){
                $arr_recs[] = $row[0];
            }

            $this->items_deleted = array_diff( $this->items_selected, $arr_recs );
            $this->items_inserted = array_diff( $arr_recs, $this->items_selected );
            return;
        }

        function _prep_cached(){
            $this->items_selected = array();
            $this->items_deleted = array();
            $this->items_inserted = array();
        }

        //////////////// Tags //////////////////////////////////////////////////
        static function related_pages_handler( $params, $node ){
            global $CTX, $FUNCS, $TAGS, $PAGE, $DB;

            extract( $FUNCS->get_named_vars(
                array( 'field'=>'',
                ),
                $params)
            );
            $field = trim( $field );

            // get page_id (return if used in context of list_view)
            if( $CTX->get('k_is_page') || $CTX->get('k_is_list_page') ){
                $page_id = $CTX->get('k_page_id');
            }
            else return; // happens in list_view

            // get relation_field_id using template_id
            $template_id = $CTX->get('k_template_id');
            $template_name = $CTX->get('k_template_name');
            if( $field ){
                $rs = $DB->select( K_TBL_FIELDS, array('*'), "template_id='" . $DB->sanitize( $template_id ). "' AND k_type='relation' AND name='" . $DB->sanitize( $field ) . "'" );
            }
            else{ // if field not specified, get the first 'relation' field defined
                $rs = $DB->select( K_TBL_FIELDS, array('*'), "template_id='" . $DB->sanitize( $template_id ). "' AND k_type='relation' LIMIT 1" );
            }

            if( count($rs) ){
                $field_id = $rs[0]['id'];
            }
            else{
                if( $field ){
                    die("ERROR: Tag \"".$node->name."\": relation field '".$FUNCS->cleanXSS($field)."' not defined in ".$template_name);
                }
                else{
                    die("ERROR: Tag \"".$node->name."\": no relation field defined in ".$template_name);
                }
            }

            // get the related template's name
            $obj_field = new Relation( $rs[0], new KError('dummy'), new KError('dummy') );
            $related_template_name = trim( $obj_field->masterpage );
            if( !$related_template_name ) return;
            unset( $obj_field );

            // delegate over to 'pages' tag with the additional info (taking care to change the 'masterpage' attribute if set)
            for( $x=0; $x<count($params); $x++ ){
                $param = &$params[$x];
                if( strtolower($param['lhs'])=='masterpage' ){
                    $param['rhs'] = $related_template_name;
                    $added = 1;
                    break;
                }
            }
            if( !$added ){
                $params[] = array('lhs'=>'masterpage', 'op'=>'=', 'rhs'=>$related_template_name);
            }
            $params[] = array('lhs'=>'pid', 'op'=>'=', 'rhs'=>$page_id);
            $params[] = array('lhs'=>'fid', 'op'=>'=', 'rhs'=>$field_id);

            $html = $TAGS->pages( $params, $node, 4 );
            return $html;
        }

        static function reverse_related_pages_handler( $params, $node ){
            global $CTX, $FUNCS, $TAGS, $PAGE, $DB;

            extract( $FUNCS->get_named_vars(
                array( 'field'=>'',
                       'masterpage'=>'' /* mandatory */
                ),
                $params)
            );
            $field = trim( $field );
            $masterpage = trim( $masterpage );
            if( !$masterpage ) die("ERROR: Tag \"".$node->name."\": 'masterpage' not specified");

            // get page_id (return if used in context of list_view)
            if( $CTX->get('k_is_page') || $CTX->get('k_is_list_page') ){
                $page_id = $CTX->get('k_page_id');
            }
            else return; // happens in list_view

            // get template_id of parent masterpage
            $rs = $DB->select( K_TBL_TEMPLATES, array('id'), "name='" . $DB->sanitize( $masterpage ). "'" );
            if( count($rs) ){
                $template_id = $rs[0]['id'];
            }
            else{
                die("ERROR: Tag \"".$node->name."\": masterpage '".$FUNCS->cleanXSS($masterpage)."' not found" );
            }

            // get relation_field_id using template_id
            if( $field ){
                $rs = $DB->select( K_TBL_FIELDS, array('*'), "template_id='" . $DB->sanitize( $template_id ). "' AND k_type='relation' AND name='" . $DB->sanitize( $field ) . "'" );
            }
            else{ // if field not specified, get the first 'relation' field defined
                $rs = $DB->select( K_TBL_FIELDS, array('*'), "template_id='" . $DB->sanitize( $template_id ). "' AND k_type='relation' LIMIT 1" );
            }

            if( count($rs) ){
                $field_id = $rs[0]['id'];
            }
            else{
                if( $field ){
                    die("ERROR: Tag \"".$node->name."\": relation field '".$FUNCS->cleanXSS($field)."' not defined in ".$FUNCS->cleanXSS($masterpage));
                }
                else{
                    die("ERROR: Tag \"".$node->name."\": no relation field defined in ".$FUNCS->cleanXSS($masterpage));
                }
            }

            // delegate over to 'pages' tag with the additional info
            $params[] = array('lhs'=>'cid', 'op'=>'=', 'rhs'=>$page_id);
            $params[] = array('lhs'=>'fid', 'op'=>'=', 'rhs'=>$field_id);

            $html = $TAGS->pages( $params, $node, 4 );
            return $html;
        }

        // routes
        static function register_routes(){
            global $FUNCS;

            $route = array(
                'name'=>'list_view',
                'path'=>'list/{:tpl_id}/{:field_name}/{:page_id}',
                'constraints'=>array(
                    'tpl_id'=>'([1-9]\d*)',
                    'field_name'=>'([0-9a-z-_]+)',
                    /*'page_id'=>'([1-9]\d*)?',*/
                    'page_id'=>'((?:[1-9]\d*)?)',
                ),
                'include_file'=>K_ADDONS_DIR.'relation/edit-relation.php',
                'filters'=>'KRelationAdmin::resolve_entities',
                'class'=> 'KRelationAdmin',
                'action'=>'list_action_ex',
                'module'=>'relation', /* owner module of this route */
            );

            $FUNCS->register_route( 'relation', $route );
        }

        // renderable theme functions
        static function register_renderables(){
            global $FUNCS;

            $FUNCS->register_render( 'field_relation',              array('renderable'=>array('Relation', '_render_relation')) );
            $FUNCS->register_render( 'field_reverse_relation',      array('renderable'=>array('ReverseRelation', '_render_reverse_relation')) );
            $FUNCS->register_render( 'field_relation_advanced',     array('template_path'=>K_ADDONS_DIR.'relation/theme/', 'template_ctx_setter'=>array('Relation', '_render_relation_advanced')) );

            $FUNCS->register_render( 'content_list_relation',       array('template_path'=>K_ADDONS_DIR.'relation/theme/') );
            $FUNCS->register_render( 'content_list_relation_inner', array('template_path'=>K_ADDONS_DIR.'relation/theme/') );
            $FUNCS->register_render( 'content_list_relation_exit',  array('template_path'=>K_ADDONS_DIR.'relation/theme/') );
            $FUNCS->register_render( 'relation_list_checkbox',      array('renderable'=>array('Relation', '_render_list_checkbox')) );
        }

        static function _render_relation( $f, $input_name, $input_id, $extra, $dynamic_insertion ){
            global $FUNCS, $CTX;

            define( 'RELATION_URL', K_ADMIN_URL . 'addons/relation/' );
            $FUNCS->load_css( RELATION_URL . 'relation.css' );

            $rows = $f->_get_rows();
            if( $FUNCS->is_error($rows) ) return $rows->err_msg;

            if( $f->has=='one' ){
                $selected = ( count($f->items_selected) ) ? $f->items_selected[0] : ''; // can have only one item selected
                if( $f->simple_mode ){
                    $html .= '<select name="'.$input_name.'_chk" id="'.$input_id.'"'.( $f->width ? ' style="width:'.$f->width.'px;"' : '' ).( $f->deleted ? ' disabled="1"' : '' ).'>';
                }
                else{
                    $html .= '<div class="select dropdown"'.( $f->width ? ' style="width:'.$f->width.'px;min-width:auto;"' : '' ).'>';
                    $html .= '<select name="'.$input_name.'_chk" id="'.$input_id.'"'.( $f->deleted ? ' class="disabled" disabled="1"' : '' ).'>';
                }
                $html .= '<option value="-">-- Select --</option>'; //TODO get label as parameter

                foreach( $rows as $key=>$value ){
                    $html .= '<option value="'.$key.'"';
                    if( $selected && $key==$selected ) $html .= '  selected="selected"';
                    $html .= '>'.$value.'</option>';
                }
                $html .= '</select>';
                if( !$f->simple_mode ){
                    $html .= '<span class="select-caret">'.$FUNCS->get_icon('caret-bottom').'</span></div>';
                }
            }
            else{
                if( $f->simple_mode ){
                    $html .= '<ul class="checklist">';
                }
                else{
                    $html .= '<div class="relation-body"'.( $f->width ? ' style="width:'.$f->width.'px;"' : '' ).'>';
                    $html .= '<div class="scroll-relation"'.( $f->height ? ' style="max-height:'.$f->height.'px;"' : '' ).'>';
                    $html .= '<ul class="checklist'.( $f->deleted ? ' checklist-disabled' : '' ).'">';
                }
                $deleted = $f->deleted ? ' disabled="1"' : '';
                $markup = !$f->simple_mode ? '<span class="ctrl-option"></span>' : '';
                $x=0;
                foreach( $rows as $key=>$value ){
                    $class = ( ($x+1)%2 ) ? ' class="alt"' : '';
                    $checked = $selected = '';
                    if( in_array($key, $f->items_selected) ){
                        $checked = ' checked="checked"';
                        $selected = ' class="selected"';
                    }

                    $html .= '<li'.$class.'><label for="'.$input_name.'_chk_'.$x.'"'.$selected.'><input id="'.$input_name.'_chk_'.$x.'" name="'.$input_name.'_chk[]" type="checkbox" value="'.$key.'"'.$checked.$deleted.' />'.$markup.$value.'</li>';
                    $x++;
                }
                $html .= '</ul>';
                if( !$f->simple_mode ){
                    $html .= '</div>';
                    $html .= '</div>';
                }
            }

            return $html;
        }

        static function _render_relation_advanced( $f, $input_name, $input_id, $extra, $dynamic_insertion ){
            global $FUNCS, $CTX;
            static $done=0;

            KField::_set_common_vars( $f->k_type, $input_name, $input_id, $extra, $dynamic_insertion, $f->simple_mode );

            $rows = array();
            if( $f->has=='one' ){
                $selected = array();
                $selected[] = $f->items_selected[0]; // can have only one item selected
                $rows = $f->_get_rows( $selected, 1 /*for render*/ );
            }
            else{
                $rows = $f->_get_rows( $f->items_selected, 1 /*for render*/ );
            }
            if( $FUNCS->is_error($rows) ){ $rows=array(); };

            $CTX->set( 'k_options', $rows );
            $CTX->set( 'k_option_ids', trim(implode(',', array_keys($rows))) );
            $CTX->set( 'k_has_one', ( $f->has=='one' ) ? '1' : '0' );

            // set link
            //$link = $FUNCS->generate_route( 'relation', 'list_view', array('tpl_id'=>$f->template_id, 'field_name'=>$f->name, 'page_id'=>$f->page->id=='-1'?'':$f->page->id) ); // won't work when rendered outside admin-panel (i.e. on front-end in dbf)
            $link = K_ADMIN_URL . K_ADMIN_PAGE.'?o=relation&q=list/'.$f->template_id.'/'.$f->name.'/'.(($f->page->id=='-1')?'':$f->page->id);
            $CTX->set( 'k_target_link', $link );

            if( !$done ){
                $CTX->set( 'k_add_js', '1' );
                $done=1;
            }
            else{
                $CTX->set( 'k_add_js', '0' );
            }
        }

        static function _render_list_checkbox( $for_header=0, $text_label=0 ){
            global $CTX, $FUNCS;

            $page_id = $CTX->get( 'k_page_id' );
            $selected = $CTX->get( 'row_is_selected' );
            $has_one = $CTX->get( 'k_has_one' );

            if( $for_header ){
                $html = '<label class="ctrl checkbox">';
                $html .= '<input class="checkbox-all" type="checkbox" name="check-all" />';
                if( $has_one ){
                    $html .= '<span class="ctrl-option"></span></label>';
                }
                else{
                    $html .= '<span class="ctrl-option tt" title="'.$FUNCS->t('select-deselect').'"></span></label>';
                }
            }
            else{
                $html = '<label class="ctrl checkbox">';
                $html .= '<input type="checkbox" value="'.$page_id.'" class="page-selector checkbox-item" name="page-id[]" id="page-selector-'.$page_id.'"';
                if( $selected ){
                    $html .= ' checked="checked"';
                }
                $html .= '/>';
                $html .= '<span class="ctrl-option"></span></label>';
            }

            return $html;
        }
    }

    // Register
    $FUNCS->register_udf( 'relation', 'Relation' );
    $FUNCS->register_tag( 'related_pages', array('Relation', 'related_pages_handler'), 1, 1 ); // The helper tag that shows the related pages
    $FUNCS->register_tag( 'reverse_related_pages', array('Relation', 'reverse_related_pages_handler'), 1, 1 ); // -do in reverse-
    $FUNCS->add_event_listener( 'register_renderables',  array('Relation', 'register_renderables') );

    // routes
    if( defined('K_ADMIN') ){
        $FUNCS->add_event_listener( 'register_admin_routes',  array('Relation', 'register_routes') );
    }


    // UDF for outputting a link that lists reverse related pages in admin-panel
    class ReverseRelation extends KUserDefinedField{

        static function handle_params( $params ){
            global $FUNCS, $AUTH;
            if( $AUTH->user->access_level < K_ACCESS_LEVEL_SUPER_ADMIN ) return;

            $attr = $FUNCS->get_named_vars(
                array(
                    'masterpage'=>'',
                    'field'=>'',
                    'anchor_text'=>'',
                  ),
                $params
            );
            $attr['masterpage'] = trim( $attr['masterpage'] );
            if( !strlen($attr['masterpage']) ) die("ERROR: Editable 'reverse_relation' type requires a 'masterpage' parameter.");
            $attr['anchor_text'] = trim($attr['anchor_text']);
            if( !strlen($attr['anchor_text']) ) $attr['anchor_text']='View related pages';

            return $attr;
        }

        // Show in admin panel
        function _render( $input_name, $input_id, $extra='', $dynamic_insertion=0 ){
            global $FUNCS, $DB;

            // get template_id of reverse related masterpage
            $rs = $DB->select( K_TBL_TEMPLATES, array('id', 'name'), "name='" . $DB->sanitize( $this->masterpage ). "'" );
            if( count($rs) ){
                $template_id = $rs[0]['id'];
                $template_name = $rs[0]['name'];
            }
            else{
                return "ERROR: Related template '" . $FUNCS->cleanXSS($this->masterpage) . "' not found";
            }

            // get relation_field_id using template_id
            if( $this->field ){
                $rs = $DB->select( K_TBL_FIELDS, array('*'), "template_id='" . $DB->sanitize( $template_id ). "' AND k_type='relation' AND name='" . $DB->sanitize( $this->field ) . "'" );
            }
            else{ // if field not specified, get the first 'relation' field defined
                $rs = $DB->select( K_TBL_FIELDS, array('*'), "template_id='" . $DB->sanitize( $template_id ). "' AND k_type='relation' LIMIT 1" );
            }
            if( count($rs) ){
                $field_id = $rs[0]['id'];
            }
            else{
                if( $this->field ){
                    return "ERROR: relation field '".$FUNCS->cleanXSS($this->field)."' not defined in related template '".$FUNCS->cleanXSS($this->masterpage) ."'";
                }
                else{
                    return "ERROR: no relation field defined in related template '".$FUNCS->cleanXSS($this->masterpage) . "'";
                }
            }

            // Find the count of reverse related pages
            $count = 0;
            $link = null;
            $cid = $this->page->id;
            if( $cid != -1 ){ // not a new page
                $rel_tables = K_TBL_PAGES . ' p inner join ' . K_TBL_RELATIONS . ' rel on rel.pid = p.id' . "\r\n";
                $rel_sql = "p.parent_id=0 AND rel.cid='" . $DB->sanitize( $cid ). "' AND rel.fid='" . $DB->sanitize( $field_id ). "'";
                $rs = $DB->select( $rel_tables, array('count(p.id) as cnt'), $rel_sql );
                $count = $rs[0]['cnt'];

                // link that will show the related pages
                $link = K_ADMIN_URL . K_ADMIN_PAGE . '?o='.$template_name.'&q=list' . '&cid=' . $cid . '&rid=' . $field_id;
            }

            $html = $FUNCS->render( 'field_reverse_relation', $this, $link, $this->anchor_text, $count );

            return $html;
        }

        static function _render_reverse_relation( $f, $link, $anchor_text, $count ){
            if( $link ){
                $html = '<a href="'.$link.'">' . $anchor_text . ' ('.$count.')</a>';
            }
            else{
                $html = $anchor_text . ' ('.$count.')';
            }

            return $html;
        }
    }

    $FUNCS->register_udf( 'reverse_relation', 'ReverseRelation' );
