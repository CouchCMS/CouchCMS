<?php
    if ( !defined('K_ADMIN') ) die(); // cannot be loaded directly

    require_once( K_COUCH_DIR.'edit-pages.php' );

    class KMosaicAdmin extends KPagesAdmin{

        function __construct(){
            parent::__construct();
        }

        /////// 1. 'form' action  ////////////////////////////////////////////////////
        function render_form(){
            global $FUNCS, $CTX, $PAGE;

            $CTX->set( 'k_cur_form', $this->form_name, 'global' );

            if( $PAGE->id==-1 && isset($_POST['k_hid_'.$this->form_name]) ){
                $FUNCS->add_event_listener( 'alter_create_insert',  array('KMosaicAdmin', 'alter_create_insert') );
            }

            $html = $FUNCS->render( 'content_form_mosaic' );
            $html = $FUNCS->render( 'main', $html, 1 );
            $FUNCS->route_fully_rendered = 1;

            return $html;
        }

        function define_form_fields(){
            global $FUNCS;

            $FUNCS->add_event_listener( 'alter_pages_form_default_fields_final',  array('KMosaicAdmin', 'alter_form_fields') );
            parent::define_form_fields();
            $FUNCS->remove_event_listener( 'alter_pages_form_default_fields_final', array('KMosaicAdmin', 'alter_form_fields') );
        }

        static function alter_create_insert( &$arr_insert, &$pg ){
            global $PAGE;

            if( $PAGE->tpl_id == $pg->tpl_id ){
                $arr_insert['ref_count']=0;
                $arr_insert['status']=K_MOSAIC_STATUS_ORPHAN;
            }
        }

        static function alter_form_fields( &$arr_default_fields ){
            global $FUNCS;

            if( array_key_exists('_system_fields_', $arr_default_fields) ){
                $arr_default_fields['_system_fields_']['skip']=1;
            }
            $FUNCS->dispatch_event( 'alter_mosaic_form_fields', array(&$arr_default_fields) );
        }

        function _default_form_page_actions(){
            global $FUNCS;

            $arr_actions = KBaseAdmin::_default_form_page_actions();

            if( array_key_exists('btn_submit', $arr_actions) ){
                $arr_actions['btn_submit']['title'] = $FUNCS->t('ok');
            }

            $arr_actions['btn_cancel'] =
                array(
                    'title'=>$FUNCS->t('cancel'),
                    'onclick'=>array(
                        "var msg = window.onbeforeunload();",
                        "if( msg ){ if( !confirm(msg) ){ this.blur(); return false; } }",
                        "window.onbeforeunload = null;",
                        "window.parent.COUCH.mosaicModalClose();",
                        "return false;",
                    ),
                    'icon'=>'circle-x',
                    'weight'=>0,
                );

            return $arr_actions;
        }

        // route filters
        static function resolve_page( $route, $act ){
            global $FUNCS, $DB, $PAGE, $CTX, $AUTH;

            $rs = $DB->select( K_TBL_TEMPLATES, array('*'), "name='" . $DB->sanitize( $route->masterpage ). "'" );
            if( !count($rs) ){
                return $FUNCS->raise_error( ROUTE_NOT_FOUND );
            }
            $tpl = $rs[0];

            $tpl_id = $tpl['id'];
            $tpl_name = $tpl['name'];
            $page_id = $route->resolved_values['id'];
            $nonce = $route->resolved_values['nonce'];

            // validate
            $FUNCS->validate_nonce( 'edit_page_' . $page_id, $nonce );

            // get page ..
            $pg = new KWebpage( $tpl_id, $page_id );
            if( $pg->error ){
                return $FUNCS->raise_error( ROUTE_NOT_FOUND );
            }

            // and clone it (in a hackish way) ..
            $name = md5( $AUTH->hasher->get_random_bytes(16) );
            $pg->id = -1;
            $pg->page_title = $name;
            $pg->page_name = $name;
            for( $x=0; $x<count($pg->fields); $x++ ){
                $f = &$pg->fields[$x];
                $f->page_id = $pg->id;
                $f->modified = 1;
                unset( $f );
            }

            // set cloned page as the page object to edit
            $PAGE = $pg;
            $PAGE->folders->set_sort( 'weight', 'asc' );
            $PAGE->folders->sort( 1 );
            $PAGE->set_context();

            // also set it as target for form submission
            $link = $FUNCS->generate_route( $PAGE->tpl_name, 'create_view', array('nonce'=>$FUNCS->create_nonce('create_page_'.$PAGE->tpl_id)) );
            $CTX->set( 'k_form_target', $link );
        }
    } // end class KMosaicAdmin
