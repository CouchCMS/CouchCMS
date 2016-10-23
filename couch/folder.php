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

    class KNestable{
        var $root;
        var $children = array();
        var $immediate_children=0; //number of immediate child nodes
        var $total_children=0; // total number of child nodes
        var $total_siblings=0; // count of parent's children
        var $pos=0; //position amongst siblings
        var $cmp_field = 'name';
        var $cmp_order = 'asc';

        // dynamically calculated
        var $immediate_children_ex = 0; //number of immediate child nodes (taking into account 'level' and 'exclude')
        var $total_children_ex = 0; // total number of child nodes (taking into account 'level' and 'exclude')
        var $total_siblings_ex = 0; // count of parent's children (taking into account 'level' and 'exclude')
        var $pos_ex = 0; // position in siblings on the same level
        var $first_pos = 0; // first and last immediate children
        var $last_pos = 0;

        function __construct(){

        }

        // Free memory (useful if pages are created in a loop)
        function destroy(){
            $this->children = array();
        }

        function add_child( &$child ){
            $this->children[] = &$child;
            //$this->sort(); //kills!
        }

        function set_sort( $orderbyfield='', $order='' ){
            $order = trim( $order );
            $this->cmp_field = ($orderbyfield) ? $orderbyfield : 'name'; //TODO set valid fields
            $this->cmp_order = ($order=='asc' || $order=='desc') ? $order : 'asc';
        }

        function sort( $recursive=0 ){
            usort( $this->children, array($this, "_cmp") );

            if( $recursive ){
                $count = count($this->children);
                for( $x=0; $x<$count; $x++ ){
                    $this->children[$x]->set_sort( $this->cmp_field, $this->cmp_order );
                    $this->children[$x]->sort( $recursive );
                }
            }
        }

        function _cmp( $a, $b ){
            $field = $this->cmp_field;

            $s1 = $a->$field;
            $s2 = $b->$field;
            if( is_numeric($s1) && is_numeric($s2) ){
                $s1 = intval($s1);
                $s2 = intval($s2);
                $is_numeric = 1;
            }
            else{
                $s1 = strtolower( $s1 );
                $s2 = strtolower( $s2 );
                $is_numeric = 0;
            }

            if( $this->cmp_order == 'asc' ){
                if( $is_numeric ){
                    return $s1 - $s2;
                }
                else{
                    return strcmp( $s1, $s2 );
                }
            }
            else{
                if( $is_numeric ){
                    return $s2 - $s1;
                }
                else{
                    return strcmp( $s2, $s1 );
                }
            }

        }

        function &find( $foldername ){
            if( $this->name!='_root_' && strtolower($foldername) == strtolower($this->name) ){
                return $this;
            }

            $count = count($this->children);
            for( $x=0; $x<$count; $x++ ){
                $f = &$this->children[$x]->find( $foldername );
                if( $f ) return $f;
            }
        }

        function &find_by_id( $folder_id ){
            if( $this->name!='_root_' && $this->id == $folder_id ){
                return $this;
            }

            $count = count($this->children);
            for( $x=0; $x<$count; $x++ ){
                $f = &$this->children[$x]->find_by_id( $folder_id );
                if( $f ) return $f;
            }
        }

        function find_and_remove( $foldername ){
            if( $this->name!='_root_' && strtolower($foldername) == $this->name ){
                return 1;
            }

            $count = count($this->children);
            for( $x=0; $x<$count; $x++ ){
                $f = $this->children[$x]->find_and_remove( $foldername );
                if( $f ){
                    array_splice( $this->children, $x, 1 );
                }
            }
        }

        // callable from root of folder tree
        function &get_parents( $foldername ){
            if( $this->name!='_root_' && strtolower($foldername) == $this->name ){
                return array( &$this );
            }

            $count = count($this->children);
            for( $x=0; $x<$count; $x++ ){
                $arr = &$this->children[$x]->get_parents( $foldername );
                if( $arr ){
                    if( $this->name!='_root_' ){
                        $arr[] = &$this;
                    }
                    return $arr;
                }
            }
        }

        function &get_parents_by_id( $folder_id ){
            if( $this->name!='_root_' && $this->id == $folder_id ){
                return array( &$this );
            }

            $count = count($this->children);
            for( $x=0; $x<$count; $x++ ){
                $arr = &$this->children[$x]->get_parents_by_id( $folder_id );
                if( $arr ){
                    if( $this->name!='_root_' ){
                        $arr[] = &$this;
                    }
                    return $arr;
                }
            }
        }

        function &get_children(){
            if( !count($this->children) ){
                return array( &$this );
            }
            $arr = array();
            if( $this->name!='_root_' ){
                $arr[] = &$this;
            }

            $count = count($this->children);
            for( $x=0; $x<$count; $x++ ){
                $child_arr = &$this->children[$x]->get_children();
                if( $child_arr ){
                    $arr = array_merge( $arr, $child_arr );
                }
            }
            return $arr;
        }

        // adds up count of child 'folders' up to the parents to give total children folders beneath each folder.
        function set_children_count( $total_siblings=0, $pos=0 ){ // prev. named 'set_folders_count'
            $this->immediate_children = count($this->children);
            $this->total_children = $this->immediate_children;
            $this->total_siblings = $total_siblings;
            $this->pos = $pos; // position amongst the children of calling parant.. (should always be equal to weight now)

            // add children of child folders
            $count = count($this->children);
            if( $count ){
                for( $x=0; $x<$count; $x++ ){
                    $this->total_children += intval( $this->children[$x]->set_children_count( $count, $x ) );
                }
            }
            return $this->total_children;
        }

        // Sets children counts taking into consideration 'level' and 'exclude'.
        function set_dynamic_count( $depth, $exclude, $total_siblings=0, $pos=0, $level=0 ){ //prev. named 'get_folders_count'
            $this->total_siblings_ex = $total_siblings;
            $this->pos_ex = $pos;

            if( $depth!=0 && $level+1>$depth ) // depth 0 is unlimited
                return 0;

            $total = $this->total_children_ex = 0;
            $count = count($this->children);
            if( $count ){
                $first_child = null;
                $last_child = null;

                for( $x=0; $x<$count; $x++ ){
                    $this->children[$x]->first_pos = $this->children[$x]->last_pos = 0;

                    if( in_array($this->children[$x]->name, $exclude) ) continue;

                    if( is_null($first_child) ) $first_child = $x;
                    $last_child = $x;
                    $this->total_children_ex += intval( $this->children[$x]->set_dynamic_count( $depth, $exclude, $count, $total, $level+1 ) );
                    $total++;
                }
            }
            $this->immediate_children_ex = $total;

            // Mark the first and the last immediate child
            if( $total ){
                $this->children[$first_child]->first_pos = 1;
                $this->children[$last_child]->last_pos = 1;
            }

            $this->total_children_ex = $this->total_children_ex + $this->immediate_children_ex;
            return $this->total_children_ex;
        }

        // Always traverses the tree hierarchicaly. 'extended_info' reports back with level changes also.
        // '$exclude_if_not_in_menu', '$exclude_if_inactive' and '$paginate' apply only to nested_pages
        function visit( $callback, &$param0, &$param1, $depth, $extended_info, $exclude, $level=0, $exclude_if_not_in_menu=0, $exclude_if_inactive=0, $paginate=0, $cb_skip=null ){
            global $CTX, $FUNCS, $PAGE;
            $continue = 1;
            if( $paginate ) $extended_info = 0; // no extended info for paginated output

            if( $this->name!='_root_' ){
                if( !$paginate ){
                    $ok = 1;
                }
                else{
                    // for pagination, $param1 contains the details
                    $cur = $param1->_counter + 1;
                    $param1->_counter++;
                    if( $cur >= $param1->_from ){
                        if( $cur > $param1->_to ) {
                            return 1;
                        }
                        else{
                            $ok = 1;
                        }
                    }
                }

                if( $ok ){
                    if( !$extended_info ) $this->set_in_context();
                    // save 'order'by and 'order' before calling child tags as they (pages tag notably), can modify these values
                    $orig_cmp_field = $this->cmp_field;
                    $orig_cmp_order = $this->cmp_order;

                    $CTX->set( 'k_folder', 1 );
                    call_user_func_array( $callback, array(&$this, &$param0, &$param1) );
                    $CTX->set( 'k_folder', 0 );

                    // check if 'order'by and 'order' have changed
                    if( $this->cmp_field!=$orig_cmp_field || $this->cmp_order!=$orig_cmp_order ){
                        $this->root->set_sort( $orig_cmp_field, $orig_cmp_order );
                        $this->root->sort(1);
                    }
                }
            }

            if( $depth!=0 && $level+1>$depth ) // depth 0 is unlimited
                return;

            $level_started = 0;
            $count = count($this->children);
            if( $count ){
                $CTX->set( 'k_level', $level );

                for( $x=0; $x<$count; $x++ ){
                    if( in_array($this->children[$x]->name, $exclude) ) continue;
                    if( $exclude_if_not_in_menu ){ // will be set only from 'nested_pages' and 'menu' tags
                        if( !$this->children[$x]->show_in_menu ) continue;
                    }
                    if( $exclude_if_inactive ){
                        if( $this->children[$x]->publish_date=='0000-00-00 00:00:00' ) continue;
                    }
                    if( !is_null($cb_skip) ){
                        if( call_user_func_array($cb_skip, array(&$this->children[$x])) ) continue;
                    }

                    if( $extended_info && !$level_started ){
                        $CTX->set( 'k_level_start', 1 ); //e.g. <UL>
                        call_user_func_array( $callback, array(&$this, &$param0, &$param1) );
                        $CTX->set( 'k_level_start', 0 );
                        $level_started = 1;
                    }

                    if( $extended_info ){
                        $this->children[$x]->set_in_context();

                        // save 'order'by and 'order' before calling child tags as they (pages tag notably), can modify these values
                        $orig_cmp_field = $this->cmp_field;
                        $orig_cmp_order = $this->cmp_order;

                        $CTX->set( 'k_element_start', 1 ); //e.g. <LI>
                        call_user_func_array( $callback, array(&$this->children[$x], &$param0, &$param1) );
                        $CTX->set( 'k_element_start', 0 );

                        // check if 'order'by and 'order' have changed
                        if( $this->cmp_field!=$orig_cmp_field || $this->cmp_order!=$orig_cmp_order ){
                            $this->root->set_sort( $orig_cmp_field, $orig_cmp_order );
                            $this->root->sort(1);
                        }
                    }

                    $quit = $this->children[$x]->visit( $callback, $param0, $param1, $depth, $extended_info, $exclude, $level+1, $exclude_if_not_in_menu, $exclude_if_inactive, $paginate, $cb_skip );
                    if( $quit ){
                        return 1; // only set by 'nested_pages' with paginate
                    }
                    $CTX->set( 'k_level', $level );

                    if( $extended_info ){
                        $CTX->set( 'k_element_end', 1 ); //e.g. </LI>
                        call_user_func_array( $callback, array(&$this->children[$x], &$param0, &$param1) );
                        $CTX->set( 'k_element_end', 0 );
                    }
                }

                if( $extended_info && $level_started ){
                    $CTX->set( 'k_level_end', 1 ); //e.g. </UL>
                    call_user_func_array( $callback, array(&$this, &$param0, &$param1) );
                    $CTX->set( 'k_level_end', 0 );
                }
            }

        }

    }//end class KNestable

    class KNestable_ex extends KNestable{
        // Takes publish_date also into consideration. Used for nested pages.
        function _cmp( $a, $b ){
            $field = $this->cmp_field;

            $s1 = $a->$field . $a->creation_date;
            $s2 = $b->$field . $b->creation_date;
            if( $this->cmp_order == 'asc' ){
                return strcmp( $s1, $s2 );
            }
            else{
                return strcmp( $s2, $s1 );
            }
        }
    }

    class KFolder extends KNestable{
        var $id;
        var $pid;
        var $template_id;
        var $name;
        var $title;
        var $k_desc;
        var $image;
        var $count=0; //page count within folder
        var $weight=0;
        var $access_level;

        var $template_name;
        var $link = null;

        var $processed;
        var $consolidated_count=0; //includes all pages in child folders too

        var $fields; // for admin form

        function __construct( $row, $template_name, &$root ){
            global $FUNCS, $Config;

            foreach( $row as $k=>$v ){
               $this->$k = $v;
            }
            $this->template_name = $template_name;
            $this->root = &$root;

            if( $this->image ){
                $data = $this->image;
                if( $data{0}==':' ){ // if marker
                    $data = substr( $data, 1 );
                    $domain_prefix = $Config['k_append_url'] . $Config['UserFilesPath'] . 'image/';
                    $data = $domain_prefix . $data;
                    $this->image = $data;
                }
            }

            // HOOK: folder_loaded
            $FUNCS->dispatch_event( 'folder_loaded', array(&$this) );
        }

        function get_link(){
            global $FUNCS;

            if( is_null($this->link) ){
                if( K_PRETTY_URLS ){
                    $link = '';
                    $arr = $this->root->get_parents_by_id( $this->id );
                    if( is_array($arr) ){
                        for( $x=count($arr)-1; $x>=0; $x-- ){
                            $link .= $arr[$x]->name . '/';
                        }
                    }
                    $this->link = $FUNCS->get_pretty_template_link( $this->template_name ) . $link;
                }
                else{
                    $this->link = $this->template_name . '?f=' . $this->id;
                }
            }
            return $this->link;
        }

        // adds up the count of 'pages' from child folders to parents to give a 'consolidated count' of 'pages' beneath every folder.
        function set_count(){
            if( !count($this->children) ){
                $this->consolidated_count = $this->count;
                return $this->count;
            }
            for( $x=0; $x<count($this->children); $x++ ){
                $this->consolidated_count += intval( $this->children[$x]->set_count() );
            }
            $this->consolidated_count += $this->count;
            return $this->consolidated_count;
        }

        function process_delete(){
            global $PAGE, $DB, $FUNCS;

            if( $this->name!='_root_' && !$this->processed ){

                // HOOK: folder_predelete
                $FUNCS->dispatch_event( 'folder_predelete', array(&$this) );

                $rs = $DB->delete( K_TBL_FOLDERS, "id='" . $DB->sanitize( $this->id ). "'" );
                if( $rs==-1 ) die( "ERROR: Unable to delete field data from K_TBL_FOLDERS" );

                // At this point all sub-folders of this would already have been adjusted so only pages need to be handled.

                // The process is so dynamic, no easy way to find if the parent of this deleted folder exists so
                // change the parent folder of all affected pages to -1
                $rs2 = $DB->update( K_TBL_PAGES, array('page_folder_id'=>'-1'), "page_folder_id='" . $DB->sanitize( $this->id ). "'" );
                if( $rs2==-1 ) die( "ERROR: Unable to remove folder from pages" );

                // HOOK: folder_deleted
                $FUNCS->dispatch_event( 'folder_deleted', array(&$this) );

            }

            for( $x=0; $x<count($this->children); $x++ ){
                $this->children[$x]->process_delete();
            }
        }

        function set_in_context( $page_specific=0){
            global $CTX, $FUNCS;

            $arr_vars = array();

            if( $page_specific ){
                $arr_vars['k_page_folderid'] = $this->id;
                $arr_vars['k_page_foldername'] = $this->name;
                $arr_vars['k_page_foldertitle'] = $this->title;
                $arr_vars['k_page_folderdesc'] = $this->k_desc;
                $arr_vars['k_page_folderimage'] = $this->image;
                $arr_vars['k_page_folderlink'] = K_SITE_URL . $this->get_link();
                $arr_vars['k_page_folderpagecount'] = $this->count;
                $arr_vars['k_page_foldertotalpagecount'] = $this->consolidated_count;

                $arr_vars['k_page_folderparentid'] = $this->pid;
                $arr_vars['k_page_folderweight'] = $this->weight;
            }
            else{
                $arr_vars['k_folder_id'] = $this->id;
                $arr_vars['k_folder_name'] = $this->name;
                $arr_vars['k_folder_title'] = $this->title;
                $arr_vars['k_folder_desc'] = $this->k_desc;
                $arr_vars['k_folder_image'] = $this->image;
                $arr_vars['k_folder_link'] = K_SITE_URL . $this->get_link();
                $arr_vars['k_folder_pagecount'] = $this->count;
                $arr_vars['k_folder_totalpagecount'] = $this->consolidated_count;

                $arr_vars['k_folder_parentid'] = $this->pid;
                $arr_vars['k_folder_weight'] = $this->weight;

                $arr_vars['k_folder_immediate_children'] = $this->immediate_children;
                $arr_vars['k_folder_totalchildren'] = $this->total_children;
                $arr_vars['k_folder_totalsiblings'] = $this->total_siblings_ex;
                $arr_vars['k_folder_pos'] = $this->pos_ex; // position amongst siblings
                $arr_vars['k_first_child'] = $this->first_pos;
                $arr_vars['k_last_child'] = $this->last_pos;
            }

            $CTX->set_all( $arr_vars );

            // HOOK: alter_folder_set_context
            $FUNCS->dispatch_event( 'alter_folder_set_context', array(&$this, $page_specific) );
        }

        // used to render the admin form
        function populate_fields(){
            global $FUNCS;

            if( count($this->fields) ) return;

            $this->fields = array();
            $fields = array(
                'title'=>$FUNCS->t('title'),
                'name'=>$FUNCS->t('name'),
                'pid'=>$FUNCS->t('parent_folder'),
                'weight'=>$FUNCS->t('weight'),
                'k_desc'=>$FUNCS->t('desc'),
                'image'=>$FUNCS->t('image')
                );

            foreach( $fields as $k=>$v ){
                $field_info = array(
                    'id' => -1,
                    'name' => 'k_'.$k,
                    'label' => $v,
                    'k_desc' => '',
                    'search_type' => 'text',
                    'k_type' => 'text',
                    'hidden' => '0',
                    'data' => $this->$k,
                    'required' => '1',
                    'validator' => '',
                    'system' => '1',
                    'module' => 'folders',
                );

                if( $k=='pid' ){
                    $field_info['validator'] = 'KFolder::validate_parent';
                    $field_info['required'] = '0';
                    $this->fields[] = new KFolderIDField( $field_info, new KError()/*dummy*/, $this->fields );
                }
                else{
                    switch( $k ){
                        case 'title':
                            $field_info['maxlength'] = '255';
                            break;
                        case 'name':
                            $field_info['k_desc'] = $FUNCS->t('title_desc');
                            $field_info['validator'] = 'title_ready|KFolder::name_unique';
                            $field_info['validator_msg'] = 'title_ready='.$FUNCS->t('user_name_restrictions');
                            $field_info['maxlength'] = '255';
                            break;
                        case 'weight':
                            $field_info['k_desc'] = $FUNCS->t('weight_desc');
                            $field_info['validator'] = 'integer';
                            $field_info['required'] = '0';
                            $field_info['width'] = '128';
                            break;
                        case 'k_desc':
                            $field_info['k_type'] = 'richtext';
                            $field_info['required'] = '0';
                            $field_info['system'] = '0';
                            $field_info['height'] = '196';
                            break;
                        case 'image':
                            $field_info['k_type'] = 'image';
                            $field_info['show_preview'] = '1';
                            $field_info['required'] = '0';
                            $field_info['system'] = '0';
                            break;
                    }

                    $this->fields[] = new KField( $field_info, new KError()/*dummy*/, $this->fields );
                }
            }

            // HOOK: alter_folder_fields_info
            $FUNCS->dispatch_event( 'alter_folder_fields_info', array(&$this->fields, &$this) );

        }

        function save(){
            global $FUNCS, $DB, $PAGE;

            $DB->begin();

            // serialize access.. lock template
            $DB->update( K_TBL_TEMPLATES, array('description'=>$DB->sanitize( $PAGE->tpl_desc )), "id='" . $DB->sanitize( $PAGE->tpl_id ) . "'" );

            // HOOK: folder_presave
            // the save process is about to begin.
            // Field values can be adjusted before subjecting them to the save routine.
            $FUNCS->dispatch_event( 'folder_presave', array(&$this) );

            // Check if name needs to be auto-generated
            $title = trim( $this->fields[0]->get_data() );
            $name = trim( $this->fields[1]->get_data() );
            if( $name=='' && $title!='' ){
                $name = $FUNCS->get_clean_url( $title );
                // verify the name does not already exist
                $unique = false;
                $unique_id = 1;
                $orig_name = $name;
                while( !$unique ){
                    $rs = $DB->select( K_TBL_FOLDERS, array('id'), "name='" . $DB->sanitize( $name ). "' and NOT id='" . $DB->sanitize( $this->id ) . "' and template_id='" . $DB->sanitize( $this->template_id ). "'" );
                    if( !count($rs) ){
                        $unique = true;
                    }
                    else{
                        $name = $orig_name . '-' . $unique_id++;
                    }
                }

                $this->fields[1]->store_posted_changes( $name );
            }
            $this->fields[0]->data = $title;

            // parent folder
            $parent_id = intval( $this->fields[2]->get_data() );
            if( !$parent_id ){
                $this->fields[2]->store_posted_changes( '-1' );
            }

            // if weight field left empty, fill it with zero
            $weight = trim( $this->fields[3]->get_data() );
            if( !strlen($weight) ){
                $this->fields[3]->store_posted_changes( '0' );
            }

            // HOOK: folder_prevalidate
            // all fields are ready for validation. Do any last minute tweaking before validation begins.
            $FUNCS->dispatch_event( 'folder_prevalidate', array(&$this->fields, &$this) );

            // Finally validate all fields before persistng changes
            $errors = 0;
            for( $x=0; $x<count($this->fields); $x++ ){
                $f = &$this->fields[$x];
                if( !$f->validate() ) $errors++;
            }

            // HOOK: folder_validate
            // can add some custom validation here if required.
            $FUNCS->dispatch_event( 'folder_validate', array(&$this->fields, &$errors, &$this) );

            if( $errors ){ $DB->rollback(); return $errors; }

            $fid = $this->id;
            $fields = array(
                       'template_id'=>$PAGE->tpl_id,
                       'pid'=>$this->fields[2]->get_data(),
                       'name'=>$this->fields[1]->get_data(),
                       'title'=>$this->fields[0]->get_data(),
                       'k_desc'=>$this->fields[4]->get_data(),
                       'image'=>$this->fields[5]->data, /*raw data without domain info*/
                       'weight'=>$this->fields[3]->get_data()
                      );

            // HOOK: alter_folder_save
            $FUNCS->dispatch_event( 'alter_folder_save', array($fid, &$fields, &$this->fields, &$this) );

            if( is_null($fid) ){
                // create
                $rs = $DB->insert( K_TBL_FOLDERS, $fields );
                if( $rs==-1 ) die( "ERROR: Unable to create folder" );
                $rs = $DB->select( K_TBL_FOLDERS, array('*'), "id='" . $DB->sanitize( $DB->last_insert_id ). "'" );
                if( !count($rs) ) die( "ERROR: Failed to insert record in K_TBL_FOLDERS" );
                $this->id = $rs[0]['id'];

                $action = 'insert';
            }
            else{
                // update
                $rs = $DB->update( K_TBL_FOLDERS, $fields, "id='" . $DB->sanitize( $fid ). "'" );
                if( $rs==-1 ) die( "ERROR: Unable to save modified folder" );

                $action = 'update';
            }

            // HOOK: folder_saved
            $FUNCS->dispatch_event( 'folder_saved', array(&$this, $action, &$errors) );
            if( $errors ){ $DB->rollback(); return $errors; }

            $DB->commit();

            // Invalidate cache
            //$FUNCS->invalidate_cache();

        }// end save

        function delete(){
            global $FUNCS, $DB, $PAGE;

            if( !is_null($this->id) ){
                $parent_id = $this->pid;

                $DB->begin();

                // HOOK: folder_predelete
                $FUNCS->dispatch_event( 'folder_predelete', array(&$this) );

                $rs = $DB->delete( K_TBL_FOLDERS, "id='" . $DB->sanitize( $this->id ). "'" );
                if( $rs==-1 ) die( "ERROR: Unable to delete field data from K_TBL_FOLDERS" );

                // allocate all sub-folders to parent
                $rs = $DB->update( K_TBL_FOLDERS, array('pid'=>$parent_id), "pid='" . $DB->sanitize( $this->id ). "'" );
                if( $rs==-1 ) die( "ERROR: Unable to move sub-folders to parent folder" );

                // allocate all pages to parent. This is different from process_delete that gets
                // invoked for static folders where deleting a folder moves all pages to -1.
                $rs = $DB->update( K_TBL_PAGES, array('page_folder_id'=>$parent_id), "page_folder_id='" . $DB->sanitize( $this->id ). "'" );
                if( $rs==-1 ) die( "ERROR: Unable to move pages to parent folder" );

                // HOOK: folder_deleted
                $FUNCS->dispatch_event( 'folder_deleted', array(&$this) );

                $DB->commit();
                $this->id = null;

                // Invalidate cache
                $FUNCS->invalidate_cache();
            }
        }

        // Custom field validators
        function validate_parent( $field ){
            global $FUNCS, $PAGE;

            $proposed_parent_id = trim( $field->get_data() );

            // If called from new folder (has no folder_id) or the proposed parent is root folder, nothing to check
            if( is_null($PAGE->folder_id) || $proposed_parent_id==-1 ) return;

            // Check if the proposed parent is not a child of the folder being edited
            $arr_parents = $PAGE->folders->get_parents_by_id( $proposed_parent_id );
            foreach( $arr_parents as $p ){
                if( $p->id==$PAGE->folder_id ){
                    return KFuncs::raise_error( $FUNCS->t('cannot_be_own_parent') );
                }
            }
        }

        function name_unique( $field ){
            global $FUNCS, $DB, $PAGE;

            $rs = $DB->select( K_TBL_FOLDERS, array('id'), "name='" . $DB->sanitize( trim($field->get_data()) ). "' and NOT id='" . $DB->sanitize( $PAGE->folder_id ) . "' and template_id='" . $DB->sanitize( $PAGE->tpl_id ). "'" );
            if( count($rs) ){
                return KFuncs::raise_error( $FUNCS->t('name_already_exists') );
            }
        }

        // callback function to create folders dropdown
        static function _k_visitor( &$folder, &$html, &$node ){
            global $CTX, $FUNCS;

            $level = $CTX->get('k_level', 1);
            $f_id = $folder->id;
            $f_name = $folder->name;
            $f_title = $folder->title;
            $f_selected = ($node==$f_id) ? ' SELECTED=1 ' : '';

            for( $x=0; $x<$level; $x++ ){
                $pad .= '- &nbsp;&nbsp;&nbsp;';
                $len_pad += 3;
            }
            $html .= '<option value="'. $f_id .'" '.$f_selected.'>';

            $avail = 30;
            if( $len_pad+$FUNCS->strlen($f_title) > $avail ){
                $func = function_exists('mb_substr') ? 'mb_substr' : 'substr';
                $abbr_title = ( ($len_pad<$avail) ? $func($f_title, 0, $avail-$len_pad) : $func($pad, 0, $len_pad-$avail) ). '&hellip;';
            }
            else{
                $abbr_title = $f_title;
            }
            $html .= $pad . $abbr_title;

            $html .= '</option>';
        }

    } //end class KFolder

    class KNestedPage extends KNestable_ex{
        var $id;
        var $title; /*$page_title;*/
        var $name; /*$page_name;*/
        var $creation_date; // for ordering
        var $publish_date; // active/inactive
        var $access_level;
        var $comments_count;
        var $pid; /*$nested_parent_id;*/
        var $weight;
        var $show_in_menu;
        var $menu_text;
        var $is_pointer;
        var $pointer_link;
        var $pointer_link_detail;
        var $is_internal_link = 0;
        var $open_external;
        var $masquerades;
        var $strict_matching;
        var $drafts_count;

        var $template_id;
        var $template_name;
        var $template_access_level;
        var $link = null;

        // transient values that will be calculated at each call to tags 'nested_pages' and 'menu'
        var $is_current = 0;
        var $most_current = 0;

        function __construct( $row, $template_name, &$root ){
            global $FUNCS, $Config;

            foreach( $row as $k=>$v ){
               $this->$k = $v;
            }

            // if pointer_page..
            if( $this->is_pointer ){

                // add domain info to internal links
                $data = $this->pointer_link;
                if( $data{0}==':' ){ // if marker, it is an internal link
                    $this->is_internal_link = 1;

                    $data = substr( $data, 1 );
                    $data = K_SITE_URL . $data;
                    $this->pointer_link = $data;

                    // add details of the page being pointed to
                    $tmp = array();
                    $arr_details = explode( '&amp;', $this->pointer_link_detail );
                    foreach( $arr_details as $detail ){
                        $detail_parts = explode( '=', $detail );
                        $tmp[$detail_parts[0]] = $detail_parts[1];
                    }
                    $this->pointer_link_detail = $tmp;
                }

                // if template not 'index.php', turn off masquerading
                if( $this->template_name != 'index.php' ) $this->masquerades = 0;
            }
            $this->weightx = $this->int_to_key( $this->weight ); //string representation of weight for sorting

            $this->template_name = $template_name;
            $this->root = &$root;
        }

        function get_link(){
            global $FUNCS;

            if( is_null($this->link) ){
                if( K_PRETTY_URLS ){
                    $link = '';
                    $arr = $this->root->get_parents_by_id( $this->id );
                    if( is_array($arr) ){
                        for( $x=count($arr)-1; $x>=0; $x-- ){
                            $link .= $arr[$x]->name . '/';
                        }
                    }
                    $this->link = $FUNCS->get_pretty_template_link( $this->template_name ) . $link;
                }
                else{
                    $this->link = $this->template_name . '?p=' . $this->id;
                }
            }
            return $this->link;
        }

        function set_in_context(){
            global $CTX;

            $arr_vars = array();
            $arr_vars['k_nestedpage_id'] = $this->id;
            $arr_vars['k_nestedpage_name'] = $this->name;
            $arr_vars['k_nestedpage_title'] = $this->title;
            $is_active = ( $this->publish_date=='0000-00-00 00:00:00' ) ? 0 : 1;
            $arr_vars['k_nestedpage_is_active'] = $is_active;
            $arr_vars['k_nestedpage_comments_count'] = $this->comments_count;
            $arr_vars['k_nestedpage_parent_id'] = $this->pid;
            $arr_vars['k_nestedpage_weight'] = $this->weight;
            $arr_vars['k_show_in_menu'] = $this->show_in_menu;
            $arr_vars['k_menu_text'] = $this->menu_text;
            $arr_vars['k_is_pointer'] = $this->is_pointer;
            $arr_vars['k_pointer_link'] = $this->pointer_link;
            $arr_vars['k_open_external'] = $this->open_external;
            $arr_vars['k_masquerades'] = $this->masquerades;
            if( defined('K_ADMIN') && $this->masquerades ){
                $arr_vars['k_masqueraded_template'] = $this->pointer_link_detail['masterpage'];
                $arr_vars['k_masqueraded_links'] = $this->get_admin_link();
            }

            // Dynamically calculated
            $arr_vars['k_is_active'] = $this->is_current;
            $arr_vars['k_is_current'] = $this->most_current;
            $arr_vars['k_immediate_children'] = $this->immediate_children_ex;
            $arr_vars['k_total_children'] = $this->total_children_ex;
            $arr_vars['k_first_child'] = $this->first_pos;
            $arr_vars['k_last_child'] = $this->last_pos;
            $arr_vars['k_total_siblings'] = $this->total_siblings_ex;
            $arr_vars['k_pos'] = $this->pos_ex;

            $arr_vars['k_nestedpage_link'] = K_SITE_URL . $this->get_link();
            $arr_vars['k_menu_link'] = ( $this->is_pointer && !$this->masquerades ) ? $this->pointer_link : $arr_vars['k_nestedpage_link'];
            $title = trim( $this->menu_text );
            if( !$title ) $title = $this->title;
            $arr_vars['k_menu_title'] = $title;

            $CTX->set_all( $arr_vars );

        }

        static function int_to_key( $k ){
          $k = $k ^ 0x80000000;
          $n = pack( "N", $k );
          return sprintf( "%s", $n );
        }

        // Used to recalculate weights of nested pages .. call from root.
        function reset_weights( $weight=0 ){
            global $DB;
            if( $this->name!='_root_' ){
                // update database
                $rs = $DB->update( K_TBL_PAGES, array('weight'=>$weight), "id='" . $DB->sanitize( $this->id ). "'" );
                if( $rs==-1 ) die( "ERROR: Tag: '.$node->name.' Unable to save modified template attribute" );
            }
            for( $x=0; $x<count($this->children); $x++ ){
                $this->children[$x]->reset_weights( $x+1 );
            }
            return;
        }

        // Called from 'nested_pages' and 'menu' tags to mark 'current items' trail
        // Also creates the crumbs leading to the $PAGE being visited.
        // Note: Normal pages without containing folder and archives do not have breadcrumb trail.
        function mark_current( $exclude_if_not_in_menu=1 ){
            global $PAGE, $FUNCS;

            if( $this->name=='_root_' ){
                if( !is_null($this->crumbs) && $this->exclude_if_not_in_menu==$exclude_if_not_in_menu ){
                    return; // trail already marked for this tree.
                }
                else{
                    $this->crumbs = array();
                    $this->exclude_if_not_in_menu = $exclude_if_not_in_menu;
                }
            }

            $is_current = 0;
            if( count($this->children) ){
                $count = count($this->children);
                for( $x=0; $x<$count; $x++ ){
                    if( $this->children[$x]->publish_date=='0000-00-00 00:00:00' ) continue;
                    if( $exclude_if_not_in_menu ){
                        if( !$this->children[$x]->show_in_menu ) continue;
                    }

                    $is_current = $this->children[$x]->mark_current( $exclude_if_not_in_menu );
                    if( $is_current ){
                        if( $this->name!='_root_' ){
                            if( is_array($is_current) ){
                                array_unshift( $is_current, $this ); // prepend self to the array of selected children returned
                            }
                        }
                        break; // if any of the children is current, parent should also be marked as current
                    }
                }
            }

            if( !$is_current && $this->name!='_root_' ){
                // none of the children is current. Check self.
                if( $is_current = $this->is_current() ){
                    $this->most_current = 1; // closest current item to the page being displayed
                }
            }

            $this->is_current = ( $is_current ) ? 1 : 0;
            if( $this->name=='_root_' && $is_current ){
                $this->crumbs = $is_current;
            }

            return $is_current;
        }

        function is_current(){
            global $PAGE, $FUNCS;

            $is_current = 0;
            if( $this->is_internal_link ){ // is_pointer and points to an internal page
                if( $this->pointer_link_detail['masterpage']==$PAGE->tpl_name ){

                    if( $this->pointer_link_detail['is_home'] ){ // home_view (non-clonable templates will always be in this view)
                        $this->pointer_link = ( K_PRETTY_URLS ) ? K_SITE_URL . $FUNCS->get_pretty_template_link( $PAGE->tpl_name ) : K_SITE_URL . $PAGE->tpl_name;

                        // home_view covers all views of the template only if not strict_matching
                        if( $this->strict_matching ){
                            if( $PAGE->is_master && !$PAGE->is_folder_view && !$PAGE->is_archive_view ){ // home-view
                                $is_current = array( $this );
                            }
                        }
                        else{
                            // crumbs.. first item is self
                            $is_current = array( $this );

                            // next if nested-pages or normal template in folder-view or page-view with containing folder, add the descendants to crumb trail
                            if( $PAGE->is_folder_view || ($PAGE->is_master==0 && $PAGE->page_folder_id!=-1) || ($PAGE->tpl_nested_pages && $PAGE->is_master==0) ){
                                if( $PAGE->tpl_nested_pages ){
                                    $folders = &$PAGE->nested_page_obj->root;
                                    $child_id = $PAGE->id;
                                }
                                else{
                                    $folders = &$PAGE->folders;
                                    $child_id = ( $PAGE->is_folder_view ) ? $PAGE->folder_id : $PAGE->page_folder_id;
                                }

                                if( $folders ){
                                    $c = $folders->get_parents_by_id( $child_id );
                                    if( is_array($c) ){
                                        for( $x=count($c)-1; $x>=0; $x-- ){ // followed by the descendents
                                            $is_current[] = $c[$x];
                                        }
                                    }
                                }
                            }
                        }

                    }
                    elseif( $this->pointer_link_detail['is_folder'] || ($this->pointer_link_detail['is_page'] && $PAGE->tpl_nested_pages) ){ // Handles folder_view (includes nested pages with prettyurl) or non-prettyurl nested pages
                        // Current are -
                        // 1. this very folder or any child folder
                        // 2. page belonging to this folder or to any child folder
                        // 3. for nested pages, this very page or any child page
                        if( $PAGE->is_folder_view || ($PAGE->is_master==0 && $PAGE->page_folder_id!=-1) || ($PAGE->tpl_nested_pages && $PAGE->is_master==0) ){//only last 'OR' applies to nested pages
                            // above params are - 1. non-nested folder-view, 2. non-nested page-view with a containing folder, 3. nested page-view

                            if( $PAGE->tpl_nested_pages ){ // nested pages are also marked as being folders by prettyurls
                                $folders = &$PAGE->nested_page_obj->root;
                            }
                            else{
                                $folders = &$PAGE->folders;
                            }

                            if( $folders ){
                                // find parent folder (the one in pointer link)
                                if( $this->pointer_link_detail['fname'] ){
                                    $p = &$folders->find( $this->pointer_link_detail['fname'] );
                                }
                                elseif( $this->pointer_link_detail['f'] ){
                                    $p = &$folders->find_by_id( $this->pointer_link_detail['f'] );
                                }
                                elseif( $this->pointer_link_detail['p'] ){
                                    $p = &$folders->find_by_id( $this->pointer_link_detail['p'] ); // nested pages without prettyurls
                                }

                                // check if the folder being viewed (if folder_view)
                                // or the containing folder of the page being viewed (if page_view) is a child of the parent folder above.
                                if( $p ){
                                    if( $PAGE->tpl_nested_pages ){
                                        $child_id = $PAGE->id;
                                    }
                                    else{
                                        $child_id = ( $PAGE->is_folder_view ) ? $PAGE->folder_id : $PAGE->page_folder_id;
                                    }

                                    // if self?
                                    if( $p->id == $child_id ){
                                        $this->pointer_link = K_SITE_URL . $p->get_link();
                                        $is_current = array( $this );
                                    }
                                    elseif( count($p->children) && !$this->strict_matching ){
                                        // find child
                                        $c = $p->get_parents_by_id( $child_id );
                                        if( is_array($c) ){
                                            $this->pointer_link = K_SITE_URL . $p->get_link();
                                            $is_current = array( $this ); // first item is self

                                            for( $x=count($c)-2; $x>=0; $x-- ){ // followed by the descendents
                                                $is_current[] = $c[$x];
                                            }

                                            // if page being viewed is in page-view, add the page object to trail too
                                            /*if( $PAGE->page_folder_id!=-1 ){
                                                $is_current[] = $PAGE;
                                            }*/
                                        }
                                    }
                                }
                            }
                        }
                    }
                    elseif( $this->pointer_link_detail['is_page'] ){ //page-view (non-nested)
                        if( $PAGE->is_master==0 ){ // page-view
                            if( $this->pointer_link_detail['pname'] ){
                                if( $this->pointer_link_detail['pname']==$PAGE->page_name ){
                                    $this->pointer_link = K_SITE_URL . $PAGE->link;
                                    $is_current=array( $this );
                                }
                            }
                            elseif( $this->pointer_link_detail['p'] ){
                                if( $this->pointer_link_detail['p']==$PAGE->id ){
                                    $this->pointer_link = K_SITE_URL . $PAGE->link;
                                    $is_current=array( $this );
                                }
                            }
                        }
                    }
                    elseif( $this->pointer_link_detail['is_archive'] ){ //archive-view
                        if( $PAGE->is_archive_view ){
                            if( !$this->strict_matching ){
                                if( $this->pointer_link_detail['yy']==$PAGE->year ){
                                    $is_current=1;
                                    if( $this->pointer_link_detail['mm'] ){
                                        if( !$PAGE->month || $PAGE->month!=$this->pointer_link_detail['mm'] ){
                                            $is_current=0;
                                        }
                                        if( $this->pointer_link_detail['dd'] ){
                                            if( !$PAGE->day || $PAGE->day!=$this->pointer_link_detail['dd'] ){
                                                $is_current=0;
                                            }
                                        }
                                    }
                                }
                            }
                            else{
                                if( $this->pointer_link_detail['yy']==$PAGE->year && $this->pointer_link_detail['mm']==$PAGE->month && $this->pointer_link_detail['dd']==$PAGE->day ){
                                    $is_current=1;
                                }
                            }

                            if( $is_current ){
                                $is_current=array( $this );
                            }
                        }
                    }
                }
            }
            else{
                // plain nested pages.
                if( $PAGE->tpl_nested_pages && $PAGE->tpl_id==$this->template_id && $PAGE->is_master==0 ){
                    if( $PAGE->id==$this->id ){ // same page as this
                        $is_current = array( $this );
                    }
                }
            }

            return $is_current;
        }

        // Sets children counts taking into consideration 'show_in_menu', 'level' and 'exclude'.
        // Called from 'nested_pages' and 'menu' tags at each of their invocation.
        function set_dynamic_count( $depth, $exclude, $exclude_if_not_in_menu=1, $ignore_active_status=0, $total_siblings=0, $pos=0, $level=0 ){
            $this->total_siblings_ex = $total_siblings;
            $this->pos_ex = $pos;

            if( $depth!=0 && $level+1>$depth ) // depth 0 is unlimited
                return 0;

            $total = $this->total_children_ex = 0;
            if( count($this->children) ){
                $first_child = null;
                $last_child = null;
                $count = count($this->children);
                for( $x=0; $x<$count; $x++ ){
                    $this->children[$x]->first_pos = $this->children[$x]->last_pos = 0;

                    if( in_array($this->children[$x]->name, $exclude) ) continue;
                    if( !$ignore_active_status ){
                        if( $this->children[$x]->publish_date=='0000-00-00 00:00:00' ) continue;
                    }
                    if( $exclude_if_not_in_menu ){
                        if( !$this->children[$x]->show_in_menu ) continue;
                    }

                    if( is_null($first_child) ) $first_child = $x;
                    $last_child = $x;
                    $this->total_children_ex += intval( $this->children[$x]->set_dynamic_count( $depth, $exclude, $exclude_if_not_in_menu, $ignore_active_status, $count, $total, $level+1 ) );
                    $total++;
                }
            }
            $this->immediate_children_ex = $total;
            // Mark the first and the last immediate child
            if( $total ){
                $this->children[$first_child]->first_pos = 1;
                $this->children[$last_child]->last_pos = 1;
            }

            $this->total_children_ex = $this->total_children_ex + $this->immediate_children_ex;
            return $this->total_children_ex;
        }

        // Gets the adjacent nested pages to a given page
        function get_neigbours( &$ballot ){
            if( $this->name!='_root_' ){
                if( $this->id==$ballot['id'] ){
                    $ballot['current'] = $this;
                }
                elseif( $ballot['current'] ){
                    // Current object has been found. $this is the next neighbour
                    if( !($this->is_pointer && !$this->pointer_link) ){
                        $ballot['next'] = $this;
                        return 1;
                    }
                }
                else{
                    if( !($this->is_pointer && !$this->pointer_link) ){
                        $ballot['prev'] = $this;
                    }
                }
            }
            else{
                $ballot['prev'] = '';
                $ballot['current'] = '';
                $ballot['next'] = '';
            }

            $count = count($this->children);
            for( $x=0; $x<$count; $x++ ){
                if( $this->children[$x]->publish_date=='0000-00-00 00:00:00' ) continue;

                $finished = $this->children[$x]->get_neigbours( $ballot );
                if( $finished ) return 1;
            }

            return 0;
        }

        // Returns an admin link to the object pointed to by the link
        function get_admin_link(){
            global $DB, $FUNCS;

            if( !$this->is_internal_link ) return;
            extract( $this->pointer_link_detail );
            if( !$masterpage ) return;
            $rs = $DB->select( K_TBL_TEMPLATES, array('id, name, clonable, nested_pages'), "name='" . $DB->sanitize( $masterpage ). "'" );
            if( !count($rs) ) return;

            if( !$rs[0]['clonable'] ){
                $str_link = K_ADMIN_URL . K_ADMIN_PAGE.'?o='.$rs[0]['name'].'&q=edit/'.$FUNCS->create_nonce( 'edit_page_'.$rs[0]['id'] ).'/';

                return '<a href="'. $str_link .'" class="nested-link">'. $FUNCS->t('edit') .'</a>';
            }
            else{
                // correct anomaly for nested_pages where folder is actually a page in prettyurl
                if( $rs[0]['nested_pages'] && $fname ){
                    $is_folder = 0;
                    $is_page = 1;
                    $pname = $fname;
                }

                // get links
                if( ($this->template_id==$rs[0]['id']) && ($is_home || $is_archive || $is_folder) ) return; // no edit links for self template

                if( $is_home || $is_archive ){
                    // links to list & add
                    $link_list = K_ADMIN_URL . K_ADMIN_PAGE . '?o='.$rs[0]['name'].'&q=list';
                    $link_add_new = K_ADMIN_URL . K_ADMIN_PAGE.'?o='.$rs[0]['name'].'&q=create/'.$FUNCS->create_nonce( 'create_page_'.$rs[0]['id'] );

                    $str_links = '<a href="'. $link_list .'" class="nested-link">'. $FUNCS->t('list') .'</a>';
                    $str_links .= '<span class="nested-sep"></span><a href="'. $link_add_new .' "class="nested-link">'. $FUNCS->t('add_new') .'</a>';
                    return $str_links;
                }
                elseif( $is_folder ){
                    // Check if folder exists
                    $sql = ( $fname ) ? "name='" .$DB->sanitize($fname). "'" : "id='" .$DB->sanitize($f). "'";
                    $rs2 = $DB->select( K_TBL_FOLDERS, array('id'), "template_id='" . $DB->sanitize( $rs[0]['id'] ). "' AND " . $sql );
                    if( !count($rs2) ) return;

                    // links to list & add pages to folder
                    $link_list = K_ADMIN_URL . K_ADMIN_PAGE . '?o='.$rs[0]['name'].'&q=list&fid='.$rs2[0]['id'];
                    $link_add_new = K_ADMIN_URL . K_ADMIN_PAGE.'?o='.$rs[0]['name'].'&q=create/'.$FUNCS->create_nonce( 'create_page_'.$rs[0]['id'] ).'&fid='.$rs2[0]['id'];

                    $str_links = '<a href="'. $link_list .'" class="nested-link">'. $FUNCS->t('list') .'</a>';
                    $str_links .= '<span class="nested-sep"></span><a href="'. $link_add_new .'" class="nested-link">'. $FUNCS->t('add_new') .'</a>';
                    return $str_links;

                }
                elseif( $is_page ){
                    // Check to see if page exists
                    $sql = ( $pname ) ? "page_name='" .$DB->sanitize($pname). "'" : "id='" .$DB->sanitize($p). "'";
                    $rs2 = $DB->select( K_TBL_PAGES, array('id'), "template_id='" . $DB->sanitize( $rs[0]['id'] ). "' AND " . $sql );
                    if( !count($rs2) ) return;

                    // link to edit page
                    $str_link = K_ADMIN_URL . K_ADMIN_PAGE . '?o='.$rs[0]['name'].'&q=edit/'.$FUNCS->create_nonce( 'edit_page_'. $rs2[0]['id'] ).'/'.$rs2[0]['id'];
                    return '<a href="'. $str_link .'" class="nested-link">'. $FUNCS->t('edit') .'</a>';
                }

            }

        }

        // callback function to create nested-pages dropdown
        static function _k_visitor_pages( &$page, &$html, &$node ){
            global $CTX, $FUNCS;

            $level = $CTX->get('k_level', 1);
            $p_id = $page->id;
            $p_title = $page->title;
            $p_selected = ($node==$p_id) ? ' SELECTED=1 ' : '';

            for( $x=0; $x<$level; $x++ ){
                $pad .= '- &nbsp;&nbsp;&nbsp;';
                $len_pad += 3;
            }
            $html .= '<option value="'. $p_id .'" '.$p_selected.'>';
            $avail = 100;
            if( $len_pad+$FUNCS->strlen($p_title) > $avail ){
                $func = function_exists('mb_substr') ? 'mb_substr' : 'substr';
                $abbr_title = ( ($len_pad<$avail) ? $func($p_title, 0, $avail-$len_pad) : $func($pad, 0, $len_pad-$avail) ). '&hellip;';
            }
            else{
                $abbr_title = $p_title;
            }
            $html .= $pad . $abbr_title;
            $html .= '</option>';
        }

    } //end class KNestedPage

    class KAdminMenuItem extends KNestedPage{

        var $parent = null;
        var $is_header = 0;
        var $icon = null;
        var $href = null;           // set if menu-item leads to an external link
        var $access_callback = null;
        var $access_callback_params = null;
        var $is_current_callback = null;
        var $desc = '';
        var $onclick = array();

        /* following attributes primarily added for 'actions' (e.g. toolbar/batch/filter/extended)  but may be useful for menu too */
        var $type;
        var $class;
        var $target;
        var $confirmation_msg;
        var $no_wrapper;
        var $is_custom;
        var $html;
        var $render;

        function __construct( $row, &$root ){
            global $FUNCS;

            foreach( $row as $k=>$v ){
               $this->$k = $v;
            }

            $this->weight = $this->int_to_key( $this->weight ).$this->int_to_key( $this->id );
            $this->root = &$root;

            if( !strlen($this->href) ) $this->href = '#';

            // is route attached?
            if( is_array($this->route) && count($this->route) ){
                $this->route['masterpage'] = trim( $this->route['masterpage'] );
                $this->route['name'] = trim( $this->route['name'] );
                if( !is_array($this->route['params']) )$this->route['params'] = array();

                if( strlen($this->route['masterpage']) && strlen($this->route['name']) ){
                    $this->href = $FUNCS->generate_route( $this->route['masterpage'], $this->route['name'], $this->route['params'] );
                    $this->is_internal_link = 1;
                }
            }
        }

        function get_link(){
            return $this->href;
        }

        function set_in_context(){
            global $CTX;

            $label = $this->type;
            $arr_vars = array();

            $arr_vars['k_'.$label.'_id'] = $this->id;
            $arr_vars['k_'.$label.'_name'] = $this->name;
            $arr_vars['k_'.$label.'_title'] = $arr_vars['k_'.$label.'_text'] = $this->title;
            $arr_vars['k_'.$label.'_desc'] = $this->desc;
            $arr_vars['k_'.$label.'_is_header'] = $this->is_header;
            $arr_vars['k_'.$label.'_onclick'] = implode( ' ', array_filter(array_map("trim", $this->onclick)) );
            $arr_vars['k_'.$label.'_link'] = $this->get_link();
            $arr_vars['k_'.$label.'_open_external'] = ( $this->is_internal_link )? '0' : '1';
            $arr_vars['k_'.$label.'_icon'] = $this->icon;
            $arr_vars['k_'.$label.'_class'] = $this->class;
            $arr_vars['k_'.$label.'_target'] = $this->target;
            $arr_vars['k_'.$label.'_confirmation_msg'] = $this->confirmation_msg;
            $arr_vars['k_'.$label.'_no_wrapper'] = $this->no_wrapper;
            $arr_vars['k_'.$label.'_is_custom'] = $this->is_custom;
            $arr_vars['k_'.$label.'_html'] = $this->html;
            $arr_vars['k_'.$label.'_render'] = $this->render;

            // Dynamically calculated
            $arr_vars['k_is_active'] = $this->is_current;
            $arr_vars['k_is_current'] = $this->most_current;
            $arr_vars['k_immediate_children'] = $this->immediate_children_ex;
            $arr_vars['k_total_children'] = $this->total_children_ex;
            $arr_vars['k_first_child'] = $this->first_pos;
            $arr_vars['k_last_child'] = $this->last_pos;
            $arr_vars['k_total_siblings'] = $this->total_siblings_ex;
            $arr_vars['k_pos'] = $this->pos_ex;

            $CTX->set_all( $arr_vars );

        }

        function is_current(){
            global $FUNCS;

            $is_current = 0;

            // HOOK: check_admin_(menu|action)_is_current
            $skip = $FUNCS->dispatch_event( 'check_admin_'.$this->type.'_is_current', array(&$this, &$is_current) );
            if( $skip ){ return $is_current; }

            $current_route = $FUNCS->current_route;

            if( $this->is_internal_link && $current_route ){
                if( !is_null($this->is_current_callback) ){
                    $callback = $FUNCS->is_callable( $this->is_current_callback );
                    $is_current = call_user_func_array( $callback, array($this) );
                }
                else{
                    // default check
                    if( $this->route['masterpage']==$current_route->masterpage ){
                        $is_current = array( $this );
                    }
                }
            }

            return $is_current;
        }

        function reset_weights( $weight=0 ){
            return; // nop
        }

        function get_admin_link(){
            return; // nop
        }

    } //end class KAdminMenuItem

    class KAdminFormField extends KAdminMenuItem{

        var $is_compound;
        var $hide;
        var $required;
        var $content;

        function set_in_context(){
            global $CTX, $FUNCS;

            $CTX->reset();
            $arr_vars = array();

            $label = $this->type; // field
            $arr_vars['k_'.$label.'_name'] = $this->name;
            $arr_vars['k_'.$label.'_label'] = $this->title;
            $arr_vars['k_'.$label.'_desc'] = $FUNCS->escape_HTML( $this->menu_text );
            $arr_vars['k_'.$label.'_group'] = $this->parent;
            $arr_vars['k_'.$label.'_icon'] = $this->icon;
            $arr_vars['k_'.$label.'_class'] = $this->class;
            $arr_vars['k_'.$label.'_no_wrapper'] = $this->no_wrapper;
            $arr_vars['k_'.$label.'_hidden'] = $this->hide;
            $arr_vars['k_'.$label.'_is_compound'] = $this->is_compound;

            $arr_vars['k_'.$label.'_input_id'] = 'f_'.$this->name;

            $f = null;
            if( !is_null($this->obj) && $this->obj instanceof KField ){
                $f = &$this->obj;

                $arr_vars['k_'.$label.'_input_name'] = $f->name;
                $arr_vars['k_'.$label.'_id'] = $f->id;
                $arr_vars['k_'.$label.'_type'] = $f->k_type;
                $arr_vars['k_'.$label.'_is_system'] = $f->system;
                $arr_vars['k_'.$label.'_is_udf'] = $f->udf;
                $arr_vars['k_'.$label.'_is_required'] = $f->required;
                $arr_vars['k_'.$label.'_is_deleted'] = $f->deleted;
                $arr_vars['k_'.$label.'_is_collapsed'] = $f->collapsed;
                $arr_vars['k_'.$label.'_data'] = $f->get_data( 1 );
                $arr_vars['k_'.$label.'_definition'] = $FUNCS->escape_HTML( $f->_html );
                $arr_vars['k_'.$label.'_err_msg'] = $CTX->get( 'k_error_'.$f->name );
                if($f->system){
                    $arr_vars['k_'.$label.'_wrapper_id'] = $f->name;
                }
                else{
                    $arr_vars['k_'.$label.'_wrapper_id'] = 'k_element_'.$f->name;
                }

                unset( $f );
            }
            else{
                $arr_vars['k_'.$label.'_input_name'] = $this->name;
                $arr_vars['k_'.$label.'_type'] = '_custom_';
                $arr_vars['k_'.$label.'_wrapper_id'] = $this->name;
                $arr_vars['k_'.$label.'_err_msg'] = $CTX->get( 'k_error_'.$this->name );
                $arr_vars['k_'.$label.'_is_required'] = $this->required;
            }

            // Dynamically calculated
            $arr_vars['k_immediate_children'] = $this->immediate_children_ex;
            $arr_vars['k_total_children'] = $this->total_children_ex;
            $arr_vars['k_first_child'] = $this->first_pos;
            $arr_vars['k_last_child'] = $this->last_pos;
            $arr_vars['k_total_siblings'] = $this->total_siblings_ex;
            $arr_vars['k_pos'] = $this->pos_ex;

            $CTX->set_all( $arr_vars );

            // finally content
            $arr = $this->content;
            if( is_array($arr) ){
                $html2 = '';
                foreach( $arr as $child ){
                    $html2 .= $child->get_HTML();
                }
                $content = $html2;
            }
            else{
                $content = '';
            }
            $CTX->set( 'k_field_content', $content );
        }

        function mark_current( $exclude_if_not_in_menu=1 ){
            if( !is_array($this->crumbs) ){
                $this->crumbs = array();
            }

            return;
        }

        function is_current(){
            return 0;
        }


    } //end class KAdminFormField
