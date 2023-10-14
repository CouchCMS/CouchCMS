<?php
    if ( !defined('K_COUCH_DIR') ) die(); // cannot be loaded directly

    if( defined('K_ADMIN') ){ require_once( K_COUCH_DIR.'addons/mosaic/gc/gc.php' ); }
    require_once( K_COUCH_DIR.'addons/mosaic/globals/globals.php' );
    require_once( K_COUCH_DIR.'addons/bootstrap-grid/bootstrap-grid.php' );

    define( 'K_MOSAIC_STATUS_DEFAULT', '1' );
    define( 'K_MOSAIC_STATUS_ORPHAN', '2' );

    class KMosaic extends KUserDefinedField{
        var $items_selected = array();
        var $items_deleted = array();
        var $items_inserted = array();
        var $_schema = null;

        function __construct( $row, &$page, &$siblings ){
            // call parent
            parent::__construct( $row, $page, $siblings );

            // now for own logic
            $this->orig_data = array();
        }

        static function tag_handler( $params, $node ){
            global $CTX, $FUNCS, $PAGE, $TAGS, $AUTH;
            if( $AUTH->user->access_level < K_ACCESS_LEVEL_SUPER_ADMIN || defined('K_ADMIN') ){ return; } // nop within admin panel

            $attr = $FUNCS->get_named_vars(
                    array(  'name'=>'',
                ),
                $params
            );
            $name = trim( $attr['name'] );
            if( !$name ) {die("ERROR: Tag \"".$node->name."\" needs a 'name' attribute");}

            // fetch saved schema from actual editable region
            if( isset($PAGE->_fields[$name]) && $PAGE->_fields[$name]->k_type=='__mosaic'){
                $orig_schema = @$FUNCS->unserialize( $PAGE->_fields[$name]->schema );
            }
            if( !is_array($orig_schema) ) $orig_schema=array();

            // isolate 'cms:tile' child tags ..
            $children = $node->children;
            $tiles = array();
            foreach( $children as $child ){
                if( $child->type==K_NODE_TYPE_CODE && strtolower($child->name)=='tile' ){
                    $tiles[] = $child;
                }
            }

            // .. and execute them
            $arr_config = array( 'orig_schema'=>$orig_schema, 'mod_schema'=>array(), 'parent_field'=>$name, 'parent_tpl'=>$PAGE->tpl_name );
            $CTX->set_object( '__config', $arr_config );
            foreach( $tiles as $tile ){
                $tile->get_HTML();
            }

            // check if schema changed ..
            $arr_deleted = array();
            $mod_schema = $arr_config['mod_schema'];
            foreach( $orig_schema as $k=>$v ){
                if( !array_key_exists($k, $mod_schema) ){
                    $v['deleted'] = 1;
                    $arr_deleted[$k] = $v;
                }
            }
            // preserve the deleted elements in schema (just mark them as deleted)
            if( count($arr_deleted) ) $mod_schema = array_merge( $mod_schema, $arr_deleted );

            // create an editable region of type '__mosaic' with schema as its custom_param
            $schema = $FUNCS->serialize( $mod_schema );
            $params[] = array( 'lhs'=>'type', 'op'=>'=', 'rhs'=>'__mosaic' );
            $params[] = array( 'lhs'=>'hidden', 'op'=>'=', 'rhs'=>'1' );
            $params[] = array( 'lhs'=>'schema', 'op'=>'=', 'rhs'=>$schema );
            $_node = clone $node;
            $_node->children = array();
            $TAGS->editable( $params, $_node );
        }

        static function tile_handler( $params, $node ){
            global $CTX, $FUNCS, $PAGE, $AUTH, $DB;
            if( $AUTH->user->access_level < K_ACCESS_LEVEL_SUPER_ADMIN || defined('K_ADMIN') ){ return; } // nop within admin panel

            // locate the parent cms:mosaic tag ..
            $arr_config = &$CTX->get_object( '__config', 'mosaic' );
            if( !is_array($arr_config) ){ return; }

            extract( $FUNCS->get_named_vars(
                        array(
                              'name'=>'',
                              'label'=>'',
                              'auto_order'=>'1',
                              ),
                        $params)
                   );
            $name = trim( $name );
            if( !$name ){ die("ERROR: Tag \"".$node->name."\" needs a 'name' attribute"); }
            $label = trim( $label );
            $auto_order = ( $auto_order==0 ) ? 0 : 1;

            // find associated template
            if( array_key_exists($name, $arr_config['orig_schema']) ){
                $tpl_name = $arr_config['orig_schema'][$name]['tpl_name'];
            }
            else{
                // .. new entry. Create a template.
                $tpl_name = md5( $AUTH->hasher->get_random_bytes(16) ) . '_'. $name;
                $rs = $DB->insert( K_TBL_TEMPLATES, array('name'=>$tpl_name, 'description'=>'', 'clonable'=>1, 'executable'=>0, 'hidden'=>2, 'type'=>'tile') );
            }
            $rs = $DB->select( K_TBL_TEMPLATES, array('*'), "name='" . $DB->sanitize( $tpl_name ). "'" );
            if( !count($rs) ) die( "ERROR: Tag \"".$node->name."\" (".$name.") cannot find record in K_TBL_TEMPLATES" );

            // custom params saved with the template..
            $custom_params = array();
            $custom_params['_tile_name'] = $name;
            $custom_params['_tile_label'] = $label;
            $custom_params['_parent_tpl'] = $arr_config['parent_tpl'];
            $custom_params['_parent_field'] = $arr_config['parent_field'];
            for( $x=0; $x<count($params); $x++ ){
                $attr = strtolower(trim($params[$x]['lhs']));
                if( $attr[0]=='_' ){ // prefixed by '_'
                    $custom_params[$attr] = trim( $params[$x]['rhs'] );
                }
            }
            $custom_params = $FUNCS->serialize($custom_params);
            if( $custom_params != $rs[0]['custom_params'] ){
                $rs2 = $DB->update( K_TBL_TEMPLATES, array('custom_params'=>$custom_params), "id='" . $DB->sanitize( $rs[0]['id'] ). "'" );
                if( $rs2==-1 ) die( "ERROR: Tag: '.$node->name.' Unable to save modified template attribute" );
            }

            // create page to hold the child editable regions
            $pg = new KWebpage( $rs[0]['id'], null );
            if( $pg->error ) die( "ERROR: Tag \"".$node->name."\" (".$name.") cannot create page: ".$pg->err_msg );
            $orig_page = $PAGE;
            $PAGE = $pg;

            // execute supported child tags to create fields
            $order = 0;
            $children = $node->children;
            foreach( $children as $child ){
                if( $child->type==K_NODE_TYPE_CODE ){
                    $child_name = strtolower( $child->name );
                    if( in_array($child_name, array('editable', 'repeatable', 'config_list_view', 'config_form_view', 'func', 'embed')) ){ //supported tags

                        // set 'order' according to occurance (if not explicitly forbidden)
                        if( $auto_order && ($child_name=='editable' || $child_name=='repeatable') ){
                            $arr_tmp = array();
                            foreach( $child->attributes as $child_attr ){
                                if( $child_attr['name']!='order' ){
                                    $arr_tmp[] = $child_attr;
                                }
                            }
                            $arr_tmp[] = array( 'name'=>'order', 'op'=>'=', 'quote_type'=>"'", 'value'=>$order++, 'value_type'=>K_VAL_TYPE_LITERAL);
                            $child->attributes = $arr_tmp;
                        }

                        $child->get_HTML();
                    }
                }
            }

            // process deleted fields
            $PAGE->is_master = 0;
            $FUNCS->post_process_page();

            // restore original $PAGE
            $PAGE = $orig_page;

            // pass on info to parent
            $arr_config['mod_schema'][$name] = array( 'name'=>$name, 'label'=>$label, 'tpl_name'=>$tpl_name, 'tpl_id'=>$pg->tpl_id, 'deleted'=>0 );
        }

        static function show_handler( $params, $node ){
            global $FUNCS, $CTX, $DB;

            extract( $FUNCS->get_named_vars(
                    array( 'var'=>'',
                           'startcount'=>'',
                           'limit'=>'',
                           'offset'=>'',
                           'order'=>'asc',
                           'tiles'=>'', /*type(s) of tiles to fetch. Can have negation*/
                           'render_content'=>'0',
                    ),
                $params)
            );
            $var = trim( $var );
            $startcount = $FUNCS->is_int( $startcount ) ? intval( $startcount ) : 1;
            $limit = $FUNCS->is_non_zero_natural( $limit ) ? intval( $limit ) : 1000;
            $offset = $FUNCS->is_natural( $offset ) ? intval( $offset ) : 0;
            $order = strtolower( trim($order) );
            if( $order!='desc' && $order!='asc' ) $order='asc';
            $types = trim( $tiles );
            $render_content = ( $render_content==1 ) ? 1 : 0;

            if( $var ){
                // get the data array from CTX
                $obj = &$CTX->get_object( $var );

                if( $obj ){
                    $rows = $obj['ids'];
                    if( !count($rows) ){ return; }
                    $tiles = $obj['tiles'];

                    if( $order=='desc' ){ $rows = array_reverse($rows); }

                    if( $types && count($tiles) ){
                        // Negation?
                        $neg_types = 0;
                        $pos = strpos( strtoupper($types), 'NOT ' );
                        if( $pos!==false && $pos==0 ){
                            $neg_types = 1;
                            $types = trim( substr($types, strpos($types, ' ')) );
                        }
                        $arr_types = array_filter( array_map("trim", explode(',', $types)) );

                        if( count($arr_types) ){
                            $tpls = array();
                            $page_ids = trim( implode(',', $rows) );
                            $sql = "SELECT id, template_id FROM ".K_TBL_PAGES." WHERE id IN (".$page_ids.")";
                            $rs = @mysql_query( $sql, $DB->conn );
                            if( $rs ){
                                while( $rec = mysql_fetch_row($rs) ) {
                                    $tpls[$rec[0]] = $rec[1];
                                }
                                mysql_free_result( $rs );
                            }

                            $tmp = array();
                            for( $x=0; $x<count($rows); $x++ ){
                                if( isset($rows[$x], $tpls) && array_key_exists($tpls[$rows[$x]], $tiles) ){
                                    $tpl = $tiles[$tpls[$rows[$x]]]['name'];

                                    if( $neg_types ){
                                        if( in_array($tpl, $arr_types) ){
                                            continue;
                                        }
                                    }
                                    else{
                                        if( !in_array($tpl, $arr_types) ){
                                            continue;
                                        }
                                    }
                                    $tmp[] = $rows[$x];
                                }
                            }
                            $rows = $tmp;
                        }
                    }

                    // loop through the rows..
                    $total_rows = count($rows) - $offset;
                    if( $limit < $total_rows ) $total_rows = $limit;

                    $tile_count = array();
                    for( $x=$offset; $x<$total_rows+$offset; $x++ ){

                        // .. and set each page representing the row in context
                        $rs = $DB->select( K_TBL_PAGES, array('template_id'), "id='" . $DB->sanitize( $rows[$x] ). "'" );
                        if( count($rs) ){
                            $pg = new KWebpage( $rs[0]['template_id'], $rows[$x] );
                            if( !$pg->error ){
                                $pg->set_context();
                                $CTX->set_object( 'k_bound_page', $pg );

                                if( count($tiles) ){
                                    $CTX->set( 'k_tile_name', $tiles[$pg->tpl_id]['name'] );
                                    $CTX->set( 'k_tile_label', $tiles[$pg->tpl_id]['label'] );
                                    $CTX->set( 'k_tile_is_deleted', $tiles[$pg->tpl_id]['deleted'] );
                                }
                                else{
                                    $CTX->set( 'k_tile_name', $pg->tpl__tile_name );
                                    $CTX->set( 'k_tile_label', $pg->tpl__tile_label );
                                    $CTX->set( 'k_tile_is_deleted', '0' );
                                }
                                $CTX->set( 'k_edit_link', K_ADMIN_URL . K_ADMIN_PAGE."?o=".$pg->tpl_name."&q=clone/".$FUNCS->create_nonce('edit_page_'.$pg->id)."/".$pg->id );

                                $CTX->set( 'k_count', $x - $offset + $startcount );
                                $CTX->set( 'k_total_rows', $total_rows );
                                $CTX->set( 'k_first_row', ($x==$offset) ? '1' : '0' );
                                $CTX->set( 'k_last_row', ($x==$total_rows+$offset-1) ? '1' : '0' );

                                if( !array_key_exists($pg->tpl_id, $tile_count) ){
                                    $tile_count[$pg->tpl_id] = 0;
                                }
                                $CTX->set( 'k_tile_count', $tile_count[$pg->tpl_id]++ );

                                // get content to show ..
                                $content = ( $render_content ) ? KMosaic::_get_default_content( $pg->tpl_id ) : '';
                                $CTX->set( 'k_content', $content );

                                // and call the children providing each row's data
                                foreach( $node->children as $child ){
                                    $html .= $child->get_HTML();
                                }

                                $pg->destroy(); // release the memory held by fields
                            }
                        }
                    }
                }

                return $html;
            }
        }

        static function _get_default_content( $tpl_id, $dynamic_insertion=0 ){
            global $FUNCS, $CTX;
            static $cache = array();
            $content = '';

            // is any specified through cms:config_list_view?
            if( !isset($cache[$tpl_id]) ){
                if( isset($FUNCS->cached_templates[$tpl_id]) ){
                    $arr_config = @unserialize( base64_decode($FUNCS->cached_templates[$tpl_id]['config_list']) );
                    if( is_array($arr_config) ){
                        $content = $arr_config['arr_fields']['k_content']['content'];
                    }
                }
                $cache[$tpl_id] = $content;
            }
            else{
                $content = $cache[$tpl_id];
            }

            $CTX->set( 'k_mosaic_field_dynamic_insertion', $dynamic_insertion );

            if( is_array($content) ){
                foreach( $content as $child ){
                    $html .= $child->get_HTML();
                }
            }
            else{ // if not, use default
                $html = $FUNCS->render( 'content_row_mosaic' );
            }

            return $html;
        }

        static function handle_params( $params ){
            global $FUNCS;
            $attr = $FUNCS->get_named_vars(
                array(
                    'schema'=>'',
                ),
                $params
            );
            return $attr;
        }

        // Load from database
        function store_data_from_saved( $data ){
            global $DB;

            $pid = $this->page->id;
            $fid = $this->id;

            $sql = "SELECT r.cid \r\n";
            $sql .= "FROM ".K_TBL_RELATIONS." r\r\n";
            $sql .= "INNER JOIN ".K_TBL_PAGES." p ON (r.cid = p.id)\r\n";
            $sql .= "INNER JOIN ".K_TBL_TEMPLATES." t ON (p.template_id = t.id)\r\n";
            $sql .= "WHERE r.pid = '".$pid."' AND r.fid = '".$fid."' AND t.deleted = '0'\r\n";
            $sql .= "ORDER BY r.weight asc\r\n";

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

        function _render( $input_name, $input_id, $extra='', $dynamic_insertion=0 ){
            global $FUNCS;

            $html = $FUNCS->render( 'field_mosaic', $this, $input_name, $input_id, $extra, $dynamic_insertion );
            return $html;
        }

        // Handle posted data
        function store_posted_changes( $post_val ){
            global $FUNCS, $Config, $AUTH;
            if( $this->deleted || $this->k_inactive ) return; // no need to store

            // rearrange posted rows
            $data = is_array( $post_val ) ? $post_val : array();
            if( count($data) ){
                $sort_field = '_f_'.$this->name.'_sortorder';
                if( strlen(trim($_POST[$sort_field])) ){
                    $arr_sort = array_map( "trim", explode( ',', $_POST[$sort_field] ) );
                    $tmp = array(); $x = 0;
                    foreach( $arr_sort as $pos ){
                        if( is_numeric($pos) && isset($data[$pos]) ){
                            $tmp[$x++] = $data[$pos];
                        }
                    }
                    $data = $tmp;
                }
            }

            $arr_posted = array();
            for( $x=0; $x<count($data); $x++ ){
                if( $FUNCS->is_non_zero_natural($data[$x]['pid']) ) $arr_posted[]= $data[$x]['pid'];
            }

            $this->items_deleted = array_diff( $this->items_selected, $arr_posted );
            $this->items_inserted = array_diff( $arr_posted, $this->items_selected );
            if( $this->items_selected !== $arr_posted ){
                $this->modified = true;
            }
            $this->items_selected = $arr_posted;
        }

        function is_empty(){
            return !count($this->items_selected);
        }

        // Output to front-end via $CTX
        function get_data( $for_ctx=0 ){
            global $CTX;

            if( $for_ctx ){
                $schema = $this->_get_schema();
                $tiles = array();

                foreach( $schema as $k=>$v ){
                    $tiles[$v['tpl_id']] = $schema[$k];
                }

                $rows = array();
                $rows['ids'] = $this->items_selected;
                $rows['tiles'] = $tiles;
                $CTX->set_object( $this->name, $rows );
            }
        }

        // Save to database.
        function get_data_to_save(){
            global $FUNCS, $DB;

            list( $being_cloned ) = func_get_args();
            if( $being_cloned ) return;

            $pid = $this->page->id;
            $fid = $this->id;

            // delete the removed items
            if( count($this->items_deleted) ){
                foreach( $this->items_deleted as $cid ){
                    $rs = $DB->delete( K_TBL_RELATIONS, "pid = '".$pid."' AND fid = '".$fid."' AND cid='".$DB->sanitize( $cid )."'" );
                    if( $rs==-1 ) die( "ERROR: Mosaic unable to delete record from K_TBL_RELATIONS" );

                    // decrease ref_count
                    $this->_update_ref_count( $cid );

                    // signal to GC
                    $FUNCS->set_setting( 'gc_mosaic_is_dirty', 1 );
                }
            }

            // insert new items and update existing ones
            for( $x=0; $x<count($this->items_selected); $x++ ){
                $cid = $this->items_selected[$x];
                if( in_array($cid, $this->items_inserted) ){
                    $rs = $DB->insert( K_TBL_RELATIONS, array(
                        'pid'=>$pid,
                        'fid'=>$fid,
                        'cid'=>$cid,
                        'weight'=>$x
                        )
                    );
                    if( $rs!=1 ) die( "ERROR: Mosaic failed to insert record in K_TBL_RELATIONS" );

                    // increase ref_count
                    $this->_update_ref_count( $cid, 1 );
                }
                else{
                    $rs = $DB->update( K_TBL_RELATIONS, array('weight'=>$x), "pid='".$DB->sanitize( $pid )."' AND fid='".$DB->sanitize( $fid )."' AND cid='".$DB->sanitize( $cid )."'" );
                    if( $rs==-1 ) die( "ERROR: Mosaic unable to update data in K_TBL_DATA" );
                }
            }
            return;
        }

        // Search value
        function get_search_data(){
            global $DB;

            $search_data = '';
            if( count($this->items_selected) ){
                $page_ids = trim( implode(',', $this->items_selected) );
                $sql = "SELECT ft.content FROM ".K_TBL_FULLTEXT." ft WHERE ft.page_id IN (".$page_ids.")";
                $rs = $DB->raw_select( $sql );
                foreach( $rs as $rec ){
                    $search_data .= $rec['content'] . ' ';
                }
            }
            return $search_data;
        }

        // Called either from a page being deleted
        // or when this field's definition gets removed from a template (in which case the $page_id param would be '-1' )
        function _delete( $page_id ){
            global $FUNCS, $DB;

            if( $page_id==-1 ){
                // mark all tile templates as deleted
                $schema = $this->_get_schema();
                foreach( $schema as $tile ){
                    $rs = $DB->update( K_TBL_TEMPLATES, array('deleted'=>1), "id='" . $DB->sanitize( $tile['tpl_id'] ). "'" );
                    if( $rs==-1 ) die( "Unable to mark template as deleted" );
                }

                // signal to GC
                $FUNCS->set_setting( 'gc_mosaic_is_dirty', 1 );
            }
            else{
                // decrease ref_count of all child pages
                $sql = "UPDATE ". K_TBL_PAGES." p\r\n";
                $sql .= "INNER JOIN ".K_TBL_RELATIONS." r\r\n";
                $sql .= "ON (p.id = r.cid)\r\n";
                $sql .= "SET p.ref_count = p.ref_count - 1\r\n";
                $sql .= "WHERE r.pid='". $DB->sanitize( $page_id ) ."' AND r.fid='".$this->id."'";

                $DB->_query( $sql );
                $rs = $DB->rows_affected = mysql_affected_rows( $DB->conn );
                if( $rs==-1 ) die( "ERROR: Mosaic unable to update ref_count in K_TBL_PAGES" );

                // Remove all records from the relation table for the page being deleted
                $rs = $DB->delete( K_TBL_RELATIONS, "pid='" . $DB->sanitize( $page_id ). "' AND fid='".$this->id."'" );
                if( $rs==-1 ) die( "ERROR: Unable to delete records from K_TBL_RELATIONS" );

                // signal to GC
                $FUNCS->set_setting( 'gc_mosaic_is_dirty', 1 );
            }
            return;
        }

        function _clone( $cloned_page_id, $cloned_page_title ){
            global $DB;

            $pid = $this->page->id;
            $fid = $this->id;

            $rs = $DB->select( K_TBL_RELATIONS, array('cid', 'weight'), "pid='" . $DB->sanitize( $pid ). "' AND fid='" . $DB->sanitize( $fid ). "'" );
            if( count($rs) ){
                foreach( $rs as $row ){
                    $cid = $row['cid'];
                    $weight = $row['weight'];

                    $rs2 = $DB->insert( K_TBL_RELATIONS, array(
                        'pid'=>$cloned_page_id,
                        'fid'=>$fid,
                        'cid'=>$cid,
                        'weight'=>$weight
                        )
                    );
                    if( $rs2!=1 ) die( "ERROR: Failed to insert record in K_TBL_RELATIONS" );

                    // increase ref_count
                    $this->_update_ref_count( $cid, 1 );
                }
            }
        }

        function _unclone( &$cloned_field ){
            global $DB;

            // Prepare for the impending get_data_to_save() via $PAGE->save()
            $pid = $cloned_field->page->id;
            $fid = $cloned_field->id;

            $sql = "SELECT r.cid \r\n";
            $sql .= "FROM ".K_TBL_RELATIONS." r\r\n";
            $sql .= "INNER JOIN ".K_TBL_PAGES." p ON (r.cid = p.id)\r\n";
            $sql .= "INNER JOIN ".K_TBL_TEMPLATES." t ON (p.template_id = t.id)\r\n";
            $sql .= "WHERE r.pid = '".$pid."' AND r.fid = '".$fid."' AND t.deleted = '0'\r\n";
            $sql .= "ORDER BY r.weight asc\r\n";

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
            $this->items_selected = $arr_recs;
            return;
        }

        function _update_ref_count( $cid, $increase=0 ){
            global $DB;

            $op = ( $increase )? '+' : '-';
            $sql = "UPDATE " . K_TBL_PAGES . " SET ref_count = ref_count ".$op." 1, status = ".K_MOSAIC_STATUS_DEFAULT." WHERE id='". $DB->sanitize( $cid ) ."'";

            $DB->_query( $sql );
            $rs = $DB->rows_affected = mysql_affected_rows( $DB->conn );
            if( $rs==-1 ) die( "ERROR: Mosaic unable to update ref_count in K_TBL_PAGES" );
        }

        function _get_schema(){
            global $FUNCS;

            if( is_null($this->_schema) ){
                $schema = @$FUNCS->unserialize( $this->schema );
                if( !is_array($schema) ) $schema=array();

                foreach( $schema as $k=>$v ){
                    if( $v['label']=='' ){
                        $schema[$k]['label']=$v['name'];
                    }
                }

                $this->_schema = $schema;
            }

            return $this->_schema;
        }

        // renderable theme functions
        static function register_renderables(){
            global $FUNCS;

            $FUNCS->register_render( 'field_mosaic', array('template_path'=>K_ADDONS_DIR.'mosaic/theme/', 'template_ctx_setter'=>array('KMosaic', '_render_mosaic')) );
            $FUNCS->register_render( 'mosaic_tile_deleted', array('template_path'=>K_ADDONS_DIR.'mosaic/theme/', 'template_ctx_setter'=>array('KMosaic', '_render_mosaic_tile_deleted')) );
            $FUNCS->register_render( 'content_form_mosaic', array('template_path'=>K_ADDONS_DIR.'mosaic/theme/') );
            $FUNCS->register_render( 'content_row_mosaic', array('template_path'=>K_ADDONS_DIR.'mosaic/theme/') );

            $fields = array( 'text', 'textarea', 'richtext', 'nicedit', 'radio', 'checkbox', 'dropdown', 'image', 'file', 'relation', 'securefile', 'datetime', '__repeatable' );
            foreach( $fields as $field ){
                $renderable_tpl = ( $field=='__repeatable') ? 'display_field_repeatable' : '';
                $FUNCS->register_render( 'display_field_'.$field,
                    array('template_path'=>K_ADDONS_DIR.'mosaic/theme/fields/',
                          'template_ctx_setter'=>array('KMosaicCTX', '_render_fields'),
                          'include_file'=>K_ADDONS_DIR.'mosaic/theme/fields/ctx-setters.php',
                          'renderable'=>$renderable_tpl,
                    )
                );
            }
        }

        static function _render_mosaic( $f, $input_name, $input_id, $extra, $dynamic_insertion ){
            global $FUNCS, $CTX, $DB, $AUTH;
            static $done=0;
            $has_deleted_tile = 0;

            KField::_set_common_vars( $f->k_type, $input_name, $input_id, $extra, $dynamic_insertion, $f->simple_mode );

            // the rows
            $f->get_data( 1 ); // piggybacking

            // the template selector buttons
            $schema = $f->_get_schema();
            foreach( $schema as $k=>$v ){
                // set link
                //$link = $FUNCS->generate_route( $v['tpl_name'], 'create_view', array('nonce'=>$FUNCS->create_nonce('create_page_'.$v['tpl_id'])) ); // won't work when rendered outside admin-panel (i.e. on front-end in dbf)
                $link = K_ADMIN_URL . K_ADMIN_PAGE."?o=".$v['tpl_name']."&q=create/".$FUNCS->create_nonce('create_page_'.$v['tpl_id']);
                $schema[$k]['link']=$link;

                if( $v['deleted'] && $AUTH->user->access_level >= K_ACCESS_LEVEL_SUPER_ADMIN && defined('K_ADMIN') ){
                    $has_deleted_tile = 1;
                }
            }
            $CTX->set( 'templates', $schema, '', 1 );
            $CTX->set( 'k_has_deleted_tile', $has_deleted_tile );

            if( !$done ){
                $FUNCS->render( 'repeatable_assets' ); // piggyback on repeatable-region's JS/CSS
                $CTX->set( 'k_add_js', '1' );
                $done=1;
            }
            else{
                $CTX->set( 'k_add_js', '0' );
                $CTX->set( 'k_add_repeatable_js', '0' );
            }
        }


        static function _render_mosaic_tile_deleted(){
            global $FUNCS, $CTX;

            static $done=0;
            if( !$done ){
                $CTX->set( 'k_add_js_for_mosaic_tile_deleted', '1' );
                $done=1;
            }
            else{
                $CTX->set( 'k_add_js_for_mosaic_tile_deleted', '0' );
            }
        }

        static function _delete_tile( $fid, $nonce ){
            global $FUNCS, $DB;

            $FUNCS->validate_nonce( 'delete_tile_' . $fid, $nonce );

            $rs = $DB->select( K_TBL_FIELDS, array('*'), "id='" . $DB->sanitize( $fid ). "'" );
            if( count($rs) ){
                $DB->begin();

                $rec = $rs[0];
                if( $rec['k_type']=='__mosaic' ){
                    $f = new KMosaic( $rec, new stdClass(), new stdClass() );

                    // mark removed tile templates as deleted
                    $mod_schema = array();
                    $schema = $f->_get_schema();
                    foreach( $schema as $key=>$tile ){
                        if( $tile['deleted'] ){
                            $rs = $DB->update( K_TBL_TEMPLATES, array('deleted'=>1), "id='" . $DB->sanitize( $tile['tpl_id'] ). "'" );
                            if( $rs==-1 ) die( "Unable to mark template as deleted" );
                        }
                        else{
                            $mod_schema[$key]=$tile;
                        }
                    }

                    // persist the modified schema
                    $custom_params = $rec['custom_params'];
                    if( is_string($custom_params) && strlen($custom_params) ){
                        $arr_params = $FUNCS->unserialize($custom_params);
                    }
                    if( !is_array($arr_params) ) $arr_params=array();

                    $arr_params['schema'] = $FUNCS->serialize( $mod_schema );
                    $rs = $DB->update( K_TBL_FIELDS, array('custom_params'=>$FUNCS->serialize($arr_params)), "id='" . $DB->sanitize($fid). "'" );
                    if( $rs==-1 ) die( "ERROR: Unable to save modified schema in field" );

                    // signal to GC
                    $FUNCS->set_setting( 'gc_mosaic_is_dirty', 1 );

                    // wrap up
                    $DB->commit( 1 );
                    die( 'OK' );
                }
                else{
                    die( 'Field is not type mosaic' );
                }
            }
            else{
                die( 'Field not found' );
            }
        }

        static function register_gc_jobs(){
            global $FUNCS, $GC;

            // if_dirty or last_run more than 30 minutes ago, add async job
            $is_dirty = $FUNCS->get_setting( 'gc_mosaic_is_dirty', 0 );
            $last_run = $FUNCS->get_setting( 'gc_mosaic_last_run', 0 );
            $cur_time = time();
            $interval = 30 * 60; // 30 minutes in seconds

            if( $is_dirty || $last_run + $interval < $cur_time ){
                $GC->log( 'Adding Job' );

                $GC->add_job( array('KMosaic', 'process_gc') );

                $FUNCS->set_setting( 'gc_mosaic_is_dirty', 0 );
                $FUNCS->set_setting( 'gc_mosaic_last_run', $cur_time );
            }
        }

        static function process_gc( $pid, $rnd='' ){ // process all deletes in the background
            global $FUNCS, $GC, $DB;

            $arr_sql = array();

            // 0. all pages of deleted tiles
            $arr_sql[] = "
                SELECT p.id, p.template_id, t.type from ".K_TBL_PAGES." p
                INNER JOIN ".K_TBL_TEMPLATES." t ON p.template_id = t.id
                WHERE t.hidden = 2 AND t.deleted = 1";

            // 1. all deleted tiles
            $arr_sql[] = "
                SELECT t.id FROM ".K_TBL_TEMPLATES." t
                LEFT OUTER JOIN ".K_TBL_PAGES." p ON t.id = p.template_id
                WHERE p.id IS NULL AND t.deleted = 1";

            // 2. all unreferenced pages that have been saved into mosaic atleast once
            $arr_sql[] = "
                SELECT p.id, p.template_id from ".K_TBL_PAGES." p
                WHERE p.status = ".K_MOSAIC_STATUS_DEFAULT." AND p.ref_count = 0";

            // 3. all unreferenced pages that have never been saved into mosaic (wait for 24 hours before removing them)
            $limit = date( 'Y-m-d H:i:s', strtotime($FUNCS->get_current_desktop_time()) - 24*60*60 );
            $arr_sql[] = "
                SELECT p.id, p.template_id from ".K_TBL_PAGES." p
                WHERE p.status = ".K_MOSAIC_STATUS_ORPHAN." AND p.ref_count = 0 AND modification_date < '".$limit."'";

            for( $x=0; $x<count($arr_sql); $x++ ){
                $sql = $arr_sql[$x];
                $GC->log( $pid . ":\r\n ".$sql );

                $result = @mysql_query( $sql, $DB->conn );
                if( !$result ){
                    $GC->log( "Could not successfully run query: " . mysql_error( $DB->conn ) );
                    return; // error in query
                }

                while( $row=mysql_fetch_assoc($result) ){
                    if( $x==1 ){
                        // remove template along with the fields and folders defined for it
                        $DB->delete( K_TBL_FIELDS, "template_id='" . $DB->sanitize( $row['id'] ). "'" );
                        $DB->delete( K_TBL_FOLDERS, "template_id='" . $DB->sanitize( $row['id'] ). "'" );
                        $DB->delete( K_TBL_TEMPLATES, "id='" . $DB->sanitize( $row['id'] ). "'" );

                        $GC->log( $pid . ":\r\n Template deleted: ".$row['id'] );
                    }
                    else{
                        $pg = new KWebpage( $row['template_id'], $row['id'] );
                        if( !$pg->error ){
                            if( $x==0 ){
                                // delete the page record from relations table for tiles
                                if( $row['type']=='tile' ){
                                    $DB->delete( K_TBL_RELATIONS, "cid='".$DB->sanitize( $row['id'] )."'" );
                                }
                            }

                            // delete page
                            $pg->delete();
                        }

                        $GC->log( $pid . ":\r\nPage deleted: ".$row['template_id'].'-'.$row['id'] );
                    }

                    // make sure it is not time to quit before continuing
                    if( !$GC->can_continue() ){
                        $GC->log( $pid . ":\r\nTime to quit! " );
                        return 1; // return true to be kept in queue so to be called again
                    }
                }
            }

            // if we are here, job is finished - return nothing to be removed from queue
            $GC->log( $pid . ":\r\nAsking to be removed " );
        }

        static function alter_register_routes( $tpl, &$default_routes ){
            if( $tpl['type']!='tile' ) return;

            if( array_key_exists('list_view', $default_routes) ){
                unset( $default_routes['list_view'] );
            }

            if( array_key_exists('edit_view', $default_routes) ){
                $default_routes['edit_view']['include_file'] = K_ADDONS_DIR.'mosaic/edit-mosaic.php';
                $default_routes['edit_view']['class'] = 'KMosaicAdmin';
                $default_routes['edit_view']['module'] = 'mosaic';

                // copy to create a route for 'clone_view' ..
                $tmp = $default_routes['edit_view'];
                $tmp['path'] = 'clone/{:nonce}/{:id}';
                $tmp['filters'] = 'KMosaicAdmin::resolve_page=edit';
                $default_routes['clone_view'] =  $tmp;
            }

            if( array_key_exists('create_view', $default_routes) ){
                $default_routes['create_view']['include_file'] = K_ADDONS_DIR.'mosaic/edit-mosaic.php';
                $default_routes['create_view']['class'] = 'KMosaicAdmin';
                $default_routes['create_view']['module'] = 'mosaic';
            }
        }

        static function register_routes(){
            global $FUNCS;

            $route = array(
                'name'=>'delete_tile',
                'path'=>'delete_tile/{:nonce}/{:fid}',
                'constraints'=>array(
                    'nonce'=>'([a-fA-F0-9]{32})',
                    'fid'=>'([1-9]\d*)',
                ),
                'include_file'=>K_ADDONS_DIR.'mosaic/mosaic.php',
                'action'=>array('KMosaic', '_delete_tile'),
            );

            $FUNCS->register_route( 'mosaic', $route );
        }

    }// end class

    // Register
    $FUNCS->register_udf( '__mosaic', 'KMosaic' ); // The UDF
    $FUNCS->register_tag( 'mosaic', array('KMosaic', 'tag_handler'), 1, 0 ); // The helper 'shim' tag that helps create the above UDF
    $FUNCS->register_tag( 'tile', array('KMosaic', 'tile_handler') );
    $FUNCS->register_tag( 'show_mosaic', array('KMosaic', 'show_handler'), 1, 1 ); // The helper tag that shows the variables via CTX
    $FUNCS->add_event_listener( 'register_renderables',  array('KMosaic', 'register_renderables') );
    $FUNCS->add_event_listener( 'register_gc_jobs',  array('KMosaic', 'register_gc_jobs') );

    // routes
    if( defined('K_ADMIN') ){
        $FUNCS->add_event_listener( 'alter_register_pages_routes',  array('KMosaic', 'alter_register_routes') );
        $FUNCS->add_event_listener( 'register_admin_routes', array('KMosaic', 'register_routes') );
    }
