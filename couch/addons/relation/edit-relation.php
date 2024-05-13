<?php
    if ( !defined('K_ADMIN') ) die(); // cannot be loaded directly

    require_once( K_COUCH_DIR.'edit-pages.php' );

    class KRelationAdmin extends KPagesAdmin{
        var $field = null;
        var $data = null;
        var $ids = '';

        function __construct(){
            parent::__construct();
        }

        /////// 1. 'list' action ////////////////////////////////////////////////////
        function list_action_ex( $field, $data ){
            global $FUNCS;

            $this->field = $field;
            $this->data = $data;

            $FUNCS->add_event_listener( 'pages_list_post_action',  array($this, '_selected_ids_handler') );
            $FUNCS->add_event_listener( 'pages_list_bulk_action',  array($this, '_exit_action_handler') );

            return KBaseAdmin::list_action();
        }

        function _selected_ids_handler( &$redirect_dest ){
            global $FUNCS;

            if( isset($_POST['page-id']) ){
                foreach( $_POST['page-id'] as $v ){
                    if( $FUNCS->is_non_zero_natural($v) && !in_array($v, $this->data->selected_ids) ){
                        if( $this->field->has=='one' ){
                            $this->data->selected_ids = array( intval($v) );
                            break;
                        }
                        else{
                            $this->data->selected_ids[] = intval( $v );
                        }
                    }
                }
            }
            if( isset($_POST['deselected_ids']) ){
                $deselected_ids = array_map( "trim", explode(',', $_POST['deselected_ids']) );

                foreach( $deselected_ids as $v ){
                    if( $FUNCS->is_non_zero_natural($v) && (($key = array_search($v, $this->data->selected_ids))!==false) ){
                        unset( $this->data->selected_ids[$key] );
                    }
                }
            }

            if( $this->field->has=='one' && (isset($_POST['page-id']) || isset($_POST['deselected_ids'])) ){
                $this->_exit_action_handler( 'exit_save', $redirect_dest );
            }
        }

        function _exit_action_handler( $action, &$redirect_dest ){
            global $FUNCS, $CTX, $KSESSION;

            if( $action!='exit_cancel' && $action!='exit_save') return;

            $CTX->set( 'k_relation_selected_ids_string', trim(implode(',', $this->data->selected_ids)), 'global' );
            $CTX->set( 'k_relation_exit_action', $action, 'global' );
            $redirect_dest = '';

            $KSESSION->delete_var( $_GET['sid'] );
        }

        function render_list(){
            global $FUNCS, $CTX;

            $CTX->set( 'k_cur_form', $this->list_form_name, 'global' );
            $CTX->set( 'k_list_is_searchable', 1, 'global' );
            $CTX->set( 'k_relation_selected_ids', $this->data->selected_ids, 'global' );
            $CTX->set( 'k_relation_selected_ids_count', count($this->data->selected_ids), 'global' );
            $CTX->set( 'k_has_one', ( $this->field->has=='one' ) ? '1' : '0', 'global' );

            $FUNCS->add_event_listener( 'alter_page_tag_query',  array($this, '_alter_relation_page_tag_query_handler') );

            $html = $FUNCS->render( 'content_list_relation' );
            $html = $FUNCS->render( 'main', $html, 1 );
            $FUNCS->route_fully_rendered = 1;

            return $html;
        }

        function define_list_fields(){
            return KBaseAdmin::define_list_fields();
        }

        function _default_list_toolbar_actions(){
            return KBaseAdmin::_default_list_toolbar_actions();
        }

        function _default_list_batch_actions(){
            return KBaseAdmin::_default_list_batch_actions();
        }

        function _default_list_page_actions(){
            global $FUNCS;
            $arr_actions = array();

            $arr_actions['btn_cancel'] =
                array(
                    'title'=>$FUNCS->t('cancel'),
                    'onclick'=>array(
                        "var form=$('#".$this->list_form_name."');",
                        "form.find('#k_bulk_action').val('exit_cancel');",
                        "form.submit();",
                        "return false;",
                    ),
                    'icon'=>'circle-x',
                    'weight'=>0,
                );

            if( $this->field->has!='one' ){
                $arr_actions['btn_submit'] =
                    array(
                        'title'=>$FUNCS->t('ok'),
                        'onclick'=>array(
                            "var form=$('#".$this->list_form_name."');",
                            "form.find('#k_bulk_action').val('exit_save');",
                            "form.submit();",
                            "return false;",
                        ),
                        'class'=>'btn-primary',
                        'icon'=>'circle-check',
                        'weight'=>10,
                    );
            }
            else{
                $arr_actions['btn_cancel']['class']='btn-primary';
            }

            return $arr_actions;
        }

        function _default_list_row_actions(){
            return KBaseAdmin::_default_list_row_actions();
        }

        function _default_list_fields(){
            $fields = parent::_default_list_fields();

            if( array_key_exists('k_page_title', $fields) ){
                $fields['k_page_title']['content']="<strong><cms:show k_page_title /></strong>";
            }

            if( array_key_exists('k_selector_checkbox', $fields) ){
                $fields['k_selector_checkbox']['header']="<cms:render 'relation_list_checkbox' for_header='1' />";
                $fields['k_selector_checkbox']['content']="<cms:render 'relation_list_checkbox' />";
            }

            $skip = array( 'k_comments_count', 'k_up_down', 'k_actions' );
            foreach( $skip as $k ){
                unset( $fields[$k] );
            }

            return $fields;
        }

        function _set_list_sort( $orderby='', $order='' ){
            global $FUNCS;

            $orderby = trim( $this->field->orderby );
            if( !in_array($orderby, array('publish_date', 'page_title', 'page_name', 'weight')) ) $orderby = 'publish_date';

            $order = trim( $this->field->order_dir );
            if( $order!='desc' && $order!='asc' ) $order = 'desc';

            $FUNCS->set_admin_list_default_sort( $orderby, $order );
        }

        function _set_list_limit( $limit='' ){
            global $FUNCS;

            $limit = trim( $limit );
            if( $limit=='' ){  $limit = '25'; }

            $FUNCS->set_admin_list_default_limit( $limit );
        }

        // event handlers
        function _alter_relation_page_tag_query_handler(&$distinct, &$count_query_field, &$count_query_field_as, &$query_fields, &$query_table, &$sql, &$group_by, &$having, &$order_sql, &$limit_sql, &$mode, $params, $node, $rec_tpl, $token){
            global $FUNCS, $CTX, $DB;
            if( $node->name!=='pages' || $token!==$CTX->get('k_cur_token') ){ return; }

            $sql = str_replace( 'AND p.parent_id=0', '', $sql );

            // folder?
            $sql .= ' ' . $this->field->_get_folder_sql( $rec_tpl['id'] );

            // reverse has one?
            if( $this->field->reverse_has=='one' ){
                // show only pages that are not already selected by others of the same relation field
                $sql .= ' ' . $this->field->_get_reverse_has_one_sql();
            }

            // already selected ids
            $sql .= $FUNCS->gen_sql( 'NOT '.$this->data->skip_ids, 'p.id', 1 /*$validate_natural*/ );

        }

        // route filters
        static function resolve_entities( $route ){
            global $FUNCS, $PAGE, $DB, $AUTH, $KSESSION;

            $page_id = $route->values['page_id'];
            $sub_tpl_id =  ( !$page_id && $_GET['sub_tpl_id'] && $FUNCS->is_non_zero_natural($_GET['sub_tpl_id']) ) ? (int)$_GET['sub_tpl_id'] : null;

            if( isset($_POST['__k_relation_ids__']) ){
                // save ids in session..
                $sid = md5( $AUTH->hasher->get_random_bytes(16) );
                $obj = new stdClass();
                $obj->skip_ids = $_POST['__k_relation_ids__'];
                $obj->selected_ids = array();
                $KSESSION->set_var( $sid, $obj );

                $redirect_dest = K_ADMIN_URL . K_ADMIN_PAGE . '?o=relation&q=' . $route->matched_path . '&sid=' . $sid;
                if( $sub_tpl_id ){
                    $redirect_dest .= '&sub_tpl_id='.$sub_tpl_id;
                }
                header( "Location: " . $redirect_dest );
                exit;
            }

            if( !isset($_GET['sid']) ){ return $FUNCS->raise_error( ROUTE_NOT_FOUND ); }
            $sid = $_GET['sid'];

            $tpl_id = $route->values['tpl_id'];
            $field_name = $route->values['field_name'];

            // set field object
            $field = null;
            if( $sub_tpl_id ){
                $listener_get_sub_template = function(&$subtpl_id) use($sub_tpl_id){
                    $subtpl_id = $sub_tpl_id;
                };
                $FUNCS->add_event_listener( 'get_sub_template_of_new_page', $listener_get_sub_template );
                $page_id = '-1';
            }

            $pg = new KWebpage( $tpl_id, $page_id );

            if( !$pg->error ){
                if( isset($pg->_fields[$field_name]) && $pg->_fields[$field_name]->k_type=='relation'){
                    $field = $pg->_fields[$field_name];
                }
            }
            if( $sub_tpl_id ){
                $FUNCS->remove_event_listener( 'get_sub_template_of_new_page', $listener_get_sub_template );
            }

            // set $PAGE object
            $pg2 = null;
            if( !is_null($field) ){
                $rs = $DB->select( K_TBL_TEMPLATES, array('id'), "name='" . $DB->sanitize( $field->masterpage ). "'" );
                if( count($rs) ){
                    $pg2 = new KWebpage( $rs[0]['id'], null );
                    if( $pg2->error ){ $pg2=null; }
                }
            }

            if( is_null($pg2) ){
                $KSESSION->delete_var( $sid );
                return $FUNCS->raise_error( ROUTE_NOT_FOUND );
            }

            $route->resolved_values['field'] = $field;
            $route->resolved_values['data'] = $KSESSION->get_var( $sid );
            $PAGE = $pg2;
            $PAGE->folders->set_sort( 'weight', 'asc' );
            $PAGE->folders->sort( 1 );
            $PAGE->set_context();
        }

    } // end class KRelationAdmin
