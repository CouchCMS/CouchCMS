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

    if ( !defined('K_ADMIN') ) die(); // cannot be loaded directly
    require_once( K_COUCH_DIR.'includes/ckeditor/ckeditor.php' );

    $tpl_id = ( isset($_GET['tpl']) && $FUNCS->is_non_zero_natural($_GET['tpl']) ) ? (int)$_GET['tpl'] : null;
    if( is_null($tpl_id) ) die( 'ERROR: No template specified' );
    $PAGE = new KWebpage( $tpl_id, null );
    if( $PAGE->error ){
        ob_end_clean();
        die( 'ERROR: ' . $PAGE->err_msg );
    }
    if( !$PAGE->tpl_dynamic_folders ){ die( 'ERROR: Template does not support dynamic folders' ); }
    $PAGE->folders->set_sort( 'weight', 'asc' );
    $PAGE->folders->sort( 1 );
    $PAGE->k_total_folders = $PAGE->folders->get_children_count( 0, array());

    // HOOK: edit_folder
    $FUNCS->dispatch_event( 'edit_folder', array(&$PAGE) );

    if( isset($_GET['act']{0}) ){

        $folder_id = ( isset($_GET['id']) && $FUNCS->is_natural($_GET['id']) ) ? (int)$_GET['id'] : null;

        if( ($_GET['act'] == 'edit') || ($_GET['act'] == 'create') ){

            if( $folder_id || (!$folder_id && ($_GET['act'] == 'create')) ){
                if( $_GET['act'] == 'create' ){
                    $FUNCS->validate_nonce( 'create_folder' );
                }
                else{
                    $FUNCS->validate_nonce( 'update_folder_' . $folder_id );
                }

                if( $_GET['act'] == 'edit' ){
                    $folder = &$PAGE->folders->find_by_id( $folder_id );
                    if( !$folder ) die( 'ERROR: No folder with id: ' . $folder_id );

                    $PAGE->is_folder_view = 1;
                    $PAGE->folder_id = $folder_id;
                }
                else{
                    // create a folder object
                    $folder = new KFolder( array('id'=>null, 'name'=>'', 'pid'=>'-1', 'template_id'=>$PAGE->tpl_id), $PAGE->tpl_name, new KError()/*dummy*/ );
                }

                // get values from database into fields
                $folder->populate_fields();

                // handle POSTed values
                $errors = '';
                if( isset($_POST['op']) && $_POST['op']=='save' ){

                    $_POST['f_k_pid'] = intval( $_POST['f_k_folders'] );

                    // HOOK: alter_edit_folder_posted_data
                    $skip = $FUNCS->dispatch_event( 'alter_edit_folder_posted_data', array(&$folder, &$PAGE) );

                    if( !$skip ){
                        for( $x=0; $x<count($folder->fields); $x++ ){
                            $f = &$folder->fields[$x];
                            $f->store_posted_changes( $_POST['f_'.$f->name] ); // get posted values into fields
                        }
                    }

                    // HOOK: edit_folder_presave
                    $FUNCS->dispatch_event( 'edit_folder_presave', array(&$folder, &$PAGE) );

                    $errors = $folder->save();

                    // HOOK: edit_folder_saved
                    $FUNCS->dispatch_event( 'edit_folder_saved', array(&$folder, &$PAGE, &$errors) );

                    // Redirect
                    if( !$errors ){
                        $FUNCS->invalidate_cache();
                        $loc = K_ADMIN_URL . K_ADMIN_PAGE.'?o=folders&act=edit&tpl='.$PAGE->tpl_id.'&id='.$folder->id.'&nonce='.$FUNCS->create_nonce( 'update_folder_'.$folder->id );
                        header("Location: ".$loc);
                        exit;
                    }
                }

                // start building content for output

                // HOOK: edit_folder_prerender
                $FUNCS->dispatch_event( 'edit_folder_prerender', array(&$folder, &$PAGE) );

                ob_start();
                $err_div = '<div class="error" style="margin-bottom:10px; color:red; display:';
                if( $errors ){
                    $err_div .= "block\">";
                    $err_title = ($errors>1)?'ERRORS':'ERROR';
                    $err_div .= $errors. ' ' .$err_title.':<br>';
                }
                else{
                    $err_div .= "none\">&nbsp;";
                }
                $err_div .= '</div>';
                echo $err_div;
            ?>

            <form name="frm_edit_folder" id="frm_edit_folder" action="" method="post" accept-charset="<?php echo K_CHARSET; ?>">
                <div id="admin-content">
                <?php
                    for( $x=0; $x<count($folder->fields); $x++ ){
                        if( $folder->fields[$x]->name=='k_pid' ){
                            ?>
                            <div class="k_element">
                            <label for="pid"><b><?php echo $FUNCS->t('parent_folder'); ?>:</b></label>
                            <br/>
                            <?php
                            $CTX->push( '__ROOT__' );
                            $dropdown_html = '';
                            $hilited = $folder->fields[$x]->get_data();
                            $PAGE->folders->visit( array('KFolder', '_k_visitor'), $dropdown_html, $hilited, 0/*$depth*/, 0/*$extended_info*/, array($folder->name)/*$exclude*/ );
                            $CTX->pop();
                            echo '<select id="f_k_folders" name="f_k_folders"><option value="-1" >--'.$FUNCS->t('none').'--</option>' .$dropdown_html . '</select>';
                            ?>
                            <span class="k_notice"><?php if($folder->fields[$x]->err_msg){ ?><font color=red><i>(<?php echo $folder->fields[$x]->err_msg; ?>)</i></font><?php } ?></span>
                            </div>
                            <p/>
                            <?php
                            continue;
                        }
                        echo $folder->fields[$x]->render() .'<p>';
                    }
                ?>

                    <input type="hidden" name="op" value="save" />
                    <a class="button" id="btn_submit" href="#" onclick="this.style.cursor='wait'; this.fireEvent('my_submit'); window.onbeforeunload=null; $('frm_edit_folder').submit(); return false;"><span><?php echo $FUNCS->t('save'); ?></span></a>
                </div>
            </form>

            <script type="text/javascript">
                //<![CDATA[
                function k_browse_result( id, fileurl ){
                    $(id).set( 'value', fileurl );
                    try{
                        $(id + "_preview").set( {href: fileurl, style:{visibility:'visible'}} );
                        $(id + "_img_preview").set( 'src', fileurl );
                    }
                    catch( e ){}

                    TB_remove();
                }
                <?php $k_form_id='frm_edit_folder'; require_once( K_COUCH_DIR.'theme/prompt_unsaved.php' );?>
                //]]>
            </script>
            <?php
                $html = ob_get_contents();
                ob_end_clean();


                // render
                $_p = array();
                $_p['module'] = 'pages';
                $_p['tpl_name'] = $PAGE->tpl_name;
                $_p['title'] = (($PAGE->tpl_title)?$PAGE->tpl_title:$PAGE->tpl_name) . ' (<i>' . ucwords( $FUNCS->t('folders') ) . '</i>)';
                $_p['link'] = K_ADMIN_URL . K_ADMIN_PAGE . '?o=folders&tpl='.$PAGE->tpl_id;
                if( $_GET['act'] != 'create' ){
                    $_p['buttons'] = '<div id="create_new"><a class="button" href="'.K_ADMIN_URL . K_ADMIN_PAGE.'?o=folders&act=create&tpl='.$tpl_id.'&nonce='.$FUNCS->create_nonce( 'create_folder' ).'" title="'.$FUNCS->t('add_new_folder').'"><span>'.$FUNCS->t('add_new').'</span></a></div>';
                }
                $_p['subtitle'] = ( $_GET['act'] == 'create' ) ? $FUNCS->t('add_new') : $FUNCS->t('edit');
                $_p['show_advanced'] = 0;
                $_p['content'] = $html;
                $FUNCS->render_admin_page_ex( $_p );
            }
        }
        elseif( $_GET['act'] == 'delete' ){
            if( $folder_id ){
                $FUNCS->validate_nonce( 'delete_folder_' . $folder_id );

                $folder = &$PAGE->folders->find_by_id( $folder_id );
                if( !$folder ) die( 'ERROR: No folder with id: ' . $folder_id );
                $folder->delete();

                $qs = '?o=folders&tpl=' . $PAGE->tpl_id;
                if( isset($_GET['pg']) ) $qs .= '&pg=' . $_GET['pg'];
                header("Location: ".K_ADMIN_URL . K_ADMIN_PAGE. $qs);
                exit;
            }
        }
    }
    else{
        // Any folders marked for deletion?
        if( isset($_POST['folder-id']) ){
            $FUNCS->validate_nonce( 'bulk_action_folder' );

            foreach( $_POST['folder-id'] as $v ){
                if( $FUNCS->is_non_zero_natural($v) ){
                    $folder_id = intval( $v );

                    // execute action
                    $folder = &$PAGE->folders->find_by_id( $folder_id );
                    if( !$folder ) die( 'ERROR: No folder with id: ' . $folder_id );
                    $folder->delete();
                }
            }

            // redirect necessary to reconstruct folder tree
            $qs = '?o=folders&tpl=' . $PAGE->tpl_id;
            if( isset($_GET['pg']) ) $qs .= '&pg=' . $_GET['pg'];
            header("Location: ".K_ADMIN_URL . K_ADMIN_PAGE. $qs);
            exit;
        }

        // list folders
        $_p = array();
        $_p['module'] = 'pages';
        $_p['tpl_name'] = $PAGE->tpl_name;
        $_p['title'] = ucwords( $FUNCS->t('folders') ) . ' (<i>' . (($PAGE->tpl_title)?$PAGE->tpl_title:$PAGE->tpl_name) . '</i>)';
        $_p['link'] = K_ADMIN_URL . K_ADMIN_PAGE . '?o=folders&tpl='.$PAGE->tpl_id;
        $_p['buttons'] = '<div id="create_new"><a class="button" href="'.K_ADMIN_URL . K_ADMIN_PAGE.'?o=folders&act=create&tpl='.$tpl_id.'&nonce='.$FUNCS->create_nonce( 'create_folder' ).'" title="'.$FUNCS->t('add_new_folder').'"><span>'.$FUNCS->t('add_new').'</span></a></div>';
        $_p['subtitle'] = $FUNCS->t('list');
        $_p['show_advanced'] = 0;
        $_p['content'] = k_admin_list_folders( $tpl_id );
        $FUNCS->render_admin_page_ex( $_p );
    }


    function k_admin_list_folders( $tpl_id ){
        global $FUNCS, $DB, $PAGE, $CTX;

        // pagination
        $limit = 50;
        $pgn_pno = 1;
        if( isset($_GET['pg']) && $FUNCS->is_non_zero_natural( $_GET['pg'] ) ){
            $pgn_pno = (int)$_GET['pg'];
        }
        $total_rows = $PAGE->k_total_folders;
        $total_pages = ceil( $total_rows/$limit );
        if( $pgn_pno>$total_pages && $total_pages>0 ) $pgn_pno=$total_pages;
        $adjacents = 2;
        $targetpage = K_ADMIN_URL . K_ADMIN_PAGE . '?o=folders&tpl='.$tpl_id;
        $pagestring = "&pg=";
        $prev_text = '&#171; ' . $FUNCS->t('prev');
        $next_text = $FUNCS->t('next') . ' &#187;';
        $simple = 0;

        // record counts
        $first_record_on_page = ($limit * ($pgn_pno - 1)) + 1;
        $last_record_on_page = $first_record_on_page + $limit - 1;
        if( $last_record_on_page > $total_rows ) $last_record_on_page = $total_rows;
        $total_records_on_page = $last_record_on_page - ( $first_record_on_page-1 );

        ob_start();
        ?>
        <form method="post" action="" id="frm_list_folders" name="frm_list_folders">
            <div class="wrap-paginator">
            <?php
            if( $total_rows > $limit ){
                $str_paginator = $FUNCS->getPaginationString( $pgn_pno, $total_rows, $limit, $adjacents, $targetpage, $pagestring, $prev_text, $next_text, $simple );
                $str_paginator .= "<div class='record-count'>".$FUNCS->t('showing')." $first_record_on_page-$last_record_on_page / $total_rows</div>";
                echo $str_paginator;
            }
            ?>
            </div>
            <div class="group-wrapper listing">
            <table cellspacing="0" cellpadding="0" class="listing clear">
                <thead>
                <th class="checkbox">
                    <input type="checkbox" onclick="$$('.folder-selector').set('checked', this.checked);" name="check-all"/>
                </th>
                <th><?php echo $FUNCS->t('title'); ?></th>
                <th><?php echo $FUNCS->t('name'); ?></th>
                <th><?php echo $FUNCS->t('pages'); ?></th>
                <th><?php echo $FUNCS->t('actions'); ?></th>
                </thead>
                <tbody>
                <?php
                $CTX->push( '__ROOT__' );
                $html = '';
                $param = new stdClass;
                $param->_from = $first_record_on_page;
                $param->_to = $last_record_on_page;
                $param->_total = $total_records_on_page;
                $param->_counter = 0;
                $PAGE->folders->visit( '_k_visitor2', $html, $param, 0/*$depth*/, 0/*$extended_info*/, array()/*$exclude*/, 0, 0, 0, 1/*paginate*/ );
                $CTX->pop();

                if( !$param->_counter ){
                ?>
                <tr>
                    <td colspan="5" class="last_row" style="text-align:center">
                    <?php echo $FUNCS->t('no_folders'); ?>
                    </td>
                <tr>
                <?php
                }
                else{
                    echo $html;
                }
                ?>
                </tbody>
            </table>
            </div>

            <div class="wrap-paginator">
            <div class="bulk-actions">
                <a href="#" id="btn_bulk_submit" class="button"><span><?php echo $FUNCS->t('delete_selected'); ?></span></a>
            </div>
            <?php echo $str_paginator; ?>
            </div>

            <input type="hidden" value="<?php echo $FUNCS->create_nonce( 'bulk_action_folder' ); ?>" name="nonce" id="nonce"/>
        </form>

        <script type="text/javascript">
                //<![CDATA[
                window.addEvent('domready', function(){
                    if( $('btn_bulk_submit') ){
                        $('btn_bulk_submit').addEvent('click', function(e){
                            var col = $$('.folder-selector');
                            for( var x=0; x<col.length; x++ ){
                                if( col[x].checked ){
                                    if( confirm('<?php echo $FUNCS->t('confirm_delete_selected_folders'); ?>') ){
                                        $$('body').setStyle('cursor', 'wait');
                                        $('frm_list_folders').submit();
                                    }
                                    return false;
                                }
                            }
                            return false;
                            });
                    }
                });
                //]]>
            </script>
        <?php
        $html = ob_get_contents();
        ob_end_clean();

        return $html;
    }

    function _k_visitor2( &$folder, &$html, &$param ){
        global $CTX, $PAGE, $FUNCS;

        $cur = $param->_counter;

        $level = $CTX->get('k_level', 1);

        // if first folder on page not of top level, output all its parents first
        if( $cur==$param->_from && $level ){
            $arr_parents = $PAGE->folders->get_parents_by_id( $folder->id );
            $level2 = 0;
            for( $x=count($arr_parents)-1; $x>0; $x-- ){
                $html .= _render_row( $arr_parents[$x], $level2 );
                $level2++;
            }
        }

        // output row being visited
        $tr_class = ( $cur==$param->_to ) ? 'last_row' : '';
        $html .= _render_row( $folder, $level, $tr_class );

    }

    function _render_row( &$f, $level, $tr_class='' ){
        global $PAGE, $FUNCS;


        for( $x=0; $x<$level; $x++ ){
            $pad .= '- &nbsp;&nbsp;&nbsp;';
            $len_pad += 3;
        }
        $update_link = '?o=folders&act=edit&tpl='.$PAGE->tpl_id.'&id='.$f->id.'&nonce='. $FUNCS->create_nonce( 'update_folder_'.$f->id );
        $delete_link = '?o=folders&act=delete&tpl='.$PAGE->tpl_id.'&id='.$f->id.'&nonce='. $FUNCS->create_nonce( 'delete_folder_'.$f->id );

        ob_start();
        ?>
        <tr>
            <td class="checkbox <?php echo $tr_class; ?>">
            <input type="checkbox" name="folder-id[]" class="folder-selector" value="<?php echo $f->id; ?>"/>
            </td>
            <td class="folder-title <?php echo $tr_class; ?>">
            <?php
                $avail = 60;
                if( $len_pad+strlen($f->title) > $avail ){
                    $abbr_title = ( ($len_pad<$avail) ? substr($f->title, 0, $avail-$len_pad) : substr($pad, 0, $len_pad-$avail) ). '&hellip;';
                }
                else{
                    $abbr_title = $f->title;
                }
            ?>
            <a href="<?php echo $update_link; ?>"><?php echo $pad . ' ' . $abbr_title; ?></a>
            </td>
            <td class="folder-name <?php echo $tr_class; ?>">
            <?php echo $f->name; ?>
            </td>
            <td class="pages-count <?php echo $tr_class; ?>">
            <?php echo $f->count; ?>
            </td>
            <td class="actions <?php echo $tr_class; ?>">
            <a href="<?php echo $update_link; ?>"><img title="<?php echo $FUNCS->t('edit'); ?>" src="<?php echo K_ADMIN_URL; ?>theme/images/page_white_edit.gif"/></a>
            <?php
            $confirm_prompt = 'onclick="if( confirm(\''.$FUNCS->t('confirm_delete_folder').': '.$f->name.'?\') ) { return true; } return false;"';
            $qs = $delete_link;
            if( isset($_GET['pg']) ) $qs .= '&pg=' . $_GET['pg'];
            echo '<a href="'.K_ADMIN_URL . K_ADMIN_PAGE.$qs.'" '.$confirm_prompt.'><img src="'.K_ADMIN_URL.'theme/images/page_white_delete.gif" title="'.$FUNCS->t('delete').'"/></a>';
            ?>
            </td>
        </tr>
        <?php
        $html .= ob_get_contents();
        ob_end_clean();

        return $html;
    }
