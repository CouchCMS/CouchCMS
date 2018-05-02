<?php
    if ( !defined('K_ADMIN') ) die(); // cannot be loaded directly

    require_once( K_COUCH_DIR.'base.php' );
    require_once( K_COUCH_DIR.'comment.php' );

    class KCommentsAdmin extends KBaseAdmin{

        function __construct(){
            global $FUNCS;

            parent::__construct();
            $FUNCS->add_event_listener( 'alter_render_vars_content_list_inner', array($this, '_alter_render_vars') );
        }

        /////// 1. 'list' action ////////////////////////////////////////////////////
        function define_list_title(){
            global $FUNCS, $PAGE, $CTX;

            $text = $FUNCS->t('comments');
            $link = $FUNCS->generate_route( 'comments', 'list_view' );
            $icon = '';
            $FUNCS->set_admin_title( $text, $link, $icon );

            // subtitle
            $subtitle = $FUNCS->t('list');
            $icon = 'chat';
            $FUNCS->set_admin_subtitle( $subtitle, $icon );
        }

        function _default_list_filter_actions(){
            global $FUNCS;
            $arr_filters = array();

            $arr_filters['filter_status'] =
                array(
                    'render'=>'filter_status',
                    'weight'=>10,
                );

            $arr_filters['filter_comments_parent'] =
                array(
                    'render'=>'filter_comments_parent',
                    'no_wrapper'=>1, // has no form gui
                    'weight'=>20,
                );

            return $arr_filters;
        }

        function _default_list_batch_actions(){
            global $FUNCS;
            $arr_actions = array();

            $arr_actions['batch_delete'] =
                array(
                    'title'=>$FUNCS->t( 'delete' ),
                    'confirmation_msg'=>$FUNCS->t( 'confirm_delete_selected_comments' ),
                    'weight'=>10,
                    'listener'=>array( 'pages_list_bulk_action', array($this, '_bulk_action_handler') ),
                );

            $arr_actions['batch_approve'] =
                array(
                    'title'=>$FUNCS->t( 'approve' ),
                    'weight'=>10,
                );

            $arr_actions['batch_unapprove'] =
                array(
                    'title'=>$FUNCS->t( 'unapprove' ),
                    'weight'=>10,
                );

            return $arr_actions;
        }

        function _default_list_row_actions(){
            global $FUNCS;
            $arr_actions = array();

            $arr_actions['approve'] =
                array(
                    'render'=>'row_action_approve',
                    'weight'=>10,
                );

            $arr_actions['edit'] =
                array(
                    'render'=>'row_action_edit',
                    'weight'=>20,
                );

            $arr_actions['delete'] =
                array(
                    'render'=>'row_action_delete',
                    'weight'=>30,
                );

            $arr_actions['view'] =
                array(
                    'render'=>'row_action_view',
                    'weight'=>40,
                );
            return $arr_actions;
        }

        // event handlers
        function _bulk_action_handler( $action ){
            global $FUNCS, $DB, $PAGE;

            if( $action!='batch_delete' && $action!='batch_approve' && $action!='batch_unapprove') return;

            if( isset($_POST['page-id']) ){
                foreach( $_POST['page-id'] as $v ){
                    if( $FUNCS->is_non_zero_natural($v) ){
                        $comment_id = intval( $v );
                        $comment = new KComment( $comment_id );

                        // execute action
                        if( $action=='batch_delete' ){
                            $comment->delete();
                        }
                        elseif( $action=='batch_approve' ){
                            $comment->approve();
                        }
                        elseif( $action=='batch_unapprove' ){
                            $comment->approve(0);
                        }
                    }
                }
            }
        }

        function _alter_render_vars( &$templates, $render ){
            global $CTX, $FUNCS, $DB;

            if( $render=='content_list_inner' ){

                // set template to use for render
                $templates[] = 'content_list_inner_comments';
            }
        }

        function _set_list_limit( $limit='' ){
            global $FUNCS;

            $limit = trim( $limit );
            if( $limit=='' ){  $limit = '10'; }

            $FUNCS->set_admin_list_default_limit( $limit );
        }

        /////// 2. 'form' action  ////////////////////////////////////////////////////
        function define_form_title(){
            global $FUNCS;

            $text = $FUNCS->t('comments');
            $link = $FUNCS->generate_route( 'comments', 'list_view' );
            $icon = '';
            $FUNCS->set_admin_title( $text, $link, $icon );

            // subtitle
            $subtitle = $FUNCS->t('edit');
            $icon = 'chat';
            $FUNCS->set_admin_subtitle( $subtitle, $icon );
        }

        function _default_form_page_actions(){
            global $FUNCS, $OBJ, $CTX;

            $arr_actions = parent::_default_form_page_actions();

            if( $OBJ->id != -1 ){
                $parent_link = ( K_PRETTY_URLS ) ? $FUNCS->get_pretty_template_link( $OBJ->tpl_name ) : $OBJ->tpl_name;
                $view_link = K_SITE_URL . $parent_link . "?comment=" . $OBJ->id;

                $arr_actions['btn_view'] =
                    array(
                        'title'=>$FUNCS->t('view'),
                        'onclick'=>array( "this.blur();" ),
                        'href'=>$view_link,
                        'target'=>'_blank',
                        'icon'=>'magnifying-glass',
                        'weight'=>20,
                    );
            }

            return $arr_actions;
        }

        function _setup_form_variables(){
            global $CTX, $OBJ;

            $CTX->set( 'k_selected_form_mode', 'edit', 'global' );
            $CTX->set( 'k_selected_masterpage', 'comments', 'global' );
            $CTX->set( 'k_selected_page_id', $OBJ->id, 'global' );
        }

        function _get_object_to_edit(){
            global $OBJ;

            return $OBJ;
        }

        function _set_advanced_setting_fields( &$arr_fields ){
            global $FUNCS;

            // move/add relevant fields below the advanced-setting field
            $arr_fields['k_date']['group'] = '_advanced_settings_';
            $arr_fields['k_date']['order'] = 10;

            $arr_fields['k_approved']['group'] = '_advanced_settings_';
            $arr_fields['k_approved']['order'] = 20;
        }

        // common
        // route filters
        static function resolve_comment( $route ){
            global $FUNCS, $CTX, $OBJ;

            $nonce = $route->resolved_values['nonce'];
            $id = $route->resolved_values['id'];

            $FUNCS->validate_nonce( 'edit_page_' . $id, $nonce );

            $comment = new KComment( $id );
            if( $comment->id == -1 ){
                return $FUNCS->raise_error( 'Comment not found' );
            }

            // set global object
            $OBJ = $comment;
        }

    } // end class KUsersAdmin
