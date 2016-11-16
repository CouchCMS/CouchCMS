<?php
    if ( !defined('K_ADMIN') ) die(); // cannot be loaded directly

    require_once( K_COUCH_DIR.'base.php' );

    class KPagesAdmin extends KBaseAdmin{

        var $persist_params = null;
        var $arr_config = null;

        function __construct(){
            parent::__construct();
        }

        /////// 1. 'list' action ////////////////////////////////////////////////////
        function list_action(){
            global $FUNCS, $PAGE, $DB, $CTX;

            // check if template's physical file missing
            if( !file_exists(K_SITE_DIR . $PAGE->tpl_name) ){
                $html = $FUNCS->render( 'template_missing' );

                $rs = $DB->select( K_TBL_PAGES, array('id'), "template_id='" . $DB->sanitize( $PAGE->tpl_id ). "' AND is_master<>'1'" );
                if( count($rs) ){
                    $FUNCS->add_html( $html );
                }
                else{
                    $this->define_list_title();
                    return $html;
                }
            }

            $rs = $DB->select( K_TBL_TEMPLATES, array('config_list'), "id='" . $DB->sanitize( $PAGE->tpl_id ). "'" );
            if( count($rs) ){
                $this->arr_config = @unserialize( base64_decode($rs[0]['config_list']) );
            }

            return parent::list_action();
        }

        function render_list(){
            global $FUNCS, $CTX, $PAGE;

            $CTX->set( 'k_cur_form', $this->list_form_name, 'global' );

            // first check if any custom list screen registered for this template (deprecated now .. included only for legacy support)
            if( array_key_exists( $PAGE->tpl_name, $FUNCS->admin_list_views ) ){
                // register the custom list-view to be rendered as 'content_list_inner'
                $this->_custom_admin_list_screen = $FUNCS->admin_list_views[$PAGE->tpl_name];
                $FUNCS->renderables['content_list_inner'][] = array( 'renderable'=>array($this, '_custom_admin_screen') );
            }

            $html = $FUNCS->render( 'content_list' );

            return $html;
        }

        function define_list_title(){
            global $FUNCS, $PAGE, $CTX;

            $text = $PAGE->tpl_title ? $PAGE->tpl_title : $PAGE->tpl_name;
            $link = ( $PAGE->tpl_is_clonable ) ? $FUNCS->generate_route( $PAGE->tpl_name, 'list_view' ) : '';
            if( $link!='' ){ $link = $FUNCS->get_qs_link( $link ); }
            $icon = '';
            $FUNCS->set_admin_title( $text, $link, $icon );

            // subtitle
            $subtitle = $FUNCS->t('list');
            $icon = $PAGE->tpl_gallery == 1 ? 'image' : 'file';
            $FUNCS->set_admin_subtitle( $subtitle, $icon );
        }

        function define_list_fields(){
            global $FUNCS, $DB, $PAGE, $CTX;

            // get default fields
            $arr_default_fields = $this->_default_list_fields();

            // give other modules a chance to override default values
            $FUNCS->dispatch_event( 'alter_pages_list_default_fields', array(&$arr_default_fields, &$this) );

            foreach( array_keys($arr_default_fields) as $name ){
                $arr_default_fields[$name]['name'] = $name;
            }

            // get fields chosen by user to show (configured through the template)
            $arr_fields = $arr_js = $arr_css = $arr_html = array();
            $orderby = $order = $limit = $exclude = $searchable = '';
            $arr_config = $this->arr_config;

            if( is_array($arr_config) ){
                if( isset($arr_config['arr_fields']) ){
                    $arr_fields = $arr_config['arr_fields'];
                }
                if( isset($arr_config['js']) ){ $arr_js = $arr_config['js']; }
                if( isset($arr_config['css']) ){ $arr_css = $arr_config['css']; }
                if( isset($arr_config['html']) ){ $arr_html = $arr_config['html']; }

                if( isset($arr_config['orderby']) ){ $orderby = $arr_config['orderby']; }
                if( isset($arr_config['order']) ){ $order = $arr_config['order']; }
                if( isset($arr_config['limit']) ){ $limit = $arr_config['limit']; }
                if( isset($arr_config['exclude']) ){ $exclude = $arr_config['exclude']; }
                if( isset($arr_config['searchable']) ){ $searchable = $arr_config['searchable']; }
            }

            // if no fields chosen by user, choose a default list of fields to show
            if( !is_array($arr_fields) || !count($arr_fields) ){
                $arr_tmp = array();
                $arr_fields = $this->_selected_list_fields();

                // give other modules a chance to override this list
                $FUNCS->dispatch_event( 'alter_pages_list_selected_fields', array(&$arr_fields, &$this) );

                foreach( $arr_fields as $k ){
                    if( array_key_exists($k, $arr_default_fields) ){
                        $arr_tmp[$k] = $arr_default_fields[$k];
                    }
                }
                $arr_default_fields = $arr_tmp;
            }
            else{ // use fields chosen by user
                $arr_tmp = array();
                foreach( $arr_fields as $k=>$v ){
                    if( array_key_exists($k, $arr_default_fields) ){
                        $arr_tmp[$k] = array_merge( $arr_default_fields[$k], $v );
                    }
                    else{
                        $arr_tmp[$k] = $v;
                    }
                }
                $arr_default_fields = $arr_tmp;
            }

            // give other modules a chance to override final values
            $FUNCS->dispatch_event( 'alter_pages_list_default_fields_final', array(&$arr_default_fields, &$this) );

            // and register the fields
            $FUNCS->admin_list_fields = array();
            foreach( $arr_default_fields as $field ){
                $FUNCS->add_list_field( $field );
            }

            $FUNCS->dispatch_event( 'add_pages_list_fields' );
            define( 'K_ADD_LIST_FIELDS_DONE', '1' );
            $FUNCS->dispatch_event( 'alter_pages_list_fields', array(&$FUNCS->admin_list_fields) );

            // add custom js/css/html, if specified
            if( is_array($arr_js) && count($arr_js) ){
                $js = '';
                foreach( $arr_js as $child ){
                    $js .= $child->get_HTML();
                }
                $FUNCS->add_js( $js );
            }

            if( is_array($arr_css) && count($arr_css) ){
                $css = '';
                foreach( $arr_css as $child ){
                    $css .= $child->get_HTML();
                }
                $FUNCS->add_css( $css );
            }

            if( is_array($arr_html) && count($arr_html) ){
                $html = '';
                foreach( $arr_html as $child ){
                    $html .= $child->get_HTML();
                }
                $FUNCS->add_html( $html );
            }

            // set pages to be exclude form the listing
            $exclude = trim( $exclude );
            if( strlen($exclude) ){ $CTX->set( 'k_selected_exclude', $exclude, 'global' ); }

            // set default limit
            $this->_set_list_limit( $limit );

            // set default sort field and order
            $this->_set_list_sort( $orderby, $order );

            // set if listing is searchable
            $CTX->set( 'k_list_is_searchable', $searchable, 'global' );
        }

        function _default_list_toolbar_actions(){
            global $FUNCS, $PAGE, $AUTH;
            $arr_buttons = array();

            if( file_exists(K_SITE_DIR . $PAGE->tpl_name) ){
                if( $AUTH->user->access_level >= $PAGE->tpl_access_level ){
                    $arr_buttons['create_new'] =
                        array(
                            'title'=>$FUNCS->t('add_new'),
                            'desc'=>$FUNCS->t('add_new_page'),
                            'href'=>$FUNCS->get_qs_link( $FUNCS->generate_route( $PAGE->tpl_name, 'create_view', array('nonce'=>$FUNCS->create_nonce('create_page_'.$PAGE->tpl_id))) ),
                            'icon'=>'plus',
                            'weight'=>10,
                        );

                    if( $PAGE->tpl_dynamic_folders ){
                        $arr_buttons['view_folders'] =
                            array(
                                'title'=>$FUNCS->t('manage_folders'),
                                'href'=>$FUNCS->generate_route( $PAGE->tpl_name, 'folder_list_view' ),
                                'icon'=>'folder',
                                'weight'=>20,
                            );
                    }
                }
                $arr_buttons['view_template'] =
                    array(
                        'title'=>$FUNCS->t('view'),
                        'href'=>K_SITE_URL.$PAGE->tpl_name,
                        'icon'=>'magnifying-glass',
                        'target'=>'_blank',
                        'weight'=>30,
                    );
            }

            return $arr_buttons;
        }

        function _default_list_filter_actions(){
            global $FUNCS;
            $arr_filters = array();

            $arr_filters['filter_folders'] =
                array(
                    'render'=>'filter_folders',
                    'weight'=>10,
                );

            $arr_filters['filter_search'] =
                array(
                    'render'=>'filter_search',
                    'weight'=>20,
                );

            $arr_filters['filter_sort'] =
                array(
                    'render'=>'filter_sort',
                    'no_wrapper'=>1, // has no form gui
                    'weight'=>30,
                );

            $arr_filters['filter_related'] =
                array(
                    'render'=>'filter_related',
                    'no_wrapper'=>1, // has no form gui
                    'weight'=>40,
                );

            return $arr_filters;
        }

        function _default_list_batch_actions(){
            global $FUNCS;
            $arr_actions = array();

            $arr_actions['batch_delete'] =
                array(
                    'title'=>$FUNCS->t( 'delete' ),
                    'confirmation_msg'=>$FUNCS->t('confirm_delete_selected_pages'),
                    'weight'=>10,
                    'listener'=>array( 'pages_list_bulk_action', array($this, _delete_handler) ),
                );

            return $arr_actions;
        }

        function _default_list_row_actions(){
            global $FUNCS;
            $arr_actions = array();

            $arr_actions['edit'] =
                array(
                    'render'=>'row_action_edit',
                    'weight'=>10,
                );

            $arr_actions['delete'] =
                array(
                    'render'=>'row_action_delete',
                    'weight'=>20,
                );

            $arr_actions['view'] =
                array(
                    'render'=>'row_action_view',
                    'weight'=>30,
                );

            return $arr_actions;
        }

        function _default_list_fields(){
            global $FUNCS;
            $arr_default_fields = array();

            $arr_default_fields['k_selector_checkbox'] =
                array(
                    'header_class'=>'checkbox',
                    'header'=>"<cms:render 'list_checkbox' for_header='1' />",
                    'weight'=>'-10',
                    'class'=>'checkbox',
                    'content'=>"<cms:render 'list_checkbox' />",
                );

            $arr_default_fields['k_page_title'] =
                array(
                    'weight'=>'0',
                    'header'=>$FUNCS->t('title'),
                    'class'=>'title',
                    'content'=>"<cms:render 'list_title' />",
                    'sortable'=>'1',
                    'sort_name'=>'page_title',
                );

            $arr_default_fields['k_comments_count'] =
                array(
                    'weight'=>'10',
                    'header'=>'&nbsp;',
                    'class'=>'count',
                    'content'=>"<cms:render 'list_comments_count' />",
                );

            $arr_default_fields['k_page_foldertitle'] =
                array(
                    'weight'=>'20',
                    'header'=>$FUNCS->t('folder'),
                    'class'=>'folder',
                );

            $arr_default_fields['k_page_date'] =
                array(
                    'weight'=>'30',
                    'header'=>$FUNCS->t('date'),
                    'content'=>"<cms:render 'list_date' />",
                    'class'=>'date',
                    'sortable'=>'1',
                    'sort_name'=>'publish_date',
                );

            $arr_default_fields['k_up_down'] =
                array(
                    'weight'=>'40',
                    'header'=>'&nbsp;',
                    'class'=>'up-down',
                    'content'=>"<cms:render 'list_updown' />",
                    'sortable'=>'0',
                    'sort_name'=>'weight',
                    'listener'=>array( 'pages_list_post_action', array($this, _updown_handler) ),
                );

            $arr_default_fields['k_actions'] =
                array(
                    'weight'=>'50',
                    'header'=>$FUNCS->t('actions'),
                    'class'=>'actions',
                    'content'=>"<cms:render 'row_actions' />",
                );

            return $arr_default_fields;
        }

        function _selected_list_fields(){
            return array( 'k_selector_checkbox', 'k_page_title', 'k_comments_count', 'k_page_foldertitle', 'k_page_date', 'k_actions' );
        }

        function _set_list_sort( $orderby='', $order='' ){
            global $FUNCS;

            $orderby = trim( $orderby );
            $order = trim( $order );
            if( $orderby=='' ){  $orderby = 'publish_date'; }
            if( $order=='' ){  $order = 'desc'; }

            $FUNCS->set_admin_list_default_sort( $orderby, $order );
        }

        // event handlers
        function _delete_handler( $action ){
            global $FUNCS, $DB, $PAGE;

            if( $action=='batch_delete' && isset($_POST['page-id']) ){

                $DB->begin();

                // serialize access.. lock template as this could involve working with nested pages tree.
                $DB->update( K_TBL_TEMPLATES, array('description'=>$DB->sanitize( $PAGE->tpl_desc )), "id='" . $DB->sanitize( $PAGE->tpl_id ) . "'" );

                foreach( $_POST['page-id'] as $v ){
                    if( $FUNCS->is_non_zero_natural($v) ){
                        $page_id = intval( $v );
                        $pg = new KWebpage( $PAGE->tpl_id, $page_id );
                        if( $pg->error ){
                            ob_end_clean();
                            die( 'ERROR in deletion: ' . $pg->err_msg );
                        }

                        // execute action
                        $pg->delete();
                        $FUNCS->invalidate_cache();
                    }
                }

                if( $PAGE->tpl_nested_pages ){
                    $PAGE->reset_weights_of(); // entire tree
                }

                $DB->commit();
            }
        }

        function _updown_handler( &$redirect_dest ){
            global $FUNCS, $DB, $PAGE;

            if( isset($_POST['k_updown']) ){ // Move pages up-down

                $FUNCS->validate_nonce( 'updown_page_'.$PAGE->tpl_id );

                $FUNCS->add_event_listener( 'alter_page_tag_query_ex',  array($this, '_alter_page_tag_query_handler') );

                // no redirection and no decorator rendering
                $redirect_dest = '';
                $FUNCS->route_fully_rendered = 1;
            }
        }

        function _alter_page_tag_query_handler(&$distinct, &$count_query_field, &$count_query_field_as, &$query_fields, &$query_table, &$orig_sql, &$sql, &$group_by, &$having, &$order_sql, &$limit_sql, &$mode, $params, $node, $rec_tpl, $token){
            global $FUNCS, $CTX, $DB;
            if( $node->name!=='pages' || $token!==$CTX->get('k_cur_token') ){ return; }

            extract( $FUNCS->get_named_vars(
                   array(
                          'orderby'=>'',
                          'order'=>'',
                         ),
                   $params)
            );
            $orderby = trim( strtolower($orderby) );
            $order = trim( strtolower($order) );
            if( $orderby!=='weight' || ( $order!='desc' && $order!='asc' ) ){ return; }

            $page_id = intval( $_POST['id'] );
            $dir = intval( $_POST['dir'] );

            if( !$FUNCS->is_non_zero_natural($_POST['id']) || !$FUNCS->is_natural($_POST['dir'])){
                die( 'ERROR: invalid input' );
            }

            // get selected page
            $my_query_fields = array( 'p.id', 'p.k_order' );
            $my_sql = "p.id='".$DB->sanitize( $page_id )."' AND " . $sql;
            $my_sql .= ' LIMIT 0, 1';

            $rs = $DB->select( $query_table, $my_query_fields, $my_sql, $distinct );
            if( !count($rs) ) die( 'Page not found' );
            $val_1 = $rs[0]['k_order'];

            // get adjacent page
            if( $order=='desc' ){
                $op = ($dir) ? '>' : '<';
                $order = ($dir) ? 'asc' : 'desc';
            }
            else{
                $op = ($dir) ? '<' : '>';
                $order = ($dir) ? 'desc' : 'asc';
            }
            $my_sql = "p.k_order ".$op." '".$DB->sanitize( $val_1 )."' AND " . $sql;
            $my_sql .= ' ORDER BY p.k_order ' .$order.' LIMIT 0, 1';

            $rs = $DB->select( $query_table, $my_query_fields, $my_sql, $distinct );
            if( !count($rs) ) die( 'Page not found' );
            $page_id_2 = $rs[0]['id'];
            $val_2 = $rs[0]['k_order'];

            // swap
            $DB->begin();
            $DB->update( K_TBL_PAGES, array('k_order'=>$val_2), "id='" . $DB->sanitize( $page_id ). "'" );
            $DB->update( K_TBL_PAGES, array('k_order'=>$val_1), "id='" . $DB->sanitize( $page_id_2 ). "'" );
            $DB->commit();
        }

        /////// 2. 'form' action (edit/create) ////////////////////////////////////////////////////
        function form_action(){
            global $PAGE, $FUNCS, $DB;

            // If template is non-clonable and physical file missing
            if( !$PAGE->tpl_is_clonable && !file_exists(K_SITE_DIR . $PAGE->tpl_name) ){
                $this->define_form_title();
                return $FUNCS->render( 'template_missing' );
            }

            $rs = $DB->select( K_TBL_TEMPLATES, array('config_form'), "id='" . $DB->sanitize( $PAGE->tpl_id ). "'" );
            if( count($rs) ){
                $this->arr_config = @unserialize( base64_decode($rs[0]['config_form']) );
            }

            return parent::form_action();
        }

        function render_form(){
            global $FUNCS, $CTX, $PAGE;

            $CTX->set( 'k_cur_form', $this->form_name, 'global' );

            // first check if any custom page screen registered for this template (deprecated now .. included only for legacy support)
            if( array_key_exists( $PAGE->tpl_name, $FUNCS->admin_page_views ) ){
                // register the custom list-view to be rendered as 'content_form'
                $this->_custom_admin_page_screen = $FUNCS->admin_page_views[$PAGE->tpl_name][0];
                $FUNCS->renderables['content_form'][] = array( 'renderable'=>array($this, '_custom_admin_screen') );
            }

            $html = $FUNCS->render( 'content_form' );

            return $html;
        }

        function define_form_title(){
            global $FUNCS, $PAGE, $CTX;

            $text = $PAGE->tpl_title ? $PAGE->tpl_title : $PAGE->tpl_name;
            $link = ( $PAGE->tpl_is_clonable ) ? $FUNCS->generate_route( $PAGE->tpl_name, 'list_view' ) : '';
            if( $link!='' ){ $link = $FUNCS->get_qs_link( $link ); }
            $icon = '';
            $FUNCS->set_admin_title( $text, $link, $icon );

            // subtitle
            $subtitle = ( $PAGE->id == -1  ) ? $FUNCS->t('add_new') : $FUNCS->t('edit');
            $icon = $PAGE->tpl_gallery == 1 ? 'image' : 'file';
            $FUNCS->set_admin_subtitle( $subtitle, $icon );
        }

        function define_form_fields(){
            global $FUNCS, $DB, $PAGE, $CTX, $AUTH;

            // get default fields
            $arr_default_fields = $this->_default_form_fields();

            // give other modules a chance to override default values
            $FUNCS->dispatch_event( 'alter_pages_form_default_fields', array(&$arr_default_fields, &$this) );

            foreach( array_keys($arr_default_fields) as $name ){
                $arr_default_fields[$name]['name'] = $name;
            }

            // get fields configured by user through the template
            $arr_fields = $arr_js = $arr_css = $arr_html = $arr_params = array();
            $arr_config = $this->arr_config;

            if( is_array($arr_config) ){
                if( isset($arr_config['arr_fields']) ){
                    $arr_fields = $arr_config['arr_fields'];
                }
                if( isset($arr_config['js']) ){ $arr_js = $arr_config['js']; }
                if( isset($arr_config['css']) ){ $arr_css = $arr_config['css']; }
                if( isset($arr_config['html']) ){ $arr_html = $arr_config['html']; }
                if( isset($arr_config['params']) ){ $arr_params = $arr_config['params']; }
            }

            // merge both
            if( is_array($arr_fields) && count($arr_fields) ){
                foreach( $arr_fields as $k=>$v ){
                    if( array_key_exists($k, $arr_default_fields) ){
                        $arr_default_fields[$k] = array_merge( $arr_default_fields[$k], $v );
                    }
                    else{
                        if( trim($v['group'])=='' ){ $v['group']='_custom_fields_'; }
                        $arr_default_fields[$k] = $v;
                    }
                }
            }

            // give other modules a chance to override final values
            $FUNCS->dispatch_event( 'alter_pages_form_default_fields_final', array(&$arr_default_fields, &$this) );

            // and register the fields
            $FUNCS->admin_form_fields = array();
            foreach( $arr_default_fields as $field ){
                $FUNCS->add_form_field( $field );
            }

            $FUNCS->dispatch_event( 'add_pages_form_fields' );
            define( 'K_ADD_FORM_FIELDS_DONE', '1' );
            $FUNCS->dispatch_event( 'alter_pages_form_fields', array(&$FUNCS->admin_form_fields) );

            // add custom js/css/html, if specified
            if( is_array($arr_js) && count($arr_js) ){
                $js = '';
                foreach( $arr_js as $child ){
                    $js .= $child->get_HTML();
                }
                $FUNCS->add_js( $js );
            }

            if( is_array($arr_css) && count($arr_css) ){
                $css = '';
                foreach( $arr_css as $child ){
                    $css .= $child->get_HTML();
                }
                $FUNCS->add_css( $css );
            }

            if( is_array($arr_html) && count($arr_html) ){
                $html = '';
                foreach( $arr_html as $child ){
                    $html .= $child->get_HTML();
                }
                $FUNCS->add_html( $html );
            }

            if( is_array($arr_params) && count($arr_params) ){
                $this->persist_params = $arr_params; // additional parameters for cms:db_persist_form tag on the form
            }

            // set databound form that will render the fields
            $this->_setup_form();
        }

        /// helper form functions
        function _default_form_toolbar_actions(){
            global $FUNCS, $PAGE, $AUTH;
            $arr_buttons = array();

            if( $PAGE->id != -1 && $PAGE->tpl_is_clonable && file_exists(K_SITE_DIR . $PAGE->tpl_name) && $AUTH->user->access_level >= $PAGE->tpl_access_level){
                $arr_buttons['create_new'] =
                    array(
                        'title'=>$FUNCS->t('add_new'),
                        'desc'=>$FUNCS->t('add_new_page'),
                        'href'=>$FUNCS->get_qs_link( $FUNCS->generate_route($PAGE->tpl_name, 'create_view', array('nonce'=>$FUNCS->create_nonce('create_page_'.$PAGE->tpl_id))) ),
                        'icon'=>'plus',
                        'weight'=>10,
                    );
            }

            return $arr_buttons;
        }

        function _default_form_filter_actions(){
            global $FUNCS;
            $arr_filters = array();

            $arr_filters['filter_related'] =
                array(
                    'render'=>'filter_related',
                    'no_wrapper'=>1, // has no form gui
                    'weight'=>10,
                );

            return $arr_filters;
        }

        function _default_form_page_actions(){
            global $FUNCS, $PAGE, $CTX;

            $arr_actions = parent::_default_form_page_actions();

            if( $PAGE->id != -1 ){
                $arr_actions['btn_view'] =
                    array(
                        'title'=>$FUNCS->t('view'),
                        'onclick'=>array( "this.blur();" ),
                        'href'=>$CTX->get('k_page_link'),
                        'target'=>'_blank',
                        'icon'=>'magnifying-glass',
                        'weight'=>20,
                    );
            }

            return $arr_actions;
        }

        function _setup_form(){
            global $FUNCS, $CTX;

            // setup event handlers
            if( $this->persist_params ){
                $token = $CTX->get( 'k_cur_token' );
                $FUNCS->add_event_listener( 'db_persist_form_alter_fields_'.$token, array($this, '_persist_form_handler') );
            }

            parent::_setup_form();
        }

        function _setup_form_variables(){
            global $CTX, $PAGE;

            $CTX->set( 'k_selected_form_mode', 'auto', 'global' );
            $CTX->set( 'k_selected_masterpage', $PAGE->tpl_name, 'global' );
            $CTX->set( 'k_selected_page_id', '', 'global' );
        }

        function _get_object_to_edit(){
            global $PAGE;

            return $PAGE;
        }

        function _set_advanced_setting_fields( &$arr_fields ){
            global $FUNCS;

            // move/add relevant fields below the advanced-setting field
            $arr_fields['k_access_level']['group'] = '_advanced_settings_';
            $arr_fields['k_access_level']['order'] = 10;

            $arr_fields['k_comments_open']['group'] = '_advanced_settings_';
            $arr_fields['k_comments_open']['order'] = 20;

            $arr_fields['k_publish_date']['group'] = '_advanced_settings_';
            $arr_fields['k_publish_date']['order'] = 30;
        }

        // event handlers

        // event handler for adding custom params to cms:db_persist_form tag
        function _persist_form_handler( &$pg, &$fields, $_mode ){
            global $CTX, $FUNCS;

            $arr_params = $this->persist_params;
            if( is_array($arr_params) && count($arr_params) ){
                $arr_params = $FUNCS->resolve_parameters( $arr_params );
                $arr_known_params = array( '_invalidate_cache'=>'0', '_auto_title'=>'0' );

                foreach( $arr_params as $param ){
                    $pname = strtolower( trim($param['lhs']) );
                    if( array_key_exists($pname, $arr_known_params) ) continue;
                    $fields[$pname]=$param['rhs'];
                }
            }
        }

        function _get_form_redirect_link( &$pg, $_mode ){
            global $CTX, $FUNCS;

            if( $_mode=='edit' ){
                $redirect_dest = $CTX->get( 'k_qs_link' );
            }
            else{ // 'create' mode
                // redirect to 'edit' view of the newly created page
                $link = $FUNCS->generate_route( $pg->tpl_name, 'edit_view', array('nonce'=>$FUNCS->create_nonce('edit_page_'.$pg->id), 'id'=>$pg->id) );
                $redirect_dest = $FUNCS->get_qs_link( $link ); // link with passed qs parameters
            }

            return $redirect_dest;
        }

        /////////// common /////////////////////////////////////////////////////
        function _custom_admin_screen(){ // Deprecated now .. included only for legacy support
            global $FUNCS, $CTX;

            if( $this->_custom_admin_list_screen ){
                $snippet = $this->_custom_admin_list_screen;
                $name = 'content_list_inner';
            }
            else{
                $snippet = $this->_custom_admin_page_screen;
                $name = 'content_form';
            }

            // render the custom screen as 'content_list_inner' or 'content_form' renderable ..
            $CTX->push( '__render_'.$name.'__', 1 /*no_check*/ );

            if( defined('K_SNIPPETS_DIR') ){ // always defined relative to the site
                $base_snippets_dir = K_SITE_DIR . K_SNIPPETS_DIR . '/';
            }
            else{
                $base_snippets_dir = K_COUCH_DIR . 'snippets/';
            }

            $filepath = $base_snippets_dir . ltrim( trim($snippet), '/\\' );
            $html = @file_get_contents( $filepath );
            if( $html!==FALSE ){
                $parser = new KParser( $html );
                if( K_CACHE_OPCODES ){
                    $html = $parser->get_cached_HTML( $filepath );
                }
                else{
                    $html = $parser->get_HTML();
                }
            }
            else{
                $html = 'ERROR: Unable to get contents from custom admin screen <b>' . $filepath . '</b>';
            }

            $CTX->pop();
            return $html;
        }

        // route filters
        static function resolve_page( $route, $act ){
            global $FUNCS, $DB, $PAGE, $CTX;

            $rs = $DB->select( K_TBL_TEMPLATES, array('*'), "name='" . $DB->sanitize( $route->masterpage ). "'" );
            if( !count($rs) ){
                return $FUNCS->raise_error( ROUTE_NOT_FOUND );
            }
            $tpl = $rs[0];

            $tpl_id = $tpl['id'];
            $tpl_name = $tpl['name'];
            $page_id = $route->resolved_values['id'];
            $nonce = $route->resolved_values['nonce'];

            if( $act == 'list' ){
                $page_id = null;
            }
            elseif( $act == 'create' ){
                $FUNCS->validate_nonce( 'create_page_' . $tpl_id, $nonce );
                $page_id = -1;
            }
            elseif( $act == 'edit' ){
                $obj_id = ( $page_id ) ? $page_id : $tpl_id;
                $FUNCS->validate_nonce( 'edit_page_' . $obj_id, $nonce );
            }
            else{
                $FUNCS->dispatch_event( 'pages_rt_filter_resolve_page', array($tpl, &$tpl_id, &$page_id, $nonce, $act) );
            }

            // set page object
            $PAGE = new KWebpage( $tpl_id, $page_id );
            if( $PAGE->error ){
                return $FUNCS->raise_error( ROUTE_NOT_FOUND );
            }
            $PAGE->set_context();
        }

        static function clonable_only( $route, $act ){
            global $PAGE, $FUNCS;

            if( !$PAGE->tpl_is_clonable ){
                if( $act == 'list' ){
                    // redirect to page-view
                    $nonce = $FUNCS->create_nonce( 'edit_page_'.$PAGE->tpl_id );
                    $link = $FUNCS->generate_route( $PAGE->tpl_name, 'edit_view', array('nonce'=>$nonce, 'id'=>'') );
                    header( "Location: " . $link );
                    exit;
                }
                else{
                    $FUNCS->dispatch_event( 'pages_rt_filter_clonable_only' );
                }
            }
        }

        static function set_related_fields( $route, $act ){
            global $PAGE, $FUNCS;

            if( $act=='create' ){
                if( !$_POST ){ // if form not posted

                    $fid = ( isset($_GET['fid']) && $FUNCS->is_non_zero_natural($_GET['fid']) ) ? (int)$_GET['fid'] : null;
                    $cid = ( isset($_GET['cid']) && $FUNCS->is_non_zero_natural($_GET['cid']) ) ? (int)$_GET['cid'] : null;
                    $rid = ( isset($_GET['rid']) && $FUNCS->is_non_zero_natural($_GET['rid']) ) ? (int)$_GET['rid'] : null;

                    // first test if the indicated folder does exist..
                    if( $fid && $PAGE->folders->find_by_id( $fid ) ){
                        // if it does, set it in the folders select dropdown
                        $PAGE->_fields['k_page_folder_id']->data = $fid;
                    }

                    // any preset related-page?
                    if( $cid && $rid ){
                        for( $x=0; $x<count($PAGE->fields); $x++ ){
                            $f = &$PAGE->fields[$x];
                            if( (!$f->system) && $f->id==$rid && $f->k_type=='relation'){
                                $f->items_selected[] = $cid;
                                unset( $f );
                                break;
                            }
                            unset( $f );
                        }
                    }
                }
            }
            else{
                $FUNCS->dispatch_event( 'pages_rt_filter_set_related_fields' );
            }
        }

    } // end class KPagesAdmin


    class KNestedPagesAdmin extends KPagesAdmin{

        function __construct(){
            global $FUNCS;

            parent::__construct();
            $FUNCS->add_event_listener( 'alter_render_vars_content_list_inner', array($this, '_alter_render_vars') );
        }

        function _default_list_toolbar_actions(){
            $buttons = parent::_default_list_toolbar_actions();

            if( array_key_exists('view_folders', $buttons) ){ // no 'folders' button for nested_pages
                unset( $buttons['view_folders'] );
            }

            return $buttons;
        }

        function _default_list_filter_actions(){
            $filters = parent::_default_list_filter_actions();

            if( array_key_exists('filter_search', $filters) ){ // no 'search' for nested-pages
                unset( $filters['filter_search'] );
            }

            return $filters;
        }

        function _default_list_fields(){
            $fields = parent::_default_list_fields();

            if( array_key_exists('k_page_title', $fields) ){
                $fields['k_page_title']['content']="<cms:render 'list_nestedpage_title' />";
                $fields['k_page_title']['class']='nested-title';
                $fields['k_page_title']['sort_name']='title'; // sort field for nested_pages
            }
            if( array_key_exists('k_page_foldertitle', $fields) ){
                unset( $fields['k_page_foldertitle'] );
            }

            foreach( $fields as $k=>$v ){
                $fields[$k]['sortable'] = '0'; // no field sortable
            }

            return $fields;
        }

        function _selected_list_fields(){
            return array( 'k_selector_checkbox', 'k_page_title', 'k_comments_count', 'k_up_down', 'k_actions' );
        }

        function _set_list_sort( $orderby='', $order='' ){
            global $FUNCS;

            $FUNCS->set_admin_list_default_sort( 'weight', 'asc' );
        }

        function _set_list_limit( $limit='' ){
            global $FUNCS;

            $limit = trim( $limit );
            if( $limit=='' ){  $limit = '25'; }

            $FUNCS->set_admin_list_default_limit( $limit );
        }

        function _set_advanced_setting_fields( &$arr_fields ){
            global $FUNCS, $PAGE;

            parent::_set_advanced_setting_fields( $arr_fields );

            $arr_fields['k_publish_date']['label'] = $FUNCS->t( 'status' );

            $arr_fields['k_show_in_menu']['group'] = '_advanced_settings_';
            $arr_fields['k_show_in_menu']['label'] = $FUNCS->t( 'menu' );
            $arr_fields['k_show_in_menu']['order'] = 40;

            $arr_fields['k_menu_text']['group'] = '_advanced_settings_';
            $arr_fields['k_menu_text']['hide'] = 0;
            $arr_fields['k_menu_text']['order'] = 50;

            // coalasce 'k_open_external' and 'k_is_pointer' fields into one
            $arr_fields['k_open_external']['skip'] = 1;
            $arr_fields['k_is_pointer']['skip'] = 1;
            $arr_fields['k_menu_link'] = array(
                'group'=>'_advanced_settings_',
                'label'=>$FUNCS->t( 'menu_link' ),
                'no_wrapper'=>1,
                'content'=>"<cms:render 'group_menu_link_fields' />",
                'order'=>60,
            );

            // create a group for 'k_pointer_link', 'k_masquerades' and 'k_strict_matching'
            $arr_fields[ '_pointer_fields_' ] = array(
                'no_wrapper'=>'1',
                'content'=>"<cms:render 'group_pointer_fields' />",
                'hide'=>( $PAGE->_fields['k_is_pointer']->get_data() ) ? 0 : 1,
                'order'=>30,
            );
            $arr_fields['k_pointer_link']['group'] = '_pointer_fields_';
            $arr_fields['k_pointer_link']['order'] = 10;
            $arr_fields['k_masquerades']['group'] = '_pointer_fields_';
            $arr_fields['k_masquerades']['no_wrapper'] = 1;
            $arr_fields['k_masquerades']['hide'] = ( strtolower($PAGE->tpl_name)=='index.php' ) ? 0 : 1; //No masquerading option for templates other than index.php (will always only redirect).
            $arr_fields['k_masquerades']['order'] = 20;
            $arr_fields['k_strict_matching']['group'] = '_pointer_fields_';
            $arr_fields['k_strict_matching']['no_wrapper'] = 1;
            $arr_fields['k_strict_matching']['order'] = 30;

            // set the visibility of custom-fields group depending on pointer status
            $arr_fields['_custom_fields_']['hide'] = ( $PAGE->_fields['k_is_pointer']->get_data() ) ? 1 : 0;

        }

        function _alter_render_vars( &$templates, $render ){
            global $CTX, $FUNCS;

            if( $render=='content_list_inner' ){
                $templates[] = 'content_list_inner_nested';
            }
        }

        // event handlers for nested-pages
        function _updown_handler( &$redirect_dest ){ // Move nestable pages up-down
            global $FUNCS, $DB, $PAGE;

            if( isset($_POST['k_updown']) ){

                $FUNCS->validate_nonce( 'updown_page_'.$PAGE->tpl_id );

                if( !$FUNCS->is_non_zero_natural($_POST['id']) || !$FUNCS->is_natural($_POST['dir'])){
                    die( 'ERROR: invalid input' );
                }

                $page_id = intval( $_POST['id'] );
                $dir = intval( $_POST['dir'] );

                $DB->begin();

                // serialize access.. lock template before getting tree
                $DB->update( K_TBL_TEMPLATES, array('description'=>$DB->sanitize( $PAGE->tpl_desc )), "id='" . $DB->sanitize( $PAGE->tpl_id ) . "'" );

                $tree = $FUNCS->get_nested_pages( $PAGE->tpl_id, $PAGE->tpl_name, $PAGE->tpl_access_level );
                $p0 = $tree->find_by_id( $page_id );
                if( !$p0 )  die( 'ERROR: Page '.$page_id.' not found' );

                if( $p0->pid != -1 ){
                    $parent_page = $tree->find_by_id( $p0->pid );
                }
                else{
                    $parent_page = $tree;
                }

                // find the adjacent sibling to swap places with
                if( $dir ){ //up
                    if( $p0->pos==0 ){
                        $cannot_swap=1; // probably user clicking on a stale listing.
                    }
                    else{
                        $p1 = $parent_page->children[$p0->pos - 1];
                    }
                }
                else{ //down
                    if( $p0->pos==count($parent_page->children)-1 ){
                        $cannot_swap=1;
                    }
                    else{
                        $p1 = $parent_page->children[$p0->pos + 1];
                    }
                }

                // Update database swapping the weights of both pages (pos+1 should now always be equal to weight)
                if( !$cannot_swap ){
                    $rs2 = $DB->update( K_TBL_PAGES, array('weight'=>$p1->pos+1), "id='" . $DB->sanitize( $p0->id ). "'" );
                    if( $rs2==-1 ) die( "ERROR: Unable to update weight" );
                    $rs2 = $DB->update( K_TBL_PAGES, array('weight'=>$p0->pos+1), "id='" . $DB->sanitize( $p1->id ). "'" );
                    if( $rs2==-1 ) die( "ERROR: Unable to update weight" );
                }

                // refresh tree
                $tree = $FUNCS->get_nested_pages( $PAGE->tpl_id, $PAGE->tpl_name, $PAGE->tpl_access_level, 'weightx', 'asc', 1 /*force*/ );

                // return modified listing
                $redirect_dest = '';
                $FUNCS->route_fully_rendered = 1;

                $DB->commit();
            }
        }

    } // end class KNestedPagesAdmin


    class KGalleryPagesAdmin extends KPagesAdmin{

        function __construct(){
            global $FUNCS;

            parent::__construct();
            $FUNCS->add_event_listener( 'alter_render_vars_filter_folders', array($this, '_alter_render_vars') );
            $FUNCS->add_event_listener( 'alter_render_vars_content_list_inner', array($this, '_alter_render_vars') );
        }

        function _default_list_toolbar_actions(){
            global $FUNCS, $PAGE, $AUTH;

            $buttons = parent::_default_list_toolbar_actions();

            // replace 'create_new' with 'upload' button
            if( array_key_exists('create_new', $buttons) ){
                unset( $buttons['create_new'] );
            }

            if( file_exists(K_SITE_DIR . $PAGE->tpl_name) && $AUTH->user->access_level >= $PAGE->tpl_access_level ){
                $fid = ( isset($_GET['fid']) && $FUNCS->is_non_zero_natural( $_GET['fid'] ) ) ? (int)$_GET['fid'] : 0;
                $fn = trim( $FUNCS->get_pretty_template_link_ex($PAGE->tpl_name, $dummy, 0), '/' );
                $href = $FUNCS->get_qs_link( K_ADMIN_URL.'upload.php?o=gallery&tpl='. $PAGE->tpl_id . '&fn='. urlencode($fn) );

                $buttons['bulk_upload'] =
                    array(
                        'title'=>$FUNCS->t('bulk_upload'),
                        'class'=>'plupload-gallery',
                        'href'=>$href,
                        'weight'=>10,
                        'icon'=>'cloud-upload',
                    );
            }

            return $buttons;
        }

        function _alter_render_vars( &$templates, $render ){
            global $CTX, $FUNCS;

            if( $render=='filter_folders' ){
                $CTX->set( 'k_root_text', $FUNCS->t('root') );
            }
            elseif( $render=='content_list_inner' ){
                $templates[] = 'content_list_inner_gallery';

                // set if up/down field being used
                $found = '0';
                $fields = &$FUNCS->get_admin_list_fields();
                foreach( $fields as $f ){
                    if( $f['name']=='k_up_down' ){
                        $found = '1';
                        break;
                    }
                }
                $CTX->set( 'k_has_list_updown', $found );
            }
        }

        function _default_list_filter_actions(){
            $filters = parent::_default_list_filter_actions();

            if( array_key_exists('filter_search', $filters) ){ // no 'search' for gallery
                unset( $filters['filter_search'] );
            }

            return $filters;
        }

    } // end class KGalleryPagesAdmin
