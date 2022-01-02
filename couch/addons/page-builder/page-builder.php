<?php
    if ( !defined('K_COUCH_DIR') ) die(); // cannot be loaded directly

    define( 'K_CACHE_PB', '1' );
    define( 'PB_CACHE_DIR', K_COUCH_DIR . 'cache/pb/' );

    class KPageBuilder extends KMosaic{

        static $tpls = array();

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

            // .. execute 'cms:section' child tags
            $arr_config = array( 'mod_schema'=>array() );
            $CTX->set_object( '__config', $arr_config );
            foreach( $node->children as $child ){
                $child->get_HTML();
            }

            // get the schema ..
            $mod_schema = $arr_config['mod_schema'];

            // create an editable region of type '__pagebuilder' with schema as its custom_param
            $schema = $FUNCS->serialize( $mod_schema );
            $params[] = array( 'lhs'=>'type', 'op'=>'=', 'rhs'=>'__pagebuilder' );
            $params[] = array( 'lhs'=>'hidden', 'op'=>'=', 'rhs'=>'1' );
            $params[] = array( 'lhs'=>'schema', 'op'=>'=', 'rhs'=>$schema );
            $_node = clone $node;
            $_node->children = array();
            $TAGS->editable( $params, $_node );
        }

        static function section_handler( $params, $node ){
            global $CTX, $FUNCS, $PAGE, $AUTH;
            if( $AUTH->user->access_level < K_ACCESS_LEVEL_SUPER_ADMIN || defined('K_ADMIN') ){ return; } // nop within admin panel

            // locate the parent cms:pagebuilder tag ..
            $arr_config = &$CTX->get_object( '__config', 'pagebuilder' );
            if( !is_array($arr_config) ){ return; }

            extract( $FUNCS->get_named_vars(
                        array(
                              'name'=>'',
                              'label'=>'',
                              'mosaic'=>'',
                              'masterpage'=>'',
                              'page'=>'',
                              'tiles'=>'',
                              'limit'=>'',
                              'offset'=>'',
                              'order'=>'',
                              ),
                        $params)
                   );

            // extract info..
            $name = trim( $name );
            if( !$name ){ die("ERROR: Tag \"".$node->name."\" needs a 'name' attribute"); }
            $label = trim( $label );
            $mosaic = trim( $mosaic );
            if( !$mosaic ){ die("ERROR: Tag \"".$node->name."\" needs a 'mosaic' attribute"); }
            $masterpage = trim( $masterpage );
            if( !$masterpage ){ die("ERROR: Tag \"".$node->name."\" needs a 'masterpage' attribute"); }
            $page = trim( $page );
            $tiles = trim( $tiles );
            $limit = $FUNCS->is_non_zero_natural( $limit ) ? intval( $limit ) : 0;
            $offset = $FUNCS->is_natural( $offset ) ? intval( $offset ) : 0;
            $order = strtolower( trim($order) );
            if( $order!='desc' && $order!='asc' ) $order='asc';

            // pass on to parent
            $arr_config['mod_schema'][$name] = array( 'name'=>$name, 'label'=>$label, 'mosaic'=>$mosaic, 'masterpage'=>$masterpage, 'page'=>$page,
                                                      'tiles'=>$tiles, 'limit'=>$limit, 'offset'=>$offset, 'order'=>$order
                                                    );
        }

        // handler for 'cms:show_mosaic_ex' and 'cms:show_pagebuilder' tags ..
        static function show_handler( $params, $node ){
            global $FUNCS, $CTX, $DB;

            extract( $FUNCS->get_named_vars(
                    array( 'var'=>'',
                           'startcount'=>'',
                           'limit'=>'',
                           'offset'=>'',
                           'order'=>'asc',
                           'tiles'=>'', /*type(s) of tiles to fetch. Can have negation*/
                           'show_overlay'=>'1',
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
            $show_overlay = ( $show_overlay==0 ) ? 0 : 1;

            if( $var ){
                // get the data array from CTX
                $obj = &$CTX->get_object( $var );

                if( $obj ){
                    $rows = $obj['ids'];
                    if( !count($rows) ){ return; }
                    $tiles = $obj['tiles'];

                    if( $order=='desc' ){ $rows = array_reverse($rows); }

                    $arr_pages = $arr_tpls = array();
                    $page_ids = trim( implode(',', $rows) );
                    $sql = "SELECT id, template_id FROM ".K_TBL_PAGES." WHERE id IN (".$page_ids.")";
                    $rs = @mysql_query( $sql, $DB->conn );
                    if( $rs ){
                        while( $rec = mysql_fetch_row($rs) ) {
                            $arr_pages[$rec[0]] = $rec[1];
                            if( !in_array($rec[1],$arr_tpls) ){
                                $arr_tpls[] = $rec[1];
                            }
                        }
                        mysql_free_result( $rs );

                        if( $node->name=='show_mosaic_ex' ){
                            $tpl_ids = trim( implode(',', $arr_tpls) );
                            $arr_tpls = array();
                            $sql = "SELECT id, name, custom_params FROM ".K_TBL_TEMPLATES." WHERE id IN (".$tpl_ids.")";
                            $rs = @mysql_query( $sql, $DB->conn );
                            if( $rs ){
                                while( $rec = mysql_fetch_array($rs, MYSQL_ASSOC) ){
                                    $custom_params=array();
                                    if( strlen($rec['custom_params']) ){
                                        $custom_params = $FUNCS->unserialize($rec['custom_params']);
                                        if( !is_array($custom_params) ) $custom_params=array();
                                    }
                                    if( $custom_params['_tile_label']=='' ){ $custom_params['_tile_label']=$custom_params['_tile_name']; }
                                    $arr_tpls[$rec['id']] = array( 'tpl_name'=>$rec['name'], 'tile_name'=>$custom_params['_tile_name'], 'tile_label'=>$custom_params['_tile_label'], 'tile_height'=>$custom_params['_pb_height'] );
                                }
                                mysql_free_result( $rs );
                            }
                        }
                    }

                    if( $types && count($tiles) ){ // type 'pagebuilder' will not have tiles
                        // Negation?
                        $neg_types = 0;
                        $pos = strpos( strtoupper($types), 'NOT ' );
                        if( $pos!==false && $pos==0 ){
                            $neg_types = 1;
                            $types = trim( substr($types, strpos($types, ' ')) );
                        }
                        $arr_types = array_filter( array_map("trim", explode(',', $types)) );

                        if( count($arr_types) ){
                            $tmp = array();
                            for( $x=0; $x<count($rows); $x++ ){
                                if( isset($rows[$x], $arr_pages) && array_key_exists($arr_pages[$rows[$x]], $arr_tpls) ){
                                    $tpl = $arr_tpls[$arr_pages[$rows[$x]]]['tile_name'];

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

                        // .. and set each row in context
                        $page_id = $rows[$x];
                        $tpl_id = $arr_pages[$page_id];

                        if( $node->name=='show_mosaic_ex' ){
                            $tpl = $arr_tpls[$tpl_id];
                            $tpl_name = $tpl['tpl_name'];
                            $tile_height = $tpl['tile_height'];
                            $tile_name = $tpl['tile_name'];
                            $tile_label = $tpl['tile_label'];
                            $tile_deleted = ( count($tiles) ) ? $tiles[$tpl_id]['deleted'] : 0;

                            $CTX->set( 'k_page_id', $page_id );
                            $CTX->set( 'k_template_id', $tpl_id );
                            $CTX->set( 'k_template__pb_height', $tile_height );
                            $CTX->set( 'k_tile_name', $tile_name );
                            $CTX->set( 'k_tile_label', $tile_label );
                            $CTX->set( 'k_tile_is_deleted', $tile_deleted );
                            $CTX->set( 'k_edit_link', K_ADMIN_URL . K_ADMIN_PAGE."?o=".$tpl_name."&q=clone/".$FUNCS->create_nonce('edit_page_'.$page_id)."/".$page_id );
                            $CTX->set( 'k_create_link', K_ADMIN_URL . K_ADMIN_PAGE."?o=pb&q=create_tile/".$FUNCS->create_nonce('create_tile_'.$page_id)."/$tpl_id/$page_id" );

                            $CTX->set( 'k_count', $x - $offset + $startcount );
                            $CTX->set( 'k_total_rows', $total_rows );
                            $CTX->set( 'k_first_row', ($x==$offset) ? '1' : '0' );
                            $CTX->set( 'k_last_row', ($x==$total_rows+$offset-1) ? '1' : '0' );

                            if( !array_key_exists($tpl_id, $tile_count) ){
                                $tile_count[$tpl_id] = 0;
                            }
                            $CTX->set( 'k_tile_count', $tile_count[$tpl_id]++ );

                            // get content to show ..
                            $content = $FUNCS->render( 'pb_show_tile', 1 /*use cache*/, $show_overlay );
                            $CTX->set( 'k_content', $content );

                            // and call the children providing each row's data
                            foreach( $node->children as $child ){
                                $html .= $child->get_HTML();
                            }
                        }
                        else{ // cms:show_pagebuilder
                            if( count($node->children) ){
                                $CTX->reset();
                                $CTX->set( 'k_count', $x - $offset + $startcount );
                                $CTX->set( 'k_total_rows', $total_rows );
                                $CTX->set( 'k_first_row', ($x==$offset) ? '1' : '0' );
                                $CTX->set( 'k_last_row', ($x==$total_rows+$offset-1) ? '1' : '0' );
                                $CTX->set( 'k_content', self::_get_tile_output( $page_id, $tpl_id ) );

                                foreach( $node->children as $child ){
                                    $html .= $child->get_HTML();
                                }
                            }
                            else{ // self-closing
                                $html .= self::_get_tile_output( $page_id, $tpl_id );
                            }
                        }
                    }
                }

                return $html;
            }
        }

        static function _get_tile_output( $page_id, $tpl_id ){
            global $FUNCS, $CTX;
            $is_post = ( $_SERVER['REQUEST_METHOD']=='POST' ) ? 1 : 0;

            if( K_CACHE_PB && !$is_post ){
                $html = self::_get_from_cache( $page_id, 0 /*full text*/ );
                if( $html!==false ){ return $html; }
            }

            $pg = new KWebpage( $tpl_id, $page_id );
            if( $pg->error ){ return; }
            $caching = array_key_exists('section_caching', $pg->_fields) ? $pg->_fields['section_caching']->data : 'always';

            if( K_CACHE_PB && $is_post && $caching=='always' ){
                $html = self::_get_from_cache( $page_id, 0 /*full text*/ );
                if( $html!==false ){ return $html; }
            }

            // render tile
            $pg->set_context();
            $CTX->set_object( 'k_bound_page', $pg );
            $html = $FUNCS->render( 'pb_tile' );

            if( K_CACHE_PB && ((!$is_post && $caching!='never') || ($is_post && $caching=='always')) ){
                $CTX->set( 'pb_tile_content', $html );
                $html_b = $FUNCS->render( 'pb_wrapper' );

                KPageBuilder::_write_to_cache( $page_id, $html_b, $html );
            }

            return $html;
        }

        function _render( $input_name, $input_id, $extra='', $dynamic_insertion=0 ){
            global $FUNCS;

            $html = $FUNCS->render( 'field_pagebuilder', $this, $input_name, $input_id, $extra, $dynamic_insertion );
            return $html;
        }

        // Output to front-end via $CTX
        function get_data( $for_ctx=0 ){
            global $CTX;

            if( $for_ctx ){
                $schema = $this->_get_schema();

                $rows = array();
                $rows['ids'] = $this->items_selected;
                $rows['tiles'] = array(); // dummy
                $CTX->set_object( $this->name, $rows );
            }
        }

        // Called either from a page being deleted
        // or when this field's definition gets removed from a template (in which case the $page_id param would be '-1' )
        function _delete( $page_id ){
            global $FUNCS, $DB;

            // decrease ref_count of all child pages
            $sql = "UPDATE ". K_TBL_PAGES." p\r\n";
            $sql .= "INNER JOIN ".K_TBL_RELATIONS." r\r\n";
            $sql .= "ON (p.id = r.cid)\r\n";
            $sql .= "SET p.ref_count = p.ref_count - 1\r\n";
            $sql .= "WHERE ";
            if( $page_id!=-1 ){
                $sql .= "r.pid='". $DB->sanitize( $page_id ) ."' AND ";

            }
            $sql .= "r.fid='".$this->id."'";

            $DB->_query( $sql );
            $rs = $DB->rows_affected = mysql_affected_rows( $DB->conn );
            if( $rs==-1 ) die( "ERROR: Mosaic unable to update ref_count in K_TBL_PAGES" );

            // Remove all records from the relation table for the page being deleted
            if( $page_id!=-1 ){
                $rs = $DB->delete( K_TBL_RELATIONS, "pid='" . $DB->sanitize( $page_id ). "' AND fid='".$this->id."'" );
            }
            else{
                $rs = $DB->delete( K_TBL_RELATIONS, "fid='".$this->id."'" );
            }
            if( $rs==-1 ) die( "ERROR: Unable to delete records from K_TBL_RELATIONS" );

            // signal to GC
            $FUNCS->set_setting( 'gc_mosaic_is_dirty', 1 );
        }

        // renderable theme functions
        static function register_renderables(){
            global $FUNCS;

            $FUNCS->register_render( 'field_pagebuilder', array('template_path'=>K_ADDONS_DIR.'page-builder/theme/', 'template_ctx_setter'=>array('KPageBuilder', '_render_pagebuilder')) );
            $FUNCS->register_render( 'pb_list_tiles', array('template_path'=>K_ADDONS_DIR.'page-builder/theme/', 'template_ctx_setter'=>array('KPageBuilder', '_render_pb_list_tiles')) );
            $FUNCS->register_render( 'pb_create_tile', array('template_path'=>K_ADDONS_DIR.'page-builder/theme/') );
            $FUNCS->register_render( 'pb_show_tile', array('renderable'=>array('KPageBuilder', '_render_pb_show_tile')) );
            $FUNCS->register_render( 'pb_repeatable_assets', array('template_path'=>K_ADDONS_DIR.'page-builder/theme/', 'template_ctx_setter'=>array('KPageBuilder', '_render_pb_repeatable_assets')) );

            // both of the following meant to be overridden from the front-end
            $FUNCS->register_render( 'pb_tile', array('template_path'=>K_ADDONS_DIR.'page-builder/theme/') );
            $FUNCS->register_render( 'pb_wrapper', array('template_path'=>K_ADDONS_DIR.'page-builder/theme/', 'template_ctx_setter'=>array('KPageBuilder', '_render_pb_wrapper')) );

            //$FUNCS->register_render( 'content_form_mosaic_ex', array('renderable'=>array('KMosaicAdminEx', '_render_content_form_mosaic_ex')) );
        }

        static function override_renderables(){
            global $FUNCS;

            $FUNCS->override_render( 'field_mosaic', array('template_path'=>K_ADDONS_DIR.'page-builder/theme/', 'template_ctx_setter'=>array('KPageBuilder', '_render_mosaic')) );

            if( $FUNCS->current_route->module=='mosaic_ex' ){
                $FUNCS->override_render( 'field_text',      array('renderable'=>array('KMosaicAdminEx', '_render_text')) );
                $FUNCS->override_render( 'field_password',  array('renderable'=>array('KMosaicAdminEx', '_render_text')) );
                $FUNCS->override_render( 'field_textarea',  array('renderable'=>array('KMosaicAdminEx', '_render_textarea')) );
                $FUNCS->override_render( 'field_dropdown',  array('renderable'=>array('KMosaicAdminEx', '_render_options')) );
                $FUNCS->override_render( 'field_radio',     array('renderable'=>array('KMosaicAdminEx', '_render_options')) );
                $FUNCS->override_render( 'field_checkbox',  array('renderable'=>array('KMosaicAdminEx', '_render_options')) );
            }
        }

        static function _render_mosaic( $f, $input_name, $input_id, $extra, $dynamic_insertion ){
            global $FUNCS, $CTX;

            if( $f->body_class=='_pb' ){
                $css .= "#k_label_f_".$f->name.", #k_label_f_".$f->name."+br{ display:none; }";
                if( $CTX->get('k_add_js') ){
                    $FUNCS->render( 'pb_repeatable_assets' );

                    $css .= ".k___mosaic th.dg-arrange-table-header,";
                    $css .= ".k___mosaic th.col-contents,";
                    $css .= ".k___mosaic td.dg-arrange-table-rows-drag-icon{ display:none; }";
                    $css .= ".k___mosaic .rr{border-bottom: 1px solid #d3d3d3;}";
                    $css .= "div.mosaic-iframe .mfp-content{max-width: 1400px;height:100%;}";
                }
                $FUNCS->add_css( $css );

                return array( 'field_pagebuilder' );
            }
        }

        static function _render_pagebuilder( $f, $input_name, $input_id, $extra, $dynamic_insertion ){
            global $FUNCS, $CTX, $DB, $AUTH;
            static $done=0;

            KField::_set_common_vars( $f->k_type, $input_name, $input_id, $extra, $dynamic_insertion, $f->simple_mode );

            // the rows
            $f->get_data( 1 ); // piggybacking

            // the template selector buttons
            $schema = $f->_get_schema();
            foreach( $schema as $k=>$v ){
                // set link
                //$link = $FUNCS->generate_route( 'pb', 'list_tiles', $schema[$k] ); // won't work when rendered outside admin-panel (i.e. on front-end in dbf)
                $link = K_ADMIN_URL . K_ADMIN_PAGE."?o=pb&q=list_tiles/".rawurlencode($schema[$k]['masterpage'])."/".$schema[$k]['page']."/".$schema[$k]['mosaic']."/".$schema[$k]['tiles']."/".$schema[$k]['limit']."/".$schema[$k]['offset']."/".$schema[$k]['order'];
                $schema[$k]['link']=$link;
            }
            $CTX->set( 'templates', $schema, '', 1 );
            $CTX->set( 'k_has_deleted_tile', 0 );

            if( !$done ){
                $FUNCS->render( 'repeatable_assets' ); // piggyback on repeatable-region's JS/CSS
                $FUNCS->render( 'pb_repeatable_assets' );

                $css = ".k___pagebuilder th.dg-arrange-table-header, .k___pagebuilder th.col-contents, .k___pagebuilder td.dg-arrange-table-rows-drag-icon{ display:none; }";
                $css .= ".k___pagebuilder .rr{border-bottom: 1px solid #d3d3d3;}";
                $css .= "div.mosaic-iframe .mfp-content{max-width: 1400px;height:100%;}";
                $FUNCS->add_css( $css );

                $CTX->set( 'k_add_js', '1' );
                $done=1;
            }
            else{
                $CTX->set( 'k_add_js', '0' );
            }
        }

        static function _render_pb_show_tile( $use_cache, $show_overlay=1 ){
            global $FUNCS, $CTX;

            $pid = $CTX->get('k_page_id');
            $tpl_id = $CTX->get('k_template_id');
            $pb_height = $CTX->get('k_template__pb_height');
            $nonce = $FUNCS->create_nonce( 'show_tile_'.$pid.'_'.$tpl_id );

            $link='';
            if( $use_cache && K_CACHE_PB ){
                $link = self::_get_from_cache($pid);
            }
            if( !$link ){
                $link = K_ADMIN_URL . K_ADMIN_PAGE.'?o=pb&q=show_tile/'.$nonce.'/'.$pid.'/'.$tpl_id;
            }

            $overlay = ( $show_overlay )? '<div style="position:absolute;top:0;left:0;bottom:0;right:0;"></div>' : '';

            $html=<<<EOS
            <iframe data-src="$link" class="lazy" scrolling="no" frameborder="0" style="height:{$pb_height}px;width:100%;border:0;" onload="if(typeof k_pb_iframe==='function'){k_pb_iframe(this);}"></iframe>
            $overlay
EOS;
            return $html;
        }

        static function _get_from_cache( $pid, $link_only=1 ){
            global $FUNCS;

            if( K_CACHE_PB ){
                if( @is_writable(PB_CACHE_DIR) ){
                    $name = self::_get_cache_name( $pid );
                    $cache_file_name = ( $link_only )? $name.'.html' : $name.'_b.html';
                    $cache_file = PB_CACHE_DIR . $cache_file_name;
                    if( file_exists($cache_file) ){

                        // Check if the cache has not expired
                        $file_time = @filemtime( $cache_file );
                        $cache_invalidated_time = @filemtime( PB_CACHE_DIR . 'cache_invalidate.dat' );

                        if( $file_time > $cache_invalidated_time ){
                            if( $link_only ){
                                return K_ADMIN_URL . 'cache/pb/' . $cache_file_name . '?ver='.$file_time;
                            }
                            else{
                                $html = file_get_contents( $cache_file );
                                return $html;
                            }
                        }
                        else{
                            // Delete the stale cached copy
                            @unlink( $cache_file );
                        }
                    }
                }
            }

            return false;
        }

        static function _write_to_cache( $pid, $html_a, $html_b ){
            global $FUNCS;

            if( K_CACHE_PB ){
                if( !file_exists(PB_CACHE_DIR) && file_exists(K_COUCH_DIR . 'cache/') && @is_writable(K_COUCH_DIR . 'cache/') ){
                    $oldumask = umask(0);
                    @mkdir( PB_CACHE_DIR, 0777 );
                    umask( $oldumask );
                }

                if( @is_writable(PB_CACHE_DIR) ){
                    $name = self::_get_cache_name( $pid );
                    $arr = array( $name.'.html', $name.'_b.html' );

                    for( $x=0; $x<2; $x++ ){
                        $cache_file = PB_CACHE_DIR . $arr[$x];
                        $html = ( !$x ) ? $html_a : $html_b;

                        $handle = @fopen( $cache_file, 'c' );
                        if( $handle ){
                            if( flock($handle, LOCK_EX) ){
                                ftruncate( $handle, 0 );
                                rewind( $handle );
                                fwrite( $handle, $html );
                                fflush( $handle );
                                flock( $handle, LOCK_UN );
                            }
                            fclose( $handle );
                        }
                    }
                }
            }
        }

        static function _remove_from_cache( $pid ){
            global $FUNCS;

            $name = self::_get_cache_name( $pid );
            $arr = array( $name.'.html', $name.'_b.html' );

            for( $x=0; $x<2; $x++ ){
                $cache_file = PB_CACHE_DIR . $arr[$x];
                if( file_exists($cache_file) ){
                    @unlink( $cache_file );
                }
            }
        }

        static function _get_cache_name( $pid ){
            global $FUNCS;

            $data = 'pb_'.$pid;
            $key = $FUNCS->hash_hmac( $data, $FUNCS->_get_nonce_secret_key() );
            $hash = $FUNCS->hash_hmac( $data, $key );

            return $pid.'_'.$hash;
        }

        static function _invalidate_cache(){
            global $FUNCS;

            // HOOK: pb_invalidate_cache
            $skip = $FUNCS->dispatch_event( 'pb_invalidate_cache' );
            if( $skip ) return;

            // Invalidate cache
            $file = PB_CACHE_DIR . 'cache_invalidate.dat';
            if( file_exists($file) ) @unlink( $file );
            @fclose( @fopen($file, 'a') );
        }

        static function delete_cached_tile( &$pg ){
            if( property_exists($pg, 'tpl__pb_height') ){
                self::_remove_from_cache( $pg->id );
            }
        }

        static function add_template_params( &$attr_custom, $params, $node ){
            global $FUNCS;

            $attr = $FUNCS->get_named_vars(
                array(
                    '_pb_height'=>'',
                    '_pb_template'=>'',
                  ),
                $params
            );
            extract( $attr );

            $attr = array();
            $_pb_height = trim( $_pb_height );
            $_pb_template = trim( $_pb_template );
            if( $_pb_height!='' ) $attr['_pb_height'] = $_pb_height;
            if( $_pb_template!='' ) $attr['_pb_template'] = $_pb_template;

            // merge with existing custom params
            if( count($attr) ){
                $attr_custom = array_merge( $attr_custom, $attr );
            }
        }

        static function _render_pb_repeatable_assets(){
            global $FUNCS, $CTX;
            static $done=0;

            if( !$done ){
                $CTX->set( 'k_add_js', '1' );
                $done=1;
            }
            else{
                $CTX->set( 'k_add_js', '0' );
            }
        }

        static function _render_pb_list_tiles( $masterpage, $mosaic, $tiles, $limit, $offset, $order ){
            global $CTX;

            $vars = array();
            $vars['k_masterpage'] = $masterpage;
            $vars['k_mosaic'] = $mosaic;
            $vars['k_tiles'] = $tiles;
            $vars['k_limit'] = $limit;
            $vars['k_offset'] = $offset;
            $vars['k_order'] = $order;

            $CTX->set_all( $vars );
        }

        static function _render_pb_wrapper( $context=null ){
            global $CTX;

            $CTX->set( 'k_pb_context', $context );
        }

        static function alter_form_fields( &$arr_default_fields ){
            global $PAGE;
            if( !$PAGE->is_pointer ) return;

            if( array_key_exists('pb_hidden', $arr_default_fields) ){
                $arr_default_fields['pb_hidden']['skip']=1;
            }
        }

        static function alter_admin_routes( &$routes ){
            foreach( self::$tpls as $tpl ){
                if( array_key_exists($tpl, $routes) && array_key_exists('edit_view', $routes[$tpl])){
                    $routes[$tpl]['edit_view']->include_file = K_ADDONS_DIR.'page-builder/edit-mosaic-ex2.php';
                    $routes[$tpl]['edit_view']->class = 'KMosaicAdminEx2';
                    $routes[$tpl]['edit_view']->action = 'form_action';
                    $routes[$tpl]['edit_view']->module = 'mosaic_ex';

                    $routes[$tpl]['create_view']->include_file = K_ADDONS_DIR.'page-builder/edit-mosaic-ex2.php';
                    $routes[$tpl]['create_view']->class = 'KMosaicAdminEx2';
                    $routes[$tpl]['create_view']->action = 'form_action';
                    $routes[$tpl]['create_view']->module = 'mosaic_ex';

                    $routes[$tpl]['list_view']->include_file = K_ADDONS_DIR.'page-builder/edit-mosaic-ex3.php';
                    $routes[$tpl]['list_view']->class = 'KMosaicAdminEx3';
                    $routes[$tpl]['list_view']->filters = 'KMosaicAdminEx3::resolve_page=list | KPagesAdmin::clonable_only=list';
                }
            }
        }

        static function alter_register_routes( $tpl, &$default_routes ){
            if( $tpl['type']=='tile' ){
                if( strpos($tpl['custom_params'], '_pb_height') !== false ){
                    foreach( array('edit_view', 'clone_view', 'create_view') as $view ){
                        if( array_key_exists($view, $default_routes) ){
                            $default_routes[$view]['include_file'] = K_ADDONS_DIR.'page-builder/edit-mosaic-ex.php';
                            $default_routes[$view]['class'] = 'KMosaicAdminEx';
                            $default_routes[$view]['module'] = 'mosaic_ex';

                            if( $view=='clone_view' ){
                                $default_routes[$view]['filters'] = 'KMosaicAdmin::resolve_page=edit | KMosaicAdminEx::fix_link';
                            }
                        }
                    }
                }
            }
            elseif( in_array($tpl['name'], self::$tpls) ){
                $route = array(
                    'include_file'=>K_ADDONS_DIR.'page-builder/edit-mosaic-ex2.php',
                    'path'=>'copy/{:nonce}/{:id}',
                    'constraints'=>array(
                        'nonce'=>'([a-fA-F0-9]{32})',
                        'id'=>'(([1-9]\d*)?)',
                    ),
                    'filters'=>'KMosaicAdminEx2::copy_page',
                    'class'=> 'KMosaicAdminEx2',
                    'action'=>'form_action',
                    'module'=>'mosaic_ex',
                );
                $default_routes['copy_view'] =  $route;

                // for editing from pb..
                if( array_key_exists('edit_view', $default_routes) ){

                    // copy to create new routes ..
                    $tmp = $default_routes['edit_view'];
                    $tmp['path'] = 'edit_ex/{:nonce}/{:id}';
                    $tmp['include_file'] = K_ADDONS_DIR.'page-builder/edit-mosaic-ex.php';
                    $tmp['class'] = 'KMosaicAdminEx';
                    $tmp['module'] = 'mosaic_ex';
                    $default_routes['edit_view_ex'] =  $tmp;

                    $tmp['path'] = 'clone/{:nonce}/{:id}';
                    $tmp['filters'] = 'KMosaicAdmin::resolve_page=edit | KMosaicAdminEx::fix_link';
                    $default_routes['clone_view'] =  $tmp;
                }
            }
        }

        static function register_routes(){
            global $FUNCS;

            $route = array(
                'name'=>'show_tile',
                'path'=>'show_tile/{:nonce}/{:pid}/{:tpl_id}',
                'constraints'=>array(
                    'nonce'=>'([a-fA-F0-9]{32})',
                    'pid'=>'([1-9]\d*)',
                    'tpl_id'=>'([1-9]\d*)',
                ),
                'include_file'=>K_ADDONS_DIR.'page-builder/edit-page-builder.php',
                'filters'=>'KPageBuilderAdmin::resolve_page',
                'action'=>'KPageBuilderAdmin::show_tile',
            );
            $FUNCS->register_route( 'pb', $route );

            $route = array(
                'name'=>'list_tiles',
                'path'=>'list_tiles/{:masterpage}/{:page}/{:mosaic}/{:tiles}/{:limit}/{:offset}/{:order}',
                'constraints'=>array(
                    'masterpage' => '(.+)',
                    'page' => '(.+)?',
                    'tiles' => '(.+)?',
                ),
                'include_file'=>K_ADDONS_DIR.'page-builder/edit-page-builder.php',
                'filters'=>'KPageBuilderAdmin::resolve_mosaic',
                'action'=>'KPageBuilderAdmin::list_tiles',
            );
            $FUNCS->register_route( 'pb', $route );

            $route = array(
                'name'=>'create_tile',
                'path'=>'create_tile/{:nonce}/{:tpl_id}/{:page_id}',
                'constraints'=>array(
                    'nonce'=>'([a-fA-F0-9]{32})',
                    'tpl_id'=>'([1-9]\d*)',
                    'page_id'=>'([1-9]\d*)',
                ),
                'include_file'=>K_ADDONS_DIR.'page-builder/edit-page-builder.php',
                'filters'=>'KPageBuilderAdmin::resolve_clone',
                'action'=>'KPageBuilderAdmin::create_tile',
                'method'=>'POST',
            );
            $FUNCS->register_route( 'pb', $route );
        }

        static function alter_admin_menuitems( &$items ){
            global $FUNCS, $DB;

            foreach( self::$tpls as $tpl ){
                if( array_key_exists($tpl, $items) ){
                    $cols = array( 't.clonable, t.nested_pages, f.id' );
                    $tables = K_TBL_TEMPLATES." t left outer join ".K_TBL_FOLDERS." f on f.template_id=t.id";
                    $where = "t.name='".$DB->sanitize( $tpl )."' and f.pid='-1' order by f.weight asc limit 0, 1";

                    $rs = $DB->select( $tables, $cols, $where );
                    if( count($rs) ){
                        $f = $rs[0];
                        if( $f['clonable'] && !$f['nested_pages'] && $f['id'] ){
                            $items[$tpl]['route']['qs']='fid='.(int)$f['id'];
                        }
                    }
                }
            }
        }

        static function config(){
            $cfg = array();
            if( file_exists(K_ADDONS_DIR.'page-builder/config.php') ){
                require_once( K_ADDONS_DIR.'page-builder/config.php' );
            }
            $tpls = array_unique( array_filter(array_map("trim", explode('|', $cfg['tpls']))) );
            self::$tpls = $tpls;
        }

    }// end class

    // Register
    $FUNCS->register_udf( '__pagebuilder', 'KPageBuilder' ); // The UDF
    $FUNCS->register_tag( 'pagebuilder', array('KPageBuilder', 'tag_handler'), 1, 0 ); // The helper 'shim' tag that helps create the above UDF
    $FUNCS->register_tag( 'section', array('KPageBuilder', 'section_handler') );
    $FUNCS->register_tag( 'show_mosaic_ex', array('KPageBuilder', 'show_handler'), 1, 1 ); // The helper tag that shows the variables via CTX
    $FUNCS->register_tag( 'show_pagebuilder', array('KPageBuilder', 'show_handler'), 1, 0 ); // shows tiles on the frontend
    $FUNCS->add_event_listener( 'register_renderables',  array('KPageBuilder', 'register_renderables') );
    $FUNCS->add_event_listener( 'override_renderables', array('KPageBuilder', 'override_renderables') );
    $FUNCS->add_event_listener( 'alter_mosaic_form_fields',  array('KPageBuilder', 'alter_form_fields') );
    $FUNCS->add_event_listener( 'page_deleted', array('KPageBuilder', 'delete_cached_tile') );
    $FUNCS->add_event_listener( 'add_template_params', array('KPageBuilder', 'add_template_params') );
    KPageBuilder::config();

    // routes
    if( defined('K_ADMIN') ){
        $FUNCS->add_event_listener( 'alter_admin_routes',  array('KPageBuilder', 'alter_admin_routes') );
        $FUNCS->add_event_listener( 'alter_register_pages_routes',  array('KPageBuilder', 'alter_register_routes'), -10 );
        $FUNCS->add_event_listener( 'register_admin_routes', array('KPageBuilder', 'register_routes') );
        $FUNCS->add_event_listener( 'alter_admin_menuitems', array('KPageBuilder', 'alter_admin_menuitems'), -10 );
    }
