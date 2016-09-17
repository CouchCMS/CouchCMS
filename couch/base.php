<?php
    if ( !defined('K_ADMIN') ) die(); // cannot be loaded directly

    class KBaseAdmin{

        var $form_name = 'k_admin_frm';
        var $list_form_name = 'k_admin_frm_list';

        function __construct(){

        }

        /////// 1. 'list' action ////////////////////////////////////////////////////
        function list_action(){
            global $FUNCS, $DB, $CTX;

            // define components to be rendered
            $this->define_list_title();
            $this->define_list_actions(); /* toolbar, filters, bulk-actions, extended-actions, row-actions */
            $this->define_list_fields();

            // allow addons to chip in
            $FUNCS->dispatch_event( 'action_pages_list', array($this) );

            // form posted?
            if( $_SERVER['REQUEST_METHOD']=='POST' ){

                $redirect_dest = $CTX->get( 'k_qs_link' );

                if( isset($_POST['k_bulk_action']{0}) ){
                    $FUNCS->validate_nonce( 'bulk_action_page' );
                    $FUNCS->dispatch_event( 'pages_list_bulk_action', array($_POST['k_bulk_action'], &$redirect_dest, $this) );
                }
                else{
                    $FUNCS->dispatch_event( 'pages_list_post_action', array(&$redirect_dest, $this) );
                }

                // redirect if not directed otherwise (e.g. by AJAX requests which can set $redirect_dest to blank to skip redirection)
                $redirect_dest = $FUNCS->sanitize_url( trim($redirect_dest) );
                if( strlen($redirect_dest) ){
                    $DB->commit( 1 );
                    header( "Location: ".$redirect_dest );
                    exit;
                }
            }

            // render output ..
            $html = $this->render_list();

            return $html;
        }

        function render_list(){
            global $FUNCS, $CTX;

            $CTX->set( 'k_cur_form', $this->list_form_name, 'global' );

            return $FUNCS->render( 'content_list' );
        }

        function define_list_title(){
            global $FUNCS;

            $text = '';
            $link = '#';
            $icon = '';
            $FUNCS->set_admin_title( $text, $link, $icon );

            // subtitle
            $subtitle = '';
            $FUNCS->set_admin_subtitle( $subtitle );
        }

        function define_list_actions(){
            global $FUNCS;

            $FUNCS->reset_admin_actions( array('toolbar', 'filter', 'batch', 'extended', 'row') );

            // register actions for each category
            $arr_action_types = array(
                'toolbar'  => 'add_toolbar_button',
                'filter'   => 'add_filter_action',
                'batch'    => 'add_batch_action',
                'extended' => 'add_extended_action',
                'row'      => 'add_row_action'
            );

            foreach( $arr_action_types as $action_type=>$register_func ){

                // get default actions for each type
                $default_actions_getter = '_default_list_'.$action_type.'_actions';
                $actions = $this->$default_actions_getter();

                // give other modules a chance to override default actions
                $FUNCS->dispatch_event( 'alter_pages_list_'.$action_type.'_actions', array(&$actions, &$this) );

                // and register them
                foreach( $actions as $name=>$action ){
                    $action['name'] = $name;
                    $FUNCS->$register_func( $action );
                }
            }

            $FUNCS->dispatch_event( 'add_pages_list_actions' );
            define( 'K_ADD_ADMIN_ACTIONS_DONE', '1' );
            $FUNCS->dispatch_event( 'alter_pages_list_actions', array(&$FUNCS->admin_actions) );
        }

        function define_list_fields(){
            global $FUNCS;

            // get default fields
            $arr_fields = $this->_default_list_fields();

            foreach( array_keys($arr_fields) as $name ){
                $arr_fields[$name]['name'] = $name;
            }

            // register
            $FUNCS->admin_list_fields = array();
            foreach( $arr_fields as $field ){
                $FUNCS->add_list_field( $field );
            }

            $FUNCS->dispatch_event( 'add_pages_list_fields' );
            define( 'K_ADD_LIST_FIELDS_DONE', '1' );
            $FUNCS->dispatch_event( 'alter_pages_list_fields', array(&$FUNCS->admin_list_fields) );

            // set default limit
            $this->_set_list_limit();

            // set default sort order
            $this->_set_list_sort();
        }

        /// helper list functions
        function _default_list_toolbar_actions(){
            $arr_buttons = array();

            return $arr_buttons;
        }

        function _default_list_filter_actions(){
            $arr_filters = array();

            return $arr_filters;
        }

        function _default_list_batch_actions(){
            $arr_actions = array();

            return $arr_actions;
        }

        function _default_list_extended_actions(){
            $arr_actions = array();

            return $arr_actions;
        }

        function _default_list_row_actions(){
            $arr_actions = array();

            return $arr_actions;
        }

        function _default_list_fields(){
            $arr_default_fields = array();

            return $arr_default_fields;
        }

        function _set_list_sort( $orderby='', $order='' ){
            global $FUNCS;

            $orderby = trim( $orderby );
            $order = trim( $order );

            $FUNCS->set_admin_list_default_sort( $orderby, $order );
        }

        function _set_list_limit( $limit='' ){
            global $FUNCS;

            $limit = trim( $limit );
            if( $limit=='' ){  $limit = '15'; }

            $FUNCS->set_admin_list_default_limit( $limit );
        }

        /////// 2. 'form' action (edit/create) ////////////////////////////////////////////////////
        function form_action(){
            global $FUNCS;

            // define components to be rendered
            $this->define_form_title();
            $this->define_form_actions(); /* toolbar, filters, page-actions, extended-actions */
            $this->define_form_fields();

            // allow addons to chip in
            $FUNCS->dispatch_event( 'action_pages_form', array($this) );

            // render output ..
            $html = $this->render_form();

            return $html;
        }

        function render_form(){
            global $FUNCS, $CTX;

            $CTX->set( 'k_cur_form', $this->form_name, 'global' );

            return $FUNCS->render( 'content_form' );
        }

        function define_form_title(){
            global $FUNCS;

            $text = '';
            $link = '#';
            $icon = '';
            $FUNCS->set_admin_title( $text, $link, $icon );

            // subtitle
            $subtitle = '';
            $FUNCS->set_admin_subtitle( $subtitle );
        }

        function define_form_actions(){
            global $FUNCS;

            $FUNCS->reset_admin_actions( array('toolbar', 'filter', 'page', 'extended') );

            // register actions for each category
            $arr_action_types = array(
                'toolbar'  => 'add_toolbar_button',
                'filter'   => 'add_filter_action',
                'page'     => 'add_page_action',
                'extended' => 'add_extended_action',
            );

            foreach( $arr_action_types as $action_type=>$register_func ){

                // get default actions for each type
                $default_actions_getter = '_default_form_'.$action_type.'_actions';
                $actions = $this->$default_actions_getter();

                // give other modules a chance to override default actions
                $FUNCS->dispatch_event( 'alter_pages_form_'.$action_type.'_actions', array(&$actions, &$this) );

                // and register them
                foreach( $actions as $name=>$action ){
                    $action['name'] = $name;
                    $FUNCS->$register_func( $action );
                }
            }

            $FUNCS->dispatch_event( 'add_pages_form_actions' );
            define( 'K_ADD_ADMIN_ACTIONS_DONE', '1' );
            $FUNCS->dispatch_event( 'alter_pages_form_actions', array(&$FUNCS->admin_actions) );
        }

        function define_form_fields(){
            global $FUNCS;

            // get default fields
            $arr_default_fields = $this->_default_form_fields();

            // give other modules a chance to override default values
            $FUNCS->dispatch_event( 'alter_pages_form_default_fields', array(&$arr_default_fields, &$this) );

            foreach( array_keys($arr_default_fields) as $name ){
                $arr_default_fields[$name]['name'] = $name;
            }

            // and register the fields
            $FUNCS->admin_form_fields = array();
            foreach( $arr_default_fields as $field ){
                $FUNCS->add_form_field( $field );
            }

            $FUNCS->dispatch_event( 'add_pages_form_fields' );
            define( 'K_ADD_FORM_FIELDS_DONE', '1' );
            $FUNCS->dispatch_event( 'alter_pages_form_fields', array(&$FUNCS->admin_form_fields) );

            // set databound form that will render the fields
            $this->_setup_form();
        }

        /// helper form functions
        function _default_form_toolbar_actions(){
            $arr_buttons = array();

            return $arr_buttons;
        }

        function _default_form_filter_actions(){
            $arr_filters = array();

            return $arr_filters;
        }

        function _default_form_page_actions(){
            global $FUNCS;
            $arr_actions = array();

            $arr_actions['btn_submit'] =
                array(
                    'title'=>$FUNCS->t('save'),
                    'onclick'=>array(
                        "$('#btn_submit').trigger('my_submit');",
                        "$('#".$this->form_name."').submit();",
                        "return false;",
                    ),
                    'class'=>'btn-primary',
                    'icon'=>'cloud-download',
                    'weight'=>10,
                );

            return $arr_actions;
        }

        function _default_form_extended_actions(){
            $arr_actions = array();

            return $arr_actions;
        }

        function _setup_form(){
            global $FUNCS, $CTX;

            // setup event handler to run on form submission
            $token = $CTX->get( 'k_cur_token' );
            $FUNCS->add_event_listener( 'db_persist_form_savesuccess_'.$token, array($this, '_persist_form_success_handler') );

            // set variables for use in the databound-form
            $this->_setup_form_variables();
        }

        function _setup_form_variables(){
            global $CTX;

            $CTX->set( 'k_selected_form_mode', '', 'global' );
            $CTX->set( 'k_selected_masterpage', '', 'global' );
            $CTX->set( 'k_selected_page_id', '', 'global' );
        }

        function _default_form_fields(){
            $arr_default_fields = array();

            $this->_set_default_field_groups( $arr_default_fields );

            $this->_set_default_fields( $arr_default_fields );

            $this->_set_advanced_setting_fields( $arr_default_fields );

            return $arr_default_fields;
        }

        function _set_default_field_groups( &$arr_fields ){
            // divide fields into three groups - 'advanced settings', 'sytem_fields' and 'custom_fields'
            // 1. advanced settings
            $arr_fields[ '_advanced_settings_' ] = array( 'no_wrapper'=>'1' );

            // 2. system fields
            $arr_fields[ '_system_fields_' ] = array(
                'no_wrapper'=>'1',
                'content'=>"<cms:render 'group_system_fields' />",
                'order'=>10,
            );

            // 3. custom fields
            $arr_fields[ '_custom_fields_' ] = array(
                'no_wrapper'=>'1',
                'content'=>"<cms:render 'group_custom_fields' />",
                'order'=>20,
            );
        }

        function _set_default_fields( &$arr_fields ){
            global $AUTH;

            // loop through the 'fields' collection of object being edited to fill this array
            $obj = $this->_get_object_to_edit();

            if( is_object($obj) && is_array($obj->fields) ){
                $count = count( $obj->fields );
                for( $x=0; $x<$count; $x++ ){
                    $f = &$obj->fields[$x];
                    $f->resolve_dynamic_params();

                    $def = array(
                        'label'=>$f->label,
                        'desc'=>$f->k_desc,
                        'order'=>$f->k_order,
                        'group'=>$f->k_group,
                        'class'=>$f->class,
                        'icon'=>'',
                        'no_wrapper'=>0,
                        'skip'=>0,
                        'hide'=>( ($f->system && $f->hidden) || ($f->deleted && $AUTH->user->access_level < K_ACCESS_LEVEL_SUPER_ADMIN) || $f->no_render ) ? 1 : 0,
                        'required'=>$f->required,
                        'content'=>"<cms:render 'form_input' />",
                        'is_compound'=>0,
                        'obj'=>&$f,
                    );
                    $f->pre_render( $def ); // allow field to change settings for itself
                    $def['group'] = ( $f->system ) ? '_system_fields_' : ( (trim($def['group'])=='') ? '_custom_fields_' : $def['group'] );

                    $arr_fields[$f->name] = $def;

                    unset( $f );
                }
            }
        }

        function _get_object_to_edit(){
            return;
        }

        function _set_advanced_setting_fields( &$arr_fields ){

            // move/add relevant fields below the advanced-setting field
            // for example -
            /*
            $arr_fields['k_publish_date']['group'] = '_advanced_settings_';
            */
        }

        // event handlers
        // event handler to add $CTX variables on succesful save of form
        function _persist_form_success_handler( &$pg, $_mode ){
            global $CTX, $FUNCS;

            $redirect_dest = $this->_get_form_redirect_link( $pg, $_mode );

            // give a chance to addons to step in (and perhaps modify the redirect destination)
            if( isset($_POST['k_custom_action']{0}) ){
                $FUNCS->dispatch_event( 'pages_form_custom_action', array($_POST['k_custom_action'], &$redirect_dest, &$pg, $_mode, $this) );
            }
            else{
                $FUNCS->dispatch_event( 'pages_form_post_action', array(&$redirect_dest, &$pg, $_mode, $this) );
            }

            $CTX->set( 'k_redirect_link', $redirect_dest , 'global');
        }

        function _get_form_redirect_link( &$pg, $_mode ){
            global $CTX;

            if( $_mode=='edit' ){
                $redirect_dest = $CTX->get( 'k_qs_link' );
            }
            else{ // 'create' mode
                $redirect_dest = $CTX->get( 'k_qs_link' );
            }

            return $redirect_dest;
        }

    } // end class KBaseAdmin
