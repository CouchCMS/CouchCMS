<?php
    if ( !defined('K_ADMIN') ) die(); // cannot be loaded directly

    require_once( K_COUCH_DIR.'base.php' );

    class KUsersAdmin extends KBaseAdmin{

        function __construct(){
            global $FUNCS;

            parent::__construct();
            $FUNCS->add_event_listener( 'alter_render_vars_content_list_inner', array($this, '_alter_render_vars') );
        }

        /////// 1. 'list' action ////////////////////////////////////////////////////
        function define_list_title(){
            global $FUNCS, $PAGE, $CTX;

            $text = $FUNCS->t('users');
            $link = $FUNCS->generate_route( 'users', 'list_view' );
            $icon = '';
            $FUNCS->set_admin_title( $text, $link, $icon );

            // subtitle
            $subtitle = $FUNCS->t('list');
            $icon = 'person';
            $FUNCS->set_admin_subtitle( $subtitle, $icon );
        }

        function _default_list_toolbar_actions(){
            global $FUNCS, $AUTH, $OBJ;
            $arr_buttons = array();

            $arr_buttons['create_new'] =
                array(
                    'title'=>$FUNCS->t('add_new'),
                    'desc'=>$FUNCS->t('add_new_user'),
                    'href'=>$FUNCS->get_qs_link( $FUNCS->generate_route( 'users', 'create_view', array('nonce'=>$FUNCS->create_nonce('create_page'))) ),
                    'icon'=>'plus',
                    'weight'=>10,
                );

            return $arr_buttons;
        }

        function _default_list_batch_actions(){
            global $FUNCS;
            $arr_actions = array();

            $arr_actions['batch_delete'] =
                array(
                    'title'=>$FUNCS->t( 'delete' ),
                    'confirmation_msg'=>$FUNCS->t( 'confirm_delete_selected_users' ),
                    'weight'=>10,
                    'listener'=>array( 'pages_list_bulk_action', array($this, '_delete_handler') ),
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

            $arr_fields['name'] =
                array(
                    'weight'=>'0',
                    'header'=>$FUNCS->t('name'),
                    'content'=>"<cms:render 'list_title' />",
                    'class'=>'user-name',
                );

            $arr_fields['title'] =
                array(
                    'weight'=>'10',
                    'header'=>$FUNCS->t('display_name'),
                    'class'=>'display-name',
                );

            $arr_fields['email'] =
                array(
                    'weight'=>'20',
                    'header'=>$FUNCS->t('email'),
                    'class'=>'email',
                );

            $arr_fields['level_str'] =
                array(
                    'weight'=>'30',
                    'header'=>$FUNCS->t('role'),
                    'class'=>'role',
                );

            $arr_fields['k_actions'] =
                array(
                    'weight'=>'40',
                    'header'=>$FUNCS->t('actions'),
                    'class'=>'actions',
                    'content'=>"<cms:render 'row_actions' />",
                );

            return $arr_fields;
        }

        // event handlers
        function _delete_handler( $action ){
            global $FUNCS, $DB, $PAGE;

            if( $action=='batch_delete' && isset($_POST['page-id']) ){
                foreach( $_POST['page-id'] as $v ){
                    if( $FUNCS->is_non_zero_natural($v) ){
                        $user_id = intval( $v );
                        $user = new KUser( $user_id, 1 );

                        // execute action
                        $user->delete();
                    }
                }
            }
        }

        function _alter_render_vars( &$templates, $render ){
            global $CTX, $FUNCS, $DB;

            if( $render=='content_list_inner' ){

                // set template to use for render
                $templates[] = 'content_list_inner_users';

                // formulate SQL for cms:query within the template
                $fields = 'u.id as id, u.name as name, u.title as title, u.email as email, u.system as is_system, lvl.title as level_str, lvl.k_level as level';
                $tables = K_TBL_USERS .' as u, '. K_TBL_USER_LEVELS .' as lvl';
                $where = 'u.access_level = lvl.k_level';
                $orderby .= 'u.access_level DESC, u.name ASC';

                $sql = 'SELECT ' . $fields . ' FROM ' . $tables . ' WHERE ' . $where . ' ORDER BY ' . $orderby;

                $CTX->set( 'k_selected_query', $sql );
            }
        }

        /////// 2. 'form' action  ////////////////////////////////////////////////////
        function define_form_title(){
            global $FUNCS;

            $text = $FUNCS->t('users');
            $link = $FUNCS->generate_route( 'users', 'list_view' );
            $icon = '';
            $FUNCS->set_admin_title( $text, $link, $icon );

            // subtitle
            $subtitle = $FUNCS->t('edit');
            $icon = 'person';
            $FUNCS->set_admin_subtitle( $subtitle, $icon );
        }

        function _default_form_toolbar_actions(){
            global $FUNCS, $AUTH, $OBJ;
            $arr_buttons = array();

            if( $OBJ->id != '-1' ){
                $arr_buttons['create_new'] =
                    array(
                        'title'=>$FUNCS->t('add_new'),
                        'desc'=>$FUNCS->t('add_new_user'),
                        'href'=>$FUNCS->get_qs_link( $FUNCS->generate_route( 'users', 'create_view', array('nonce'=>$FUNCS->create_nonce('create_page'))) ),
                        'icon'=>'plus',
                        'weight'=>10,
                    );
            }

            return $arr_buttons;
        }

        function _setup_form_variables(){
            global $CTX;

            $CTX->set( 'k_selected_form_mode', 'auto', 'global' );
            $CTX->set( 'k_selected_masterpage', 'users', 'global' );
            $CTX->set( 'k_selected_page_id', '', 'global' );
        }

        function _get_object_to_edit(){
            global $OBJ;

            return $OBJ;
        }

        // event handlers

        // event handler to set redirect on succesful save of form
        function _get_form_redirect_link( &$pg, $_mode ){
            global $CTX, $FUNCS, $PAGE, $OBJ;

            if( $_mode=='edit' ){
                $redirect_dest = $CTX->get( 'k_qs_link' );
            }
            else{ // 'create' mode
                // redirect to 'edit' view of the newly created user
                $link = $FUNCS->generate_route( 'users', 'edit_view', array('nonce'=>$FUNCS->create_nonce('edit_page_'.$pg->id), 'id'=>$pg->id) );
                $redirect_dest = $FUNCS->get_qs_link( $link ); // link with passed qs parameters
            }

            return $redirect_dest;
        }

        // common
        // route filters
        static function resolve_user( $route, $act ){
            global $FUNCS, $CTX, $OBJ;

            $nonce = $route->resolved_values['nonce'];
            $id = $route->resolved_values['id'];

            if( $act == 'create' ){
                $FUNCS->validate_nonce( 'create_page', $nonce );
                $user = new KUser();
            }
            elseif( $act == 'edit' ){
                $FUNCS->validate_nonce( 'edit_page_' . $id, $nonce );
                $user = new KUser( $id, 1 );
                if( $user->id == -1 ){
                    return $FUNCS->raise_error( 'User not found' );
                }
            }
            else{
                return $FUNCS->raise_error( ROUTE_NOT_FOUND );
            }

            // set user object
            $user->populate_fields();
            $OBJ = $user;
        }

    } // end class KUsersAdmin
