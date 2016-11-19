<?php
    if( !defined('K_COUCH_DIR') ) die(); // cannot be loaded directly

    $FUNCS->add_event_listener( 'register_renderables',         'k_register_renderables' );
    $FUNCS->add_event_listener( 'add_render_vars',              'k_add_render_vars' );

    if( defined('K_ADMIN') ){
        $FUNCS->add_event_listener( 'register_admin_routes',        'k_register_admin_routes' );
        $FUNCS->add_event_listener( 'register_admin_menuitems',     'k_register_admin_menuitems' );
        $FUNCS->add_event_listener( 'register_admin_sub_menuitems', 'k_register_admin_sub_menuitems' );
        $FUNCS->add_event_listener( 'skip_qs_params_in_paginator',  'k_skip_qs_params' );
    }

    // Register all render functions of the core modules (i.e. not addons which do it on their own)
    function k_register_renderables(){
        global $FUNCS;

        // editable regions
        $FUNCS->register_render( 'field_textarea',    array('template_path'=>K_SYSTEM_THEME_DIR, 'template_ctx_setter'=>array('KField', '_render_textarea')) );
        $FUNCS->register_render( 'field_richtext',    array('renderable'=>array('KField', '_render_richtext')) );
        $FUNCS->register_render( 'field_image',       array('template_path'=>K_SYSTEM_THEME_DIR, 'template_ctx_setter'=>array('KField', '_render_image')) );
        $FUNCS->register_render( 'field_thumbnail',   array('template_path'=>K_SYSTEM_THEME_DIR, 'template_ctx_setter'=>array('KField', '_render_thumbnail')) );
        $FUNCS->register_render( 'field_file',        array('template_path'=>K_SYSTEM_THEME_DIR, 'template_ctx_setter'=>array('KField', '_render_file')) );
        $FUNCS->register_render( 'field_text',        array('template_path'=>K_SYSTEM_THEME_DIR, 'template_ctx_setter'=>array('KField', '_render_text')) );
        $FUNCS->register_render( 'field_password',    array('template_path'=>K_SYSTEM_THEME_DIR, 'template_ctx_setter'=>array('KField', '_render_text')) );
        $FUNCS->register_render( 'field_dropdown',    array('template_path'=>K_SYSTEM_THEME_DIR, 'template_ctx_setter'=>array('KField', '_render_options')) );
        $FUNCS->register_render( 'field_radio',       array('template_path'=>K_SYSTEM_THEME_DIR, 'template_ctx_setter'=>array('KField', '_render_options')) );
        $FUNCS->register_render( 'field_checkbox',    array('template_path'=>K_SYSTEM_THEME_DIR, 'template_ctx_setter'=>array('KField', '_render_options')) );
        $FUNCS->register_render( 'field_message',     array('renderable'=>array('KField', '_render_message')) );
        $FUNCS->register_render( 'field_hidden',      array('renderable'=>array('KField', '_render_hidden')) );

        // admin panel elements
        $FUNCS->register_render( 'main',                    array('template_path'=>K_SYSTEM_THEME_DIR, 'template_ctx_setter'=>'_render_main') );
        $FUNCS->register_render( 'logo',                    array('template_path'=>K_SYSTEM_THEME_DIR, 'template_ctx_setter'=>'_render_logo') );
        $FUNCS->register_render( 'subnav',                  array('template_path'=>K_SYSTEM_THEME_DIR) );
        $FUNCS->register_render( 'title',                   array('template_path'=>K_SYSTEM_THEME_DIR, 'template_ctx_setter'=>'_render_title') );
        $FUNCS->register_render( 'subtitle',                array('template_path'=>K_SYSTEM_THEME_DIR, 'template_ctx_setter'=>'_render_subtitle') );
        $FUNCS->register_render( 'toolbar',                 array('template_path'=>K_SYSTEM_THEME_DIR) );
        $FUNCS->register_render( 'sidebar',                 array('template_path'=>K_SYSTEM_THEME_DIR) );
        $FUNCS->register_render( 'content_list',            array('template_path'=>K_SYSTEM_THEME_DIR) );
        $FUNCS->register_render( 'content_list_inner',      array('template_path'=>K_SYSTEM_THEME_DIR) );
        $FUNCS->register_render( 'list_row',                array('template_path'=>K_SYSTEM_THEME_DIR, 'template_ctx_setter'=>'_render_list_row') );
        $FUNCS->register_render( 'list_header',             array('template_path'=>K_SYSTEM_THEME_DIR) );
        $FUNCS->register_render( 'gallery_item',            array('template_path'=>K_SYSTEM_THEME_DIR, 'template_ctx_setter'=>'_render_gallery_item') );
        $FUNCS->register_render( 'gallery_folder',          array('template_path'=>K_SYSTEM_THEME_DIR, 'template_ctx_setter'=>'_render_gallery_folder') );
        $FUNCS->register_render( 'gallery_upload',          array('template_path'=>K_SYSTEM_THEME_DIR) );
        $FUNCS->register_render( 'paginator',               array('template_path'=>K_SYSTEM_THEME_DIR) );
        $FUNCS->register_render( 'filters',                 array('template_path'=>K_SYSTEM_THEME_DIR) );
        $FUNCS->register_render( 'filter_folders',          array('template_path'=>K_SYSTEM_THEME_DIR, 'template_ctx_setter'=>'_render_filter_folders') );
        $FUNCS->register_render( 'filter_search',           array('template_path'=>K_SYSTEM_THEME_DIR, 'template_ctx_setter'=>'_render_filter_search') );
        $FUNCS->register_render( 'filter_sort',             array('renderable'=>'_render_filter_sort') );
        $FUNCS->register_render( 'filter_related',          array('template_path'=>K_SYSTEM_THEME_DIR, 'template_ctx_setter'=>'_render_filter_related') );
        $FUNCS->register_render( 'batch_actions',           array('template_path'=>K_SYSTEM_THEME_DIR) );
        $FUNCS->register_render( 'extended_actions',        array('template_path'=>K_SYSTEM_THEME_DIR) );
        $FUNCS->register_render( 'page_actions',            array('template_path'=>K_SYSTEM_THEME_DIR) );
        $FUNCS->register_render( 'content_form',            array('template_path'=>K_SYSTEM_THEME_DIR) );
        $FUNCS->register_render( 'form_row',                array('template_path'=>K_SYSTEM_THEME_DIR, 'template_ctx_setter'=>'_render_form_row') );
        $FUNCS->register_render( 'form_input',              array('template_path'=>K_SYSTEM_THEME_DIR, 'template_ctx_setter'=>'_render_form_input') );
        $FUNCS->register_render( 'form_field_deleted',      array('template_path'=>K_SYSTEM_THEME_DIR, 'template_ctx_setter'=>'_render_form_field_deleted') );
        $FUNCS->register_render( 'group_advanced_settings', array('template_path'=>K_SYSTEM_THEME_DIR, 'template_ctx_setter'=>'_render_advanced_settings') );
        $FUNCS->register_render( 'group_system_fields',     array('template_path'=>K_SYSTEM_THEME_DIR) );
        $FUNCS->register_render( 'group_custom_fields',     array('template_path'=>K_SYSTEM_THEME_DIR) );
        $FUNCS->register_render( 'menu_ul',                 array('renderable'=>'_render_menu_ul') );
        $FUNCS->register_render( 'menu_li',                 array('renderable'=>'_render_menu_li') );
        $FUNCS->register_render( 'icon',                    array('renderable'=>'_render_icon') );
        $FUNCS->register_render( 'alert',                   array('template_path'=>K_SYSTEM_THEME_DIR, 'template_ctx_setter'=>'_render_alert') );
        $FUNCS->register_render( 'default_route',           array('template_path'=>K_SYSTEM_THEME_DIR) );
        $FUNCS->register_render( 'template_missing',        array('template_path'=>K_SYSTEM_THEME_DIR, 'template_ctx_setter'=>'_render_template_missing') );
        $FUNCS->register_render( 'simple',                  array('template_path'=>K_SYSTEM_THEME_DIR, 'template_ctx_setter'=>'_render_simple') );
        $FUNCS->register_render( 'login',                   array('template_path'=>K_SYSTEM_THEME_DIR, 'template_ctx_setter'=>'_render_login') );
        $FUNCS->register_render( 'forgot_password',         array('template_path'=>K_SYSTEM_THEME_DIR, 'template_ctx_setter'=>'_render_forgot_password') );
        $FUNCS->register_render( 'insufficient_privileges', array('template_path'=>K_SYSTEM_THEME_DIR) );
        $FUNCS->register_render( 'site_offline',            array('template_path'=>K_SYSTEM_THEME_DIR) );
        // nested-pages specific elements
        $FUNCS->register_render( 'group_pointer_fields',    array('template_path'=>K_SYSTEM_THEME_DIR) );
        $FUNCS->register_render( 'group_menu_link_fields',  array('template_path'=>K_SYSTEM_THEME_DIR) );
        // drafts specific elements
        $FUNCS->register_render( 'filter_templates',        array('template_path'=>K_SYSTEM_THEME_DIR, 'template_ctx_setter'=>'_render_filter_templates') );
        $FUNCS->register_render( 'filter_parent',           array('template_path'=>K_SYSTEM_THEME_DIR, 'template_ctx_setter'=>'_render_filter_parent') );
        $FUNCS->register_render( 'draft_button',            array('template_path'=>K_SYSTEM_THEME_DIR, 'template_ctx_setter'=>'_render_draft_button') );
        // comments specific elements
        $FUNCS->register_render( 'filter_status',           array('template_path'=>K_SYSTEM_THEME_DIR, 'template_ctx_setter'=>'_render_filter_status') );
        $FUNCS->register_render( 'filter_comments_parent',  array('template_path'=>K_SYSTEM_THEME_DIR, 'template_ctx_setter'=>'_render_filter_comments_parent') );
        $FUNCS->register_render( 'comment_item',            array('template_path'=>K_SYSTEM_THEME_DIR, 'template_ctx_setter'=>'_render_comment_item') );

        // columns in admin list
        $FUNCS->register_render( 'list_checkbox',           array('renderable'=>'_render_list_checkbox') );
        $FUNCS->register_render( 'list_title',              array('renderable'=>'_render_list_title') );
        $FUNCS->register_render( 'list_nestedpage_title',   array('renderable'=>'_render_list_nestedpage_title') );
        $FUNCS->register_render( 'list_comments_count',     array('renderable'=>'_render_list_comments_count') );
        $FUNCS->register_render( 'list_date',               array('renderable'=>'_render_list_date') );
        $FUNCS->register_render( 'list_updown',             array('renderable'=>'_render_list_updown') );
        $FUNCS->register_render( 'row_actions',             array('renderable'=>'_render_row_actions') );
        $FUNCS->register_render( 'row_action_edit',         array('renderable'=>'_render_row_action_edit') );
        $FUNCS->register_render( 'row_action_delete',       array('renderable'=>'_render_row_action_delete') );
        $FUNCS->register_render( 'row_action_view',         array('renderable'=>'_render_row_action_view') );
        $FUNCS->register_render( 'row_action_drafts',       array('renderable'=>'_render_row_action_drafts') );
        // drafts specific
        $FUNCS->register_render( 'list_template',           array('renderable'=>'_render_list_template') );
        $FUNCS->register_render( 'list_mod_date',           array('renderable'=>'_render_list_mod_date') );
        // comments specific
        $FUNCS->register_render( 'row_action_approve',      array('renderable'=>'_render_row_action_approve') );
    }

    // Register admin-routes for all core modules
    function k_register_admin_routes(){
        global $FUNCS, $DB;

        // for 'pages' module
        $rs = $DB->select( K_TBL_TEMPLATES, array('*'), '1=1 ORDER BY id ASC' );
        if( count($rs) ){
            foreach( $rs as $tpl ){
                $default_routes = array();

                $default_routes['list_view'] = array(
                    'path'=>'list',
                    'include_file'=>K_COUCH_DIR.'edit-pages.php',
                    'filters'=>'KPagesAdmin::resolve_page=list | KPagesAdmin::clonable_only=list',
                    'class'=> $tpl['nested_pages'] ? 'KNestedPagesAdmin' : ( $tpl['gallery'] ? 'KGalleryPagesAdmin' : 'KPagesAdmin' ),
                    'action'=>'list_action',
                    'module'=>'pages', /* owner module of this route */
                );

                $default_routes['edit_view'] = array(
                    'path'=>'edit/{:nonce}/{:id}',
                    'constraints'=>array(
                        'nonce'=>'([a-fA-F0-9]{32})',
                        'id'=>'(([1-9]\d*)?)',
                    ),
                    'include_file'=>K_COUCH_DIR.'edit-pages.php',
                    'filters'=>'KPagesAdmin::resolve_page=edit',
                    'class'=> $tpl['nested_pages'] ? 'KNestedPagesAdmin' : 'KPagesAdmin',
                    'action'=>'form_action',
                    'module'=>'pages',
                );

                $default_routes['create_view'] = array(
                    'path'=>'create/{:nonce}',
                    'constraints'=>array(
                        'nonce'=>'([a-fA-F0-9]{32})',
                    ),
                    'include_file'=>K_COUCH_DIR.'edit-pages.php',
                    'filters'=>'KPagesAdmin::resolve_page=create | KPagesAdmin::set_related_fields=create',
                    'class'=> $tpl['nested_pages'] ? 'KNestedPagesAdmin' : 'KPagesAdmin',
                    'action'=>'form_action',
                    'module'=>'pages',
                );

                // folders module
                if( $tpl['dynamic_folders'] ){
                    $default_routes['folder_list_view'] = array(
                        'path'=>'list_folders',
                        'include_file'=>K_COUCH_DIR.'edit-folders.php',
                        'filters'=>'KPagesAdmin::resolve_page=list | KFoldersAdmin::set_ctx',
                        'class'=> 'KFoldersAdmin',
                        'action'=>'list_action',
                        'module'=>'folders', /* owner module of this route */
                    );

                    $default_routes['folder_edit_view'] = array(
                        'path'=>'edit_folder/{:nonce}/{:fid}',
                        'constraints'=>array(
                            'nonce'=>'([a-fA-F0-9]{32})',
                            'id'=>'(([1-9]\d*)?)',
                        ),
                        'include_file'=>K_COUCH_DIR.'edit-folders.php',
                        'filters'=>'KPagesAdmin::resolve_page=edit | KFoldersAdmin::set_ctx | KFoldersAdmin::resolve_folder=edit',
                        'class'=> 'KFoldersAdmin',
                        'action'=>'form_action',
                        'module'=>'folders',
                    );

                    $default_routes['folder_create_view'] = array(
                        'path'=>'create_folder/{:nonce}',
                        'constraints'=>array(
                            'nonce'=>'([a-fA-F0-9]{32})',
                        ),
                        'include_file'=>K_COUCH_DIR.'edit-folders.php',
                        'filters'=>'KPagesAdmin::resolve_page=edit | KFoldersAdmin::set_ctx | KFoldersAdmin::resolve_folder=create',
                        'class'=> 'KFoldersAdmin',
                        'action'=>'form_action',
                        'module'=>'folders',
                    );

                }

                // give other modules a chance to override default routes
                $FUNCS->dispatch_event( 'alter_register_pages_routes', array($tpl, &$default_routes) );

                foreach( $default_routes as $name=>$route ){
                    $route['name'] = $name;
                    $FUNCS->register_route( $tpl['name'], $route );
                }
            }
        }

        // drafts module
        $default_routes = array();
        $default_routes['list_view'] = array(
            'path'=>'list',
            'include_file'=>K_COUCH_DIR.'edit-drafts.php',
            'class'=> 'KDraftsAdmin',
            'action'=>'list_action',
            'module'=>'drafts', /* owner module of this route */
        );


        $default_routes['edit_view'] = array(
            'path'=>'edit/{:nonce}/{:tpl_id}/{:id}',
            'constraints'=>array(
                'nonce'=>'([a-fA-F0-9]{32})',
                'tpl_id'=>'(([1-9]\d*)?)',
                'id'=>'(([1-9]\d*)?)',
            ),
            'include_file'=>K_COUCH_DIR.'edit-drafts.php',
            'filters'=>'KDraftsAdmin::resolve_draft',
            'class'=> 'KDraftsAdmin',
            'action'=>'form_action',
            'module'=>'drafts', /* owner module of this route */
        );

        foreach( $default_routes as $name=>$route ){
            $route['name'] = $name;
            $FUNCS->register_route( 'drafts', $route );
        }

        // users module
        $default_routes = array();
        $default_routes['list_view'] = array(
            'path'=>'list',
            'include_file'=>K_COUCH_DIR.'edit-users.php',
            'class'=> 'KUsersAdmin',
            'action'=>'list_action',
            'module'=>'users', /* owner module of this route */
        );

        $default_routes['edit_view'] = array(
            'path'=>'edit/{:nonce}/{:id}',
            'constraints'=>array(
                'nonce'=>'([a-fA-F0-9]{32})',
                'id'=>'(([1-9]\d*)?)',
            ),
            'include_file'=>K_COUCH_DIR.'edit-users.php',
            'filters'=>'KUsersAdmin::resolve_user=edit',
            'class'=> 'KUsersAdmin',
            'action'=>'form_action',
            'module'=>'users', /* owner module of this route */
        );

        $default_routes['create_view'] = array(
            'path'=>'create/{:nonce}',
            'constraints'=>array(
                'nonce'=>'([a-fA-F0-9]{32})',
            ),
            'include_file'=>K_COUCH_DIR.'edit-users.php',
            'filters'=>'KUsersAdmin::resolve_user=create',
            'class'=> 'KUsersAdmin',
            'action'=>'form_action',
            'module'=>'users', /* owner module of this route */
        );

        foreach( $default_routes as $name=>$route ){
            $route['name'] = $name;
            $FUNCS->register_route( 'users', $route );
        }

        // comments module
        $default_routes = array();
        $default_routes['list_view'] = array(
            'path'=>'list',
            'include_file'=>K_COUCH_DIR.'edit-comments.php',
            'class'=> 'KCommentsAdmin',
            'action'=>'list_action',
            'module'=>'comments', /* owner module of this route */
        );

        $default_routes['edit_view'] = array(
            'path'=>'edit/{:nonce}/{:id}',
            'constraints'=>array(
                'nonce'=>'([a-fA-F0-9]{32})',
                'id'=>'(([1-9]\d*)?)',
            ),
            'include_file'=>K_COUCH_DIR.'edit-comments.php',
            'filters'=>'KCommentsAdmin::resolve_comment',
            'class'=> 'KCommentsAdmin',
            'action'=>'form_action',
            'module'=>'comments', /* owner module of this route */
        );

        foreach( $default_routes as $name=>$route ){
            $route['name'] = $name;
            $FUNCS->register_route( 'comments', $route );
        }

    }

    // Register sidebar menuitems for all core modules
    function k_register_admin_menuitems(){
        global $FUNCS, $DB, $AUTH;

        // 'pages' module -
        $FUNCS->register_admin_menuitem(
            array(
                'name'=>'_templates_',
                'title'=>$FUNCS->t('menu_templates'),
                'is_header'=>'1',
                'weight'=>'0',
            )
        );
        // loop through all registered templates and register them
        $rs = $DB->select( K_TBL_TEMPLATES, array('*'), '1=1 ORDER BY id ASC' );
        if( count($rs) ){
            foreach( $rs as $tpl ){
                $icon = trim( $tpl['icon'] );
                $class = '';
                $show = 1;

                if( $tpl['hidden'] ){
                    if( $AUTH->user->access_level < K_ACCESS_LEVEL_SUPER_ADMIN ){
                        $show = 0;
                    }
                    else{
                        $class='hidden-template';
                    }
                }

                if( $tpl['clonable'] ){
                    $route = array( 'masterpage'=>$tpl['name'], 'name'=>'list_view' );
                    if( !strlen($icon) ){ $icon = 'layers'; }
                }
                else{
                    $route = array( 'masterpage'=>$tpl['name'], 'name'=>'edit_view', 'params'=>array('nonce'=>$FUNCS->create_nonce('edit_page_'.$tpl['id']), 'id'=>'') );
                }

                $parent = strlen( $parent = trim($tpl['parent']) ) ? $parent : '_templates_';

                $FUNCS->register_admin_menuitem(
                    array(
                        'name'=>$tpl['name'],
                        'title'=>$tpl['title'],
                        'desc'=>($AUTH->user->access_level >= K_ACCESS_LEVEL_SUPER_ADMIN) ? $tpl['name'] : '',
                        'weight'=>$tpl['k_order'],
                        'parent'=>$parent,
                        /*'is_current_callback'=>'TestIsCurrent::is_current',*/
                        'route'=>$route,
                        'icon'=>$icon,
                        'class'=>$class,
                        'show_in_menu'=>$show,
                    )
                );

                if( $tpl['commentable'] ) $show_comments_link=1;
            }
        }

        // other core modules
        $FUNCS->register_admin_menuitem(
            array(
                'name'=>'_modules_',
                'title'=>$FUNCS->t('menu_modules'),
                'is_header'=>'1',
                'class'=>'separator',
                'weight'=>'10',
            )
        );
        // 'comments' module
        if( $show_comments_link || ($DB->select( K_TBL_COMMENTS, array('id'), '1=1 LIMIT 1' )) ){
            $FUNCS->register_admin_menuitem(
                array(
                    'name'=>'comments',
                    'title'=>$FUNCS->t('comments'),
                    'desc'=>$FUNCS->t('manage_comments'),
                    'weight'=>'10',
                    'icon'=>'chat',
                    'parent'=>'_modules_',
                    'route' => array( 'masterpage'=>'comments', 'name'=>'list_view' ),
                )
            );
        }
        // 'users' module
        $FUNCS->register_admin_menuitem(
            array(
                'name'=>'users',
                'title'=>$FUNCS->t('users'),
                'desc'=>$FUNCS->t('manage_users'),
                'weight'=>'20',
                'icon'=>'person',
                'parent'=>'_modules_',
                'route' => array( 'masterpage'=>'users', 'name'=>'list_view' ),
            )
        );
        // 'drafts' module
        if( $FUNCS->current_route->module=='drafts' || ($DB->select( K_TBL_PAGES, array('id'), 'parent_id>0 LIMIT 1' )) ){
            $FUNCS->register_admin_menuitem(
                array(
                    'name'=>'drafts',
                    'title'=>$FUNCS->t('drafts'),
                    'desc'=>$FUNCS->t('manage_drafts'),
                    'weight'=>'30',
                    'icon'=>'document',
                    'parent'=>'_modules_',
                    'route' => array( 'masterpage'=>'drafts', 'name'=>'list_view' ),
                )
            );
        }
    }

    // Register sub-nav menu items
    function k_register_admin_sub_menuitems(){
        global $FUNCS, $AUTH;

        $items = array(
            array(
                'name'=>'greeting',
                'html'=>$FUNCS->t('greeting').', <a href="'.K_ADMIN_URL . K_ADMIN_PAGE.'?o=users&act=edit&id='.$AUTH->user->id.'&nonce='.$FUNCS->create_nonce( 'edit_page_'.$AUTH->user->id ).'"><b>' . ucwords( strtolower($AUTH->user->title) ) . '</b></a>',
            ),

            array(
                'name'=>'view_site',
                'html'=>'<a href="'.K_SITE_URL.'" target="_blank">'.$FUNCS->t('view_site').'</a>',
            ),

            array(
                'name'=>'logout',
                'html'=>'<a href="'.$FUNCS->get_logout_link(K_ADMIN_URL . K_ADMIN_PAGE).'">'.$FUNCS->t('logout').'</a>',
            )

        );

        foreach( $items as $item ){
            $FUNCS->register_admin_sub_menuitem( $item );
        }

    }

    function k_add_render_vars(){
        global $CTX, $AUTH;

        $vars = array();
        $vars['k_cms_version'] = K_COUCH_VERSION;
        $vars['k_cms_build'] = K_COUCH_BUILD;
        $vars['k_admin_link'] = K_ADMIN_URL;
        $vars['k_admin_page'] = K_ADMIN_PAGE;
        $vars['k_site_link'] = K_SITE_URL;
        $vars['k_theme_link'] = K_THEME_URL;
        $vars['k_system_theme_link'] = K_SYSTEM_THEME_URL;
        $vars['k_admin_path'] = K_COUCH_DIR;
        $vars['k_site_path'] = K_SITE_DIR;
        $vars['k_theme_path'] = K_THEME_DIR;
        $vars['k_system_theme_path'] = K_SYSTEM_THEME_DIR;
        $vars['k_theme_name'] = K_THEME_NAME;
        $vars['k_site_charset'] = K_CHARSET;

        $vars['k_email_from'] = K_EMAIL_FROM;
        $vars['k_email_to'] = K_EMAIL_TO;
        $vars['k_prettyurls'] = ( K_PRETTY_URLS ) ? '1' : '0';
        $vars['k_cur_token'] = md5( $AUTH->hasher->get_random_bytes(16) );

        $CTX->set_all( $vars );
    }

    function _render_template_missing(){
        global $FUNCS, $CTX, $DB, $PAGE;

        $rs = $DB->select( K_TBL_PAGES, array('id'), "template_id='" . $DB->sanitize( $PAGE->tpl_id ). "' AND is_master<>'1'" );
        $has_pages = ( count($rs) ) ? '1' : '0';
        $msg = ( $PAGE->tpl_is_clonable ) ? $FUNCS->t('remove_template_completely') : $FUNCS->t('remove_uncloned_template_completely');

        $CTX->set( 'k_has_pages', $has_pages );
        $CTX->set( 'k_template_removal_msg', $msg );
    }

    function _render_list_row(){
        global $FUNCS, $CTX, $DB, $AUTH;

        $route = $FUNCS->current_route;

        if( $route->module=='drafts' ){
            $page_id  = $CTX->get('id');
            $tpl_name  = $CTX->get('tpl_name');
            $tpl_id  = $CTX->get('template_id');

            if( $CTX->get('parent_name') ){
                if( !$CTX->get('tpl_clonable') ){
                    $page_title = $CTX->get('tpl_title');
                    if( $page_title=='' ){ $page_title = $tpl_name; }
                }
                else{
                    $page_title = $CTX->get( 'parent_title' );
                }
            }
            else{
                $page_title = $FUNCS->t('original_deleted').' (id: '.$CTX->get('parent_id').')';
            }

            $update_link = $FUNCS->generate_route( 'drafts', 'edit_view', array('nonce'=>$FUNCS->create_nonce('edit_draft_'.$tpl_id.','.$page_id), 'tpl_id'=>$tpl_id, 'id'=>$page_id) );
            $view_link = K_SITE_URL . $tpl_name .'?p='. $page_id;

            $CTX->set( 'k_page_title', $page_title );
            $CTX->set( 'k_page_id', $page_id );

            $tpl_access_level = $CTX->get( 'tpl_access_level' );
            $page_access_level = $CTX->get( 'access_level' );

            // access levels
            $access_level = ( $tpl_access_level ) ? $tpl_access_level : $page_access_level; // access level at template level will override page level access
            $can_update = $can_delete = ( $access_level <= $AUTH->user->access_level ) ? 1 : 0;
            $can_view = 1;

            $delete_prompt = $FUNCS->t( 'confirm_delete_draft' );
        }
        elseif( $route->module=='users' ){
            $user_id  = $CTX->get('id');
            $user_title = $CTX->get('name');
            $user_level = $CTX->get('level');

            $update_link = $FUNCS->generate_route( 'users', 'edit_view', array('nonce'=>$FUNCS->create_nonce('edit_page_'.$user_id), 'id'=>$user_id) );

            $CTX->set( 'k_page_title', $user_title );
            $CTX->set( 'k_page_id', $user_id );
            $CTX->set( 'k_show_in_menu', '1' );

            // access levels
            $can_update = ( ($AUTH->user->access_level > $user_level) || ($AUTH->user->access_level == $user_level && $AUTH->user->id == $user_id) ) ? 1 : 0;
            $can_delete = ( $AUTH->user->access_level > $user_level ) ? 1 : 0;
            $can_view = 1;

            $delete_prompt = $FUNCS->t( 'confirm_delete_user' );
        }
        elseif( $route->module=='folders' ){
            global $PAGE;
            $page_id  = $CTX->get('k_folder_id');
            $page_title = $CTX->get('k_folder_title');
            $tpl_name  = $PAGE->tpl_name;
            $tpl_id  = $PAGE->tpl_id;

            $update_link = $FUNCS->generate_route( $tpl_name, 'folder_edit_view', array('nonce'=>$FUNCS->create_nonce('edit_page_'.$tpl_id), 'fid'=>$page_id) );

            $CTX->set( 'k_page_title', $page_title );
            $CTX->set( 'k_page_id', $page_id );
            $CTX->set( 'k_show_in_menu', '1' );

            // access levels
            $access_level = ( $PAGE->tpl_access_level ) ? $PAGE->tpl_access_level : $PAGE->access_level; // access level at template level will override page level access
            $can_update = $can_delete = ( $access_level <= $AUTH->user->access_level ) ? 1 : 0;
            $can_view = 1;

            $delete_prompt = $FUNCS->t( 'confirm_delete_folder' );
        }
        else{ // pages module
            $page_id  = $CTX->get('k_page_id');
            $tpl_id   = $CTX->get('k_template_id');
            $tpl_name = $CTX->get('k_template_name');

            $update_link = $FUNCS->generate_route( $tpl_name, 'edit_view', array('nonce'=>$FUNCS->create_nonce('edit_page_'.$page_id), 'id'=>$page_id) );
            $view_link = K_SITE_URL . $tpl_name .'?p='. $page_id;
            $drafts_link = $FUNCS->generate_route( 'drafts', 'list_view' ) . '&tpl='.$tpl_id.'&pid='.$page_id;
            $CTX->set( 'k_drafts_link', $drafts_link );
            $comments_link = $FUNCS->generate_route( 'comments', 'list_view' ) . '&page_id='.$page_id;
            $CTX->set( 'k_comments_link', $comments_link );

            // Count of drafts
            $rs = $DB->select( K_TBL_PAGES, array('count(id) as cnt'), "parent_id='" . $DB->sanitize( $page_id ). "'" );
            $count_drafts = $rs[0]['cnt'];
            $CTX->set( 'k_count_drafts', $count_drafts );

            $tpl_access_level = $CTX->get('k_template_access_level');
            $page_access_level = $CTX->get('k_access_level');

            // access levels
            $access_level = ( $tpl_access_level ) ? $tpl_access_level : $page_access_level; // access level at template level will override page level access
            $can_update = ( $access_level <= $AUTH->user->access_level ) ? 1 : 0;
            $can_delete = ( $can_update && !$count_drafts ) ? 1 : 0;
            $can_view = 1;

            $delete_prompt = $FUNCS->t( 'confirm_delete_page' );
        }

        $arr_vars['k_update_link'] = $FUNCS->get_qs_link( $update_link );
        $arr_vars['k_view_link'] = $view_link;
        $arr_vars['k_can_update'] = $can_update;
        $arr_vars['k_can_delete'] = $can_delete;
        $arr_vars['k_can_view'] = $can_view;
        $arr_vars['k_delete_prompt'] = $delete_prompt;

        $CTX->set_all( $arr_vars );
    }

    function _render_gallery_item( $max_in_row=0, $current_pos=0, $start_from=0 ){
       global $FUNCS, $CTX, $DB;

        _render_list_row();

        // calculate if item is last in row (taking into consideration any folders displayed before)
        if( $max_in_row ){
            $is_last = (($current_pos+$start_from) % $max_in_row) ? '0' : '1';
            $CTX->set( 'k_last_item', $is_last );
        }

        // label
        $item_label = $CTX->get( 'k_page_title' );
        if( $item_label=='' ) $item_label = $CTX->get( 'k_page_name' );
        $CTX->set( 'k_item_label', $item_label );

        // image
        $item_image = $CTX->get( 'gg_image' );
        $item_thumb = $CTX->get( 'gg_thumb' );

        $CTX->set( 'k_item_image', $item_image );
        $CTX->set( 'k_item_thumb', $item_thumb );
    }

    function _render_comment_item(){
        global $FUNCS, $CTX, $DB;

        $comment_id  = $CTX->get('k_comment_id');
        $tpl_name  = $CTX->get('k_comment_template_name');
        $comment_approved  = $CTX->get('k_comment_approved');

        $parent_link = ( K_PRETTY_URLS ) ? $FUNCS->get_pretty_template_link( $tpl_name ) : $tpl_name;
        $view_link = K_SITE_URL . $parent_link . "?comment=" . $comment_id;
        $update_link = $FUNCS->generate_route( 'comments', 'edit_view', array('nonce'=>$FUNCS->create_nonce('edit_page_'.$comment_id), 'id'=>$comment_id) );
        $can_update = $can_delete = $can_approve = 1;
        $can_view = $comment_approved;
        $delete_prompt = $FUNCS->t( 'confirm_delete_comment' );

        $arr_vars = array();
        $arr_vars['k_page_id'] = $comment_id;
        $arr_vars['k_view_link'] = $view_link;
        $arr_vars['k_update_link'] = $FUNCS->get_qs_link( $update_link );
        $arr_vars['k_can_update'] = $can_update;
        $arr_vars['k_can_delete'] = $can_delete;
        $arr_vars['k_can_approve'] = $can_approve;
        $arr_vars['k_can_view'] = $can_view;
        $arr_vars['k_delete_prompt'] = $delete_prompt;

        $CTX->set_all( $arr_vars );


    }

    function _render_gallery_folder( $max_in_row=0, $current_pos=0, $start_from=0 ){
       global $FUNCS, $CTX, $DB;

        // calculate if item is last in row (taking into consideration any folders displayed before)
        if( $max_in_row ){
            $is_last = (($current_pos+$start_from) % $max_in_row) ? '0' : '1';
            $CTX->set( 'k_last_item', $is_last );
        }

        // label
        $item_label = $CTX->get( 'k_folder_title' );
        if( $item_label=='' ) $item_label = $CTX->get( 'k_folder_name' );
        $CTX->set( 'k_item_label', $item_label );
    }

    function _render_filter_folders(){
        global $DB, $CTX, $FUNCS, $PAGE;

        // Set info about selected folder
        $fid = ( isset($_GET['fid']) && $FUNCS->is_non_zero_natural($_GET['fid']) ) ? (int)$_GET['fid'] : '';
        $fname = '';
        if( $fid ){
            $rs = $DB->select( K_TBL_FOLDERS, array('name'), "template_id='".$DB->sanitize( $PAGE->tpl_id )."' and id='".$DB->sanitize( $fid )."'" );
            if( count($rs) ){
                $fname = $rs[0]['name'];
            }
        }
        $CTX->set( 'k_selected_folderid', $fid, 'global' );
        $CTX->set( 'k_selected_foldername', $fname, 'global' );
        $CTX->set( 'k_filter_link', $FUNCS->get_qs_link($CTX->get('k_route_link'), array('pg','fid')) );
        $CTX->set( 'k_root_text', $FUNCS->t('view_all_folders') );
    }

    function _render_filter_search(){
        global $CTX, $FUNCS, $PAGE;

        $page_ids = '';
        $msg = 'Search&hellip;';
        $query = strip_tags( trim($_GET['s']) );

        if( $query ){
            $code = "<cms:search masterpage='".$PAGE->tpl_name."' ids_only='1' show_future_entries='1' qs_param='<' />"; // the dummy 'qs_param' will effectively make the cms:search tag ignore pagination
            $parser = new KParser( $code );
            $page_ids = $parser->get_HTML();
            if( !$page_ids ) $page_ids='0';
        }
        $CTX->set( 'k_selected_pageids', $page_ids, 'global' );
        $CTX->set( 'k_search_msg', $msg );
        $CTX->set( 'k_search_text', $query );
        $CTX->set( 'k_filter_link', $FUNCS->get_qs_link($CTX->get('k_route_link'), array('pg','s')) );
    }

    function _render_filter_sort(){
        global $CTX, $FUNCS, $PAGE;

        $fields = &$FUNCS->get_admin_list_fields();

        $orderby = trim($_GET['orderby']);
        if( strlen($orderby) ){
            $sortable = array();
            foreach( $fields as $field ){
                if( $field['sortable'] ) $sortable[] = $field['sort_name'];
            }

            if( !in_array($orderby, $sortable) ) $orderby='';
        }
        if( $orderby=='' ) $orderby = $FUNCS->admin_list_default_sort['orderby'];

        $order = strtolower( trim($_GET['order']) );
        if( strlen($order) ){
            if( $order!='desc' && $order!='asc' ) $order = '';
        }
        if( $order=='' ) $order = $FUNCS->admin_list_default_sort['order'];

        for( $x=0; $x<count($fields); $x++ ){
            if( $fields[$x]['sort_name']==$orderby ){
                $fields[$x]['is_current'] = '1';
                break;
            }
        }

        $CTX->set( 'k_selected_orderby', $orderby, 'global' );
        $CTX->set( 'k_selected_order', $order, 'global' );
    }

    function _render_filter_related(){
        global $CTX, $FUNCS, $PAGE, $DB;

        $CTX->set( 'k_show_banner', '0' );

        // check if valid 'cid' and 'rid' querystring parameters present
        $cid = ( isset($_GET['cid']) && $FUNCS->is_non_zero_natural($_GET['cid']) ) ? (int)$_GET['cid'] : null;
        $rid = ( isset($_GET['rid']) && $FUNCS->is_non_zero_natural($_GET['rid']) ) ? (int)$_GET['rid'] : null;

        if( $cid && $rid ){

            // $rid - check if a relation field with this id exists in the current template
            $sql = "template_id='" . $DB->sanitize($PAGE->tpl_id) . "' AND id='" . $DB->sanitize($rid) . "' AND deleted='0' AND k_type='relation'";
            $rs = $DB->select( K_TBL_FIELDS, array('name'), $sql );
            if( count($rs) ){
                $field_name = $rs[0]['name'];
                $f = $PAGE->_fields[$field_name];
                if( $f ){
                    $rel_template = $f->masterpage;

                    // $cid - check if this page belongs to the related template
                    $tables = K_TBL_PAGES . ' p' . "\r\n";
                    $tables .= 'inner join ' . K_TBL_TEMPLATES . ' t on t.id = p.template_id' . "\r\n";
                    $sql = "p.parent_id=0 AND p.id='" . $DB->sanitize( $cid ). "' AND t.name='" . $DB->sanitize($rel_template) . "' limit 1";

                    $rs = $DB->select( $tables, array('page_name', 'page_title', 'template_id', 'name', 'title'), $sql );
                    if( count($rs) ){
                        // all checks fine .. set variables in context
                        $vars = array();

                        // for cms:pages
                        $str_custom_field = "$field_name=id($cid)";
                        $CTX->set( 'k_selected_custom_field', $str_custom_field, 'global' );

                        // for banner
                        $vars['k_show_banner'] = '1';
                        $vars['k_rel_tpl_id'] = $rs[0]['template_id'];
                        $vars['k_rel_tpl_name'] = $rs[0]['name'];
                        $vars['k_rel_tpl_title'] = $rs[0]['title'];
                        $vars['k_rel_page_id'] = $cid;
                        $vars['k_rel_page_name'] = $rs[0]['page_name'];
                        $vars['k_rel_page_title'] = $rs[0]['page_title'];
                        $vars['k_rel_page_edit_link'] = $FUNCS->generate_route( $rs[0]['name'], 'edit_view', array('nonce'=>$FUNCS->create_nonce('edit_page_'.$cid), 'id'=>$cid) );
                        $CTX->set_all( $vars );
                    }
                }
            }
        }
    }

    function _render_filter_comments_parent(){
        global $CTX, $FUNCS, $DB;

        $CTX->set( 'k_show_banner', '0' );

        // check if valid 'page_id' querystring parameter present
        $page_id = ( isset($_GET['page_id']) && $FUNCS->is_non_zero_natural($_GET['page_id']) ) ? (int)$_GET['page_id'] : null;

        if( $page_id ){
            $rs = $DB->select( K_TBL_PAGES, array('page_title', 'page_name'), "id='" . $DB->sanitize( $page_id ). "'" );
            if( count($rs) ){
                $page_title = ( $rs[0]['page_title'] )? $rs[0]['page_title'] : $rs[0]['page_name'];

                // for cms:comments
                $CTX->set( 'k_selected_pageids', $page_id, 'global' );

                // for banner
                $vars = array();
                $vars['k_show_banner'] = '1';
                $vars['k_parent_page'] = $page_title;
                $CTX->set_all( $vars );
            }
        }
    }

    function _render_filter_status(){
        global $DB, $CTX, $FUNCS, $PAGE;

        // Set info about selected status
        $status = ( isset($_GET['status']) && $FUNCS->is_natural($_GET['status']) ) ? (int)$_GET['status'] : '';
        $CTX->set( 'k_selected_status', $status, 'global' );
        $CTX->set( 'k_filter_link', $FUNCS->get_qs_link($CTX->get('k_route_link'), array('comments_pg','status')) );
    }

    function _render_filter_templates(){
        global $CTX, $FUNCS, $DB;

        // check if valid 'tpl' querystring parameter present
        $tpl_id = ( isset($_GET['tpl']) && $FUNCS->is_non_zero_natural($_GET['tpl']) ) ? (int)$_GET['tpl'] : null;

        // List of templates with drafts available
        $rs3 = $DB->select( K_TBL_PAGES . ' p inner join ' . K_TBL_TEMPLATES . ' t on p.template_id = t.id', array('t.id', 't.name', 't.title', 'count(t.name) as cnt'), 'p.parent_id>0 GROUP BY t.name ORDER BY t.name asc' );
        $str .= '<select id="f_k_templates" name="f_k_templates">';
        $str .= '<option value="-1" >-- '.$FUNCS->t('view_all_drafts').' --</option>';
        if( count($rs3) ){
            foreach( $rs3 as $t ){
                $abbr_title = $t['title'] ? $t['title'] : $t['name'];
                $abbr_title = $FUNCS->excerpt( $abbr_title, '30', '&hellip;' );
                $t_selected = ($t['id']==$tpl_id) ? ' SELECTED=1 ' : '';
                $str .= '<option value="'.$t['id'].'" '.$t_selected.'>'.$abbr_title.'</option>';
            }
        }
        $str .= '</select>';

        $CTX->set( 'k_templates_dropdown', $str );
        $CTX->set( 'k_selected_templateid', $tpl_id, 'global' );
        $CTX->set( 'k_filter_link', $FUNCS->get_qs_link($CTX->get('k_route_link'), array('pg','tpl','pid')) );
    }

    function _render_filter_parent(){ // drafts
        global $CTX, $FUNCS, $PAGE, $DB;

        $route = $FUNCS->current_route;

        if( $route->name=='list_view' ){
            // check if valid 'pid' querystring parameter present (to show drafts only of a particular parent)
            $parent_id = ( isset($_GET['pid']) && $FUNCS->is_non_zero_natural($_GET['pid']) ) ? (int)$_GET['pid'] : null;

            $CTX->set( 'k_show_banner', '0' );
            $CTX->set( 'k_selected_parentid', $parent_id, 'global' );
        }
        else{ // edit_view

            // Template link
            $tpl_name = $PAGE->tpl_title ? $PAGE->tpl_title : $PAGE->tpl_name;
            if( $PAGE->tpl_is_clonable ){
                $tpl_link = $FUNCS->generate_route( $PAGE->tpl_name, 'list_view' );
            }
            else{
                $tpl_link = $FUNCS->generate_route( $PAGE->tpl_name, 'edit_view', array('nonce'=>$FUNCS->create_nonce('edit_page_'.$PAGE->tpl_id), 'id'=>'') );
            }

            // Parent page's link
            $draft_of = $PAGE->parent_id;
            $rs = $DB->select( K_TBL_PAGES, array('page_name', 'page_title'), "id='" . $DB->sanitize( $draft_of ). "'" );
            if( count($rs) ){
                $parent_of_draft = $rs[0]['page_name'];
                $parent_of_draft_title = $rs[0]['page_title'];
            }

            if( $PAGE->tpl_is_clonable ){
                if( $parent_of_draft ){
                    $parent_name = $parent_of_draft_title ? $parent_of_draft_title : $parent_of_draft;
                    $parent_link = $FUNCS->generate_route( $PAGE->tpl_name, 'edit_view', array('nonce'=>$FUNCS->create_nonce('edit_page_'.$draft_of), 'id'=>$draft_of) );
                }
                else{
                   $parent_name = $FUNCS->t('original_deleted');
                   $parent_link = '#';
                }
            }

            // for banner
            $vars['k_show_banner'] = '1';
            $vars['k_draft_tpl'] = $tpl_name;
            $vars['k_draft_tpl_link'] = $tpl_link;
            $vars['k_draft_parent'] = $parent_name;
            $vars['k_draft_parent_link'] = $parent_link;
            $CTX->set_all( $vars );
        }
    }

    function _render_draft_button( $mode='create' ){
        global $CTX;

        if( $mode=='update' ){
            $CTX->set( 'k_draft_mode', 'update' );
        }
        else{
            $CTX->set( 'k_draft_mode', 'create' );
        }
    }

    function _render_form_row(){
        global $CTX;

        $field_type = $CTX->get( 'k_field_type' );

        static $done = array();
        if( !$done[$field_type] ){
            $CTX->set( 'k_add_js_for_field_'.$field_type, '1' );
            $done[$field_type]=1;

            if( $field_type=='group' ){
                if( $CTX->get('k_field_is_collapsed')=='-1' ){ // first group is always shown expanded by default
                    $CTX->set( 'k_field_is_collapsed', '0' );
                }
            }
        }
        else{
            $CTX->set( 'k_add_js_for_field_'.$field_type, '0' );

            if( $field_type=='group' ){
                if( $CTX->get('k_field_is_collapsed')=='-1' ){
                    $CTX->set( 'k_field_is_collapsed', '1' );
                }
            }
        }

        if( $field_type=='group' && $CTX->get('k_error')){ // if form error, expand all groups
            $CTX->set( 'k_field_is_collapsed', '0' );
        }

        // return an array of candidate templates
        return array( 'form_row_'.$field_type );
    }

    function _render_form_input( $name=null ){
        global $CTX, $PAGE;

        if( !is_null($name) ){
            $vars = array();
            $vars['k_field_input_name'] = $name;
            if( isset($PAGE->_fields[$name]) ){
                $vars['k_field_type'] = $PAGE->_fields[$name]->k_type;
            }

            $CTX->set_all( $vars );
        }
    }

    function _render_form_field_deleted(){
        global $CTX;

        static $done=0;
        if( !$done ){
            $CTX->set( 'k_add_js_for_deleted_field', '1' );
            $done=1;
        }
        else{
            $CTX->set( 'k_add_js_for_deleted_field', '0' );
        }
    }

    function _render_advanced_settings(){
        global $CTX;

        $CTX->set( 'k_show_advanced_settings', '0', 'global' );
    }

    function _render_simple( $content='', $title='', $show_logo='1' ){
        global $FUNCS, $CTX;

        if( $title=='' ){ $title = $FUNCS->t('login_title'); }

        $CTX->set( 'title', $title );
        $CTX->set( 'content', $content );
        $CTX->set( 'show_logo', $show_logo );
    }

    function _render_login( $res ){
        global $FUNCS, $CTX;

        if( $FUNCS->is_error($res) ){
            $CTX->set( 'k_show_error', 1 );
            $CTX->set( 'k_error_msg', $res->err_msg );
        }
    }

    function _render_forgot_password( $msg, $msg_class, $msgonly ){
        global $FUNCS, $CTX;

        if( empty($msg) ){
            $msg = $FUNCS->t('recovery_prompt');
        }

        $CTX->set( 'k_message', $msg );
        $CTX->set( 'k_message_class', $msg_class );
        $CTX->set( 'k_show_form', !$msgonly );
    }

    function _render_main( $content='', $simple='0' ){
        global $CTX;

        $CTX->set( 'k_main_content', $content );
        $CTX->set( 'k_show_simple', $simple );

        $admin_footer = '';
        if( K_PAID_LICENSE && defined('K_ADMIN_FOOTER') ){
            $admin_footer = K_ADMIN_FOOTER;
        }
        $CTX->set( 'k_admin_footer', $admin_footer );
    }

    function _render_logo( $dark=0, $href='', $id='', $class='' ){
        global $CTX;

        if( K_PAID_LICENSE ){
            if( $dark ){
                $logo_src = ( defined('K_LOGO_DARK') ) ? K_ADMIN_URL.'theme/images/'.K_LOGO_DARK : K_SYSTEM_THEME_URL.'includes/admin/images/couch_dark.png';
            }
            else{
                $logo_src = ( defined('K_LOGO_DARK') ) ? K_ADMIN_URL.'theme/images/'.K_LOGO_LIGHT : K_SYSTEM_THEME_URL.'includes/admin/images/couch_light.png';
            }
        }
        else{
            $logo_src = ( $dark ) ? K_SYSTEM_THEME_URL.'includes/admin/images/couch_dark.png' : K_SYSTEM_THEME_URL.'includes/admin/images/couch_light.png';
        }

        $CTX->set( 'k_logo_src', $logo_src );
        $CTX->set( 'k_logo_href', trim($href) );
        $CTX->set( 'k_logo_class', trim($class) );
        $CTX->set( 'k_logo_id', trim($id) );
    }

    function _render_title(){
        global $CTX, $FUNCS;

        $title = $FUNCS->admin_title;
        $CTX->set( 'k_title_text', $title['text'] );
        $CTX->set( 'k_title_link', $title['link'] );
        $CTX->set( 'k_title_icon', $title['icon'] );
    }

    function _render_subtitle(){
        global $CTX, $FUNCS;

        $subtitle = $FUNCS->admin_subtitle;
        $CTX->set( 'k_subtitle_text', $subtitle['text'] );
        $CTX->set( 'k_subtitle_icon', $subtitle['icon'] );
    }

    function _render_menu_ul( &$item, &$config ){
        global $CTX;

        $level = $CTX->get('k_level', 1);
        if( $level==0 ){
            if( $config['menu_class'] ) $class .= $config['menu_class'] . ' ';
            if( $config['menu_id'] ) $id = 'id="' . $config['menu_id'] . '" ';
        }
        $class .= 'level-' . $level;
        $html .= '<'.$config['list_type'].' ' . $id .'class="'.$class.'">';

        return $html;
    }

    function _render_menu_li( &$item, &$config ){
        global $CTX, $FUNCS;

        $id = 'item-' . $item->name;
        $class = ( strlen($item->class) ) ? $item->class . ' ' : '';
        $class .= 'level-' . $CTX->get('k_level', 1) . ' ';
        if( $item->total_children_ex ) $class .= 'has-submenu ';
        if( $item->first_pos ) $class .= $config['first_class'] . ' ';
        if( $item->last_pos ) $class .= $config['last_class'] . ' ';
        if( !$config['no_active_trail'] && $item->is_current ) $class .= $config['active_trail_class'] . ' ';
        if( !$config['no_selected'] && $item->most_current ) $class .= $config['selected_class'] . ' ';
        $html .= '<li id="'.$id.'" class="'.$class.'">';

        $title = $item->title;
        $desc = ( strlen($item->desc) ) ? ' title="' . $item->desc . '"' : '';
        $link = $item->href;
        $link = ( $link ) ? ' href="' . $link . '"' : ' href="#" onClick="return false"';
        if( $item->open_external ) $target = ' target="_blank"';
        $html .= '<a' . $desc . $link . $target . '>';
        if( $item->icon ){
            $html .= $FUNCS->get_icon( $item->icon ) .' ';
        }
        $html .= $title . '</a>';

        return $html;
    }

    function _render_icon( $name ){
        global $FUNCS;

        $html .= '<svg class="i"><use xlink:href="'.K_SYSTEM_THEME_URL.'assets/open-iconic.svg#'.$FUNCS->ti($name).'"></use></svg>';

        return $html;
    }

    function _render_alert( $heading='', $content='', $type='', $center='' ){
        global $CTX;

        $type = strtolower( trim($type) );
        if( !in_array($type, array('success', 'error', 'warning', 'info')) ){
            $type = 'info';
        }

        $CTX->set( 'k_alert_type', $type );
        $CTX->set( 'k_alert_heading', trim($heading) );
        $CTX->set( 'k_alert_content', trim($content) );
        $CTX->set( 'k_alert_center', $center == '1' ? '1' : '0' );
    }

    function _render_list_checkbox( $for_header=0, $text_label=0 ){
        global $CTX, $FUNCS;

        $page_id = $CTX->get( 'k_page_id' );
        $can_delete = $CTX->get( 'k_can_delete' );

        if( $for_header ){
            $html = '<label class="ctrl checkbox">';
            $html .= '<input class="checkbox-all" type="checkbox" name="check-all" />';
            if( $text_label ){
                $html .= '<span class="ctrl-option"></span>'.$FUNCS->t('select-deselect').'</label>';
            }
            else{
                $html .= '<span class="ctrl-option tt" title="'.$FUNCS->t('select-deselect').'"></span></label>';
            }
        }
        else{
            $html = '<label class="ctrl checkbox'.( !$can_delete ? ' ctrl-disabled' : '' ).'">';
            $html .= '<input type="checkbox" value="'.$page_id.'" class="page-selector checkbox-item" name="page-id[]" id="page-selector-'.$page_id.'"';
            if(  !$can_delete  ) $html .= ' disabled="1"';
            $html .= '/>';
            $html .= '<span class="ctrl-option"></span></label>';
        }

        return $html;
    }

    function _render_list_title( $count=0 ){
        global $CTX, $FUNCS;

        $page_title = $CTX->get( 'k_page_title' );
        //$page_date = $CTX->get( 'k_page_date' );
        //if( $page_date=='0000-00-00 00:00:00' ) $page_class = ' class="unpublished"';

        $count = $FUNCS->is_non_zero_natural( $count ) ? intval( $count ) : 48;
        $link = $CTX->get( 'k_update_link' );
        $can_update = $CTX->get( 'k_can_update' );

        if( $can_update ){
            $html ='<a href="'.$link.'" title="'.$page_title.'"'.$page_class.'>'.$FUNCS->excerpt( $page_title, $count, '&hellip;' ).'</a>';
        }
        else{
            $html = $FUNCS->excerpt( $page_title, $count, '&hellip;' );
        }

        return $html;
    }

    function _render_list_nestedpage_title( $count=0 ){
        global $CTX, $FUNCS;

        $page_title = $CTX->get( 'k_page_title' );
        $page_date = $CTX->get( 'k_page_date' );
        $level = $CTX->get( 'k_level' );
        $weight = $CTX->get( 'k_nestedpage_weight' );
        $show_in_menu = $CTX->get( 'k_show_in_menu' );
        $link = $CTX->get( 'k_update_link' );
        $can_update = $CTX->get( 'k_can_update' );

        if( $page_date=='0000-00-00 00:00:00' ) $page_class = ' class="unpublished"';
        $count = $FUNCS->is_non_zero_natural( $count ) ? intval( $count ) : 60;
        $len_pad = 0;

        for( $x=0; $x<$level; $x++ ){
            $pad .= '<span class="level-sep">-</span>';
            $len_pad += 3;
        }

        $avail = $count;
        if( $len_pad+$FUNCS->strlen($page_title) > $avail ){
            $func = function_exists('mb_substr') ? 'mb_substr' : 'substr';
            $abbr_title = ( ($len_pad<$avail) ? $func($page_title, 0, $avail-$len_pad) : $func($pad, 0, $len_pad-$avail) ). '&hellip;';
        }
        else{
            $abbr_title = $page_title;
        }

        $html = $pad;
        if( $can_update ){
            $html .= '<a href="'.$link.'" title="['.$weight.'] '.$page_title.'"'.$page_class.'>'.$abbr_title.'</a>';
        }
        else{
            $html .= $abbr_title;
        }
        if( !$show_in_menu ){
            $html .= '&nbsp;<a href="#" onclick="return false" class="icon tt" title="'.$FUNCS->t('not_shown_in_menu').'">'.$FUNCS->get_icon('ban').'</a>';
        }
        if( $CTX->get( 'k_is_pointer' ) ){
            if( $CTX->get( 'k_masquerades' ) ){
                $html .= '&nbsp;<a href="#" onclick="return false" class="icon tt" title="'.$FUNCS->t('masquerades'). ': '. $CTX->get( 'k_masqueraded_template' ). '">'.$FUNCS->get_icon('arrow-thick-right').'</a>';
                $masqueraded_links = $CTX->get( 'k_masqueraded_links' );
                if( $masqueraded_links ){
                    $html .= '&nbsp;<span class="pointer_links">'. $masqueraded_links .'</span>';
                }
            }
            else{
                $pointer_link = $CTX->get( 'k_pointer_link' );
                $html .= '&nbsp;<a href="'.$pointer_link.'" target="_blank" class="icon tt" title="'.$FUNCS->t('points_to'). ': '. $pointer_link. '">'.$FUNCS->get_icon('arrow-thick-right').'</a>';
            }
        }

        return $html;
    }

    function _render_list_template( $count=0 ){
        global $CTX, $FUNCS;

        if( $CTX->get('tpl_clonable') ){
            $tpl_title = $CTX->get( 'tpl_title' );
            if( $tpl_title=='' ){ $tpl_title = $CTX->get('tpl_name'); }

            $count = $FUNCS->is_non_zero_natural( $count ) ? intval( $count ) : 30;
            $html = $FUNCS->excerpt( $tpl_title, $count, '&hellip;' );
        }
        else{
            $html = '&nbsp;';
        }

        return $html;
    }

    function _render_list_comments_count(){
        global $CTX, $FUNCS;

        $count_drafts = $CTX->get( 'k_count_drafts' );
        $comments_count = $CTX->get( 'k_comments_count' );

        if( $count_drafts ){
            $a_title = ( $count_drafts > 1 ) ? ' '.$FUNCS->t('drafts') : ' '.$FUNCS->t('draft');
            $html .= '<a class="icon tt" href="'.$CTX->get( 'k_drafts_link' ).'" title="'.$count_drafts.$a_title.'">'.$FUNCS->get_icon('document').$count_drafts.'</a>';
        }

        if( $comments_count ){
            $a_title = ( $comments_count > 1 ) ? ' '.$FUNCS->t('comments') : ' '.$FUNCS->t('comment');
            $html .= '<a class="icon tt" href="'.$CTX->get( 'k_comments_link' ).'" title="'.$comments_count.$a_title.'">'.$FUNCS->get_icon('chat').$comments_count.'</a>';
        }

        if( !$count_drafts && !$comments_count ){
            $html .= '&nbsp;';
        }

        return $html;
    }

    function _render_list_date(){
        global $CTX, $FUNCS;

        $publish_date = $CTX->get( 'k_page_date' );

        if( $publish_date != '0000-00-00 00:00:00' ){
            $html = date( "M jS Y", strtotime($publish_date) );
        }
        else{
            $html = '<span class="label label-error">'.$FUNCS->t('unpublished').'</span>';
        }

        return $html;
    }

    function _render_list_mod_date(){
        global $CTX, $FUNCS;

        $mod_date = $CTX->get( 'modification_date' );
        $html = date( "M jS Y @ H:i", strtotime($mod_date) );

        return $html;
    }

    function _render_list_updown( $horizontal=0 ){
        global $CTX, $FUNCS;

        $html = '';
        $page_id = $CTX->get( 'k_page_id' );
        $tpl_id = $CTX->get( 'k_template_id' );
        $nested_pages = $CTX->get( 'k_template_nested_pages' );
        $cur_link = $CTX->get( 'k_qs_link' );

        if( $nested_pages ){
            $k_pos = $CTX->get( 'k_pos' );
            $k_total_siblings = $CTX->get( 'k_total_siblings' );
            $show_up = ( $k_pos != '0' );
            $show_down = ( $k_pos != $k_total_siblings-1 );
        }
        else{
            $k_current_record = $CTX->get( 'k_current_record' );
            $k_total_records = $CTX->get( 'k_total_records' );
            $show_up = ( $k_current_record != '1' );
            $show_down = ( $k_current_record != $k_total_records );
        }

        // up arrow
        $dir = ( $horizontal ) ? 'left' : 'up';
        $icon = ( $horizontal ) ? 'chevron-left' : 'chevron-top';
        if( $show_up ){
            $html .='<a class="up icon tt" href="#" onclick="$( this ).blur(); k_updown( \''.$page_id.'\', 1 ); return false;" title="'.$FUNCS->t($dir).'">'.$FUNCS->get_icon($icon).'</a>';
        }
        else{
            $html .='<span class="icon-spacer"></span>';
        }

        // down arrow
        $dir = ( $horizontal ) ? 'right' : 'down';
        $icon = ( $horizontal ) ? 'chevron-right' : 'chevron-bottom';
        if( $show_down ){
            $html .='<a class="down icon tt" href="#" onclick="$( this ).blur(); k_updown( \''.$page_id.'\', 0 ); return false;" title="'.$FUNCS->t($dir).'">'.$FUNCS->get_icon($icon).'</a>';
        }
        else{
            $html .='<span class="icon-spacer"></span>';
        }

        static $done=0;
        if( !$done ){
            $done=1;

            $FUNCS->add_js("
                function k_updown( id, dir ){
                    $('#k_overlay').css('display', 'block');

                    $.ajax({
                        dataType: 'html',
                        url: '".$cur_link."',
                        type: 'POST',
                        data: {
                            k_updown: 1,
                            id: id,
                            dir: dir,
                            nonce: encodeURIComponent( '".$FUNCS->create_nonce( 'updown_page_'.$tpl_id )."' )
                        },
                    })
                    .done(function( data ){
                        $( '#listing' ).replaceWith( $( $.parseHTML( data, document ) ).find( '#listing' ) );
                    })
                    .always(function() {
                        $('#k_overlay').css('display', 'none');
                    });
                }
            ");

            $FUNCS->add_js("
                $(function(){
                    $('<div/>', {
                        id: 'k_overlay',
                    })
                    .css({
                        'filter':'alpha(opacity=60)', 'zoom':'1',
                        'opacity':'0.6',
                        'height': '100%',
                        'width': '100%',
                        'background-color': '#0b0b0b',
                        'z-index': 10000,
                        'position': 'absolute',
                        'top': 0,
                        'left': 0,
                        'display': 'none'
                    })
                    .appendTo( 'body' );
                });
            ");
        }

        return $html;
    }

    function _render_row_actions(){
        global $FUNCS, $CTX;

        $arr_actions = $FUNCS->get_actions( 'row' );

        $html = '';
        foreach( $arr_actions as $action ){
            if( $action->render ){
                $action->set_in_context();
                $html .= $FUNCS->render( $action->render );
            }
        }

        // add an hidden element for showing the buttons in smaller viewports of responsive design
        $html .='<a class="btn btn-actions" role="button" tabindex="0">'.$FUNCS->get_icon('ellipses').'</a>';

        return $html;
    }

    function _render_row_action_edit(){
        global $FUNCS, $CTX;

        $can_update = $CTX->get( 'k_can_update' );

        if( $can_update ){
            $link = $CTX->get( 'k_update_link' );
            $html ='<a class="icon tt" href="'.$link.'" title="'.$FUNCS->t('edit').'">'.$FUNCS->get_icon('pencil').'</a>';
        }

        return $html;
    }

    function _render_row_action_delete(){
        global $FUNCS, $CTX;

        static $done=0;
        $can_delete = $CTX->get( 'k_can_delete' );
        $page_id = $CTX->get( 'k_page_id' );
        $page_title = $CTX->get( 'k_page_title' );
        $delete_prompt = str_replace( "'", "\'", $CTX->get('k_delete_prompt') );

        if( $can_delete ){
            $html ='<a class="icon tt delete-list" href="#"  onclick="k_delete_single( \''.$page_id.'\', \''.$page_title.'\' ); return false;" title="'.$FUNCS->t('delete').'">'.$FUNCS->get_icon('trash').'</a>';

            if( !$done ){
                $done=1;
                $form_name = $CTX->get( 'k_cur_form' );

                $FUNCS->add_js("
                    function k_delete_single( id, name ){
                        var msg;
                        if( name ){
                            msg = '".$delete_prompt.": '+name+'?';
                        }
                        else{
                            msg = '".$delete_prompt."';
                        }
                        if( confirm(msg) ){
                            $('body').css('cursor', 'wait');
                            var col = $('#page-selector-'+id);
                            col.prop( 'checked', true );

                            var form = $('#".$form_name."');
                            form.find('#k_bulk_action').val('batch_delete');
                            form.submit();
                        }
                        return false;
                    }
                ");
            }
        }

        return $html;
    }

    function _render_row_action_approve(){
        global $FUNCS, $CTX;

        static $done=0;
        $comment_id = $CTX->get( 'k_page_id' );
        $approved = $CTX->get( 'k_comment_approved' );
        $can_approve = $CTX->get( 'k_can_approve' );
        $icon = ( $approved ) ? 'thumb-down' : 'thumb-up';
        $title = ( $approved ) ? $FUNCS->t('unapprove') : $FUNCS->t('approve');
        $class = ( $approved ) ? 'disapprove-comment' : 'approve-comment';

        if( $can_approve ){
            $html ='<a href="#" onclick="k_approve_single( \''.$comment_id.'\', \''.$approved.'\' ); return false;" title="'.$title.'" class="icon tt '.$class.'">'.$FUNCS->get_icon($icon).'</a>';

            if( !$done ){
                $done=1;
                $form_name = $CTX->get( 'k_cur_form' );

                $FUNCS->add_js("
                    function k_approve_single( id, approved ){
                        $('body').css('cursor', 'wait');
                        var col = $('#page-selector-'+id);
                        col.prop( 'checked', true );

                        var form = $('#".$form_name."');
                        var action = (approved==1) ? 'batch_unapprove' : 'batch_approve';
                        form.find('#k_bulk_action').val(action);
                        form.submit();

                        return false;
                    }
                ");
            }
        }

        return $html;
    }

    function _render_row_action_view(){
        global $FUNCS, $CTX;

        $can_view = $CTX->get( 'k_can_view' );

        if( $can_view ){
            $link = $CTX->get( 'k_view_link' );
            $html ='<a class="icon tt" href="'.$link.'" target="_blank" title="'.$FUNCS->t('view').'">'.$FUNCS->get_icon('magnifying-glass').'</a>';
        }

        return $html;
    }

    function _render_row_action_drafts(){ // for gallery
        global $FUNCS, $CTX;

        $count_drafts = $CTX->get( 'k_count_drafts' );
        if( $count_drafts ){
            $link = $CTX->get( 'k_drafts_link' );

            $a_title = ( $count_drafts > 1 ) ? ' '.$FUNCS->t('drafts') : ' '.$FUNCS->t('draft');
            $html = '<a title="'.$count_drafts.$a_title.'" href="'.$link.'" class="icon tt">'.$FUNCS->get_icon('document').'</a>';
        }

        return $html;
    }

    function k_skip_qs_params( &$arr_skip_qs ){
        $arr_skip_qs[] = 'o';
        $arr_skip_qs[] = 'q';
    }

    if( defined('K_ADMIN') ){
        $FUNCS->add_event_listener( 'alter_pages_form_default_fields',  'k_add_drafts_button' );

        function k_add_drafts_button( &$arr_fields ){
            global $FUNCS, $PAGE;

            $route = $FUNCS->current_route;
            if( $route->module=='pages' && $route->name=='edit_view' ){
                if( $PAGE->tpl_nested_pages && $PAGE->_fields['k_is_pointer']->get_data() ) return;

                // add 'Create drafts' button
                $arr_fields[ 'k_draft_button' ] = array(
                    'no_wrapper'=>'1',
                    'content'=>"<cms:render 'draft_button' />",
                    'group'=> '_advanced_settings_',
                    'order'=>0,
                    'listener'=>array( 'pages_form_custom_action', 'k_create_draft' ),
                );
            }
        }

        function k_create_draft( $action, &$redirect_dest ){
            global $FUNCS, $DB, $PAGE;

            if( $action=='create_draft' ){
                $draft_id = $PAGE->create_draft();
                if( $FUNCS->is_error($draft_id) ){
                    ob_end_clean();
                    die( $draft_id->err_msg );
                }

                // redirect to the draft
                $tpl_id = $PAGE->tpl_id;
                $nonce = $FUNCS->create_nonce('edit_draft_'.$tpl_id.','.$draft_id);
                $edit_link = $FUNCS->generate_route( 'drafts', 'edit_view', array('nonce'=>$nonce, 'tpl_id'=>$tpl_id, 'id'=>$draft_id) );

                header( "Location: ".$edit_link );
                exit;
            }
        }
    }

    $FUNCS->register_spl_template( 'folders', 'k_spl_template_handler' );
    $FUNCS->register_spl_template( 'users', 'k_spl_template_handler' );
    $FUNCS->register_spl_template( 'comments', 'k_spl_template_handler' );

    function k_spl_template_handler( $id, $name, &$mode, $masterpage ){
        global $FUNCS, $PAGE;

        if( $masterpage=='folders' ){
            if( $mode == 'auto' ){
                return $FUNCS->raise_error( "mode 'auto' not supported for folders" );
            }
            elseif( $mode == 'create' ){
                // create a folder object
                $folder = new KFolder( array('id'=>null, 'name'=>'', 'pid'=>'-1', 'template_id'=>$PAGE->tpl_id), $PAGE->tpl_name, new KError()/*dummy*/ );
            }
            else{ // edit or delete
                if( $id ){
                    $folder = &$PAGE->folders->find_by_id( $id );
                }
                else{
                    $folder = &$PAGE->folders->find( $name );
                }

                if( !$folder ){
                    return $FUNCS->raise_error( 'Folder not found' );
                }

                $PAGE->is_folder_view = 1;
                $PAGE->folder_id = $folder->id;
            }

            $folder->populate_fields();
            return $folder;
        }
        elseif( $masterpage=='users' ){
            global $OBJ;

            if( $mode=='auto' ){ // will make the form bind to the global $OBJ object
                if( !($OBJ instanceof KUser) ){
                    return $FUNCS->raise_error( "Object to bind in 'auto' mode is not a 'user'" );
                }

                $user = $OBJ;

                // set correct mode
                $mode = ( $user->id==-1 ) ? 'create' : 'edit';
            }
            else{
                if( $mode=='edit' || $mode=='delete' ){
                    $user = ( $name ) ? new KUser( $name ) : new KUser( $id, 1 );
                    if( $user->id == -1 ){
                        return $FUNCS->raise_error( 'User not found' );
                    }
                }
                else{ // 'create'
                    $user = new KUser();
                }
            }

            $user->populate_fields();
            return $user;
        }
        elseif( $masterpage=='comments' ){
            global $OBJ;

            if( $mode=='edit' || $mode=='delete' ){
                require_once( K_COUCH_DIR.'comment.php' );

                $comment = new KComment( $id );
                if( $comment->id == -1 ){
                    return $FUNCS->raise_error( 'Comment not found' );
                }
            }
            else{ // 'create' or 'auto'
                return $FUNCS->raise_error( "mode '".$mode."' not supported for comments" );
            }

            return $comment;
        }
    }
