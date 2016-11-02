<?php
    if ( !defined('K_COUCH_DIR') ) die(); // cannot be loaded directly

    class KExtendedFolders{
        static function add_template_params( &$attr_custom, $params, $node ){
            global $FUNCS;

            $attr = $FUNCS->get_named_vars(
                array(
                    'folder_masterpage'=>'',
                  ),
                $params
            );
            $attr['folder_masterpage'] = trim( $attr['folder_masterpage'] );

            // merge with existing custom params
            $attr_custom = array_merge( $attr_custom, $attr );

        }

        static function &_get_associated_page( &$folder ){
            global $FUNCS, $DB;

            if( isset($folder->_assoc_page) ) return $folder->_assoc_page;

            // is the 'folder_masterpage' parameter set in the template defining the folder?
            $folder_masterpage = KExtendedFolders::_get_folder_masterpage( $folder->template_name );
            if( !strlen($folder_masterpage) ){
                $folder->_assoc_page = '';
                return;
            }

            // 'folder_masterpage' parameter is set. Pass it through some sanity checks
            $rs = $DB->select( K_TBL_TEMPLATES, array('id', 'clonable'), "name='" . $DB->sanitize( $folder_masterpage ). "'" );
            if( !count($rs) ){
                die( "ERROR: Tag 'template' - folder_masterpage '".$FUNCS->cleanXSS($folder_masterpage)."' not found" );
            }
            elseif( !$rs[0]['clonable'] ){
                die( "ERROR: Tag 'template' -  folder_masterpage '".$FUNCS->cleanXSS($folder_masterpage)."' is not clonable" );
            }

            // create the associated page object
            $tpl_id = $rs[0]['id'];
            $page_id = -1;
            if( $folder->id ){ // not a new folder

                // does the corresponding page exist?
                $rs = $DB->select( K_TBL_PAGES, array('id'), "page_name='folder-" . $DB->sanitize( $folder->id ). "' AND template_id='" . $DB->sanitize( $tpl_id ). "'" );
                if( count($rs) ){
                    $page_id = $rs[0]['id'];
                }

            }

            $pg = new KWebpage( $tpl_id, $page_id );
            if( $pg->error ){
                die( "ERROR: in attaching custom page to folder - " . $pg->err_msg );
            }

            // associate the page object to the folder
            $folder->_assoc_page = &$pg;

            return $folder->_assoc_page;
        }

        static function _get_folder_masterpage( $tpl_name ){
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

            return $custom_params['folder_masterpage'];
        }

        static function add_custom_folder_fields( &$fields, &$folder ){
            global $FUNCS, $DB;

            $pg = KExtendedFolders::_get_associated_page( $folder );
            if( !$pg ) return;

            $count = count( $pg->fields );
            for( $x=0; $x<$count; $x++ ){
                $f = &$pg->fields[$x];
                $f->resolve_dynamic_params();

                if( !$f->system ){
                    $folder->fields[] = &$pg->fields[$x];
                }

                unset( $f );

            }

        }

        static function save_custom_folder_fields( &$folder, $action, &$errors ){
            global $FUNCS, $DB;

            $pg = KExtendedFolders::_get_associated_page( $folder );
            if( !$pg ) return;

            $title = trim( $folder->fields[0]->get_data() );
            $name = 'folder-'.$folder->id;
            $pg->_fields['k_page_title']->store_posted_changes( $title );
            $pg->_fields['k_page_name']->store_posted_changes( $name );
            $pg->_fields['k_publish_date']->store_posted_changes( $FUNCS->get_current_desktop_time() );

            $errors = $pg->save();
        }

        static function delete_custom_folder_fields( &$folder ){

            $pg = KExtendedFolders::_get_associated_page( $folder );
            if( !$pg || $pg->id==-1 ) return;

            $pg->delete();

            // if we are here, delete was successful (script would have died otherwise)
            $pg->destroy();
            unset( $pg );

        }

        static function add_param_to_tag( &$attr, $params, $node ){
            global $FUNCS, $CTX;

            if( $node->name=='folders' ){
                $attr2 = $FUNCS->get_named_vars(
                        array(
                              'include_custom_fields'=>'0',
                            ),
                        $params);
                $include_custom_fields = ( $attr2['include_custom_fields']==1 ) ? 1 : 0;

                $CTX->set( 'k_include_custom_fields', $include_custom_fields );
            }
        }

        static function set_custom_fields_in_context( &$folder, $page_specific ){
            global $CTX;

            if( !$page_specific ){
                if( count($CTX->ctx)==1 /*folder-view*/ || $CTX->get('k_include_custom_fields', 1) /*cms:folders*/ ){
                    KExtendedFolders::_set_in_context( $folder );
                }
            }
        }

        static function _set_in_context( &$folder ){
            global $FUNCS, $CTX, $DB;

            $folder_masterpage = $CTX->get('k_folder_masterpage', 1);
            $folder_masterpage_id = $CTX->get('k_folder_masterpage_id', 1);

            if( is_null($folder_masterpage_id) ){
                $folder_masterpage_id = '';
                $folder_masterpage = KExtendedFolders::_get_folder_masterpage( $folder->template_name );
                if( strlen($folder_masterpage) ){
                    if( array_key_exists( $folder_masterpage, $FUNCS->cached_templates ) ){
                        $rs = $FUNCS->cached_templates[$folder_masterpage];
                    }
                    else{
                        $rs = $DB->select( K_TBL_TEMPLATES, array('*'), "name='" . $DB->sanitize( $folder_masterpage ). "'" );
                        if( !count($rs) ){
                            return;
                        }
                        $FUNCS->cached_templates[$rs[0]['id']] = $rs[0];
                        $FUNCS->cached_templates[$rs[0]['name']] = $rs[0];
                        $rs = (array)$rs[0];
                    }
                    $folder_masterpage_id = $rs['id'];
                }
                $CTX->set( 'k_folder_masterpage', $folder_masterpage );
                $CTX->set( 'k_folder_masterpage_id', $folder_masterpage_id );
            }

            if( strlen($folder_masterpage) ){
                $vars = array();

                // get the associated page
                $tpl_id = $folder_masterpage_id;
                $page_id = -1;
                $page_name = "folder-" . $DB->sanitize( $folder->id );

                $rs = $DB->select( K_TBL_PAGES, array('id'), "page_name='".$page_name."' AND template_id='" . $DB->sanitize( $tpl_id ). "'" );
                if( count($rs) ){
                    $page_id = $rs[0]['id'];
                    $vars['k_folder_extended_page_id'] = $page_id;
                    $vars['k_folder_extended_page_name'] = $page_name;
                }
                else{
                    $vars['k_folder_extended_page_id'] = '';
                    $vars['k_folder_extended_page_name'] = '';
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

    }// end class

    $FUNCS->add_event_listener( 'add_template_params', array('KExtendedFolders', 'add_template_params') );
    $FUNCS->add_event_listener( 'alter_folder_fields_info', array('KExtendedFolders', 'add_custom_folder_fields') );
    $FUNCS->add_event_listener( 'folder_saved', array('KExtendedFolders', 'save_custom_folder_fields') );
    $FUNCS->add_event_listener( 'folder_deleted', array('KExtendedFolders', 'delete_custom_folder_fields') );
    $FUNCS->add_event_listener( 'alter_folders_tag_params', array('KExtendedFolders', 'add_param_to_tag') );
    $FUNCS->add_event_listener( 'alter_folder_set_context', array('KExtendedFolders', 'set_custom_fields_in_context') );
