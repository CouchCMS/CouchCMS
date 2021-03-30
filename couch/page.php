<?php
    /*
    The contents of this file are subject to the Common Public Attribution License
    Version 1.0 (the "License"); you may not use this file except in compliance with
    the License. You may obtain a copy of the License at
    http://www.couchcms.com/cpal.html. The License is based on the Mozilla
    Public License Version 1.1 but Sections 14 and 15 have been added to cover use
    of software over a computer network and provide for limited attribution for the
    Original Developer. In addition, Exhibit A has been modified to be consistent with
    Exhibit B.

    Software distributed under the License is distributed on an "AS IS" basis, WITHOUT
    WARRANTY OF ANY KIND, either express or implied. See the License for the
    specific language governing rights and limitations under the License.

    The Original Code is the CouchCMS project.

    The Original Developer is the Initial Developer.

    The Initial Developer of the Original Code is Kamran Kashif (kksidd@couchcms.com).
    All portions of the code written by Initial Developer are Copyright (c) 2009, 2010
    the Initial Developer. All Rights Reserved.

    Contributor(s):

    Alternatively, the contents of this file may be used under the terms of the
    CouchCMS Commercial License (the CCCL), in which case the provisions of
    the CCCL are applicable instead of those above.

    If you wish to allow use of your version of this file only under the terms of the
    CCCL and not to allow others to use your version of this file under the CPAL, indicate
    your decision by deleting the provisions above and replace them with the notice
    and other provisions required by the CCCL. If you do not delete the provisions
    above, a recipient may use your version of this file under either the CPAL or the
    CCCL.
    */

    if ( !defined('K_COUCH_DIR') ) die(); // cannot be loaded directly
    require_once( K_COUCH_DIR.'includes/timthumb.php' );
    require_once( K_COUCH_DIR.'field.php' );
    require_once( K_COUCH_DIR.'folder.php' );

    class KWebpage{
        var $tpl_name = null;
        var $tpl_title;
        var $tpl_id = null;
        var $tpl_desc;
        var $tpl_access_level;
        var $tpl_is_clonable;
        var $tpl_is_commentable;
        var $tpl_is_executable;
        var $tpl_is_hidden;
        var $tpl_order;
        var $tpl_dynamic_folders;
        var $tpl_nested_pages;
        var $tpl_gallery;
        var $tpl_custom_params = array();
        var $tpl_handlers = array();
        var $tpl_type = '';
        var $tpl_has_globals = 0;

        var $id = null;
        var $parent_id = 0;
        var $page_title;
        var $page_name = null;
        var $creation_date;
        var $modification_date;
        var $publish_date;
        var $status;
        var $is_master;
        var $page_folder_id;
        var $access_level;
        var $comments_count; // count of approved comments only
        var $comments_open; // can new comments be added?

        var $fields = array();
        var $_fields = array(); // fields keyed by names
        var $folders = null;
        var $folder;
        var $is_folder_view = 0;
        var $is_archive_view = 0;
        var $folder_id = null; //set if folder view
        var $folder_name; // --do--
        var $comment_id = null; // set only if page loaded by comment id in url
        var $comment_date = null; // --do--
        var $comment_page;

        // fields peculiar to nested pages
        var $nested_parent_id = -1;
        var $weight;
        var $show_in_menu;
        var $menu_text;
        var $is_pointer;
        var $pointer_link;
        var $open_external;
        var $masquerades;
        var $strict_matching;
        var $nested_page_obj;

        var $html;
        var $link;
        var $error = 0;
        var $err_msg = '';

        var $forms = array();
        var $form_num = 0;

        var $CKEditor = null; // used by KField while rendering itself
        var $content_type; //can be optionally set by script via 'content_type' tag

        var $accessed_via_browser = 0; // will be set to 1 for only the template accessed via URL in browser
        var $_template_locked = 0;


        function __construct( $template_id=null, $page_id=null, $page_name=null, $html=null, $skip_custom_fields=null ){
            global $FUNCS;

            $template_id = trim( $template_id );
            $page_id = trim( $page_id );
            $page_name = trim( $page_name );

            if( $template_id ){
                if( $FUNCS->is_non_zero_natural($template_id) ){
                    $this->tpl_id = $template_id;
                }
                else{
                    $this->tpl_name = $template_id; // addons should take care to give non-numeric names to templates
                }
            }
            if( $page_id ) $this->id = $page_id;
            if( $page_name != '' ) $this->page_name = $page_name;
            if( $html != '' ) $this->html = $html;

            $rs = $this->_fill_template_info();
            if( $FUNCS->is_error($rs) ){ $this->error=1; $this->err_msg=$rs->err_msg; return; }

            $this->_fill_fields_info();
            $this->_fill_folders_info();
            $rs = $this->_fill_page_info( $skip_custom_fields ); // $skip_custom_fields set only by the 'pages', 'search' and 'entries' tag

            // HOOK: page_load_complete
            $FUNCS->dispatch_event( 'page_load_complete', array(&$this, &$rs) );

            if( $FUNCS->is_error($rs) ){ $this->error=1; $this->err_msg=$rs->err_msg; return; }
        }

        // Free memory (useful if pages are created in a loop)
        function destroy(){
            global $FUNCS;

            // release fields
            $this->fields = array();
            $this->_fields = array();

            /*if( $this->tpl_nested_pages ){
                if( array_key_exists($this->tpl_id, $FUNCS->cached_nested_pages) ){
                    $tree = &$FUNCS->cached_nested_pages[$this->tpl_id];
                    $tree->destroy();
                    unset( $FUNCS->cached_nested_pages[$this->tpl_id] );
                }
            }*/
        }

        function _fill_template_info(){
            global $DB, $AUTH, $FUNCS;

            if( is_null($this->tpl_id) && is_null($this->tpl_name) ){
                // can only happen when template accessed via URL in browser
                $this->accessed_via_browser = 1;

                $tpl_name = $this->get_template_name();
                if( $FUNCS->is_error($tpl_name) ) return $tpl_name;

                if( array_key_exists( $tpl_name, $FUNCS->cached_templates ) ){
                    $rec = $FUNCS->cached_templates[$tpl_name];
                }
                else{
                    $rs = $DB->select( K_TBL_TEMPLATES, array('*'), "name='" . $DB->sanitize( $tpl_name ). "'" );
                    if( !count($rs) ){
                        // Template needs to be added. Make sure the user is logged-in as super-admin
                        $AUTH->check_access( K_ACCESS_LEVEL_SUPER_ADMIN );
                        $rs = $DB->insert( K_TBL_TEMPLATES, array('name'=>$tpl_name, 'description'=>'') );
                        $rs = $DB->select( K_TBL_TEMPLATES, array('*'), "name='" . $DB->sanitize( $tpl_name ). "'" );
                        if( !count($rs) ) die( "ERROR: Failed to insert record in K_TBL_TEMPLATES" );

                        // HOOK: template_inserted
                        $FUNCS->dispatch_event( 'template_inserted', array(&$rs[0], &$this) );
                    }
                    else{
                        // Since the page is being executed directly from browser (no tpl_id),
                        // check if it is executable before proceeding.
                        if( !$rs[0]['executable'] && $AUTH->user->access_level < K_ACCESS_LEVEL_SUPER_ADMIN ){
                            return $FUNCS->raise_error( "Page not found" );
                        }
                    }
                    $FUNCS->cached_templates[$rs[0]['id']] = $rs[0];
                    $FUNCS->cached_templates[$rs[0]['name']] = $rs[0];
                    $rec = (array)$rs[0];
                }
            }
            else{
                if( array_key_exists( $this->tpl_id, $FUNCS->cached_templates ) ){
                    $rec = $FUNCS->cached_templates[$this->tpl_id];
                }
                else{
                    if( !is_null($this->tpl_id) ){
                        $rs = $DB->select( K_TBL_TEMPLATES, array('*'), "id='" . $DB->sanitize( $this->tpl_id ). "'" );
                    }
                    else{
                        $rs = $DB->select( K_TBL_TEMPLATES, array('*'), "name='" . $DB->sanitize( $this->tpl_name ). "'" );
                    }
                    if( !count($rs) ){
                        return $FUNCS->raise_error( "Failed to find record in K_TBL_TEMPLATES" );
                    }
                    $FUNCS->cached_templates[$rs[0]['id']] = $rs[0];
                    $FUNCS->cached_templates[$rs[0]['name']] = $rs[0];
                    $rec = $rs[0];
                }
            }

            // custom template params
            $custom_params = $rec['custom_params'];
            if( strlen($custom_params) ){
                $custom_params = $FUNCS->unserialize($custom_params);
            }
            if( !is_array($custom_params) ) $custom_params=array();
            foreach( $custom_params as $k=>$v ){
                $k = 'tpl_' . $k;
                $this->$k = $v;
            }
            $this->tpl_custom_params = $custom_params;

            $this->tpl_name = $rec['name'];
            $this->tpl_title = $rec['title'];
            $this->tpl_id = $rec['id'];
            $this->tpl_is_clonable = $rec['clonable'];
            $this->tpl_desc = $rec['description'];
            $this->tpl_access_level = $rec['access_level'];
            $this->tpl_is_commentable = $rec['commentable'];
            $this->tpl_is_executable = $rec['executable'];
            $this->tpl_is_hidden = $rec['hidden'];
            $this->tpl_order = $rec['k_order'];
            $this->tpl_dynamic_folders = $rec['dynamic_folders'];
            $this->tpl_nested_pages = $rec['nested_pages'];
            if( !$this->tpl_is_clonable ) $this->tpl_nested_pages = 0;
            $this->tpl_gallery = $rec['gallery'];
            if( !$this->tpl_is_clonable ) $this->tpl_gallery = 0;
            if( $this->tpl_gallery ) $this->tpl_nested_pages = 0;

            $this->tpl_handlers = array_filter( array_map("trim", explode(',', $rec['handler'])) );
            foreach( $this->tpl_handlers as $handler ){
                $handler_name = 'tpl_handler_' . $handler;
                $this->$handler_name = 1;
            }
            $this->tpl_type = $rec['type'];
            $this->tpl_parent = $rec['parent'];
            $this->tpl_icon = $rec['icon'];
            $this->tpl_has_globals = $rec['has_globals'];

            // HOOK: alter_template_info
            // At this point only the template's info is available in the page object. Can be be manipulated.
            $FUNCS->dispatch_event( 'alter_template_info', array(&$this) );
        }

        function _fill_fields_info(){
            global $DB, $FUNCS;

            if( array_key_exists( $this->tpl_id, $FUNCS->cached_fields ) && $this->id != -1 ){ // skip cache for 'new' page
                if( defined('K_PHP_4') ){
                    $this->fields = $FUNCS->cached_fields[$this->tpl_id];
                }
                else{
                    $this->fields = array();
                    foreach( $FUNCS->cached_fields[$this->tpl_id] as $k=>$v ){
                        $this->fields[$k]=clone($v);
                    }
                }

                // set the current page and siblings
                for( $x=0; $x<count($this->fields); $x++ ){
                    $f = &$this->fields[$x];
                    unset( $f->page );
                    $f->page = &$this;
                    $f->page_id = $this->id;
                    unset( $f->siblings );
                    $f->siblings = &$this->fields;
                    unset( $f->data );
                    unset( $f->orig_data );
                    unset( $f->k_inactive );

                    $f->_prep_cached();

                    unset( $f );
                }

                // make fields accessible by names
                $this->_fields = array();
                for( $x=0; $x<count($this->fields); $x++ ){
                    $this->_fields[$this->fields[$x]->name] = &$this->fields[$x];
                }
            }
            else{
                // The custom fields -
                $rs2 = $DB->select( K_TBL_FIELDS, array('*'), "template_id='" . $DB->sanitize( $this->tpl_id ). "' ORDER BY k_group, k_order, id" );

                // HOOK: alter_custom_fields_info_db
                // Array of custom fields info, as fetched from the database, can be manipulated at this point.
                $FUNCS->dispatch_event( 'alter_custom_fields_info_db', array(&$rs2, &$this) );

                for( $x=0; $x<count($rs2); $x++ ){
                    $rs2[$x]['module'] = 'pages';
                    $fieldtype = $rs2[$x]['k_type'];
                    if( $FUNCS->is_core_type($fieldtype) ){
                        $this->fields[] = new KField( $rs2[$x], $this, $this->fields );
                    }
                    else{
                        // is it a udf?
                        if( array_key_exists($fieldtype, $FUNCS->udfs) ){
                            $classname = $FUNCS->udfs[$fieldtype]['handler'];
                            $this->fields[] = new $classname( $rs2[$x], $this, $this->fields );
                        }
                        else{
                            //die("ERROR: Field has unknown type \"".$fieldtype."\"");
                            $this->fields[] = new KField( $rs2[$x], $this, $this->fields );
                        }

                    }
                }

                // HOOK: alter_custom_fields_info
                // Array of custom field objects can be manipulated at this point before being added to the page.
                $FUNCS->dispatch_event( 'alter_custom_fields_info', array(&$this->fields, &$this) );

                // The system fields -
                $sys_fields = array(
                                    'k_page_title'=>$FUNCS->t('title'),/*'Title'*/
                                    'k_page_name'=>$FUNCS->t('name'),/*'Name',*/
                                    'k_page_folder_id'=>$FUNCS->t('folder'),
                                    'k_publish_date'=>'Publish Date',
                                    'k_access_level'=>$FUNCS->t('access_level'),
                                    'k_comments_open'=>$FUNCS->t('comments'),
                                    );

                // Nested pages will require some additonal system fields
                if( $this->tpl_nested_pages ){
                    $sys_fields = array_merge( $sys_fields, array(
                                    'k_nested_parent_id'=>$FUNCS->t('parent_page'),
                                    'k_weight'=>$FUNCS->t('weight'),
                                    'k_show_in_menu'=>$FUNCS->t('show_in_menu'),
                                    'k_menu_text'=>$FUNCS->t('menu_text'),
                                    'k_is_pointer'=>$FUNCS->t('points_to_another_page'),
                                    'k_open_external'=>$FUNCS->t('separate_window'),
                                    'k_pointer_link'=>$FUNCS->t('link_url'),
                                    'k_pointer_link_detail'=>'pointer_link_detail',
                                    'k_masquerades'=>'masquerades',
                                    'k_strict_matching'=>$FUNCS->t('strict_matching'),
                                    )
                                 );
                }

                // Gallery will have the extra fields pertaining to the associated file
                if( $this->tpl_gallery ){
                    $sys_fields = array_merge( $sys_fields, array(
                                    'k_weight'=>$FUNCS->t('weight'),
                                    'k_file_name'=>'file_name',
                                    'k_file_ext'=>'file_ext',
                                    'k_file_size'=>'file_size',
                                    'k_file_meta'=>'file_meta',
                                    )
                                 );
                }

                $arr_sys_fields = array();
                foreach( $sys_fields as $k=>$v ){
                    $field_info = array(
                        'id' => -1,
                        'template_id' =>  $this->tpl_id,
                        'name' => '',
                        'label' => '',
                        'k_desc' => '',
                        'search_type' => 'text',
                        'k_type' => 'text',
                        'hidden' => ($this->tpl_is_clonable) ? '0' : '1',
                        'searchable' => '1',
                        'k_order' => '-1',
                        'data' => '',
                        'default_data' => '',
                        'required' => '1',
                        'validator' => '',
                        'system' => '1',
                        'module' => 'pages',
                    );

                    $field_info['name'] = $k;
                    $field_info['label'] = $v;

                    // tweak individual fields
                    switch( $k ){
                        case 'k_page_title':
                            $field_info['required'] = '0';
                            $field_info['maxlength'] = '255';
                            break;
                        case 'k_page_name':
                            $field_info['k_desc'] = $FUNCS->t('title_desc');
                            $field_info['validator'] = 'title_ready|unique_page'; //'KWebpage::validate_title';
                            $field_info['validator_msg'] = 'title_ready='.$FUNCS->t('user_name_restrictions');
                            $field_info['maxlength'] = '255';
                            break;
                        case 'k_weight':
                            $field_info['hidden'] = '1';
                            $field_info['required'] = '0';
                            $field_info['k_desc'] = $FUNCS->t('page_weight_desc');
                            $field_info['validator'] = 'integer';
                            $field_info['width'] = '128';
                            break;
                        case 'k_menu_text':
                            $field_info['k_desc'] = $FUNCS->t('leave_empty');
                            $field_info['maxlength'] = '255';
                        case 'k_pointer_link_detail':
                            $field_info['required'] = '0';
                            $field_info['hidden'] = '1'; //comment out to make visible foe debugging
                            break;
                        case 'k_masquerades':
                            $field_info['k_type']='radio';
                            $field_info['opt_values']=$FUNCS->t('redirects').'=0 | '.$FUNCS->t('masquerades').'=1';
                            $field_info['opt_selected']='0';
                            $field_info['hidden'] = '1';
                            break;
                        case  'k_file_name':
                        case  'k_file_ext':
                        case  'k_file_size':
                        case  'k_file_meta':
                            $field_info['required'] = '0';
                            $field_info['hidden'] = '1';
                            break;
                    }

                    // create field object
                    if( $k=='k_page_folder_id' ){
                        $field_info['required'] = '0';
                        $arr_sys_fields[] = new KPageFolderIDField( $field_info, $this, $this->fields );
                    }
                    elseif( $k=='k_publish_date' ){
                        $field_info['hidden'] = '0';
                        $arr_sys_fields[] = new KPublishDateField( $field_info, $this, $this->fields );
                    }
                    elseif( $k=='k_comments_open' ){
                        $field_info['hidden'] = '0';
                        $arr_sys_fields[] = new KCommentsOpenField( $field_info, $this, $this->fields, $FUNCS->t('allow_comments') );
                    }
                    elseif( $k=='k_access_level' ){
                        $field_info['hidden'] = '0';
                        $arr_sys_fields[] = new KAccessLevel( $field_info, $this, $this->fields );
                    }
                    elseif( $k=='k_nested_parent_id' ){
                        $field_info['required'] = '0';
                        $field_info['validator'] = 'KWebpage::validate_parent';
                        $arr_sys_fields[] = new KNestedPagesField( $field_info, $this, $this->fields );
                    }
                    elseif( $k=='k_show_in_menu' || $k=='k_is_pointer' || $k=='k_open_external' ){
                        $arr_sys_fields[] = new KSingleCheckField( $field_info, $this, $this->fields );
                    }
                    elseif( $k=='k_strict_matching' ){
                        $arr_sys_fields[] = new KSingleCheckField( $field_info, $this, $this->fields, '', 1/*inverse*/ );
                    }
                    elseif( $k=='k_pointer_link' ){
                        $field_info['required'] = '0';
                        $field_info['k_desc'] = $FUNCS->t('link_url_desc');
                        $field_info['validator'] = 'KWebpage::validate_masquerade_link';
                        $field_info['k_separator'] ='#';
                        $arr_sys_fields[] = new KLinkUrlField( $field_info, $this, $this->fields );
                    }
                    elseif( $k=='k_file_meta' ){
                        $arr_sys_fields[] = new KExif( $field_info, $this, $this->fields );
                    }
                    else{
                        $arr_sys_fields[] = new KField( $field_info, $this, $this->fields );
                    }

                }

                // HOOK: alter_system_fields_info
                // Array of system field objects can be manipulated at this point before being added to the page.
                $FUNCS->dispatch_event( 'alter_system_fields_info', array(&$arr_sys_fields, &$this) );

                // merge system fields to the head of custom fields
                $this->fields = array_merge( $arr_sys_fields, $this->fields );

                // make fields accessible by names
                for( $x=0; $x<count($this->fields); $x++ ){
                    $this->_fields[$this->fields[$x]->name] = &$this->fields[$x];
                }

                // default data (will be overwritten in _fill_page_info..needed for new pages)
                $this->_fields['k_page_folder_id']->data = -1;
                $this->_fields['k_access_level']->data = 0;
                $this->_fields['k_comments_open']->data = $this->tpl_is_commentable;
                if( $this->tpl_nested_pages ){
                    $this->_fields['k_nested_parent_id']->data = -1;
                    $this->_fields['k_weight']->data = 0;
                    $this->_fields['k_show_in_menu']->data = 1;
                    $this->_fields['k_is_pointer']->data = 0;
                    $this->_fields['k_open_external']->data = 0;
                    $this->_fields['k_masquerades']->data = 0;
                    $this->_fields['k_strict_matching']->data = 0;
                }

                // HOOK: alter_fields_info
                // All the field objects (system as well as custom) are ready and accessible by names.
                $skip_cache = 0;
                $FUNCS->dispatch_event( 'alter_fields_info', array(&$this->_fields, &$this, &$skip_cache) );

                if( !$skip_cache ){
                    $FUNCS->cached_fields[$this->tpl_id] = $this->fields;
                }
            }
        }

        function _fill_folders_info(){
            global $FUNCS;

            if( $this->tpl_nested_pages ){
                $this->folders = new KFolder( array('id'=>'-1', 'name'=>'_root_', 'pid'=>'-1'), $tpl_name, new KError()/*dummy*/ );
                return;
            }
            $this->folders = &$FUNCS->get_folders_tree( $this->tpl_id, $this->tpl_name, 'name', 'asc' );

            // HOOK: alter_folders_info
            // The entire folder tree can be manipulated at this point.
            $FUNCS->dispatch_event( 'alter_folders_info', array(&$this->folders, &$this) );
        }

        function _fill_page_info( $skip_custom_fields ){
            global $DB, $FUNCS, $AUTH, $CTX;

            if( $this->id == -1 ){ // new page
                return;
            }

            if( $this->tpl_nested_pages ){

                $tree = $FUNCS->get_nested_pages( $this->tpl_id, $this->tpl_name, $this->tpl_access_level );

                // Nested pages bring in a new set of complexities when accessed via their URLs..
                if( $this->accessed_via_browser && $CTX->ignore_context!=1 ){
                    // 1. If template being accessed is 'index.php', individual nested pages of this template could be masquerading as other templates..
                    // ..so are there any? (goes into effect only if prettyurls + curl are on)
                    if( strtolower($this->tpl_name)=='index.php' && K_MASQUERADE_ON ){
                        $rs = $DB->select( K_TBL_PAGES, array('id'), "template_id='" . $DB->sanitize( $this->tpl_id ). "' AND is_pointer='1' AND masquerades='1'" );
                        if( count($rs) ){
                            // get the canonical urls of the masquerading pages
                            $arr_masquering_pages = array();
                            $arr_link_masquering_pages = array();
                            $tpl_pretty_name = $FUNCS->get_pretty_template_link_ex( $this->tpl_name, $dummy, 0 );
                            for( $i=0; $i<count( $rs ); $i++ ){
                                $link_masquering_page = '';
                                if( count($tree->children) ) {
                                    $arr = $tree->children[0]->root->get_parents_by_id( $rs[$i]['id'] );
                                    if( is_array($arr) ){
                                        for( $x=count($arr)-1; $x>=0; $x-- ){
                                            $link_masquering_page .= $arr[$x]->name . '/';
                                        }
                                        $link_masquering_page = K_SITE_URL . $tpl_pretty_name . $link_masquering_page;
                                        $arr_link_masquering_pages[$link_masquering_page] = count($arr);
                                        $arr_masquering_pages[$link_masquering_page] = $arr[0]->name;
                                    }
                                }
                            }
                            // sort the links with the one with more segments coming on top
                            arsort( $arr_link_masquering_pages, SORT_NUMERIC );

                            // does the URL being accessed match any of the masqueradng page's urls?
                            $chopped_url_0 = $_SERVER['REQUEST_URI'];
                            if( $chopped_url_0 ){
                                foreach( $arr_link_masquering_pages as $k_url=>$v_pos ){
                                    $chopped_url_1 = @parse_url( $k_url );
                                    if( $chopped_url_1===false || !$chopped_url_1['path'] ) continue;
                                    if( strpos($chopped_url_0, $chopped_url_1['path']) === 0 ){

                                        $masquerade_this_uri = substr( $chopped_url_0, strlen($chopped_url_1['path']) );
                                        if( $masquerade_this_uri ){
                                            $chopped_url_0 = @parse_url( $masquerade_this_uri );
                                            if( $chopped_url_0!==false ){
                                                // check if remaining uri does not represent actual child pages
                                                // Only folder-view is problematic. No risk of confusion in page-view & archive-view.
                                                if( isset($_GET['fname']) ){
                                                    if( $chopped_url_0['path'] ){
                                                        $arr_probable_child_pages = explode( '/', rtrim($chopped_url_0['path'], '/') );
                                                        $count_probable_child_pages = count( $arr_probable_child_pages );
                                                        $probable_parent = $arr_masquering_pages[$k_url];
                                                        $probable_parent_obj = &$tree->find( $probable_parent );
                                                        if( $probable_parent_obj ){
                                                            $page_exists = 0;
                                                            $count_tested = 0;
                                                            foreach( $arr_probable_child_pages as $probable_child ){
                                                                unset( $probable_child_obj );
                                                                $probable_child = strtolower($probable_child);

                                                                // find if the page is a direct decendent
                                                                for( $cc=0; $cc<count($probable_parent_obj->children); $cc++ ){
                                                                    if( $probable_child == $probable_parent_obj->children[$cc]->name ){
                                                                        $probable_child_obj = &$probable_parent_obj->children[$cc];
                                                                        break;
                                                                    }
                                                                }
                                                                if( $probable_child_obj ){
                                                                    $page_exists = 1;
                                                                    unset( $probable_parent_obj );
                                                                    $probable_parent_obj = $probable_child_obj;
                                                                }
                                                                else{
                                                                    $page_exists = 0;
                                                                    break;
                                                                }
                                                                $count_tested++;
                                                            }

                                                            if( $count_tested==$count_probable_child_pages && $page_exists ){
                                                                // physical pages exist in the given hierarchy. Cannot be a masqueraded link.
                                                                unset( $masquerade_this_uri );
                                                                break;
                                                            }
                                                        }
                                                    }
                                                }

                                                // add instruction not to redirect
                                                if( $chopped_url_0['query'] ){
                                                    $arr_qs = explode('&', $chopped_url_0['query']);
                                                    $arr_qs[] = '_nr_=1';
                                                    $masquerade_this_uri = $chopped_url_0['path'] . '?' . implode( '&', $arr_qs );
                                                }
                                                else{
                                                    $masquerade_this_uri = $chopped_url_0['path'] . '?_nr_=1';
                                                }
                                            }
                                        }
                                        else{
                                            $masquerade_this_uri = '?_nr_=1';
                                        }
                                        $this->page_name = $arr_masquering_pages[$k_url];
                                        $this->id = null; //set by cms.php for comments
                                        break;
                                    }
                                }// end for each masquerading page
                            }
                        }
                    }

                    // 2. Their pretty-URL structure looks like 'folder-view' and that is how COUCH::invoke()
                    // calls this KWebpage object - with both the page_id and page_name null.
                    // For normal 'folder-view', the default page would get loaded, however now we first check if the executing template supports 'nested_pages'.
                    // If it does, then the $_GET array is checked to see if the 'fname' parameter is set (i.e. folder-view).
                    // If so, the foldername is considered to mean the pagename.
                    if( is_null($this->id) && is_null($this->page_name) && isset($_GET['fname']) && $FUNCS->is_title_clean($_GET['fname']) ){
                        $this->page_name = trim( $_GET['fname'] );
                        $this->changed_from_folder_to_page = 1;
                    }
                }
            }

            // HOOK: page_preload
            // Page data not yet fetched from the database. Info required to do so can be manipulated at this point.
            $FUNCS->dispatch_event( 'page_preload', array(&$this) );

            if( /*!$this->tpl_is_clonable ||*/ (is_null($this->id) && is_null($this->page_name)) ){
                // Either non-clonable or no id specified. Use the default-page. Create default-page if not present.
                $rs = $DB->select( K_TBL_PAGES, array('*'), "template_id='" . $DB->sanitize( $this->tpl_id ). "' AND is_master='1'" );
                if( !count($rs) ){
                    if( $AUTH->user->access_level >= K_ACCESS_LEVEL_SUPER_ADMIN ){
                        $title = 'Default page for '.$DB->sanitize( $this->tpl_name ).' * PLEASE CHANGE THIS TITLE *'; // . $this->tpl_name;
                        $name = $FUNCS->get_clean_url( $title );
                        $last_id= $this->create( $title, $name, 1 );
                        if( $FUNCS->is_error($last_id) ) return $last_id;

                        $rs = $DB->select( K_TBL_PAGES, array('*'), "template_id='" . $DB->sanitize( $this->tpl_id ). "' AND is_master='1'" );
                        if( !count($rs) ) return $FUNCS->raise_error( "Failed to insert record in K_TBL_PAGES" );
                    }
                    else{
                        //not a super-admin.
                        return $FUNCS->raise_error( "Page not found" );
                    }
                }
                $rec = (array)$rs[0];
                foreach( $rec as $k=>$v ){
                    $this->$k = $v;
                }

                // check for existence of folder, if folder-view requested (folders ignored for nested-pages)
                if( $this->accessed_via_browser && !$this->tpl_nested_pages && !is_null($CTX->folder_info) ){
                    $qfield = ( is_int($CTX->folder_info) ) ? 'id' : 'name';
                    $rs = $DB->select( K_TBL_FOLDERS, array('name'), "template_id='" . $DB->sanitize( $this->tpl_id ). "' and ".$qfield."='" . $DB->sanitize( $CTX->folder_info ). "'" );
                    if( !count($rs) ){
                        return $FUNCS->raise_error( "Page not found" );
                    }
                }
            }
            else{
                if( $this->id ){
                    $rs = $DB->select( K_TBL_PAGES, array('*'), "id='" . $DB->sanitize( $this->id ). "' AND template_id='" . $DB->sanitize( $this->tpl_id ). "'" );
                }
                else{
                    $rs = $DB->select( K_TBL_PAGES, array('*'), "page_name='" . $DB->sanitize( $this->page_name ). "' AND template_id='" . $DB->sanitize( $this->tpl_id ). "'" );
                }
                if( !count($rs) ){
                    return $FUNCS->raise_error( "Page not found" );
                }
                $rec = $rs[0];
                foreach( $rec as $k=>$v ){
                    $this->$k = $v;
                }
                if( $this->is_master ) $this->is_master=0; //the default page when accessed with pageid (page view) is not considered master.
            }
            unset( $this->template_id );

            // HOOK: alter_page_info
            // At this point we have a page selected into the object. Its info can be manipulated at this point.
            // Access can also be restricted deprnding on conditions.
            $stop = $FUNCS->dispatch_event( 'alter_page_info', array(&$this) );
            if( $stop ){
                return $FUNCS->raise_error( "Page not found" );
            }

            // If page status is unpublished - send 404 for non-admins
            if( $this->accessed_via_browser && $this->publish_date=='0000-00-00 00:00:00' && $AUTH->user->access_level < K_ACCESS_LEVEL_ADMIN ){
                if( !($this->is_master && $this->tpl_is_clonable) ){// skip default page accessed in list-views of cloned templates
                    return $FUNCS->raise_error( "Page not found" );
                }
            }

            // Fill the system fields with values fetched from database
            // e:g $this->_fields['k_page_title']->data = $this->title;
            //     $this->_fields['k_page_name']->data = $this->name;
            for( $x=0; $x<count($this->fields); $x++ ){
                $dest = &$this->fields[$x];
                if( $dest->system ){
                    $name = substr( $dest->name, 2 ); // remove the 'k_' prefix from system fields
                    $dest->store_data_from_saved( $this->$name );
                }
            }

            // Next fill the custom fields
            if( !$skip_custom_fields ){
                $this->_fill_custom_fields();
            }

            // HOOK: alter_fields_data
            // The data filled in the fields can be manipulated at this point.
            $FUNCS->dispatch_event( 'alter_fields_data', array(&$this->fields, $skip_custom_fields, &$this) );

            if( $this->tpl_nested_pages ){

                $this->nested_page_obj = &$tree->find_by_id( $this->id );
                if( strtolower($this->tpl_name)!='index.php' ){ // fix for pre 1.2.1 which allowed all templates to masquerade
                    $this->masquerades=0;
                    $this->_fields['k_masquerades']->data = 0;
                }

                // If nested-page accessed in standalone page-view, check if it is a pointer page. If it is, redirect to the location pointed by it-
                if( $this->accessed_via_browser && !$this->is_master ){
                    if( $this->is_pointer ){
                        if( isset($masquerade_this_uri) ){
                            $target_template = @$this->nested_page_obj->pointer_link_detail['masterpage'];
                            if( $target_template ){
                                $link = K_SITE_URL . $FUNCS->get_pretty_template_link_ex( $target_template, $dummy, 0 ) . $masquerade_this_uri;
                            }
                            else{
                                return $FUNCS->raise_error( "Page not found" );
                            }
                        }
                        else{
                            $link = trim( @$this->nested_page_obj->pointer_link );
                        }

                        if( $link ){
                            // Authenticate user before following link
                            $AUTH->check_access( $this->get_access_level($inherited) );

                            $link = $FUNCS->sanitize_url( $link );
                            if( $this->masquerades ){
                                $FUNCS->masquerade( $link );
                            }
                            else{
                                header("Location: " . $link, TRUE, 301 );
                                exit;
                            }
                        }
                        else{
                            return $FUNCS->raise_error( "Page not found" );
                        }
                    }
                }

                // Nested pages cannot have folder info
                $this->page_folder_id = -1;

                // If in draft mode, hide menu related field
                if( $this->parent_id ){
                    $this->_fields['k_nested_parent_id']->hidden = 1;
                    $this->_fields['k_weight']->hidden = 1;
                }

            }

            // set the containing folder (if the page resides in any)
            if( $this->page_folder_id != -1 ){
                $this->folder = &$this->folders->find_by_id( $this->page_folder_id );
            }


            // HOOK: page_loaded
            // page object is completely loaded at this point (fields, folders etc.)
            // Last minute manipulation to the object can be done at this point.
            $FUNCS->dispatch_event( 'page_loaded', array(&$this) );

        }

        function _fill_custom_fields(){
            global $DB, $FUNCS;

            $vals = $this->_get_field_values();

            // HOOK: alter_custom_fields_data
            // The data fetched from database to fill the custom fields can be manipulated at this point.
            $FUNCS->dispatch_event( 'alter_custom_fields_data', array(&$vals, &$this) );

            if( count($vals) ){
                for( $x=0; $x<count($this->fields); $x++ ){
                    $dest = &$this->fields[$x];
                    if( !$dest->system && array_key_exists($dest->id, $vals) ){
                        $dest->store_data_from_saved( $vals[$dest->id] );
                    }
                }
            }
        }

        function _get_field_values(){
            global $DB;

            $page_id = $DB->sanitize( $this->id );
            $tbls = array( K_TBL_DATA_TEXT, K_TBL_DATA_NUMERIC );
            $vals = array();
            foreach( $tbls as $tbl ){
                $sql = "SELECT field_id, value FROM ".$tbl." WHERE page_id='".$page_id."'";

                $result = @mysql_query( $sql, $DB->conn );
                if( !$result ){
                    ob_end_clean();
                    die( "Could not successfully run query: " . mysql_error( $DB->conn ) );
                }
                while( $row=mysql_fetch_row($result) ){
                    $vals[$row[0]]=$row[1];
                }
            }

            return $vals;
        }

        function get_template_name(){
            global $FUNCS;

            $tpl = $FUNCS->get_template_name();
            if( $FUNCS->is_error($tpl) ) return $tpl;

            // HOOK: alter_template_name
            $FUNCS->dispatch_event( 'alter_template_name', array(&$tpl, &$this) );

            return $tpl;
        }

        function save(){
            global $DB, $FUNCS, $AUTH, $Config;

            // ensure the person setting levels is privileged enough
            //if( $this->access_level > $AUTH->user->access_level ){
            if( $this->get_access_level($inherited) > $AUTH->user->access_level ){ //take into account access control placed on template and folders
                die( "Cheating?!" );
            }

            $DB->begin();

            $this->__args = func_get_args();

            // HOOK: page_presave
            // the save process is about to begin.
            // Field values can be adjusted before subjecting them to the save routine.
            $FUNCS->dispatch_event( 'page_presave', array(&$this) );

            // Pre-save..
            // Adjust system fields.

            // If name empty, we create it from title field if set
            $title = trim( $this->_fields['k_page_title']->get_data() );
            $name = trim( $this->_fields['k_page_name']->get_data() );

            // HOOK: lock_template
            $skip = $FUNCS->dispatch_event( 'lock_template', array(&$this) );
            if( !$skip ){
                if( $this->tpl_nested_pages || $this->_fields['k_page_name']->modified || ($name=='' && $title!='') ){
                    $this->_lock_template(); // serialize access.. lock template
                }
            }

            if( $name=='' && $title!='' ){
                $name = $FUNCS->get_clean_url( $title );
                // verify the name does not already exist
                $unique = false;
                $unique_id = 1;
                $orig_name = $name;
                while( !$unique ){
                    $rs = $DB->select( K_TBL_PAGES, array('id'), "page_name='" . $DB->sanitize( $name ). "' and NOT id=" . $DB->sanitize( $this->id ) . " and template_id='" . $DB->sanitize( $this->tpl_id ). "'" );
                    if( !count($rs) ){
                        $unique = true;
                    }
                    else{
                        $name = $orig_name . '-' . $unique_id++;
                    }
                }

                $this->_fields['k_page_name']->store_posted_changes( $name );
            }
            $this->_fields['k_page_title']->data = $title;

            // Folder ID
            $folder_id = intval( $this->_fields['k_page_folder_id']->get_data() );
            if( !$folder_id ){
                $this->_fields['k_page_folder_id']->store_posted_changes( '-1' );
            }

            // Access level
            $access_level = intval( $this->_fields['k_access_level']->get_data() );
            if( $access_level<0 ) $access_level=0;
            if( $access_level > $AUTH->user->access_level ){
                $access_level = $AUTH->user->access_level;
            }
            $this->_fields['k_access_level']->data = $access_level;

            // Weight field of nested pages..
            if( $this->tpl_nested_pages ){
                $weight = trim( $this->_fields['k_weight']->get_data() );
                if( !$weight || $this->_fields['k_nested_parent_id']->modified ){ // if new page or parent page changed

                    // Calculate a weight that will place it below the last child of its parent
                    $tree = $FUNCS->get_nested_pages( $this->tpl_id, $this->tpl_name, $this->tpl_access_level );
                    $nested_parent_id = $this->_fields['k_nested_parent_id']->data;
                    $nested_parent_page = ( $nested_parent_id != -1 ) ? $tree->find_by_id( $nested_parent_id ) : $tree;
                    if( !$nested_parent_page ) die( 'ERROR: Parent page ' . $nested_parent_id . ' not found' );
                    $this->_fields['k_weight']->store_posted_changes( count($nested_parent_page->children)+1 );

                    $refresh_tree = 1; // signal to add the new page into tree
                }

                // If pointer-page, fill details of the link
                if( $this->_fields['k_pointer_link']->modified || $this->_fields['k_pointer_link_detail']->modified ){
                    $this->_fields['k_pointer_link_detail']->store_posted_changes( $FUNCS->analyze_link($this->_fields['k_pointer_link']->get_data()) );
                    $this->_fields['k_pointer_link_detail']->modified = 1;
                    $this->_fields['k_pointer_link']->modified = 1; //mutually dependent
                }

            }

            // Weight of a gallery page. Make it the last in its folder.
            if( $this->tpl_gallery ){
                //todo
            }

            // HOOK: page_prevalidate
            // all fields are ready for validation. Do any last minute tweaking before validation begins.
            $FUNCS->dispatch_event( 'page_prevalidate', array(&$this->fields, &$this) );

            // Validate all fields before persisting changes
            $errors = 0;
            for( $x=0; $x<count($this->fields); $x++ ){
                $f = &$this->fields[$x];
                $f->page_id = $this->id;

                // HOOK: validate_field
                $skip = $FUNCS->dispatch_event( 'validate_field', array(&$f, &$errors, &$this) );
                if( $skip ) continue; // skip field if indicated

                if( !$f->validate() ) $errors++;
            }

            // HOOK: page_validate
            // can add some custom page-level validation here if required.
            $FUNCS->dispatch_event( 'page_validate', array(&$this->fields, &$errors, &$this) );

            if( $errors ){ $DB->rollback(); return $errors; }

            if( $this->id == -1 ){
                // New page. Create a record for it first.
                $last_id = $this->create( $title, $name );
                if( $FUNCS->is_error($last_id) ) die( "Failed to insert record for new page in K_TBL_PAGES" );

                $this->id = $last_id;
                $rs = $DB->select( K_TBL_PAGES, array('*'), "id='" . $DB->sanitize( $this->id ). "'" );
                if( !count($rs) ) die( "Failed to insert record for new page in K_TBL_PAGES" );
                $rec = $rs[0];
                foreach( $rec as $k=>$v ){
                    $this->$k = $v;
                }
                unset( $this->template_id );

            }

            // the process of getting data from fields for persisting into database begins
            $arr_update = array();
            $arr_custom_fields = array();
            $arr_fulltext_update = array();
            $refresh_fulltext = 0;

            // HOOK: page_save_start
            // validation completed. Save begins.
            $FUNCS->dispatch_event( 'page_save_start', array(&$arr_update, &$arr_custom_fields, &$arr_fulltext_update, &$refresh_fulltext, &$this->fields, &$this) );

            unset( $f );
            for( $x=0; $x<count($this->fields); $x++ ){
                $f = &$this->fields[$x];
                if( $f->modified ){
                    if( $f->system ){

                        // HOOK: alter_save_system_field
                        $skip = $FUNCS->dispatch_event( 'alter_save_system_field', array(&$arr_update, &$f, &$this) );
                        if( $skip ) continue;

                        $name = substr( $f->name, 2 ); // remove the 'k_' prefix from system fields
                        $prev_value = $this->$name;

                        $this->$name = $arr_update[$name] = $f->get_data_to_save();

                        // if folder changed, have to set new parents
                        if( $name=='page_folder_id' ){
                            if( $this->page_folder_id != -1 ){

                                // set the page's containing folder (if the page resides in any)
                                $this->folder = &$this->folders->find_by_id( $this->page_folder_id );
                                if( !$this->folder ) die( 'ERROR: Folder id ' . $this->page_folder_id . ' not found' );
                            }
                            else{
                                unset( $this->folder);
                            }
                        }
                        elseif( $name=='page_title' ){
                            $arr_fulltext_update['title'] = $FUNCS->strip_tags( $f->get_data() );
                        }
                        elseif( $name=='nested_parent_id' && $this->tpl_nested_pages ){
                            // The children of the original parent of this nested page will require reordering.. post save processing.
                            $reset_weights_of = $prev_value;
                        }

                        // HOOK: save_system_field
                        $FUNCS->dispatch_event( 'save_system_field', array(&$arr_update, &$f, &$this) );

                    }
                    else{

                        // HOOK: alter_save_custom_field
                        $skip = $FUNCS->dispatch_event( 'alter_save_custom_field', array(&$arr_custom_fields, &$f, &$this) );
                        if( $skip ) continue;

                        if( $f->k_type == 'image' ){
                            // Resize
                            $resized = 0;
                            $domain_prefix = $Config['k_append_url'] . $Config['UserFilesPath'] . 'image/';

                            if( extension_loaded('gd') && function_exists('gd_info') ){
                                $src = $f->get_data();
                                if( strpos($src, $domain_prefix)===0 ){ // process image only if local
                                    $src = substr( $src, strlen($domain_prefix) );
                                    if( $src ){
                                        $src = $Config['UserFilesAbsolutePath'] . 'image/' . $src;

                                        // is EXIF data required?
                                        if( $this->tpl_gallery && K_EXTRACT_EXIF_DATA && $f->name=='gg_image' ){
                                            require_once( K_COUCH_DIR.'includes/phpExifRW/exifReader.inc' );
                                            $exifreader = new phpExifReader( $src );
                                            if( !($exifreader->errno || $exifreader->errorno) ){
                                                $exifreader->ImageReadMode = 1;
                                                $exifdata = $FUNCS->filterExif( $exifreader->getImageInfo() );
                                                $resized = 1; // do not manipulate uploaded image if contains exif (GD destroys exif).
                                            }
                                        }

                                        if( !$resized ){
                                            // OK to resize now
                                            $dest = $src;
                                            $w = $f->width;
                                            $h = $f->height;
                                            $crop = $f->crop;
                                            $enforce_max = ( $crop ) ? 0 : $f->enforce_max; // make crop and enforce_max mutually exclusive
                                            $quality = $f->quality;

                                            $res = k_resize_image( $src, $dest, $w, $h, $crop, $enforce_max, $quality );
                                            if( $FUNCS->is_error($res) ){
                                                //$f->err_msg = $res->err_msg;
                                                //$errors++;
                                                // TODO: Non critical error. Will continue but have to report.
                                            }
                                            else{
                                                $resized = 1; // signal ok for creating thumbnail
                                            }
                                        }
                                    }
                                }
                            }
                            // Find any associated thumbnail fields and update thumbnails (only for local files)
                            for( $t=0; $t<count($this->fields); $t++ ){
                                $tb = &$this->fields[$t];
                                if( (!$tb->system) && $tb->k_type=='thumbnail' && $tb->assoc_field==$f->name ){
                                    $existing_thumb = null;
                                    if( strlen($tb->data) && strlen($f->data) ){
                                        $path_parts = $FUNCS->pathinfo( $tb->data );
                                        $match = preg_match("/^(.+)?-(?:\d+?)x(?:\d+?)$/i", $path_parts['filename'], $matches);
                                        if( $match ){
                                            $path_parts['dirname'] = ( $path_parts['dirname']=='.' || $path_parts['dirname']=='' ) ? '' : $path_parts['dirname'].'/';
                                            $match = $path_parts['dirname'].$matches[1].'.'.$path_parts['extension'];
                                            if( $f->data == $match ){
                                                $existing_thumb = $path_parts['basename'];
                                                if( $existing_thumb[0]==':' ) $existing_thumb = substr( $existing_thumb, 1 );
                                            }
                                        }
                                    }

                                    if( $resized ){
                                        // create thumbnail
                                        $dest = null;
                                        $w = $tb->width;
                                        $h = $tb->height;

                                        // Make provision for enforce max. Make crop & enforce_max exclusive.
                                        $enforce_max = $tb->enforce_max;
                                        $crop = ( $enforce_max ) ? 0 : 1;
                                        $quality = $tb->quality;

                                        if( !$existing_thumb ){
                                            $thumbnail = k_resize_image( $src, $dest, $w, $h, $crop, $enforce_max, $quality );
                                        }
                                        else{
                                            $thumbnail = $existing_thumb;
                                        }
                                        if( $FUNCS->is_error($thumbnail) ){
                                            //$tb->err_msg = $thumbnail->err_msg;
                                            //$errors++;
                                            // TODO: Non critical error. Will continue but have to report.
                                        }
                                        else{
                                            $tb->modified = 1;
                                            $path_parts = $FUNCS->pathinfo( $f->get_data() );
                                            $img_path = $path_parts['dirname'] . '/';
                                            $img_path = substr( $img_path, strlen($domain_prefix) );
                                            if( $img_path ) $thumbnail = $img_path . $thumbnail;
                                            $tb->data = ':' . $thumbnail; // add marker
                                            $arr_custom_fields[$tb->id]['data'] = $tb->data;
                                            $arr_custom_fields[$tb->id]['type'] = $tb->search_type;
                                            $arr_custom_fields[$tb->id]['strip_domain'] = 1;
                                            $arr_custom_fields[$tb->id]['not_searchable'] = 1;
                                        }
                                    }
                                    else{
                                        $tb->data = '';
                                        $arr_custom_fields[$tb->id]['data'] = '';
                                        $arr_custom_fields[$tb->id]['type'] = $tb->search_type;
                                    }

                                    if( isset($arr_custom_fields[$tb->id]) ){

                                        // HOOK: save_custom_field
                                        $FUNCS->dispatch_event( 'save_custom_field', array(&$arr_custom_fields, &$tb, &$this) );

                                    }
                                }
                                unset( $tb );
                            }
                            // Update meta data of gallery pages
                            if( $this->tpl_gallery && $f->name=='gg_image' ){
                                if( $resized ){
                                    clearstatcache();
                                    $path_parts = $FUNCS->pathinfo( $f->get_data() );
                                    $arr_update['file_name'] = $path_parts['basename'];
                                    $arr_update['file_ext'] = $path_parts['extension'];
                                    $arr_update['file_size'] = @filesize( $src );
                                    if( is_array($exifdata) && count($exifdata) ){
                                        $arr_update['file_meta'] = $FUNCS->serialize( $exifdata );
                                    }
                                    else{
                                        $arr_update['file_meta'] = '';
                                    }
                                }
                                else{
                                    $arr_update['file_name'] = '';
                                    $arr_update['file_ext'] = '';
                                    $arr_update['file_size'] = 0;
                                    $arr_update['file_meta'] = '';
                                }
                            }
                        }

                        if( $f->k_type != 'thumbnail' ){ // all the rest
                            if( $f->k_type=='image' || $f->k_type=='file' ){
                                $arr_custom_fields[$f->id]['data'] = $f->data; // raw data without domain info
                                $arr_custom_fields[$f->id]['strip_domain'] = 1;
                            }
                            else{
                                $arr_custom_fields[$f->id]['data'] = $f->get_data_to_save();
                                if( $f->udf ){
                                    // Intimate about the 'update' event
                                    $f->_update( $this->id );
                                }
                            }
                            $arr_custom_fields[$f->id]['type'] = $f->search_type;
                            if( $f->udf ){
                                if( !$FUNCS->udfs[$f->k_type]['searchable'] || !$f->searchable ){
                                    $arr_custom_fields[$f->id]['not_searchable'] = 1;
                                }
                                $arr_custom_fields[$f->id]['search_data'] = $f->get_search_data();
                            }
                            else{ // core types
                                if( ($f->k_type=='textarea' && $f->no_xss_check) || $f->k_type=='password' || !$f->searchable){
                                    $arr_custom_fields[$f->id]['not_searchable'] = 1; // code & password exempt ..
                                }
                            }

                            // HOOK: save_custom_field
                            $FUNCS->dispatch_event( 'save_custom_field', array(&$arr_custom_fields, &$f, &$this) );

                        }
                    }
                }
                unset( $f );
            }

            $arr_update['modification_date'] = $FUNCS->get_current_desktop_time();
            if( $last_id ){ // new page
                $rs = $DB->raw_select( 'SELECT MAX(k_order) as max FROM ' . K_TBL_PAGES );
                $arr_update['k_order'] = ( $rs[0]['max'] ) + 1;
            }

            // HOOK: alter_page_save
            $FUNCS->dispatch_event( 'alter_page_save', array(&$arr_update, &$arr_custom_fields, &$arr_fulltext_update, &$refresh_fulltext, &$this->fields, &$this) );

            // update page record
            $rs = $DB->update( K_TBL_PAGES, $arr_update, "id='" . $DB->sanitize( $this->id ). "'" );
            if( $rs==-1 ) die( "ERROR: Unable to save data in K_TBL_PAGES" );

            // update the custom fields
            if( count($arr_custom_fields) ){
                foreach( $arr_custom_fields as $k=>$v ){
                    $arr_custom_update = array('value'=>$v['data']);

                    if( $v['type']=='text' ){
                        $data_table = K_TBL_DATA_TEXT;
                        if( isset($v['search_data']) ){ // presence of this signifies it is a udf
                            $arr_custom_update['search_value'] = ( $v['not_searchable']==1 ) ? '' : $FUNCS->strip_tags( $v['search_data'] );
                        }
                        else{ // core types
                            if( $v['strip_domain'] && substr($v['data'], 0, 1)==':' ){
                                $arr_custom_update['search_value'] = ( $v['not_searchable']==1 ) ? '' : substr( $v['data'], 1 ); //..or should the entire path be stripped?
                            }
                            else{
                                $arr_custom_update['search_value'] = ( $v['not_searchable']==1 ) ? '' : $FUNCS->strip_tags( $v['data'] ); //TODO: strip shortcodes
                            }
                        }
                    }
                    else{
                        $data_table = K_TBL_DATA_NUMERIC;
                    }

                    $rs = $DB->update( $data_table, $arr_custom_update, "page_id='" . $DB->sanitize( $this->id ). "' AND field_id='" . $DB->sanitize( $k ). "'" );
                    if( $rs==-1 ) die( "ERROR: Unable to save data in K_TBL_DATA" );
                }

                $refresh_fulltext = 1;
            }

            if( $refresh_fulltext ){
                // get the consolidated text data for this page (only from 'textarea', 'richtext' and 'text' core editable regions and udfs if searchable)
                // skip fields that do not want their data to be shown in fulltext search result
                $full_text = '';
                $rs = $DB->select( K_TBL_DATA_TEXT . ' dt, ' . K_TBL_FIELDS . ' f ', array('field_id', 'f.k_type as field_type', 'search_value'), "dt.page_id='" . $DB->sanitize( $this->id ). "' AND dt.field_id=f.id" );
                if( count($rs) ){
                    foreach( $rs as $rec ){
                        if( (($rec['field_type']=='textarea' || $rec['field_type']=='richtext' || $rec['field_type']=='text') || !$FUNCS->is_core_type($rec['field_type'])) && $rec['search_value'] ){
                            $full_text .= $rec['search_value'] . ' ';
                        }
                    }
                }

                // HOOK: alter_page_save_full_text
                $FUNCS->dispatch_event( 'alter_page_save_full_text', array(&$full_text, &$this) );

                $arr_fulltext_update['content'] = $full_text;
            }

            // update modification time_stamp
            $this->modification_date = $arr_update['modification_date'];

            // update full-text MyISAM table for searching
            if( count($arr_fulltext_update) ){
                $rs = $DB->update( K_TBL_FULLTEXT, $arr_fulltext_update, "page_id='" . $DB->sanitize( $this->id ). "'" );
                if( $rs==-1 )  die( "ERROR: Unable to update data in K_TBL_FULLTEXT" );
            }

            // post save processing.. adjust weights of remaining children of the previous parent of this nested page.
            if( $reset_weights_of ){
                $this->reset_weights_of( $reset_weights_of );
            }
            elseif( $refresh_tree ){
                $FUNCS->get_nested_pages( $this->tpl_id, $this->tpl_name, $this->tpl_access_level, 'weightx', 'asc', 1 /*force refresh*/ );
            }

            // HOOK: page_saved
            $FUNCS->dispatch_event( 'page_saved', array(&$this, &$errors) );
            if( $errors ){ $DB->rollback(); return $errors; }

            $DB->commit();

            return;
        }

        function create( $title, $name, $is_master=0 ){
            global $DB, $FUNCS;

            $cur_time = $FUNCS->get_current_desktop_time();

            $arr_insert = array(
                'template_id'=>$this->tpl_id,
                'page_title'=>$title,
                'page_name'=>$name,
                'creation_date'=>$cur_time,
                'creation_IP'=>trim( $FUNCS->cleanXSS(strip_tags($_SERVER['REMOTE_ADDR'])) ),
                /* default page of gallery remains unpublished (always cloned) */
                'publish_date'=>( $this->tpl_gallery && $is_master ) ? '0000-00-00 00:00:00' : $cur_time,
                'is_master'=>$is_master
            );

            // HOOK: alter_create_insert
            $FUNCS->dispatch_event( 'alter_create_insert', array(&$arr_insert, &$this) );

            $rs = $DB->insert( K_TBL_PAGES, $arr_insert );
            if( $rs!=1 ) return $FUNCS->raise_error( "Failed to insert record in K_TBL_PAGES" );
            $page_id = $DB->last_insert_id;

            $res = $this->_create_fields( $page_id, $arr_insert['page_title'] );
            if( $FUNCS->is_error($res) ) return $res;

            return $page_id;
        }

        // Creates a new page with values cloned from current object
        // By default the page is unpublished and can be accessed only by admins.
        function create_draft(){
            global $DB, $FUNCS;

            $DB->begin();
            $cur_time = $FUNCS->get_current_desktop_time();

            $arr_insert = array(
                'template_id'=>$this->tpl_id,
                'parent_id'=>$this->id,
                'page_title'=>$this->page_title,
                'page_name'=>$this->id . '-draft-' . time(),
                'creation_date'=>$cur_time,
                'modification_date'=>$cur_time,
                'is_master'=>0,
                'page_folder_id'=>$this->page_folder_id,
                'access_level'=>K_ACCESS_LEVEL_ADMIN,
                'comments_open'=>0
            );

            // HOOK: alter_draft_insert
            $FUNCS->dispatch_event( 'alter_draft_insert', array(&$arr_insert, &$this) );

            $rs = $DB->insert( K_TBL_PAGES, $arr_insert );
            if( $rs!=1 ){ $DB->rollback();  return $FUNCS->raise_error( "Failed to insert record in K_TBL_PAGES for draft" ); }
            $page_id = $DB->last_insert_id;

            $res = $this->_create_fields( $page_id, $this->page_title, 1 );
            if( $FUNCS->is_error($res) ){ $DB->rollback();  return $res; }

            $DB->commit();
            return $page_id;
        }

        // Called from drafts to recreate deleted parent.
        function _recreate_parent(){
            global $DB, $FUNCS;

            if( !$this->parent_id )  return $FUNCS->raise_error( "Does not have a parent to recreate" );

            $DB->begin();
            $cur_time = $FUNCS->get_current_desktop_time();

            $arr_insert = array(
                'id'=>$this->parent_id,
                'template_id'=>$this->tpl_id,
                'page_title'=>$this->page_title,
                'page_name'=>'recreated_page_'.$this->parent_id,
                'creation_date'=>$cur_time,
                'publish_date'=>$cur_time,
                'is_master'=>!$this->tpl_is_clonable,
                'page_folder_id'=>$this->page_folder_id,
            );

            // HOOK: alter_recreate_parent_insert
            $FUNCS->dispatch_event( 'alter_recreate_parent_insert', array(&$arr_insert, &$this) );

            $rs = $DB->insert( K_TBL_PAGES, $arr_insert );
            if( $rs!=1 ){ $DB->rollback();  return $FUNCS->raise_error( "Failed to insert record in K_TBL_PAGES" ); }

            $res = $this->_create_fields( $this->parent_id, $this->page_title );
            if( $FUNCS->is_error($res) ){ $DB->rollback();  return $res; }

            $DB->commit();
            return 1;
        }

        // Called from drafts to update parent.
        function update_parent(){
            global $DB, $FUNCS, $Config;

            if( !$this->parent_id ) return $FUNCS->raise_error( "Does not have a parent to update" );

            // get parent
            $rs = $DB->select( K_TBL_PAGES, array('page_name'), "id='" . $DB->sanitize( $this->parent_id ). "'" );
            if( count($rs) ){
                $parent_of_draft = $rs[0]['page_name'];
            }

            // if parent of draft no longer exists, recreate one with the original ID (..probably unused now)
            if( !$parent_of_draft ){
                $res = $this->_recreate_parent();
                if( $FUNCS->is_error($res) ){
                    return $res;
                }
            }

            // update parent ..
            $_PAGE = new KWebpage( $this->tpl_id, $this->parent_id );
            if( $_PAGE->error ){
                return $FUNCS->raise_error( $_PAGE->err_msg );
            }

            for( $x=0; $x<count($_PAGE->fields); $x++ ){
                $f = &$_PAGE->fields[$x];
                if( $f->system ){
                    if( $f->name=='k_page_title' ){
                        $f->store_posted_changes( $this->fields[$x]->get_data() );
                    }
                    elseif( $f->name=='k_page_name' && !$parent_of_draft ){
                        // if recreating parent, blank out name. Will be generated by the system.
                        $f->store_posted_changes('');
                    }
                    else{
                        unset( $f );  continue;
                    }
                }
                else{
                    if( $this->tpl_gallery ){
                        // if gallery, delete the images associated with the original (if changed)
                        if( ($f->k_type=='image' && $f->name=='gg_image')||($f->k_type=='thumbnail' && $f->assoc_field=='gg_image') ){
                            $orig_img = $f->data;
                            $cur_img = $this->fields[$x]->data;
                                if( $orig_img != $cur_img ){
                                    if( $orig_img[0]==':' ){ // if local
                                    $orig_img = $Config['UserFilesAbsolutePath'] . 'image/' . substr( $orig_img, 1 );
                                    @unlink( $orig_img );
                                }
                            }
                        }
                    }

                    if( $f->k_type == 'thumbnail' || $f->k_type == 'hidden' ||
                       $f->k_type == 'message' || $f->k_type == 'group' ){
                        unset( $f );  continue;
                    }

                    if( $f->udf ){
                        // Intimate about the 'uncloning' event
                        $f->_unclone( $this->fields[$x] );
                    }
                    $f->data = $this->fields[$x]->data;
                    $f->modified = 1;
                }
                unset( $f );
            }

            // HOOK: alter_page_unclone
            $FUNCS->dispatch_event( 'alter_page_unclone', array(&$_PAGE, &$this) );

            $errors = $_PAGE->save();
            if( $errors ){
                return $FUNCS->raise_error( $_PAGE->err_msg );
            }

            // HOOK: page_uncloned
            $FUNCS->dispatch_event( 'page_uncloned', array(&$_PAGE, &$this) );

            return 1;
        }

        function _create_fields( $page_id, $title, $clone_values=0 ){
            global $DB, $FUNCS;

            // create a set of fields for page $page_id
            foreach( $this->fields as $f ){
                if( !$f->system ){

                    if( $FUNCS->is_core_type($f->k_type) ){
                        $value = ( $clone_values ) ? $f->data : '';
                    }
                    else{ //udf
                        $value = ( $clone_values ) ? $f->get_data_to_save(1) : '';

                        // inform udf of the event
                        if( $clone_values ){
                            $f->_clone( $page_id, $title );
                        }
                        else{
                            $f->_create( $page_id );
                        }
                    }
                    $arr_insert = array( 'page_id'=>$page_id, 'field_id'=>$f->id, 'value'=>$value );
                    if( $f->search_type=='text' ){
                        $data_table = K_TBL_DATA_TEXT;
                        $arr_insert['search_value'] = '';
                    }
                    else{
                        $data_table = K_TBL_DATA_NUMERIC;
                    }

                    // HOOK: alter_datafield_insert_for_newpage
                    $FUNCS->dispatch_event( 'alter_datafield_insert_for_newpage', array(&$arr_insert, &$data_table, &$f, &$this) );

                    $rs = $DB->insert( $data_table, $arr_insert );
                    if( $rs!=1 ) return $FUNCS->raise_error( "Failed to insert record in K_TBL_DATA for new page" );
                }
            }

            // fulltext search record
            $arr_fulltext_update = array();
            $arr_fulltext_update['page_id'] = $DB->sanitize( $page_id );
            $arr_fulltext_update['title'] = $FUNCS->strip_tags( $title );
            $arr_fulltext_update['content'] = '';

            // HOOK: alter_page_fulltext_insert( &$arr_fulltext_update, &$this )
            $FUNCS->dispatch_event( 'alter_page_fulltext_insert', array(&$arr_fulltext_update, &$this) );

            // following delete is necessary to remove any orphaned myisam fulltext record left over from a failed previous call to create
            $rs = $DB->delete( K_TBL_FULLTEXT, "page_id='" . $DB->sanitize( $page_id ). "'" );
            $rs = $DB->insert( K_TBL_FULLTEXT, $arr_fulltext_update );
            if( $rs!=1 ) return $FUNCS->raise_error( "Failed to insert record in K_TBL_FULLTEXT for new page" );

            // HOOK: page_inserted //is_clone
            $FUNCS->dispatch_event( 'page_inserted', array($page_id, $title, $clone_values, &$this) );

            return 1;

        }

        function delete( $draft=0 ){
            global $FUNCS, $DB, $AUTH, $Config;

            if( $this->id != -1 ){
                if( $this->get_access_level($inherited) > $AUTH->user->access_level ){
                    die( "Cheating?!" );
                }

                $DB->begin();

                // HOOK: page_predelete
                $FUNCS->dispatch_event( 'page_predelete', array(&$this) );

                // Intimate all custom fields about the impending deletion
                for( $x=0; $x<count($this->fields); $x++ ){
                    $f = &$this->fields[$x];
                    if( $f->udf ){
                        $f->_delete( $this->id );
                    }
                    unset( $f );
                }

                // remove page
                $rs = $DB->delete( K_TBL_PAGES, "id='" . $DB->sanitize( $this->id ). "'" );
                if( $rs==-1 ) die( "ERROR: Unable to delete page from K_TBL_PAGES" );

                // remove all text fields
                $rs = $DB->delete( K_TBL_DATA_TEXT, "page_id='" . $DB->sanitize( $this->id ). "'" );
                if( $rs==-1 ) die( "ERROR: Unable to delete page data from K_TBL_DATA_TEXT" );

                // remove all numeric fields
                $rs = $DB->delete( K_TBL_DATA_NUMERIC, "page_id='" . $DB->sanitize( $this->id ). "'" );
                if( $rs==-1 ) die( "ERROR: Unable to delete page data from K_TBL_DATA_NUMERIC" );

                // remove full-text search record
                $rs = $DB->delete( K_TBL_FULLTEXT, "page_id='" . $DB->sanitize( $this->id ). "'" );
                if( $rs==-1 ) die( "ERROR: Unable to delete page data from K_TBL_FULLTEXT" );

                // remove comments
                $rs = $DB->delete( K_TBL_COMMENTS, "page_id='" . $DB->sanitize( $this->id ). "'" );
                if( $rs==-1 ) die( "ERROR: Unable to delete page data from K_TBL_COMMENTS" );

                // HOOK: page_deleted
                $FUNCS->dispatch_event( 'page_deleted', array(&$this) );

                $DB->commit();

                // delete images of gallery pages
                if( $this->tpl_gallery && !$draft ){
                    for( $x=0; $x<count($this->fields); $x++ ){
                        $f = $this->fields[$x];
                        if( (!$f->system) && (($f->k_type=='image' && $f->name=='gg_image')||($f->k_type=='thumbnail' && $f->assoc_field=='gg_image')) ){
                            $src = $f->data;
                            if( $src[0]==':' ){ // if local
                                $src = $Config['UserFilesAbsolutePath'] . 'image/' . substr( $src, 1 );
                                @unlink( $src );
                            }
                        }
                    }
                }

            }
        }

        function get_access_level( &$inherited ){
            if( (!$this->tpl_is_clonable) || ($this->tpl_is_clonable && !$this->is_master) ){
                $tpl_access = $this->tpl_access_level;
                $folder_access = ($this->folder) ? $this->folder->access_level : 0;
                if( $tpl_access || $folder_access ){
                    $inherited = 1;
                    return ( $tpl_access > $folder_access ) ? $tpl_access : $folder_access;
                }
                return (int)$this->access_level;
            }
            return 0; // no 'page-level' access control for non-clonable templates and templates in list-views
        }

        // Custom field validator for nested pages
        static function validate_parent( $field ){
            global $FUNCS/*, $PAGE*/;

            $PAGE = &$field->page;
            $proposed_parent_id = trim( $field->get_data() );

            // If called from new page (id=-1) or the proposed parent is root, nothing to check
            if( $PAGE->id==-1 || $proposed_parent_id==-1 ) return;

            // Check if the proposed parent is not a child of the folder being edited
            $tree = $FUNCS->get_nested_pages( $PAGE->tpl_id, $PAGE->tpl_name, $PAGE->tpl_access_level, 'weightx', 'asc' );
            $arr_parents = $tree->get_parents_by_id( $proposed_parent_id );
            foreach( $arr_parents as $p ){
                if( $p->id==$PAGE->id ){
                    return KFuncs::raise_error( $FUNCS->t('cannot_be_own_parent') );
                }
            }
        }

        // Custom field validator for nested pages
        static function validate_masquerade_link( $field ){
            global $FUNCS, $DB/*, $PAGE*/;

            $PAGE = &$field->page;
            // Validate only if template is 'index.php' and link modified and 'masquerade' is set to on
            if( strtolower($PAGE->tpl_name)=='index.php' && ($PAGE->_fields['k_pointer_link']->modified || $PAGE->_fields['k_masquerades']->modified) && $PAGE->_fields['k_masquerades']->data ){
                // 1. link being masqueraded has to be an internal link and
                // 2. it cannot be that of 'index.php' iteself
                // 3. Template not already masqueraded
                $masquerade_link = strtolower( trim($PAGE->_fields['k_pointer_link_detail']->data) ); // pointer_link_detail
                if( !$masquerade_link ){
                    return KFuncs::raise_error( 'Cannot masquerade an external link' );
                }
                if( strpos($masquerade_link, 'masterpage=index.php&')===0 ){
                    return KFuncs::raise_error( "Cannot masquerade 'index.php' itself" );
                }
                $arr = explode( '&amp;', $masquerade_link );
                $masquerade_tpl = $arr[0];
                $rs = $DB->select( K_TBL_PAGES. " p INNER JOIN " .K_TBL_TEMPLATES." t on p.template_id = t.id", array('p.id'), "t.name='index.php' AND is_pointer='1' AND masquerades='1' AND pointer_link_detail LIKE '" . $DB->sanitize( $masquerade_tpl ) . "%' AND t.nested_pages='1' AND t.clonable='1' AND p.id<>'" . $DB->sanitize( $PAGE->id ) . "'" );
                if( count($rs) ){
                    return KFuncs::raise_error( "Already masqueraded" );
                }
            }
        }

        // Resets weights of child pages
        function reset_weights_of( $parent_page_id=-1 ){
            global $FUNCS;

            $tree = $FUNCS->get_nested_pages( $this->tpl_id, $this->tpl_name, $this->tpl_access_level, 'weightx', 'asc', 1 /*force refresh*/ );
            if( $parent_page_id != -1 ){
                $nested_parent_page = $tree->find_by_id( $parent_page_id );
            }
            else{
                $nested_parent_page = $tree;
            }

            if( $nested_parent_page && count($nested_parent_page->children) ){
                $nested_parent_page->reset_weights( $nested_parent_page->weight );
            }
        }

        function get_page_view_link(){
            global $FUNCS;

            // HOOK: get_page_view_link // return from here if true

            if( K_PRETTY_URLS ){
                if( $this->tpl_nested_pages ){// nestable pages will have a different URL structure
                    if( $this->nested_parent_id!=-1 ){
                        $link = $this->nested_page_obj->get_link();

                    }
                    else{
                        $link = $FUNCS->get_pretty_template_link( $this->tpl_name ) . $this->page_name . '/';
                    }
                }
                else{
                    if( !$this->folder ){
                        $link = $FUNCS->get_pretty_template_link( $this->tpl_name ) . $this->page_name .'.html';
                    }
                    else{
                        $link = $this->folder->get_link() . $this->page_name .'.html';
                    }
                }
            }
            else{
                $link = $this->tpl_name . '?p=' . $this->id;
            }
            return $link;
        }

        function _lock_template(){
            global $DB;

            if( !$this->_template_locked ){
                // Hack of a semaphore. To serialize access.. lock template
                $DB->update( K_TBL_TEMPLATES, array('description'=>$DB->sanitize( $this->tpl_desc )), "id='" . $DB->sanitize( $this->tpl_id ) . "'" );
                $this->_template_locked = 1;
            }

        }

        // Is called while displaying a page (home, folder, archive or single page)
        // and also from within 'pages' tag to fetch info for single pages.
        // When called from within 'pages' tag, all pages will be 'k_is_page'.
        function set_context(){
            global $CTX, $DB, $FUNCS, $AUTH;

            $vars = array();
            $vars['k_cms_version'] = K_COUCH_VERSION;
            $vars['k_cms_build'] = K_COUCH_BUILD;
            $vars['k_admin_link'] = K_ADMIN_URL;
            $vars['k_admin_page'] = K_ADMIN_PAGE;
            $vars['k_site_link'] = K_SITE_URL;
            $vars['k_admin_path'] = K_COUCH_DIR;
            $vars['k_site_path'] = K_SITE_DIR;
            foreach( $this->tpl_custom_params as $k=>$v ){
                $k = 'k_template_' . $k;
                $vars[$k] = $v;
            }
            $vars['k_template_title'] = $this->tpl_title;
            $vars['k_template_name'] = $this->tpl_name;
            $vars['k_template_id'] = $this->tpl_id;
            $vars['k_template_is_clonable'] = $this->tpl_is_clonable;
            $vars['k_template_desc'] = $this->tpl_desc;
            $vars['k_template_access_level'] = $this->tpl_access_level;
            $vars['k_template_is_commentable'] = $this->tpl_is_commentable;
            $vars['k_template_is_executable'] = $this->tpl_is_executable;
            $vars['k_template_is_hidden'] = $this->tpl_is_hidden;
            $vars['k_template_order'] = $this->tpl_order;
            $vars['k_template_nested_pages'] = $this->tpl_nested_pages;
            $vars['k_template_gallery'] = $this->tpl_gallery;
            foreach( $this->tpl_handlers as $handler ){
                $handler_name = 'k_template_handler_' . $handler;
                $vars[$handler_name] = '1';
            }
            $vars['k_template_type'] = $this->tpl_type;
            if( K_PRETTY_URLS ){
                $vars['k_template_link'] = K_SITE_URL . $FUNCS->get_pretty_template_link( $this->tpl_name );
                $vars['k_prettyurls'] = 1;
            }
            else{
                $vars['k_template_link'] = K_SITE_URL . $this->tpl_name;
                $vars['k_prettyurls'] = 0;
            }
            $vars['k_site_charset'] = K_CHARSET;
            $vars['k_email_from'] = K_EMAIL_FROM;
            $vars['k_email_to'] = K_EMAIL_TO;

            $vars['k_is_commentable'] = '0';

            if( $this->tpl_is_clonable ){
                // Every template (master page) has an associated default page which has 'is_master' set.
                // This signifies that this page will be loaded when the template is accessed directly
                // e:g 'news.php' or 'item.php'
                //
                // For non clonable templates there is no confusion- there are no child pages
                // so the only existing page (i:e the default page) stands only for the template.
                //
                // For templates that are cloned, things get a little complex-
                // The default page is made the first cloned page of the template.
                // That means that now the default page can also be accessed by page id like any other
                // cloned page.
                // That is to say that for clonable templates, the default page serves two purposes-
                // 1. it shows up when the master page is called in isolation (e:g 'news.php')
                // 2. it also shows up when the first cloned page of the template is called (e:g 'news.php?p=x')
                //
                // To differentiate between the two usages, 'k_is_page' is set in the page's context.
                // For all cloned pages, 'k_is_page' is set. This includes the default first page when it
                // is accessed like a cloned page via page id.
                // When the default page is loaded while the master page is accessed in isolation, this
                // is not set. This also happens when template is accessed in folder-view or archive-view.
                // All custom and system fields associated with the page are ignored.
                //
                $vars['k_is_list_page'] = 0;

                if( $this->is_master ){
                    $vars['k_is_list'] = 1; // k_is_template renamed to k_is_list
                    $vars['k_is_page'] = 0;

                    // is it folder view (e:g news.php?f=10)
                    if( $this->is_folder_view ){

                        if( !$this->folder_name ){
                            $folder = $this->folders->find_by_id( $this->folder_id );
                        }
                        else{
                            $folder = $this->folders->find( $this->folder_name );
                        }

                        if( $folder ){
                            $vars['k_is_folder'] = 1;
                            $vars['k_is_home'] = 0;
                            $folder->set_in_context();
                            $this->link = $folder->get_link();
                            if( !$this->folder_name ) $this->folder_name = $folder->name;
                            if( !$this->folder_id ) $this->folder_id = $folder->id;

                        }
                        else{
                            // should never reach here because we are now throwing 404 on non-existent requested folders
                            $this->is_folder_view = 0;
                            $this->folder_name = null;
                        }

                    }
                    // is it archive view (e:g news.php?d=20080514)
                    else if( $this->is_archive_view ){
                        $this->link = $FUNCS->get_archive_link($this->tpl_name, $this->year, $this->month, $this->day );
                        $vars['k_is_archive'] = 1;
                        $vars['k_is_home'] = 0;
                        $vars['k_archive_date'] = $this->archive_date;
                        $vars['k_next_archive_date'] = $this->next_archive_date;
                        $vars['k_archive_link'] = K_SITE_URL . $this->link;
                        if( $this->is_archive_day_view ){
                            $vars['k_is_day'] = 1;
                        }
                        elseif( $this->is_archive_month_view ){
                            $vars['k_is_month'] = 1;
                        }
                        else{
                            $vars['k_is_year'] = 1;
                        }
                        $vars['k_day'] = $this->day;
                        $vars['k_month'] = $this->month;
                        $vars['k_year'] = $this->year;
                    }

                    // just the master page (home for the template)
                    if( !$this->is_folder_view && !$this->is_archive_view ){
                        if( K_PRETTY_URLS ){
                            $this->link = $FUNCS->get_pretty_template_link( $this->tpl_name );
                        }
                        else{
                            $this->link = $this->tpl_name;
                        }

                        $vars['k_is_home'] = 1;

                        // home page is also the 'root' folder
                        $sql = "template_id='".$this->tpl_id."' AND page_folder_id='-1'";
                        $rs = $DB->select( K_TBL_PAGES, array('count(id) as cnt'), $sql );
                        $pagecount = $rs[0]['cnt'];
                        $vars['k_folder_pagecount'] = $pagecount;
                        if( $this->folders ){
                            $totalpagecount = $pagecount + $this->folders->consolidated_count;
                            $vars['k_folder_totalpagecount'] = $totalpagecount;
                            $vars['k_folder_immediate_children'] = $this->folders->immediate_children;
                            $vars['k_folder_totalchildren'] = $this->folders->total_children;
                        }
                        else{
                            $vars['k_folder_totalpagecount'] = $pagecount;
                            $vars['k_folder_immediate_children'] = 0;
                            $vars['k_folder_totalchildren'] = 0;
                        }
                    }
                }
                else{
                    // is page..
                    // set all system fields ('k_page_title', 'k_page_name', 'k_page_folder_id' & 'k_publish_date')
                    // and all custom fields
                    foreach( $this->fields as $f ){
                        if( $f->deleted ) continue;
                        $vars[$f->name] = $f->get_data( 1 );
                    }
                    $vars['k_publish_date'] = ''; // 'k_page_date' will be used
                    $vars['k_page_folder_id'] = ''; // 'k_page_folderid' will be used instead.

                    $this->link = $this->get_page_view_link();

                    $vars['k_is_page'] = 1;
                    $vars['k_is_list'] = 0;
                    $vars['k_page_id'] = $this->id;
                    $vars['k_access_level'] = $this->access_level;
                    $vars['k_page_date'] = $this->publish_date;
                    $vars['k_page_creation_date'] = $this->creation_date;
                    $vars['k_page_modification_date'] = $this->modification_date;
                    $vars['k_page_draft_of'] = $this->parent_id;

                    // add the pages's folder info
                    if( $this->folder ){
                        $this->folder->set_in_context(1);
                    }
                    else{
                        $vars['k_page_folderid'] = '';
                        $vars['k_page_foldername'] = '';
                        $vars['k_page_foldertitle'] = '';
                        $vars['k_page_folderdesc'] = '';
                        $vars['k_page_folderlink'] = '';
                        $vars['k_page_folderpagecount'] = '';
                        $vars['k_page_foldertotalpagecount'] = '';
                    }

                    // comments
                    $vars['k_comments_count'] = $this->comments_count;
                    if( $this->tpl_is_commentable && $this->comments_open ){
                        $vars['k_is_commentable'] = '1';
                    }

                    // if nested_page
                    if( $this->nested_page_obj ){
                        // set the neighbouring pages
                        $ballot = array( "id"=>$this->id );
                        $this->nested_page_obj->root->get_neigbours( $ballot );
                        if( $ballot['current'] ){
                            if( $ballot['prev'] ){
                                $prev_obj = $ballot['prev'];
                                $vars['k_prev_nestedpage_id'] = $prev_obj->id;
                                $vars['k_prev_nestedpage_name'] = $prev_obj->name;
                                $vars['k_prev_nestedpage_title'] = $prev_obj->title;
                                $vars['k_prev_nestedpage_link'] = ( $prev_obj->is_pointer ) ? $prev_obj->pointer_link : K_SITE_URL . $prev_obj->get_link();
                            }
                            else{
                                $vars['k_prev_nestedpage_id'] = '';
                                $vars['k_prev_nestedpage_name'] = '';
                                $vars['k_prev_nestedpage_title'] = '';
                                $vars['k_prev_nestedpage_link'] = '';
                            }

                            if( $ballot['next'] ){
                                $next_obj = $ballot['next'];
                                $vars['k_next_nestedpage_id'] = $next_obj->id;
                                $vars['k_next_nestedpage_name'] = $next_obj->name;
                                $vars['k_next_nestedpage_title'] = $next_obj->title;
                                $vars['k_next_nestedpage_link'] = ( $next_obj->is_pointer ) ? $next_obj->pointer_link : K_SITE_URL . $next_obj->get_link();
                            }
                            else{
                                $vars['k_next_nestedpage_id'] = '';
                                $vars['k_next_nestedpage_name'] = '';
                                $vars['k_next_nestedpage_title'] = '';
                                $vars['k_next_nestedpage_link'] = '';
                            }
                        }
                    }
                }

            }
            else{ // non-clonable page
                $vars['k_is_list_page'] = 1;
                $vars['k_is_list'] = 0;
                $vars['k_is_page'] = 0;

                // Set only custom fields.
                foreach( $this->fields as $f ){
                    // System fields have no meaning for such pages
                    if( $f->system || $f->deleted ) continue;
                    $vars[$f->name] = $f->get_data( 1 );
                }


                if( K_PRETTY_URLS ){
                    $this->link = $FUNCS->get_pretty_template_link( $this->tpl_name );
                }
                else{
                    $this->link = $this->tpl_name;
                }

                // comments
                $vars['k_comments_count'] = $this->comments_count;
                if( $this->tpl_is_commentable && $this->comments_open ){
                    $vars['k_is_commentable'] = '1';
                }

                $vars['k_access_level'] = $this->access_level;
                $vars['k_page_date'] = $this->publish_date;
                $vars['k_page_creation_date'] = $this->creation_date;
                $vars['k_page_modification_date'] = $this->modification_date;
                $vars['k_page_draft_of'] = $this->parent_id;
                $vars['k_page_id'] = $this->id;
            }

            // for all
            $vars['k_page_link'] = K_SITE_URL . $this->link;

            // HOOK: alter_page_set_context
            $FUNCS->dispatch_event( 'alter_page_set_context', array(&$vars, &$this) );

            $CTX->set_all( $vars );
        }
    }
