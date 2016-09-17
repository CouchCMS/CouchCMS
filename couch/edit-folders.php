<?php
    if ( !defined('K_ADMIN') ) die(); // cannot be loaded directly

    require_once( K_COUCH_DIR.'edit-pages.php' );

    class KFoldersAdmin extends KPagesAdmin{

        function __construct(){
            global $FUNCS;

            parent::__construct();
            $FUNCS->add_event_listener( 'alter_render_vars_content_list_inner', array($this, '_alter_render_vars') );
        }

        /////// 1. 'list' action ////////////////////////////////////////////////////
        function list_action(){
            return KBaseAdmin::list_action(); // Calling grandparent statically! Not a bug: https://bugs.php.net/bug.php?id=42016
        }

        function render_list(){
            return KBaseAdmin::render_list();
        }

        function define_list_title(){
            global $FUNCS, $PAGE;

            $text = $FUNCS->t('folders') . ' (';
            $text .= $PAGE->tpl_title ? $PAGE->tpl_title : $PAGE->tpl_name;
            $text .= ')';
            $link = $FUNCS->generate_route( $PAGE->tpl_name, 'folder_list_view' );
            $icon = '';
            $FUNCS->set_admin_title( $text, $link, $icon );

            // subtitle
            $subtitle = $FUNCS->t('list');
            $icon = 'folder';
            $FUNCS->set_admin_subtitle( $subtitle, $icon );
        }

        function define_list_fields(){
            return KBaseAdmin::define_list_fields();
        }

        function _default_list_toolbar_actions(){
            global $FUNCS, $PAGE, $AUTH;
            $arr_buttons = array();

            $arr_buttons['create_new'] =
                array(
                    'title'=>$FUNCS->t('add_new'),
                    'desc'=>$FUNCS->t('add_new_folder'),
                    'href'=>$FUNCS->get_qs_link( $FUNCS->generate_route( $PAGE->tpl_name, 'folder_create_view', array('nonce'=>$FUNCS->create_nonce('edit_page_'.$PAGE->tpl_id))) ),
                    'icon'=>'plus',
                    'weight'=>10,
                );

            return $arr_buttons;
        }

        function _default_list_filter_actions(){
            global $FUNCS;
            $arr_filters = array();

            $arr_filters['filter_sort'] =
                array(
                    'render'=>'filter_sort',
                    'no_wrapper'=>1, // has no form gui
                    'weight'=>10,
                );

            return $arr_filters;
        }

        function _default_list_batch_actions(){
            global $FUNCS;
            $arr_actions = array();

            $arr_actions['batch_delete'] =
                array(
                    'title'=>$FUNCS->t( 'delete' ),
                    'confirmation_msg'=>$FUNCS->t( 'confirm_delete_selected_folders' ),
                    'weight'=>10,
                    'listener'=>array( 'pages_list_bulk_action', array($this, _delete_handler) ),
                );

            return $arr_actions;
        }

        function _default_list_extended_actions(){
            global $FUNCS;
            $arr_actions = array();

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

            return $arr_actions;
        }

        function _default_list_fields(){
            global $FUNCS;
            $arr_fields = array();

            $arr_fields['k_selector_checkbox'] =
                array(
                    'header_class'=>'checkbox',
                    'header'=>"<cms:render 'list_checkbox' for_header='1' />",
                    'weight'=>'-10',
                    'class'=>'checkbox',
                    'content'=>"<cms:render 'list_checkbox' />",
                );

            $arr_fields['k_folder_title'] =
                array(
                    'weight'=>'0',
                    'header'=>$FUNCS->t('title'),
                    'class'=>'folder-title',
                    'content'=>"<cms:render 'list_nestedpage_title' />",
                );

            $arr_fields['k_folder_name'] =
                array(
                    'weight'=>'20',
                    'header'=>$FUNCS->t('name'),
                    'class'=>'folder-name',
                );

            $arr_fields['k_folder_pagecount'] =
                array(
                    'weight'=>'30',
                    'header'=>$FUNCS->t('pages'),
                    'class'=>'pages-count',
                );

            $arr_fields['k_actions'] =
                array(
                    'weight'=>'50',
                    'header'=>$FUNCS->t('actions'),
                    'class'=>'actions',
                    'content'=>"<cms:render 'row_actions' />",
                );

            return $arr_fields;
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

        // event handlers
        function _delete_handler( $action ){
            global $FUNCS, $DB, $PAGE;

            if( $action=='batch_delete' && isset($_POST['page-id']) ){
                foreach( $_POST['page-id'] as $v ){
                    if( $FUNCS->is_non_zero_natural($v) ){
                        $folder_id = intval( $v );

                        // execute action
                        $folder = &$PAGE->folders->find_by_id( $folder_id );
                        if( $folder ){
                            $folder->delete();
                        }
                    }
                }
            }
        }

        function _alter_render_vars( &$templates, $render ){
            global $CTX, $FUNCS;

            if( $render=='content_list_inner' ){
                $templates[] = 'content_list_inner_folders';
            }
        }

        /////// 2. 'form' action  ////////////////////////////////////////////////////
        function form_action(){
            return KBaseAdmin::form_action();
        }

        function render_form(){
            return KBaseAdmin::render_form();
        }

        function define_form_title(){
            global $FUNCS, $PAGE;

            $text = $FUNCS->t('folders') . ' (';
            $text .= $PAGE->tpl_title ? $PAGE->tpl_title : $PAGE->tpl_name;
            $text .= ')';
            $link = $FUNCS->generate_route( $PAGE->tpl_name, 'folder_list_view' );
            $icon = '';
            $FUNCS->set_admin_title( $text, $link, $icon );

            // subtitle
            $subtitle = $FUNCS->t('edit');
            $icon = 'folder';
            $FUNCS->set_admin_subtitle( $subtitle, $icon );
        }

        function define_form_fields(){
            return KBaseAdmin::define_form_fields();
        }

        function _default_form_toolbar_actions(){
            global $FUNCS, $PAGE, $OBJ;
            $arr_buttons = array();

            if( $OBJ->id ){
                $arr_buttons['create_new'] =
                    array(
                        'title'=>$FUNCS->t('add_new'),
                        'desc'=>$FUNCS->t('add_new_folder'),
                        'href'=>$FUNCS->get_qs_link( $FUNCS->generate_route( $PAGE->tpl_name, 'folder_create_view', array('nonce'=>$FUNCS->create_nonce('edit_page_'.$PAGE->tpl_id))) ),
                        'icon'=>'plus',
                        'weight'=>10,
                    );
            }

            return $arr_buttons;
        }

        function _default_form_filter_actions(){
            global $FUNCS;
            $arr_filters = array();

            return $arr_filters;
        }

        function _default_form_page_actions(){
            global $FUNCS, $PAGE, $CTX;

            $arr_actions = parent::_default_form_page_actions();
            if( array_key_exists('btn_view', $arr_actions) ){ // no 'view' for folders
                unset( $arr_actions['btn_view'] );
            }

            return $arr_actions;
        }

        function _setup_form_variables(){
            global $CTX, $OBJ;

            $mode = ( $OBJ->id ) ? 'edit' : 'create';
            $CTX->set( 'k_selected_form_mode', $mode, 'global' );
            $CTX->set( 'k_selected_masterpage', 'folders', 'global' );
            $CTX->set( 'k_selected_page_id', $OBJ->id, 'global' );
        }

        function _get_object_to_edit(){
            global $OBJ;

            return $OBJ;
        }

        function _set_advanced_setting_fields( &$arr_fields ){
            return;
        }

        // event handlers

        // event handler to set redirect on succesful save of form
        function _get_form_redirect_link( &$pg, $_mode ){
            global $CTX, $FUNCS, $PAGE, $OBJ;

            if( $_mode=='edit' ){
                $redirect_dest = $CTX->get( 'k_qs_link' );
            }
            else{ // 'create' mode
                // redirect to 'edit' view of the newly created folder
                $link = $FUNCS->generate_route( $pg->template_name, 'folder_edit_view', array('nonce'=>$FUNCS->create_nonce('edit_page_'.$pg->template_id), 'fid'=>$pg->id) );
                $redirect_dest = $FUNCS->get_qs_link( $link ); // link with passed qs parameters
            }

            return $redirect_dest;
        }

        // common

        // route filters
        static function set_ctx( $route ){
            global $FUNCS, $PAGE, $CTX;

            if( !$PAGE->tpl_dynamic_folders ){ die( 'ERROR: Template does not support dynamic folders' ); }
            $PAGE->folders->set_sort( 'weight', 'asc' );
            $PAGE->folders->sort( 1 );
            $PAGE->k_total_folders = $PAGE->folders->set_dynamic_count( 0, array());
        }

        static function resolve_folder( $route, $act ){
            global $FUNCS, $PAGE, $CTX, $OBJ;

            $folder_id = $route->resolved_values['fid'];

            if( $act == 'edit' ){
                $folder = &$PAGE->folders->find_by_id( $folder_id );
                if( !$folder ){ return $FUNCS->raise_error( 'Folder not found' ); }

                $PAGE->is_folder_view = 1;
                $PAGE->folder_id = $folder_id;
            }
            else{
                // create a folder object
                $folder = new KFolder( array('id'=>null, 'name'=>'', 'pid'=>'-1', 'template_id'=>$PAGE->tpl_id), $PAGE->tpl_name, new KError()/*dummy*/ );
            }

            $folder->populate_fields();
            $OBJ = $folder;
        }

    } // end class KFoldersAdmin
