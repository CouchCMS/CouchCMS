<?php
    if ( !defined('K_ADMIN') ) die(); // cannot be loaded directly

    require_once( K_COUCH_DIR.'edit-pages.php' );

    class KDraftsAdmin extends KPagesAdmin{

        function __construct(){
            global $FUNCS;

            parent::__construct();
            $FUNCS->add_event_listener( 'alter_render_vars_content_list_inner', array($this, '_alter_render_vars') );
            $FUNCS->add_event_listener( 'alter_pages_form_fields', array($this, '_hide_system_fields') );
        }

        /////// 1. 'list' action ////////////////////////////////////////////////////
        function list_action(){
            return KBaseAdmin::list_action(); // Calling grandparent statically! Not a bug: https://bugs.php.net/bug.php?id=42016
        }

        function render_list(){
            return KBaseAdmin::render_list();
        }

        function define_list_title(){
            global $FUNCS, $PAGE, $CTX;

            $text = $FUNCS->t('drafts');
            $link = $FUNCS->generate_route( 'drafts', 'list_view' );
            $icon = '';
            $FUNCS->set_admin_title( $text, $link, $icon );

            // subtitle
            $subtitle = $FUNCS->t('list');
            $icon = 'document';
            $FUNCS->set_admin_subtitle( $subtitle, $icon );
        }

        function define_list_fields(){
            return KBaseAdmin::define_list_fields();
        }

        function _default_list_toolbar_actions(){
            $arr_buttons = array();

            return $arr_buttons;
        }

        function _default_list_filter_actions(){
            global $FUNCS;
            $arr_filters = array();

            $arr_filters['filter_templates'] =
                array(
                    'render'=>'filter_templates',
                    'weight'=>10,
                );


            $arr_filters['filter_parent'] =
                array(
                    'render'=>'filter_parent',
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
                    'confirmation_msg'=>$FUNCS->t( 'confirm_delete_selected_drafts' ),
                    'weight'=>10,
                    'listener'=>array( 'pages_list_bulk_action', array($this, _delete_handler) ),
                );

            $arr_actions['batch_update_original'] =
                array(
                    'title'=>$FUNCS->t( 'update_original' ),
                    'confirmation_msg'=>$FUNCS->t( 'confirm_apply_selected_drafts' ),
                    'weight'=>20,
                    'listener'=>array( 'pages_list_bulk_action', array($this, _update_handler) ),
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

            $arr_fields['page_title'] =
                array(
                    'weight'=>'0',
                    'header'=>$FUNCS->t('original_page'),
                    'class'=>'original-page',
                    'content'=>"<cms:render 'list_title' />",
                );

            $arr_fields['k_page_foldertitle'] =
                array(
                    'weight'=>'20',
                    'header'=>$FUNCS->t('template'),
                    'class'=>'template',
                    'content'=>"<cms:render 'list_template' />",
                );

            $arr_fields['k_page_date'] =
                array(
                    'weight'=>'30',
                    'header'=>$FUNCS->t('modified'),
                    'class'=>'modified',
                    'content'=>"<cms:render 'list_mod_date' />",
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

            $order = trim( $order );
            if( $order=='' ){  $order = 'desc'; }

            $FUNCS->set_admin_list_default_sort( '', $order );
        }

        // event handlers
        function _delete_handler( $action ){
            global $FUNCS, $DB, $PAGE;

            if( $action=='batch_delete' && isset($_POST['page-id']) ){
                foreach( $_POST['page-id'] as $v ){
                    if( $FUNCS->is_non_zero_natural($v) ){
                        $draft_id = intval( $v );

                        $rs = $DB->select( K_TBL_PAGES, array('template_id'), "id = '" . $DB->sanitize( $draft_id )."'" );
                        $_tpl_id = $rs[0]['template_id'];

                        $pg = new KWebpage( $_tpl_id, $draft_id );
                        if( $pg->error ){
                            ob_end_clean();
                            die( 'ERROR in batch delete: ' . $pg->err_msg );
                        }

                        $pg->delete( 1 );
                    }
                }
            }
        }

        function _update_handler( $action ){
            global $FUNCS, $DB, $PAGE;

            if( $action=='update_original' ){
                $DB->begin();

                $res = $PAGE->update_parent();
                if( $FUNCS->is_error($res) ){
                    ob_end_clean();
                    die( $res->err_msg );
                }

                // the draft can be deleted now
                $PAGE->delete( 1 );
                $DB->commit( 1 );

                $FUNCS->invalidate_cache();

                // redirect to the original page
                $tpl_name = $PAGE->tpl_name;
                $page_id = $PAGE->parent_id;
                $edit_link = $FUNCS->generate_route( $tpl_name, 'edit_view', array('nonce'=>$FUNCS->create_nonce('edit_page_'.$page_id), 'id'=>$page_id) );

                header( "Location: ".$edit_link );
                exit;
            }
            elseif( $action=='batch_update_original' && isset($_POST['page-id']) ){

                foreach( $_POST['page-id'] as $v ){
                    if( $FUNCS->is_non_zero_natural($v) ){
                        $draft_id = intval( $v );

                        $rs = $DB->select( K_TBL_PAGES, array('template_id'), "id = '" . $DB->sanitize( $draft_id )."'" );
                        $_tpl_id = $rs[0]['template_id'];

                        $pg = new KWebpage( $_tpl_id, $draft_id );
                        if( $pg->error ){
                            ob_end_clean();
                            die( 'ERROR in batch update: ' . $pg->err_msg );
                        }

                        $DB->begin();
                        $res = $pg->update_parent();
                        if( $FUNCS->is_error($res) ){
                            ob_end_clean();
                            die( $res->err_msg );
                        }
                        $pg->delete( 1 );
                        $DB->commit( 1 );
                    }
                }
            }
        }

        function _alter_render_vars( &$templates, $render ){
            global $CTX, $FUNCS, $DB;

            if( $render=='content_list_inner' ){

                // set template to use for render
                $templates[] = 'content_list_inner_drafts';

                // formulate SQL for cms:query within the template
                $tpl_id = $CTX->get( 'k_selected_templateid' );
                $parent_id = $CTX->get( 'k_selected_parentid' );

                $fields = 'p.*, p2.page_name as parent_name, p2.page_title as parent_title, t.clonable as tpl_clonable, t.title as tpl_title, t.name as tpl_name, t.access_level as tpl_access_level';
                $tables = K_TBL_PAGES.' p left outer join '.K_TBL_PAGES.' p2 on p.parent_id = p2.id left outer join '.K_TBL_TEMPLATES.' t on p.template_id = t.id';
                $where = "p.parent_id>0";
                if( $tpl_id ){
                    $where .= " AND t.id = '" . $DB->sanitize( $tpl_id )."'";
                }
                if( $parent_id ){
                    $where .= " AND p2.id = '" . $DB->sanitize( $parent_id )."'";
                }
                $orderby .= "p.template_id, p.parent_id, p.modification_date desc";

                $sql = 'SELECT ' . $fields . ' FROM ' . $tables . ' WHERE ' . $where . ' ORDER BY ' . $orderby;

                $CTX->set( 'k_selected_query', $sql );
            }
        }

        function _hide_system_fields( &$fields ){
            global $PAGE;

            foreach( $fields as $k=>$v ){
                if( array_key_exists($k, $PAGE->_fields) && $PAGE->_fields[$k]->system ){
                    if( !($k=='k_page_title' /*|| $k=='k_draft_button'*/) ){
                        $fields[$k]['hide'] = 1;
                    }
                }
            }
            $x=10;
        }

        /////// 2. 'form' action  ////////////////////////////////////////////////////
        function define_form_title(){
            global $FUNCS;

            $text = $FUNCS->t('drafts');
            $link = $FUNCS->generate_route( 'drafts', 'list_view' );
            $icon = '';
            $FUNCS->set_admin_title( $text, $link, $icon );

            // subtitle
            $subtitle = $FUNCS->t('edit');
            $icon = 'document';
            $FUNCS->set_admin_subtitle( $subtitle, $icon );
        }

        function _default_form_toolbar_actions(){
            $arr_buttons = array();

            return $arr_buttons;
        }

        function _default_form_filter_actions(){
            global $FUNCS;
            $arr_filters = array();

            $arr_filters['filter_parent'] =
                array(
                    'render'=>'filter_parent',
                    'no_wrapper'=>1, // has no form gui
                    'weight'=>10,
                );

            return $arr_filters;
        }

        function _default_form_page_actions(){
            global $FUNCS, $PAGE, $CTX;

            $arr_actions = parent::_default_form_page_actions();

            if( is_array($arr_actions['btn_view']) ){
                $arr_actions['btn_view']['title'] = $FUNCS->t('preview');
                $arr_actions['btn_view']['href'] = K_SITE_URL . $PAGE->tpl_name . '?p=' . $PAGE->id;
            }

            return $arr_actions;
        }

        function _set_advanced_setting_fields( &$arr_fields ){
            global $FUNCS;

            // add 'Update original' button
            $arr_fields[ 'k_draft_button' ] = array(
                'no_wrapper'=>'1',
                'content'=>"<cms:render 'draft_button' mode='update' />",
                'group'=> '_advanced_settings_',
                'order'=>0,
                'listener'=>array( 'pages_form_custom_action', array($this, _update_handler) ),
            );

        }

        // common
        // route filters
        static function resolve_draft( $route ){
            global $FUNCS, $PAGE, $CTX;

            $nonce = $route->resolved_values['nonce'];
            $tpl_id = $route->resolved_values['tpl_id'];
            $draft_id = $route->resolved_values['id'];

            $FUNCS->validate_nonce( 'edit_draft_' . $tpl_id . ',' . $draft_id, $nonce );

            // set page object
            $PAGE = new KWebpage( $tpl_id, $draft_id );
            if( $PAGE->error ){
                return $FUNCS->raise_error( ROUTE_NOT_FOUND );
            }
            if( !$PAGE->parent_id ){
                return $FUNCS->raise_error( 'Not a draft' );
            }

            $PAGE->set_context();
        }

    } // end class KDraftsAdmin
