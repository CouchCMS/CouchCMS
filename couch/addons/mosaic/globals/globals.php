<?php
    if ( !defined('K_COUCH_DIR') ) die(); // cannot be loaded directly

    class KGlobals{

        // cms:globals
        static function globals_handler( $params, $node ){
            global $CTX, $FUNCS, $PAGE, $AUTH, $DB;
            if( $AUTH->user->access_level < K_ACCESS_LEVEL_SUPER_ADMIN || defined('K_ADMIN') ){ return; } // nop within admin panel

            // check if the current template has an associated globals template..
            $global_tpl_name = KGlobals::_get_filename( $PAGE->tpl_name );
            $rs = $DB->select( K_TBL_TEMPLATES, array('*'), "name='" . $DB->sanitize( $global_tpl_name ). "'" );

            // .. if not, create one
            if( !count($rs) ){
                $rs = $DB->insert( K_TBL_TEMPLATES, array('name'=>$global_tpl_name, 'description'=>'', 'clonable'=>0, 'executable'=>0, 'hidden'=>2) );
                $rs = $DB->select( K_TBL_TEMPLATES, array('*'), "name='" . $DB->sanitize( $global_tpl_name ). "'" );
                if( !count($rs) ) die( "ERROR: Tag \"".$node->name."\" (".$name.") cannot create record in K_TBL_TEMPLATES for globals template" );

                // mark the current template as having globals
                $DB->update( K_TBL_TEMPLATES, array('has_globals'=>'1'), "id='" . $DB->sanitize( $PAGE->tpl_id ) . "'" );

                // HOOK: globals_template_inserted
                $FUNCS->dispatch_event( 'globals_template_inserted', array($PAGE->tpl_name, $rs[0]) );
            }

            // get page to hold the child editable regions
            $pg = new KWebpage( $rs[0]['id'], null );
            if( $pg->error ) die( "ERROR: Tag \"".$node->name."\" (".$name.") cannot create page for globals: ".$pg->err_msg );
            $orig_page = $PAGE;
            $PAGE = $pg;

            // execute supported child tags to create fields
            $order = 0;
            $children = $node->children;
            foreach( $children as $child ){
                if( $child->type==K_NODE_TYPE_CODE ){
                    $child_name = strtolower( $child->name );
                    if( in_array($child_name, array('editable', 'repeatable', 'mosaic', 'config_form_view', 'func', 'embed')) ){ //supported tags

                        // set 'order' according to occurance
                        if( $child_name=='editable' || $child_name=='repeatable' || $child_name=='mosaic' ){
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

            // mark as processed
            $PAGE->globals_processed = 1;
        }

        static function get_global_handler( $params, $node ){
            global $FUNCS, $PAGE, $TAGS;

            $attr = $FUNCS->get_named_vars(
                        array( 'var'=>'',
                               'masterpage'=>'',
                               'into'=>'',
                               'scope'=>'',
                              ),
                        $params);
            extract( $attr );

            $var = trim($var);
            if( $var ){
                if( !$masterpage ){
                    // use the current template
                    $masterpage = $PAGE->tpl_name;
                }
                $global_tpl_name = KGlobals::_get_filename( $masterpage );

                // delegate to cms:get_field
                $params = array();
                $params[] = array( 'lhs'=>'var', 'op'=>'=', 'rhs'=>$var );
                $params[] = array( 'lhs'=>'masterpage', 'op'=>'=', 'rhs'=>$global_tpl_name );
                $params[] = array( 'lhs'=>'into', 'op'=>'=', 'rhs'=>$into );
                $params[] = array( 'lhs'=>'scope', 'op'=>'=', 'rhs'=>$scope );
                return $TAGS->get_field( $params, $node );
            }
        }

        static function show_globals_handler( $params, $node ){
            global $CTX, $FUNCS, $PAGE;

            $attr = $FUNCS->get_named_vars(
                        array(
                               'masterpage'=>'',
                              ),
                        $params);
            extract( $attr );

            if( !$masterpage ){
                // use the current template
                $masterpage = $PAGE->tpl_name;
            }
            $global_tpl_name = KGlobals::_get_filename( $masterpage );

            $pg = new KWebpage( $global_tpl_name );
            if( $pg->error ) return;

            $vars = array();
            foreach( $pg->fields as $f ){
                if( $f->system || $f->deleted ) continue;
                $vars[$f->name] = $f->get_data( 1 );
            }
            $CTX->set_all( $vars );

            foreach( $node->children as $child ){
                $html .= $child->get_HTML();
            }

            return $html;
        }

        static function _get_filename( $tpl ){
            return  $tpl . '__globals';
        }

        static function _delete_template( $tpl ){
            global $DB, $FUNCS, $AUTH;

            $global_tpl_name = KGlobals::_get_filename( $tpl );
            $rs = $DB->select( K_TBL_TEMPLATES, array('*'), "name='" . $DB->sanitize( $global_tpl_name ). "' LIMIT 1" );
            if( count($rs) ){
                $tmp_name = $global_tpl_name . '_' . md5( $AUTH->hasher->get_random_bytes(16) );
                $DB->update( K_TBL_TEMPLATES, array('name'=>$tmp_name, 'deleted'=>1), "name='" . $DB->sanitize( $global_tpl_name ). "'" );

                // HOOK: globals_template_deleted
                $FUNCS->dispatch_event( 'globals_template_deleted', array($tpl, $rs[0]) );

                // signal to GC (can piggyback on existing gc logic of mosaic)
                $FUNCS->set_setting( 'gc_mosaic_is_dirty', 1 );
            }
        }

        // remove deleted globals template
        static function post_process_page(){
            global $DB, $PAGE, $FUNCS;

            if( $PAGE->tpl_has_globals && !$PAGE->globals_processed ){ // meaning <cms:globals> has been removed
                // mark the globals template as deleted
                KGlobals::_delete_template( $PAGE->tpl_name );

                // mark the current template as no longer having globals
                $DB->update( K_TBL_TEMPLATES, array('has_globals'=>'0'), "id='" . $DB->sanitize( $PAGE->tpl_id ) . "'" );
            }
        }

        static function delete_global_template( $rec ){
            global $DB, $FUNCS;

            if( $rec['has_globals'] ){
                // mark the globals template as deleted
                KGlobals::_delete_template( $rec['name'] );
            }
        }

        static function add_toolbar_button( &$arr_actions, &$obj ){
            global $FUNCS, $PAGE;

            $route = $FUNCS->current_route;
            if( is_object($route) && $route->module=='pages' ){

                if( $PAGE->tpl_has_globals && $PAGE->tpl_is_clonable ){ // if template is clonable and has globals, add the new button to toolbar
                    $global_tpl_name = KGlobals::_get_filename( $PAGE->tpl_name );
                    $link = $FUNCS->generate_route( $PAGE->tpl_name, 'edit_globals', array('nonce'=>$FUNCS->create_nonce('edit_globals_'.$global_tpl_name)) );

                    $arr_actions['btn_manage_globals'] =
                        array(
                            'title'=>$FUNCS->t('manage_globals'),
                            'href'=>$link,
                            'icon'=>'globe',
                            'weight'=>15,
                        );
                }
            }
        }

        static function alter_register_routes( $tpl, &$default_routes ){
            if( !($tpl['has_globals'] && $tpl['clonable']) ) return;

            $default_routes['edit_globals'] = array(
                'path'=>'edit_globals/{:nonce}/',
                'constraints'=>array(
                    'nonce'=>'([a-fA-F0-9]{32})',
                ),
                'include_file'=>K_COUCH_DIR.'addons/mosaic/globals/edit-globals.php',
                'filters'=>'KEditGlobals::resolve_page=edit',
                'class'=> 'KEditGlobals',
                'action'=>'form_action_ex',
                'module'=>'globals',
            );
        }
    }// end class

    // Register
    $FUNCS->register_tag( 'globals', array('KGlobals', 'globals_handler') );
    $FUNCS->register_tag( 'get_global', array('KGlobals', 'get_global_handler'), 1, 0 );
    $FUNCS->register_tag( 'show_globals', array('KGlobals', 'show_globals_handler'), 1, 0 );

    $FUNCS->add_event_listener( 'post_process_page_end', array('KGlobals', 'post_process_page') );
    $FUNCS->add_event_listener( 'template_deleted', array('KGlobals', 'delete_global_template') );
    if( defined('K_ADMIN') ){ // if admin-panel being displayed ..
        $FUNCS->add_event_listener( 'alter_pages_list_toolbar_actions', array('KGlobals', 'add_toolbar_button') );
        $FUNCS->add_event_listener( 'alter_register_pages_routes',  array('KGlobals', 'alter_register_routes') );
    }
