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
        var $items_posted = array();
        var $items_deleted = array();
        var $items_inserted = array();

        function handle_params( $params ){
            global $FUNCS, $AUTH;
            if( $AUTH->user->access_level < K_ACCESS_LEVEL_SUPER_ADMIN ) return;

            $attr = $FUNCS->get_named_vars(
                array(  'masterpage'=>'',
                    'has'=>'', /* one, many */
                    'reverse_has'=>'', /*  -do-  */
                    'folder'=>'',
                    'include_subfolders'=>'1',
                    'orderby'=>'', /* publish_date, page_title, page_name */
                    'order'=>'' /* desc, asc */
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
            $attr['order_dir'] = strtolower( trim($attr['order']) ); // 'order' is a 'core parameter' and will be stripped off
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
        function _render( $input_name, $input_id, $extra1='', $extra2='', $dynamic_insertion=0  ){
            global $FUNCS, $CTX, $DB;

            define( 'RELATION_URL', K_ADMIN_URL . 'addons/relation/' );
            $FUNCS->load_css( RELATION_URL . 'relation.css' );

            $rs = $DB->select( K_TBL_TEMPLATES, array('id'), "name='" . $DB->sanitize( $this->masterpage ). "'" );
            if( !count($rs) ) return 'Error: masterpage: "'.$this->masterpage.'" not found.';
            $tpl_id = $rs[0]['id'];

            // folder?
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
                    $fsql = "AND ";
                    if( $neg ) $fsql .= "NOT";
                    $fsql .= "(";
                    $sep = "";
                    foreach( $arr_folders as $k=>$v ){
                        $fsql .= $sep . "page_folder_id='" . $DB->sanitize( $v )."'";
                        $sep = " OR ";
                    }
                    $fsql .= ") ";
                }
            }
            else{
                if( !$include_subfolders ){
                    $fsql .= "AND page_folder_id='-1' ";
                }
            }

            if( $this->reverse_has=='one' ){
                // show only pages that are not already selected by others of the same relation field
                $pid = $this->page->id;
                $fid = $this->id;
                $sql = "SELECT p.id, p.page_title, p.publish_date"."\r\n";
                $sql .= "FROM ".K_TBL_PAGES." p"."\r\n";
                $sql .= "left outer join ".K_TBL_RELATIONS." rel on rel.cid = p.id"."\r\n";
                $sql .= "WHERE p.template_id='".$tpl_id."'"."\r\n";
                $sql .= "AND (rel.fid is null or rel.fid <> '".$fid."')"."\r\n";
                $sql .= $fsql;
                $sql .= "AND NOT publish_date = '0000-00-00 00:00:00'"."\r\n";

                $sql .= "UNION"."\r\n";

                // or those already associated with the page being edited
                $sql .= "SELECT p.id, p.page_title, p.publish_date"."\r\n";
                $sql .= "FROM ".K_TBL_PAGES." p"."\r\n";
                $sql .= "inner join ".K_TBL_RELATIONS." rel on rel.cid = p.id"."\r\n";
                $sql .= "WHERE p.template_id='".$tpl_id."'"."\r\n";
                $sql .= "AND rel.pid = '".$pid."' AND rel.fid = '".$fid."'"."\r\n";
                $sql .= $fsql;
                $sql .= "AND NOT publish_date = '0000-00-00 00:00:00'"."\r\n";
            }
            else{
                // show all pages for selection
                $sql = "SELECT id, page_title"."\r\n";
                $sql .= "FROM ".K_TBL_PAGES."\r\n";
                $sql .= "WHERE template_id='".$tpl_id."'"."\r\n";
                $sql .= $fsql;
                $sql .= "AND NOT publish_date = '0000-00-00 00:00:00'"."\r\n";
            }
            // order & orderby
            $orderby = trim( $this->orderby );
            if( $orderby!='publish_date' && $orderby!='page_title' && $orderby!='page_name' ) $orderby = 'publish_date';
            $order = trim( $this->order_dir );
            if( $order!='desc' && $order!='asc' ) $order = 'desc';
            $sql .= "ORDER BY ".$orderby." ".$order."\r\n";

            $result = @mysql_query( $sql, $DB->conn );
            if( !$result ){
                ob_end_clean();
                die( "Could not successfully run query: " . mysql_error( $DB->conn ) );
            }

            if( $this->has=='one' ){
                $selected = ( count($this->items_selected) ) ? $this->items_selected[0] : ''; // can have only one item selected
                $html .= '<select name="'.$input_name.'_chk" id="'.$input_id.'" >';
                $html .= '<option value="-">-- Select --</option>'; //TODO get label as parameter
                while( $row=mysql_fetch_row($result) ){
                    $html .= '<option value="'.$row[0].'"';
                    if( $selected && $row[0]== $selected ) $html .= '  selected="selected"';
                    $html .= '>'.$row[1].'</option>';
                }
                $html .= '</select>';
            }
            else{
                $html = '<ul class="checklist cl1">';
                $x=0;
                while( $row=mysql_fetch_row($result) ){
                    $class = ( ($x+1)%2 ) ? ' class="alt"' : '';
                    $selected = ( in_array($row[0], $this->items_selected) ) ? ' checked="checked"' : '';
                    $html .= '<li'.$class.'><label for="'.$input_name.'_chk_'.$x.'"><input id="'.$input_name.'_chk_'.$x.'" name="'.$input_name.'_chk[]" type="checkbox" value="'.$row[0].'"'.$selected.' /> '.$row[1].'</li>';
                    $x++;
                }
                $html .= '</ul>';
            }

                return $html;
            }

        // Handle posted data
        function store_posted_changes( $post_val ){
            if( $this->deleted ) return; // no need to store

            $input_name = 'f_'.$this->name.'_chk';
            $arr_posted = array();
            if( isset($_POST[$input_name]) ){
                if( !is_array($_POST[$input_name]) ){ // has='one'
                    if( $_POST[$input_name]!='-' ) $arr_posted[] = $_POST[$input_name];
                }
                else{
                    $arr_posted = $_POST[$input_name];
                }
            }

            $this->items_posted = $arr_posted;
            $this->items_deleted = array_diff( $this->items_selected, $arr_posted );
            $this->items_inserted = array_diff( $arr_posted, $this->items_selected );
            if( count($this->items_deleted) || count($this->items_inserted) ){
                $this->modified = true;
            }
            $this->items_selected = $this->items_posted;
        }

        // before save
        function validate(){ // for now only checking for 'required'
            global $FUNCS;

            if( $this->required && !count($this->items_posted) ){
                $this->err_msg = $FUNCS->t('required_msg');
                return false;
            }
            return true;
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
        function _update( $orig_values ){
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
                $rs = $DB->delete( K_TBL_RELATIONS, "fid='" . $this->id . "'" );
                if( $rs==-1 ) die( "ERROR: Unable to delete records from K_TBL_RELATIONS" );
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
            }
            return;
        }

        function _clone( $cloned_page_id, $cloned_page_title ){
            global $DB;
            /*
            // This works but somehow I feel it's better to get directly from the database
            if( count($this->items_posted) ){
                $pid = $cloned_page_id;
                $fid = $this->id;

                foreach( $this->items_posted as $cid ){
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
            */

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

        //////////////// Tags //////////////////////////////////////////////////
        function related_pages_handler( $params, $node ){
            global $CTX, $FUNCS, $TAGS, $PAGE, $DB;

            extract( $FUNCS->get_named_vars(
                array( 'field'=>'',
                ),
                $params)
            );
            $field = trim( $field );

            // get page_id (return if used in context of list_view)
            if( $CTX->get('k_is_page') ){
                $page_id = $CTX->get('k_page_id');
            }
            elseif( $CTX->get('k_is_list_page') ){ // non-clonable template
                $page_id = $PAGE->id;
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

        function reverse_related_pages_handler( $params, $node ){
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
            if( $CTX->get('k_is_page') ){
                $page_id = $CTX->get('k_page_id');
            }
            elseif( $CTX->get('k_is_list_page') ){ // non-clonable template
                $page_id = $PAGE->id;
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
    }

    // Register AFTER defining class to please ioncube loader
    $FUNCS->register_udf( 'relation', 'Relation' );
    $FUNCS->register_tag( 'related_pages', array('Relation', 'related_pages_handler'), 1, 1 ); // The helper tag that shows the related pages
    $FUNCS->register_tag( 'reverse_related_pages', array('Relation', 'reverse_related_pages_handler'), 1, 1 ); // -do in reverse-
