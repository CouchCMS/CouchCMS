<?php
    if ( !defined('K_ADMIN') ) die(); // cannot be loaded directly

    class KPageBuilderAdmin{

        static function show_tile( $pid, $tpl_id ){
            global $FUNCS, $CTX, $PAGE;

            $CTX->set( 'k_disable_edit', '1', 'global' );
            $html_b = $FUNCS->render( 'pb_tile' );
            $CTX->set( 'pb_tile_content', $html_b );
            $html = $FUNCS->render( 'pb_wrapper' );

            if( K_CACHE_PB ){
                $caching = $CTX->get( 'section_caching' );
                if( $caching != 'never' ){
                    KPageBuilder::_write_to_cache( $pid, $html, $html_b );
                }
                else{
                    KPageBuilder::_remove_from_cache( $pid );
                }
            }

            return $html;
        }

        static function list_tiles( $masterpage, $mosaic, $tiles, $limit, $offset, $order ){
            global $FUNCS, $CTX;

            $FUNCS->reset_admin_actions( array('page') );
            $FUNCS->add_page_action(
                array(
                    'name'=>'btn_cancel',
                    'title'=>$FUNCS->t('cancel'),
                    'onclick'=>array(
                        "window.parent.COUCH.mosaicModalClose();",
                        "return false;",
                    ),
                    'icon'=>'circle-x',
                    'weight'=>0,
                ) );

            $FUNCS->add_event_listener( 'alter_page_tag_query_ex',  array('KPageBuilderAdmin', '_alter_page_tag_query_handler') );
            $FUNCS->add_event_listener( 'pre_alter_page_tag_context',  array('KPageBuilderAdmin', '_pre_alter_page_tag_context') );
            $FUNCS->add_event_listener( 'alter_page_tag_context',  array('KPageBuilderAdmin', '_alter_page_tag_context') );
            $FUNCS->add_event_listener( 'post_alter_page_tag_context',  array('KPageBuilderAdmin', '_post_alter_page_tag_context') );

            $html = $FUNCS->render( 'pb_list_tiles', $masterpage, $mosaic, $tiles, $limit, $offset, $order );
            $html = $FUNCS->render( 'main', $html, 1 );

            return $html;
        }

        static function _alter_page_tag_query_handler(&$distinct, &$count_query_field, &$count_query_field_as, &$query_fields, &$query_table, &$orig_sql, &$sql, &$group_by, &$having, &$order_sql, &$limit_sql, &$mode, $params, $node, $rec_tpl, $token){
            global $FUNCS, $CTX;
            if( $node->name!=='pages' || $token!=='pb_list_tiles' ){ return; }

            $sql .= " AND p.file_ext='pb'";
        }

        static function _pre_alter_page_tag_context( $rec, $mode, $params, $node, $rec_tpl, $token, $x, $count ){
            global $FUNCS, $CTX;
            if( $node->name!=='pages' || $token!=='pb_list_tiles' ){ return; }

            if( $x==0 ){
                $FUNCS->add_event_listener( 'alter_custom_fields_info_db',  array('KPageBuilderAdmin', '_alter_custom_fields_info_db') );
            }
        }

        static function _alter_page_tag_context( $rec, $mode, $params, $node, $rec_tpl, $token ){
            global $FUNCS, $CTX;
            if( $node->name!=='pages' || $token!=='pb_list_tiles' ){ return; }

            $content = $FUNCS->render( 'pb_show_tile', 1 /*use cache*/, 1 /*overlay*/ );
            $CTX->set( 'k_content', $content );
            $page_id = $rec['id'];
            $tpl_id = $rec['template_id'];
            $CTX->set( 'k_create_link', K_ADMIN_URL . K_ADMIN_PAGE."?o=pb&q=create_tile/" . $FUNCS->create_nonce('create_tile_'.$page_id) . "/$tpl_id/$page_id" );
        }

        static function _post_alter_page_tag_context( $rec, $mode, $params, $node, $rec_tpl, $token, $x, $count ){
            global $FUNCS, $CTX;
            if( $node->name!=='pages' || $token!=='pb_list_tiles' ){ return; }

            if( $x==$count-1 ){
                $FUNCS->remove_event_listener( 'alter_custom_fields_info_db', array('KPageBuilderAdmin', '_alter_custom_fields_info_db') );
            }
        }

        static function _alter_custom_fields_info_db( &$rs2, &$pg ){
            $rs2=array();
        }

        static function create_tile(){
            global $FUNCS, $CTX;

            $html = $FUNCS->render( 'pb_create_tile' );
            $html = $FUNCS->render( 'main', $html, 1 );

            return $html;
        }

        // route filters
        static function resolve_page( $route ){
            global $FUNCS, $PAGE, $CTX;

            $FUNCS->route_fully_rendered = 1;

            $nonce = $route->resolved_values['nonce'];
            $page_id = $route->resolved_values['pid'];
            $tpl_id = $route->resolved_values['tpl_id'];

            // validate
            $FUNCS->validate_nonce( 'show_tile_'.$page_id.'_'.$tpl_id, $nonce );

            // get page ..
            $pg = new KWebpage( $tpl_id, $page_id );
            if( $pg->error ){
                return $FUNCS->raise_error( ROUTE_NOT_FOUND );
            }

            $PAGE = $pg;
            $PAGE->set_context();
        }

        static function resolve_mosaic( $route ){
            global $FUNCS, $PAGE, $CTX;

            $FUNCS->route_fully_rendered = 1;

            $masterpage = trim( $route->resolved_values['masterpage'] );
            $page_name = trim( $route->resolved_values['page'] );
            $mosaic = trim( $route->resolved_values['mosaic'] );

            if( $masterpage=='' ){ return $FUNCS->raise_error( ROUTE_NOT_FOUND ); }
            if( $page_name=='' ){ $page_name=null; }

            if( $mosaic!='@' ){
                // get page ..
                $pg = new KWebpage( $masterpage, null, $page_name );
                if( $pg->error ){
                    return $FUNCS->raise_error( ROUTE_NOT_FOUND );
                }

                $PAGE = $pg;
                $PAGE->set_context();
            }

            // prep params
            $tiles = trim( $route->resolved_values['tiles'] );
            $limit = trim( $route->resolved_values['limit'] );
            $offset = trim( $route->resolved_values['offset'] );
            $order = trim( $route->resolved_values['order'] );

            $limit = $FUNCS->is_non_zero_natural( $limit ) ? intval( $limit ) : '';
            $offset = $FUNCS->is_natural( $offset ) ? intval( $offset ) : '';
            $order = strtolower( $order );
            if( $order!='desc' && $order!='asc' ) $order='';

            $route->resolved_values['masterpage'] = $masterpage;
            $route->resolved_values['mosaic'] = $mosaic;
            $route->resolved_values['tiles'] = $tiles;
            $route->resolved_values['limit'] = $limit;
            $route->resolved_values['offset'] = $offset;
            $route->resolved_values['order'] = $order;
        }

        static function resolve_clone( $route ){
            global $FUNCS, $PAGE, $CTX, $AUTH, $DB;

            $FUNCS->route_fully_rendered = 1;

            $page_id = $route->resolved_values['page_id'];
            $tpl_id = $route->resolved_values['tpl_id'];
            $nonce = $route->resolved_values['nonce'];

            // validate
            $FUNCS->validate_nonce( 'create_tile_' . $page_id, $nonce );

            // get page ..
            $pg = new KWebpage( $tpl_id, $page_id );
            if( $pg->error ){
                return $FUNCS->raise_error( ROUTE_NOT_FOUND );
            }

            // and clone it ..
            $DB->begin();
            $cur_time = $FUNCS->get_current_desktop_time();
            $name = md5( $AUTH->hasher->get_random_bytes(16) );

            $arr_insert = array(
                'template_id'=>$pg->tpl_id,
                'page_title'=>$name,
                'page_name'=>$name,
                'creation_date'=>$cur_time,
                'creation_IP'=>trim( $FUNCS->cleanXSS(strip_tags($_SERVER['REMOTE_ADDR'])) ),
                'modification_date'=>$cur_time,
                'publish_date'=> $cur_time,
                'page_folder_id'=>$pg->page_folder_id,
                'is_master'=>0,
                'ref_count'=>0,
                'status'=>K_MOSAIC_STATUS_ORPHAN,
                'is_pointer'=>1, /* overloaded ;) */
            );

            // HOOK: alter_tile_clone_insert
            $FUNCS->dispatch_event( 'alter_tile_clone_insert', array(&$arr_insert, &$pg) );

            $rs = $DB->insert( K_TBL_PAGES, $arr_insert );
            if( $rs!=1 ) return $FUNCS->raise_error( "Failed to insert record in K_TBL_PAGES" );
            $page_id = $DB->last_insert_id;

            $res = $pg->_create_fields( $page_id, $pg->page_title, 1 );
            if( $FUNCS->is_error($res) ){ $DB->rollback();  return $res; }

            $DB->commit();

            // fetch the cloned page in context
            $pg = new KWebpage( $tpl_id, $page_id );
            if( $pg->error ){ return $FUNCS->raise_error( ROUTE_NOT_FOUND ); }
            $PAGE = $pg;
            $PAGE->set_context();
        }

    } // end class KPageBuilderAdmin
