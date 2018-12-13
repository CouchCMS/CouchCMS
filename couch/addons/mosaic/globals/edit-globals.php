<?php
    if ( !defined('K_ADMIN') ) die(); // cannot be loaded directly

    require_once( K_COUCH_DIR.'edit-pages.php' );

    class KEditGlobals extends KPagesAdmin{
        private $parent_title;
        function __construct(){
            global $FUNCS;

            parent::__construct();
            $FUNCS->add_event_listener( 'alter_pages_form_fields', array($this, '_hide_system_fields') );
        }

        function form_action_ex( $parent_title ){
            global $PAGE, $DB;

            $this->parent_title = $parent_title;
            $rs = $DB->select( K_TBL_TEMPLATES, array('config_form'), "id='" . $DB->sanitize( $PAGE->tpl_id ). "'" );
            if( count($rs) ){
                $this->arr_config = @unserialize( base64_decode($rs[0]['config_form']) );
            }

            return KBaseAdmin::form_action();
        }

        function define_form_title(){
            global $FUNCS, $PAGE;

            $text = $FUNCS->t('globals') . ' (';
            $text .= $this->parent_title;
            $text .= ')';
            $link = '';
            $icon = '';
            $FUNCS->set_admin_title( $text, $link, $icon );

            // subtitle
            $subtitle = $FUNCS->t('edit');
            $icon = 'globe';
            $FUNCS->set_admin_subtitle( $subtitle, $icon );
        }

        function _set_advanced_setting_fields( &$arr_fields ){
            return;
        }

        function _hide_system_fields( &$fields ){
            global $PAGE;

            foreach( $fields as $k=>$v ){
                if( array_key_exists($k, $PAGE->_fields) && $PAGE->_fields[$k]->system ){
                    $fields[$k]['hide'] = 1;
                }
            }
        }

        function _default_form_page_actions(){
            $arr_actions = parent::_default_form_page_actions();
            if( array_key_exists('btn_view', $arr_actions) ){ // no 'view'
                unset( $arr_actions['btn_view'] );
            }

            return $arr_actions;
        }

        // route filters
        static function resolve_page( $route, $act ){
            global $FUNCS, $DB, $PAGE, $CTX;

            $global_tpl_name = KGlobals::_get_filename( $route->masterpage );
            $rs = $DB->select( K_TBL_TEMPLATES, array('*'), "name='" . $DB->sanitize( $global_tpl_name ). "'" );
            if( !count($rs) ){
                return $FUNCS->raise_error( ROUTE_NOT_FOUND );
            }
            $tpl_id = $rs[0]['id'];

            // validate nonce
            $nonce = $route->resolved_values['nonce'];
            $FUNCS->validate_nonce( 'edit_globals_' . $global_tpl_name, $nonce );

            $rs = $DB->select( K_TBL_TEMPLATES, array('*'), "name='" . $DB->sanitize( $route->masterpage ). "'" );
            $route->resolved_values['parent_title'] = $rs[0]['title'] ? $rs[0]['title'] : $rs[0]['name'];

            // set page object
            $PAGE = new KWebpage( $tpl_id );
            if( $PAGE->error ){
                return $FUNCS->raise_error( ROUTE_NOT_FOUND );
            }
            $PAGE->folders->set_sort( 'weight', 'asc' );
            $PAGE->folders->sort( 1 );
            $PAGE->set_context();
        }
    }// end class KEditGlobals
