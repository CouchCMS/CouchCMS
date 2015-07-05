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

    if( isset($_GET['act']{0}) ){
        $tpl_id = ( isset($_GET['tpl']) && $FUNCS->is_non_zero_natural($_GET['tpl']) ) ? (int)$_GET['tpl'] : null;
        $page_id = ( isset($_GET['p']) && $FUNCS->is_non_zero_natural($_GET['p']) ) ? (int)$_GET['p'] : null;
        $cid = ( isset($_GET['cid']) && $FUNCS->is_non_zero_natural($_GET['cid']) ) ? (int)$_GET['cid'] : null;
        $rid = ( isset($_GET['rid']) && $FUNCS->is_non_zero_natural($_GET['rid']) ) ? (int)$_GET['rid'] : null;

        if( $tpl_id && (($_GET['act'] == 'edit') || ($_GET['act'] == 'create')) ){

            if( $_GET['act'] == 'create' ){
                $FUNCS->validate_nonce( 'create_page_' . $tpl_id );
                $page_id = -1;
            }
            else{
                $obj_id = ( $page_id ) ? $page_id : $tpl_id;
                $FUNCS->validate_nonce( 'edit_page_' . $obj_id );
            }

            $PAGE = new KWebpage( $tpl_id, $page_id );
            if( $PAGE->error ){
                ob_end_clean();
                die( 'ERROR: ' . $PAGE->err_msg );
            }
            $draft_of = $PAGE->parent_id;
            // if draft, get parent page's name
            if( $draft_of ){
                $rs = $DB->select( K_TBL_PAGES, array('page_name', 'page_title'), "id='" . $DB->sanitize( $draft_of ). "'" );
                if( count($rs) ){
                    $parent_of_draft = $rs[0]['page_name'];
                    $parent_of_draft_title = $rs[0]['page_title'];
                }
            }

            // first check if any custom edit screen registered for this template
            if( array_key_exists( $PAGE->tpl_name, $FUNCS->admin_page_views ) ){
                $snippet = $FUNCS->admin_page_views[$PAGE->tpl_name][0];
                $show_advanced_settings = $FUNCS->admin_page_views[$PAGE->tpl_name][1];

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
                    //$html = $parser->get_HTML();
                    $html = $parser->get_cached_HTML( $filepath );
                }
                else{
                    $html = 'ERROR: Unable to get contents from custom page_view <b>' . $filepath . '</b>';
                }
            }
            else{ // continue with default logic

                $show_advanced_settings = 1;

                // If non-clonable and physical template missing
                if( !$PAGE->tpl_is_clonable && !file_exists(K_SITE_DIR . $PAGE->tpl_name) ){
                    $html = '<div class="error" style="margin-bottom:10px;">';
                    $html .= '<strong>'. $FUNCS->t('template_missing') .'</strong>';
                    if( $AUTH->user->access_level >= K_ACCESS_LEVEL_SUPER_ADMIN ){
                        // make sure no drafts exist before prompting for template removal
                        $rs = $DB->select( K_TBL_PAGES, array('id'), "template_id='" . $DB->sanitize( $PAGE->tpl_id ). "' AND is_master<>'1'" );
                        if( count($rs) ){
                            $html .= ' <i>('. $FUNCS->t('remove_uncloned_template_completely') .')</i>';
                            $html .= '</div>';
                        }
                        else{
                            $html .= '</div><a class="button" href="javascript:k_delete_template('.$PAGE->tpl_id.', \''.$FUNCS->create_nonce( 'delete_tpl_'.$PAGE->tpl_id ).'\')" title="'.$FUNCS->t('remove_template').'"><span>'.$FUNCS->t('remove_template').'</span></a>';
                        }
                    }
                    else{
                        $html .= '</div>';
                    }
                    $_p = array();
                    $_p['module'] = 'pages';
                    $_p['tpl_name'] = $PAGE->tpl_name;
                    $_p['title'] = $PAGE->tpl_title ? $PAGE->tpl_title : $PAGE->tpl_name;
                    $_p['content'] = $html;
                    ob_start();
                    ?>
                    <script type="text/javascript">
                        //<![CDATA[
                        function k_delete_template( tpl, nonce ){
                            var qs = 'ajax.php?act=delete-tpl&tpl='+tpl+'&nonce='+encodeURIComponent( nonce );
                            var requestHTMLData = new Request (
                                {
                                    url: qs,
                                    onComplete: function(response){
                                        if( response=='OK' ){
                                            document.location.href = "<?php echo K_ADMIN_URL . K_ADMIN_PAGE; ?>";
                                        }
                                        else{
                                            alert(response);
                                        }
                                    }
                                }
                            ).send();
                        }
                        //]]>
                    </script>
                    <?php
                    $_p['content'] .= ob_get_contents();
                    ob_end_clean();
                    $FUNCS->render_admin_page_ex( $_p );
                    return;
                }

                $requires_multipart = 0;
                for( $x=0; $x<count($PAGE->fields); $x++ ){
                    $f = &$PAGE->fields[$x];
                    $f->resolve_dynamic_params();
                    // check if any field requires 'multipart/form-data'
                    if( $f->requires_multipart ) $requires_multipart = 1;
                    unset( $f );
                }

                // If creating new page and not in save mode and a folder_id or related_page is provided..
                if( $_GET['act'] == 'create' && $_POST['op']!='save' ){
                    $f_id = ( isset($_GET['fid']) && $FUNCS->is_non_zero_natural($_GET['fid']) ) ? (int)$_GET['fid'] : null;

                    // first test if the indicated folder does exist..
                    if( $f_id && $PAGE->folders->find_by_id( $f_id ) ){
                        // if it does, set it in the folders select dropdown
                        $PAGE->_fields['k_page_folder_id']->data = $f_id;
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

                $errors = '';
                if( isset($_POST['op']) && $_POST['op']=='save' ){

                    // map proxy fields to their cardinal system counterparts
                    if( $_POST['f_publish_status'] ){
                        $_POST['f_k_publish_date'] = $FUNCS->sanitize_posted_date();
                    }
                    else{
                        $_POST['f_k_publish_date'] = '0000-00-00 00:00:00';
                    }
                    if( isset($_POST['f_k_levels_list']) ){
                        $_POST['f_k_access_level'] = intval( $_POST['f_k_levels_list']);
                    }
                    $_POST['f_k_comments_open'] = ( isset($_POST['f_allow_comments']) ) ? '1' : '0';
                    if( $PAGE->tpl_nested_pages ){
                        $_POST['f_k_show_in_menu'] = ( isset($_POST['f_show_in_menu']) ) ? '1' : '0';
                        if( isset($_POST['f_menu_text']) ) $_POST['f_k_menu_text'] = $_POST['f_menu_text'];
                        $_POST['f_k_is_pointer'] = ( isset($_POST['f_is_pointer']) ) ? '1' : '0';
                        $_POST['f_k_open_external'] = ( isset($_POST['f_open_external']) ) ? '1' : '0';
                        $_POST['f_k_masquerades'] = ( $_POST['f_masquerades'] ) ? '1' : '0';
                        $_POST['f_k_strict_matching'] = ( isset($_POST['f_strict_matching']) ) ? '0' : '1';
                    }

                    // move posted data into fields
                    $refresh_form = $refresh_errors = 0;

                    // HOOK: alter_edit_page_posted_data
                    $skip = $FUNCS->dispatch_event( 'alter_edit_page_posted_data', array(&$PAGE, &$refresh_form, &$refresh_errors) );

                    if( !$skip ){
                        for( $x=0; $x<count($PAGE->fields); $x++ ){
                            $f = &$PAGE->fields[$x];
                            $f->store_posted_changes( $_POST['f_'.$f->name] );
                            if( $f->refresh_form ) $refresh_form = 1;
                            if( $f->err_msg_refresh ) $refresh_errors++;
                            unset( $f );
                        }
                    }

                    if( !$refresh_form ){

                        // HOOK: edit_page_presave
                        $FUNCS->dispatch_event( 'edit_page_presave', array(&$PAGE) );

                        $errors = $PAGE->save();

                        // HOOK: edit_page_saved
                        $FUNCS->dispatch_event( 'edit_page_saved', array(&$PAGE, &$errors) );

                        if( !$errors ){

                            if( $draft_of ){
                                if( $_POST['f_k_update_original'] ){

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

                                    // redirect to the original
                                    $nonce = $FUNCS->create_nonce( 'edit_page_'.$draft_of );
                                    $extra = '?act=edit&tpl='.$tpl_id.'&p='.$draft_of .'&nonce='.$nonce;
                                    header("Location: ".K_ADMIN_URL . K_ADMIN_PAGE."$extra");
                                    exit;

                                }
                            }
                            else{

                                $FUNCS->invalidate_cache();

                                // If draft needs to be created ..
                                if( $_POST['f_k_create_draft'] ){
                                    $draft_id = $PAGE->create_draft();
                                    if( $FUNCS->is_error($draft_id) ){
                                        ob_end_clean();
                                        die( $draft_id->err_msg );
                                    }

                                    // redirect to the draft
                                    $nonce = $FUNCS->create_nonce( 'edit_page_'.$draft_id );
                                    $extra = '?act=edit&tpl='.$tpl_id.'&p='.$draft_id .'&nonce='.$nonce;
                                    header("Location: ".K_ADMIN_URL . K_ADMIN_PAGE."$extra");
                                    exit;
                                }

                                $nonce = $FUNCS->create_nonce( 'edit_page_'.$PAGE->id );
                                $extra = '?act=edit&tpl='.$tpl_id.'&p='.$PAGE->id;
                                if( $cid && $rid ) $extra .= '&cid='.$cid.'&rid='.$rid;
                                $extra .= '&nonce='.$nonce;
                                header("Location: ".K_ADMIN_URL . K_ADMIN_PAGE."$extra");
                                exit;
                            }
                        }
                    }
                    else{
                        $errors = $refresh_errors;
                    }
                    // if not form refresh
                } // if save

                // start building content for output

                // HOOK: edit_page_prerender
                $FUNCS->dispatch_event( 'edit_page_prerender', array(&$PAGE) );

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
                    <form name="frm_edit_page" id="frm_edit_page" action="" method="post" accept-charset="<?php echo K_CHARSET; ?>"<?php if($requires_multipart){echo ' enctype="multipart/form-data" ';}?>>
                        <div id="admin-sidebar" >
                        <?php if( !$draft_of ){ ?>
                            <?php if( $_GET['act'] == 'edit' ){
                                $visibility = ( $PAGE->tpl_nested_pages && $PAGE->_fields['k_is_pointer']->get_data() ) ? 'hidden' : 'visible'; // hide draft button if nestable page is a 'pointer page'
                            ?>
                            <div id="create-draft" style="margin-top:0px; visibility:<?php echo $visibility; ?>;">
                                <a class="button" id="btn_draft" href="#" title="<?php echo $FUNCS->t('create_draft_msg'); ?>" onclick="this.style.cursor='wait'; $('f_k_create_draft').set( 'value', '1' ); $('frm_edit_page').submit(); return false;"><span><?php echo $FUNCS->t('create_draft'); ?></span></a>
                                <input type="hidden" id="f_k_create_draft" name="f_k_create_draft" value="0" />
                                <div style="clear:both"></div>
                            </div>
                            <?php } ?>

                            <div id="access-levels" style="margin-top:<?php if($_GET['act'] == 'edit') echo '10'; else echo '0'; ?>px">
                                <label><b><?php echo $FUNCS->t('access_level'); ?>:</b></label><br>
                                <?php
                                    $inherited = 0;
                                    $level = $PAGE->get_access_level( $inherited ); //template level and folder level override page level
                                    if( $PAGE->access_level > $AUTH->user->access_level ){
                                        $inherited = 1;
                                        echo $FUNCS->access_levels_dropdown( $level, 10, 0, $inherited);
                                    }
                                    else{
                                        if( !$inherited ){
                                            $level = $PAGE->_fields['k_access_level']->get_data();
                                        }
                                        echo $FUNCS->access_levels_dropdown( $level /*selected*/, $AUTH->user->access_level/*max*/, 0/*min*/, $inherited/*disabled*/);
                                    }
                                    $PAGE->effective_level = $level;
                                ?>
                            </div>

                            <?php
                                $visibility = ( $PAGE->tpl_is_commentable ) ? 'block' : 'none';
                                $checked = ( $PAGE->_fields['k_comments_open']->get_data() ) ? 'checked="checked"' : '';
                             ?>
                            <div id="comments-open" style="margin-top:10px; display:<?php echo $visibility; ?>">
                                <label><b><?php echo $FUNCS->t('comments'); ?>:</b></label><br>
                                <label>
                                    <input type="checkbox" value="1" <?php echo $checked; ?> name="f_allow_comments"/><?php echo $FUNCS->t('allow_comments'); ?>
                                </label>

                            </div>

                            <?php if( $PAGE->tpl_nested_pages ): ?>
                                <div id="publish-date" style="margin-top:10px">
                                    <?php $publish_date = $PAGE->_fields['k_publish_date']->get_data(); ?>
                                    <label><b><?php echo $FUNCS->t('status'); ?>:</b></label><br>
                                    <input type="radio" <?php if( $publish_date != '0000-00-00 00:00:00' ){?>checked="checked"<?php } ?> value="1" id="f_publish_status_1" name="f_publish_status" />
                                    <label for="f_publish_status_1"><?php echo $FUNCS->t('active'); ?></label>&nbsp;
                                    <input type="radio" <?php if( $publish_date == '0000-00-00 00:00:00' ){?>checked="checked"<?php } ?> value="0" id="f_publish_status_0" name="f_publish_status" />
                                    <label for="f_publish_status_0"><?php echo $FUNCS->t('inactive'); ?></label><br>
                                    <div id="date-dropdown" style="display:none">
                                    <?php
                                        echo $FUNCS->date_dropdowns( $PAGE->_fields['k_publish_date']->get_data() );
                                    ?>
                                    </div>
                                </div>
                                <div id="menu" style="margin-top:10px;">
                                    <label><b><?php echo $FUNCS->t('menu'); ?>:</b></label><br>
                                    <label>
                                        <?php $checked = ( $PAGE->_fields['k_show_in_menu']->get_data() ) ? 'checked="checked"' : ''; ?>
                                        <input type="checkbox" value="1" <?php echo $checked; ?> name="f_show_in_menu" /><?php echo $FUNCS->t('show_in_menu'); ?>
                                    </label>
                                </div>
                                <div id="menu-text" style="margin-top:10px;">
                                    <label><b><?php echo $FUNCS->t('menu_text'); ?>:</b></label><br>
                                    <input type="text" style="width: 185px;" class="k_text" maxlength="255" value="<?php echo $PAGE->_fields['k_menu_text']->get_data(); ?>" name="f_menu_text" id="f_menu_text"/>
                                    <span class="k_desc"><i>(<?php echo $FUNCS->t('leave_empty'); ?>)</i></span>
                                </div>
                                <div id="is_pointer" style="margin-top:10px;">
                                    <label><b><?php echo $FUNCS->t('menu_link'); ?>:</b></label><br>
                                    <label>
                                        <?php $checked = ( $PAGE->_fields['k_open_external']->get_data() ) ? 'checked="checked"' : ''; ?>
                                        <input type="checkbox" value="1" <?php echo $checked; ?> name="f_open_external" /><?php echo $FUNCS->t('separate_window'); ?>
                                    </label><br />
                                    <label>
                                        <?php $checked = ( $PAGE->_fields['k_is_pointer']->get_data() ) ? 'checked="checked"' : ''; ?>
                                        <input type="checkbox" value="1" <?php echo $checked; ?> name="f_is_pointer" onClick="if(this.checked){$('admin-wrapper-custom_fields').setStyle('visibility', 'hidden');<?php if( $_GET['act'] != 'create' ){ echo"\$('create-draft').setStyle('visibility', 'hidden');"; } ?>$('wrapper_k_pointer_link').setStyle('display', 'block'); }
                                        else{$('admin-wrapper-custom_fields').setStyle('visibility', 'visible');<?php if( $_GET['act'] != 'create' ){ echo"\$('create-draft').setStyle('visibility', 'visible');"; } ?>$('wrapper_k_pointer_link').setStyle('display', 'none');}" /><?php echo $FUNCS->t('points_to_another_page'); ?>
                                    </label>
                                </div>
                            <?php else: ?>
                                <div id="publish-date" style="margin-top:10px">
                                    <?php $publish_date = $PAGE->_fields['k_publish_date']->get_data(); ?>
                                    <label><b><?php echo $FUNCS->t('status'); ?>:</b></label><br>
                                    <input type="radio" <?php if( $publish_date == '0000-00-00 00:00:00' ){?>checked="checked"<?php } ?> value="0" id="f_publish_status_0" name="f_publish_status" onClick="$('date-dropdown').setStyle('visibility', 'hidden')"/>
                                    <label for="f_publish_status_0"><?php echo $FUNCS->t('unpublished'); ?></label><br>
                                    <input type="radio" <?php if( $publish_date != '0000-00-00 00:00:00' ){?>checked="checked"<?php } ?> value="1" id="f_publish_status_1" name="f_publish_status" onClick="$('date-dropdown').setStyle('visibility', 'visible')"/>
                                    <label for="f_publish_status_1"><?php echo $FUNCS->t('published'); ?></label><br>
                                    <div id="date-dropdown" style="visibility:<?php if( $publish_date == '0000-00-00 00:00:00' ){ echo 'hidden'; } else{ echo 'visible'; }?>; margin-top:4px;">
                                    <?php
                                        echo $FUNCS->date_dropdowns( $PAGE->_fields['k_publish_date']->get_data() );
                                    ?>
                                    </div>
                                </div>
                            <?php endif; ?>
                        <?php }else{ /* draft */ ?>
                            <div id="update-original" style="margin-top:0px">
                                <a class="button" id="btn_update_original" href="#" title="<?php echo $FUNCS->t('update_original_msg'); ?>" onclick="this.style.cursor='wait'; $('f_k_update_original').set( 'value', '1' ); $('frm_edit_page').submit(); return false;"><span><?php if($parent_of_draft){ echo $FUNCS->t('update_original'); }else{ echo $FUNCS->t('recreate_original'); } ?></span></a>
                                <input type="hidden" id="f_k_update_original" name="f_k_update_original" value="0" />
                                <div style="clear:both"></div>
                            </div>
                        <?php } ?>
                        </div>

                        <div id="admin-content">
                            <?php
                            if( $cid && $rid ){
                                echo _get_rel_banner( $cid, $rid );
                            }
                            ?>

                            <?php if( $draft_of ){ ?>
                            <div class="notice" style="margin-bottom:10px;">
                                <strong><?php echo $FUNCS->t('draft_caps').': '; ?></strong>
                                <?php
                                // Template link
                                $tpl_name = $PAGE->tpl_title ? $PAGE->tpl_title : $PAGE->tpl_name;
                                if( $PAGE->tpl_is_clonable ){
                                    $tpl_link = K_ADMIN_URL . K_ADMIN_PAGE . '?act=list&tpl=' . $PAGE->tpl_id;
                                }
                                else{
                                    $tpl_link = K_ADMIN_URL . K_ADMIN_PAGE . '?act=edit&tpl=' . $PAGE->tpl_id .'&nonce='.$FUNCS->create_nonce( 'edit_page_'.$PAGE->tpl_id );
                                }
                                echo '<a href="'.$tpl_link.'">'.$tpl_name.'</a>';

                                // Page link
                                if( $PAGE->tpl_is_clonable ){
                                    if( $parent_of_draft ){
                                        $nonce = $FUNCS->create_nonce( 'edit_page_'.$draft_of );
                                        $abbr_title = $parent_of_draft_title;
                                        $abbr_title = (strlen($abbr_title)>90) ? substr($abbr_title, 0, 90) . '&hellip;' : $abbr_title;
                                        echo '<a href="'.K_ADMIN_URL . K_ADMIN_PAGE.'?act=edit&tpl='. $PAGE->tpl_id .'&p='. $draft_of .'&nonce='.$nonce.'" title="'.$parent_of_draft_title.'"> / '. $abbr_title .'</a>';
                                    }
                                    else{
                                        echo ' / <font color="red">'.$FUNCS->t('original_deleted').'</font>';
                                    }
                                }
                                ?>
                            </div>
                            <?php } ?>

                            <?php
                            $has_custom_fields = 0;
                            if( $draft_of ) $PAGE->_fields['k_page_name']->hidden = 1;
                            if( $PAGE->tpl_nested_pages ) {
                                $custom_fields_visibility = ($PAGE->_fields['k_is_pointer']->get_data()) ? 'hidden' : 'visible'; // hide custom fields if this nested page is pointer_page
                            }
                            for( $x=0; $x<count($PAGE->fields); $x++ ){
                                if( !$PAGE->fields[$x]->system ){
                                    if( $PAGE->tpl_nested_pages && !$has_custom_fields) {
                                        //$custom_fields_visibility = ($PAGE->_fields['k_is_pointer']->get_data()) ? 'hidden' : 'visible'; // hide custom fields if this nested page is pointer_page
                                        echo '<div id="admin-wrapper-custom_fields" style="visibility:'.$custom_fields_visibility.';" >';
                                    }
                                    $has_custom_fields = 1;
                                }
                                echo $PAGE->fields[$x]->render();

                            }
                            if( $PAGE->group_div_open ){
                                echo '</div></div>';
                            }

                            if( !$has_custom_fields ){
                                if( $PAGE->tpl_nested_pages ){
                                    echo '<div id="admin-wrapper-custom_fields" style="visibility:'.$custom_fields_visibility.';" >';
                                }
                                echo '<h4>'.$FUNCS->t('no_regions_defined').'</h4>';
                            }
                            ?>
                            <p>
                            <input type="hidden" name="op" value="save" />
                            <?php /* ?><input class="button" type="submit" value="<?php echo ("Save" ); ?>" /><?php */ ?>
                            <?php if( $level <= $AUTH->user->access_level ){ ?>
                            <a class="button" id="btn_submit" href="#" onclick="this.style.cursor='wait'; this.fireEvent('my_submit'); window.onbeforeunload=null; $('frm_edit_page').submit(); return false;"><span><?php echo $FUNCS->t('save'); ?></span></a>
                            <?php } ?>
                            <?php
                            if( $_GET['act'] == 'edit' ){
                                $link = K_SITE_URL . $PAGE->tpl_name;
                                if( !is_null($page_id) ) $link .= '?p=' . $page_id;
                                echo '<a class="button" href="'. $link .'" target="_blank" onclick="this.blur();"><span>';
                                if( $draft_of ) echo $FUNCS->t('preview'); else echo $FUNCS->t('view');
                                echo '</span></a>';
                            }
                            ?>
                            </p>
                            <?php
                                if( $PAGE->tpl_nested_pages ) echo '</div>';
                            ?>
                        </div>
                    </form>

                    <script type="text/javascript">
                        //<![CDATA[
                        window.addEvent('domready',
                            function(){
                                new Fx.Accordion(
                                    $('container'), 'div.group-toggler', 'div.group-slider',
                                    {
                                        onActive: function(toggler, element) {},
                                        onBackground: function(toggler, element) {},
                                        duration: 300,
                                        opacity: false,
                                        alwaysHide: false
                                    }
                                );
                            }
                        );

                        window.addEvent('domready', function(){
                            var mySlide = new Fx.Slide('admin-sidebar').hide();
                            $('admin-sidebar').setStyle('display', 'block');
                            $('toggle').addEvent('click', function(e){
                                e = new Event(e);
                                mySlide.toggle().chain(function(){
                                    if (mySlide.open ){
                                        $('toggle').removeClass('collapsed').addClass('expanded');
                                    }
                                    else{
                                        $('toggle').removeClass('expanded').addClass('collapsed');
                                    }
                                });
                                e.stop();
                            });
                        });

                        window.addEvent('domready', function(){
                            var del = $$('.k_element_deleted');
                            for( var x=0; x<del.length; x++ ){
                                del[x].setStyle('height', del[x].offsetParent.offsetHeight);
                            }
                        });

                        function k_browse_result( id, fileurl ){
                            $(id).set( 'value', fileurl );
                            try{
                                $(id + "_preview").set( {href: fileurl, style:{visibility:'visible'}} );
                                $(id + "_img_preview").set( 'src', fileurl );
                            }
                            catch( e ){}

                            TB_remove();
                        }

                        function k_crop_image( tpl_id, page_id, field_id, nonce ){
                            var el_notice = 'k_notice_f_' + field_id;
                            var el_preview = 'f_'+field_id+'_preview';
                            var crop_pos = $('f_k_crop_pos_' + field_id).value;
                            var qs = '<?php echo K_ADMIN_URL; ?>ajax.php?act=crop&tpl='+tpl_id+'&p='+page_id+'&tb='+encodeURIComponent( field_id )+'&nonce='+ encodeURIComponent( nonce )+'&cp='+encodeURIComponent(crop_pos);
                            var requestHTMLData = new Request (
                                {
                                    url: qs,
                                    onComplete: function(response){
                                        if( response=='OK' ){
                                            var href = $(el_preview).get('href');
                                            if( href.indexOf('?') != -1 ){
                                                href = href.substr(0, href.indexOf('?'));
                                            }
                                            href = href + '?rand=' + Math.random();
                                            $(el_preview).set('href', href);
                                            try{
                                                $('f_'+field_id+'_tb_preview').set('src', href);
                                            }
                                            catch( e ){}

                                            alert('<?php echo $FUNCS->t('thumb_recreated'); ?>');
                                        }
                                        else{
                                            alert(response);
                                        }
                                    }
                                }
                            ).send();
                        }

                        function k_delete_field( fid, fname, nonce ){
                            if( confirm('<?php echo $FUNCS->t('confirm_delete_field'); ?>') ){
                                var qs = 'ajax.php?act=delete-field&fid='+fid+'&nonce='+encodeURIComponent( nonce );
                                var requestHTMLData = new Request (
                                    {
                                        url: qs,
                                        onComplete: function(response){
                                            if( response=='OK' ){
                                                $('k_element_'+fname).setStyle('display', 'none');
                                            }
                                            else{
                                                alert(response);
                                            }
                                        }
                                    }
                                ).send();
                            }
                        }
                        function k_delete_column( fid, nonce ){
                            if( confirm('<?php echo $FUNCS->t('confirm_delete_columns'); ?>') ){
                                var qs = 'ajax.php?act=delete-columns&fid='+fid+'&nonce='+encodeURIComponent( nonce );
                                var requestHTMLData = new Request (
                                    {
                                        url: qs,
                                        onComplete: function(response){
                                            if( response=='OK' ){
                                                window.location.reload( true );
                                            }
                                            else{
                                                alert(response);
                                            }
                                        }
                                    }
                                ).send();
                            }
                        }
                        <?php $k_form_id='frm_edit_page'; require_once( K_COUCH_DIR.'theme/prompt_unsaved.php' );?>
                        //]]>
                    </script>
                <?php

                $html = ob_get_contents();
                ob_end_clean();
            }

            // render
            $_p = array();
            if( !$draft_of ){
                $_p['module'] = 'pages';
                $_p['tpl_name'] = $PAGE->tpl_name;
                $_p['title'] = $PAGE->tpl_title ? $PAGE->tpl_title : $PAGE->tpl_name;
                $_p['link'] = ( $PAGE->tpl_is_clonable ) ? K_ADMIN_URL . K_ADMIN_PAGE . '?act=list&tpl=' . $PAGE->tpl_id : '';
                if( $cid && $rid && $PAGE->tpl_is_clonable ) $_p['link'] .= '&cid='.$cid.'&rid='.$rid;
                if( $_GET['act'] != 'create' && !$draft_of ){
                    if( file_exists(K_SITE_DIR . $PAGE->tpl_name) && $PAGE->tpl_is_clonable && $AUTH->user->access_level >= $PAGE->tpl_access_level ){
                        $_p['buttons'] = '<div id="create_new"><a class="button" href="'.K_ADMIN_URL . K_ADMIN_PAGE.'?act=create&tpl='. $PAGE->tpl_id;
                        if( $cid && $rid ) $_p['buttons'] .= '&cid='.$cid.'&rid='.$rid;
                        $_p['buttons'] .= '&nonce='.$FUNCS->create_nonce( 'create_page_'.$PAGE->tpl_id ).'" title="'.$FUNCS->t('add_new_page').'"><span>'.$FUNCS->t('add_new').'</span></a></div>';
                    }
                }
                $_p['subtitle'] = ( $_GET['act'] == 'create' ) ? $FUNCS->t('add_new') : $FUNCS->t('edit');
            }
            else{
                $_p['module'] = 'drafts';
                $_p['title'] = $FUNCS->t('drafts');
                $_p['link'] = K_ADMIN_URL . K_ADMIN_PAGE . '?o=drafts';
                $_p['subtitle'] = $FUNCS->t('edit');
            }
            $_p['show_advanced'] = $show_advanced_settings;
            $_p['content'] = $html;
            $FUNCS->render_admin_page_ex( $_p );

        }
        elseif( $_GET['act'] == 'delete' ){
            if( $tpl_id && $page_id ){
                $FUNCS->validate_nonce( 'delete_page_' . $page_id );

                $DB->begin();

                $rs = $DB->select( K_TBL_TEMPLATES, array('id, name, description, access_level'), "id='" . $DB->sanitize( $tpl_id ). "'" );
                if( !count($rs) )  die( 'ERROR: Template not found' );

                // serialize access.. lock template as this could involve working with nested pages tree.
                $DB->update( K_TBL_TEMPLATES, array('description'=>$DB->sanitize( $rs[0]['description'] )), "id='" . $DB->sanitize( $tpl_id ) . "'" );

                $PAGE = new KWebpage( $tpl_id, $page_id );
                if( $PAGE->error ){
                    ob_end_clean();
                    die( 'ERROR in deletion: ' . $PAGE->err_msg );
                }
                $PAGE->delete();
                $FUNCS->invalidate_cache();

                if( $PAGE->tpl_nested_pages ){
                    $PAGE->reset_weights_of( $PAGE->nested_parent_id );
                }

                $DB->commit();

                $qs = '?act=list&tpl='.$tpl_id;
                if( isset($_GET['fid']) ) $qs .= '&fid=' . intval($_GET['fid']);
                if( $cid && $rid ) $qs .= '&cid='.$cid.'&rid='.$rid;
                if( isset($_GET['pg']) ) $qs .= '&pg=' . intval($_GET['pg']);
                header("Location: ".K_ADMIN_URL . K_ADMIN_PAGE. $qs);
                exit;
            }
        }
        elseif( $_GET['act'] == 'list' ){
            if( $tpl_id ){
                // Any pages marked for deletion?
                if( isset($_POST['page-id']) && $_POST['bulk-action']=='delete' ){
                    $FUNCS->validate_nonce( 'bulk_action_page' );

                    $DB->begin();

                    $rs = $DB->select( K_TBL_TEMPLATES, array('id, name, description, access_level, nested_pages'), "id='" . $DB->sanitize( $tpl_id ). "'" );
                    if( !count($rs) )  die( 'ERROR: Template not found' );

                    // serialize access.. lock template as this could involve working with nested pages tree.
                    $DB->update( K_TBL_TEMPLATES, array('description'=>$DB->sanitize( $rs[0]['description'] )), "id='" . $DB->sanitize( $tpl_id ) . "'" );

                    foreach( $_POST['page-id'] as $v ){
                        if( $FUNCS->is_non_zero_natural($v) ){
                            $page_id = intval( $v );
                            $PAGE = new KWebpage( $tpl_id, $page_id );
                            if( $PAGE->error ){
                                ob_end_clean();
                                die( 'ERROR in deletion: ' . $PAGE->err_msg );
                            }

                            // execute action
                            $PAGE->delete();
                            $FUNCS->invalidate_cache();
                        }
                    }

                    if( $rs[0]['nested_pages'] ){
                        $PAGE->reset_weights_of(); // entire tree
                    }

                    $DB->commit();

                    $qs = '?act=list&tpl='.$tpl_id;
                    if( isset($_GET['fid']) ) $qs .= '&fid=' . intval($_GET['fid']);
                    if( isset($_GET['pg']) ) $qs .= '&pg=' . intval($_GET['pg']);
                    if( $cid && $rid ) $qs .= '&cid='.$cid.'&rid='.$rid;
                    header("Location: ".K_ADMIN_URL . K_ADMIN_PAGE. $qs);
                    exit;
                }
                elseif( isset($_POST['k_updown']) ){ // Move nestable pages up-down
                    $FUNCS->validate_nonce( 'updown_page_'.$tpl_id );
                    if( !$FUNCS->is_non_zero_natural($_POST['id']) || !$FUNCS->is_natural($_POST['dir'])){
                        die( 'ERROR: invalid input' );
                    }

                    $page_id = intval( $_POST['id'] );
                    $dir = intval( $_POST['dir'] );

                    $DB->begin();

                    $rs = $DB->select( K_TBL_TEMPLATES, array('id, name, description, access_level'), "id='" . $DB->sanitize( $tpl_id ). "'" );
                    if( !count($rs) )  die( 'ERROR: Template not found' );

                    // serialize access.. lock template before getting tree
                    $DB->update( K_TBL_TEMPLATES, array('description'=>$DB->sanitize( $rs[0]['description'] )), "id='" . $DB->sanitize( $tpl_id ) . "'" );

                    $tree = $FUNCS->get_nested_pages( $rs[0]['id'], $rs[0]['name'], $rs[0]['access_level'] );
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
                    $tree = $FUNCS->get_nested_pages( $rs[0]['id'], $rs[0]['name'], $rs[0]['access_level'], 'weightx', 'asc', 1 /*force*/ );

                    // return modified listing
                    echo k_admin_list_nested_pages( $rs[0], 1 ); // get without JS;

                    $DB->commit();
                    die;

                }

                // List pages
                $rs = $DB->select( K_TBL_TEMPLATES, array('*'), "id='" . $DB->sanitize( $tpl_id ). "'" );
                if( count($rs) ){
                    if( $rs[0]['clonable'] ){
                        // render
                        $_p = array();
                        $_p['module'] = 'pages';
                        $_p['tpl_name'] = $rs[0]['name'];
                        $_p['title'] = $rs[0]['title'] ? $rs[0]['title'] : $rs[0]['name'];
                        $_p['link'] = ( $rs[0]['clonable'] ) ? K_ADMIN_URL . K_ADMIN_PAGE . '?act=list&tpl=' . $rs[0]['id'] : '';
                        if( $cid && $rid && $rs[0]['clonable'] ) $_p['link'] .= '&cid='.$cid.'&rid='.$rid;
                        if( file_exists(K_SITE_DIR . $rs[0]['name']) ){
                            if( $AUTH->user->access_level >= $rs[0]['access_level'] ){
                                if( $rs[0]['gallery'] ){ // Gallery
                                    $fid = ( isset($_GET['fid']) && $FUNCS->is_non_zero_natural( $_GET['fid'] ) ) ? (int)$_GET['fid'] : 0;
                                    $fn = trim( $FUNCS->get_pretty_template_link_ex($rs[0]['name'], $dummy, 0), '/' );
                                    $_p['buttons'] = '<div id="bulk_upload"><a class="button nocurve smoothbox" href="'.K_ADMIN_URL.'upload.php?o=gallery&tpl='. $rs[0]['id'] .'&fid='. $fid;
                                    if( $cid && $rid ) $_p['buttons'] .= '&cid='.$cid.'&rid='.$rid;
                                    $_p['buttons'] .= '&fn='. $fn . '&TB_iframe=true&height=309&width=640&modal=true" title="'.$FUNCS->t('bulk_upload').'"><span>'.$FUNCS->t('bulk_upload').'</span></a></div>';
                                }
                                else{
                                    $_p['buttons'] = '<div id="create_new"><a class="button nocurve" href="'.K_ADMIN_URL . K_ADMIN_PAGE.'?act=create&tpl='. $rs[0]['id'];
                                    if( $cid && $rid ) $_p['buttons'] .= '&cid='.$cid.'&rid='.$rid;
                                    $_p['buttons'] .= '&nonce='.$FUNCS->create_nonce( 'create_page_'.$rs[0]['id'] ).'" title="'.$FUNCS->t('add_new_page').'"><span>'.$FUNCS->t('add_new').'</span></a></div>';
                                }
                            }
                            if( $rs[0]['dynamic_folders'] && !$rs[0]['nested_pages'] ){
                                $_p['buttons'] .= '<div id="view_folders"><a class="button nocurve" href="'.K_ADMIN_URL . K_ADMIN_PAGE.'?o=folders&tpl='. $tpl_id . '" title="'.$FUNCS->t('manage_folders').'"><span class="nocurve">'.$FUNCS->t('manage_folders').'</span></a></div>';
                            }
                            $_p['buttons'] .= '<div id="view_template"><a class="button" href="'.K_SITE_URL.$rs[0]['name'].'" target="_blank"><span class="nocurve">'.$FUNCS->t('view').'</span></a></div>';
                        }
                        $_p['subtitle'] = $FUNCS->t('list');
                        $_p['show_advanced'] = 0;

                        if( $rs[0]['nested_pages'] ){
                            $_p['content'] = k_admin_list_nested_pages( $rs[0] );
                        }
                        else{
                            $_p['content'] = k_admin_list_pages( $rs[0] );
                        }

                        $FUNCS->render_admin_page_ex( $_p );

                    }
                    else{
                        $nonce = $FUNCS->create_nonce( 'edit_page_'.$rs[0]['id'] );
                        $link = K_ADMIN_URL . K_ADMIN_PAGE . '?act=edit&tpl=' . $rs[0]['id'] .'&nonce='.$nonce;
                        header( "Location: " . $link );
                    }
                }
            }
        }
    }
    else{

        // No template specified. If templates available, select topmost template
        $rs = $DB->select( K_TBL_TEMPLATES, array('*'), '1=1 ORDER BY k_order, id ASC LIMIT 1' );
        if( count($rs) ){

            if( $rs[0]['clonable'] ){
                $link = K_ADMIN_URL . K_ADMIN_PAGE . '?act=list&tpl=' . $rs[0]['id'];
            }
            else{
                $nonce = $FUNCS->create_nonce( 'edit_page_'.$rs[0]['id'] );
                $link = K_ADMIN_URL . K_ADMIN_PAGE . '?act=edit&tpl=' . $rs[0]['id'] .'&nonce='.$nonce;
            }
            header( "Location: " . $link );
        }
        else{
            $_p = array();
            $_p['module'] = 'pages';
            $_p['title'] = $FUNCS->t('welcome');
            $_p['content'] = '<h4>'.$FUNCS->t('no_templates_defined').'</h4>';
            $FUNCS->render_admin_page_ex( $_p );
        }
    }

    // Given a clonable template record, lists all pages belonging to it
    function k_admin_list_pages( $tpl ){
        global $DB, $AUTH, $FUNCS, $TAGS, $CTX, $Config, $PAGE, $cid, $rid;

        // first check if any custom viewer registered for this template
        if( array_key_exists( $tpl['name'], $FUNCS->admin_list_views ) ){
            $snippet = $FUNCS->admin_list_views[$tpl['name']];
            if( defined('K_SNIPPETS_DIR') ){ // always defined relative to the site
                $base_snippets_dir = K_SITE_DIR . K_SNIPPETS_DIR . '/';
            }
            else{
                $base_snippets_dir = K_COUCH_DIR . 'snippets/';
            }

            $filepath = $base_snippets_dir . ltrim( trim($snippet), '/\\' );
            $html = @file_get_contents( $filepath );
            if( $html!==FALSE ){
                $PAGE = new KWebpage( $tpl['id'], null );
                if( $PAGE->error ){
                    ob_end_clean();
                    die( 'ERROR: ' . $PAGE->err_msg );
                }
                $parser = new KParser( $html );
                //$html = $parser->get_HTML();
                $html = $parser->get_cached_HTML( $filepath );

            }
            else{
                $html = 'ERROR: Unable to get contents from custom list_view <b>' . $filepath . '</b>';
            }
            return $html;
        }

        // proceed with the default logic
        $name = $tpl['title'] ? $tpl['title'] : $tpl['name'];
        $limit = 15;
        $pgn_pno = 1;
        if( isset($_GET['pg']) && $FUNCS->is_non_zero_natural( $_GET['pg'] ) ){
            $pgn_pno = (int)$_GET['pg'];
        }
        if( isset($_GET['fid']) && $FUNCS->is_non_zero_natural( $_GET['fid'] ) ){
            $fid = (int)$_GET['fid'];
        }

        if( $tpl['clonable'] ){

            $folders = &$FUNCS->get_folders_tree( $tpl['id'], $tpl['name'] );
            if( count($folders->children) ) {
                $has_folders = 1;
            }
            $arr_folders = array();
            if( $fid ){
                if( $has_folders ){
                    $folder = &$folders->find_by_id( $fid );
                    if( $folder ){
                        if( !$tpl['gallery'] ){
                            // get all the child folders of it. (except Gallery that shows content of only current folder)
                            $sub_folders = $folder->get_children(); //includes the parent folder too
                            foreach( $sub_folders as $sf ){
                                $arr_folders[$sf->name] = $sf->id;
                            }
                        }
                    }
                    else{
                        $fid = 0;
                    }
                }
                else{
                    $fid = 0;
                }
            }

            if( $tpl['gallery'] && !$fid ) $fid = '-1'; //root folder

            // Get pages derived from this template
            // formulate query
            $thumb_field = null;
            if( $tpl['gallery'] ){
                // get id of the field that holds the thumbnail
                $rs3 = $DB->select( K_TBL_FIELDS, array('id'), "template_id='" . $DB->sanitize( $tpl['id'] ). "' and name='gg_thumb'" );
                if( count($rs3) ){ $thumb_field=$rs3[0]['id']; }
            }

            $tables = K_TBL_PAGES.' p left outer join '.K_TBL_FOLDERS.' f on p.page_folder_id = f.id';
            $tables .= ' inner join '.K_TBL_TEMPLATES.' t on t.id = p.template_id';
            if( $thumb_field ){
                $tables .= ' inner join '.K_TBL_DATA_TEXT.' d on p.id = d.page_id';
            }
            if( $cid && $rid ){
                $tables .= ' inner join '.K_TBL_RELATIONS.' rel on rel.pid = p.id';
            }

            $sql = "p.template_id='" . $DB->sanitize( $tpl['id'] ). "'";
            $sql .= " AND p.parent_id=0";
            if( $thumb_field ){
                $sql .= " AND d.field_id='".$DB->sanitize( $thumb_field )."'";
            }
            if( $cid && $rid ){
                $sql .= " AND rel.cid='".$DB->sanitize($cid)."' AND rel.fid='".$DB->sanitize($rid)."'";
            }

            if( !$tpl['gallery'] ){
                if( count($arr_folders) ){
                    $sql .= " AND ";
                    $sql .= "(";
                    $sep = "";
                    foreach( $arr_folders as $k=>$v ){
                        $sql .= $sep . "p.page_folder_id='" . $DB->sanitize( $v )."'";
                        $sep = " OR ";
                    }
                    $sql .= ")";
                }
            }
            else{
                $sql .= " AND p.page_folder_id='" . $DB->sanitize( $fid )."'";
                if( $AUTH->user->access_level < K_ACCESS_LEVEL_SUPER_ADMIN ){
                    $sql .= " AND p.is_master <> 1";
                }
            }
            $sql .= " ORDER BY publish_date desc";

            // first query for pagination
            $rs = $DB->select( $tables, array('count(p.id) as cnt'), $sql );
            $total_rows = $rs[0]['cnt'];
            $total_pages = ceil( $total_rows/$limit );

            // actual query
            if( $pgn_pno>$total_pages && $total_pages>0 ) $pgn_pno=$total_pages;
            $limit_sql = sprintf( " LIMIT %d, %d", ($pgn_pno - 1) * $limit, $limit );
            $arr_fields = array('p.*', 'f.title', 'f.access_level as flevel', 't.access_level as tlevel');
            if( $thumb_field ){ $arr_fields[]='d.value as thumb'; }
            $rs2 = $DB->select( $tables, $arr_fields, $sql . $limit_sql );
            $count = count($rs2);

            // paginator
            $adjacents = 2;
            $targetpage = K_ADMIN_URL . K_ADMIN_PAGE . '?act=list&tpl=' . $tpl['id'];
            if( $fid ) $targetpage .= '&fid=' . $fid;
            if( $cid && $rid ) $targetpage .= '&cid=' . $cid . '&rid=' . $rid;
            $pagestring = "&pg=";
            $prev_text = '&#171; ' . $FUNCS->t('prev');
            $next_text = $FUNCS->t('next') . ' &#187;';
            $simple = 0;

            // record counts
            $total_records_on_page = ( $count<$limit ) ? $count : $limit;
            if( $total_records_on_page > 0 ){
                $first_record_on_page = ($limit * ($pgn_pno - 1)) + 1;
                $last_record_on_page = $first_record_on_page + $total_records_on_page - 1;
            }
            else{
                $first_record_on_page = $last_record_on_page = 0;
            }

            $str .= '<form name="frm_list_pages" id="frm_list_pages" action="" method="post">';

            // check for missing template
            if( !file_exists(K_SITE_DIR . $tpl['name']) ){
                $str .= '<div class="error" style="margin-bottom:10px;">';
                $str .= '<strong>'. $FUNCS->t('template_missing') .'</strong>';
                if( $AUTH->user->access_level >= K_ACCESS_LEVEL_SUPER_ADMIN ){
                    $rs3 = $DB->select( K_TBL_PAGES, array('id'), "template_id='" . $DB->sanitize( $tpl['id'] ). "'" );
                    if( count($rs3) ){
                        $str .= ' <i>('. $FUNCS->t('remove_template_completely') .')</i>';
                    }
                }
                $str .= '</div>';
            }

            if( $cid && $rid ){
                $str .= _get_rel_banner( $cid, $rid );
            }

            $str .= '<div class="wrap-paginator">';
            if( $has_folders ){
                $str .= '<div class="bulk-actions">';
                $CTX->push( '__ROOT__' );
                $html = '';
                $param2 = $fid;
                $folders->visit( array('KFolder', '_k_visitor'), $html, $param2, 0/*$depth*/, 0/*$extended_info*/, array()/*$exclude*/ );
                $CTX->pop();
                $root_folder = ( $tpl['gallery'] ) ? '--- '.$FUNCS->t('root').' ---' : $FUNCS->t('view_all_folders');
                $str .= '<select id="f_k_folders" name="f_k_folders"><option value="-1" >'.$root_folder.'</option>' .$html . '</select>';
                $link = K_ADMIN_URL . K_ADMIN_PAGE . '?act=list&tpl=' . $tpl['id'];
                if( $cid && $rid ) $link .= '&cid=' . $cid . '&rid=' . $rid;
                $str .= '<a class="button" id="btn_folder_submit" href="'.$link.'" onclick="this.style.cursor=\'wait\'; return false;"><span>'.$FUNCS->t('filter').'</span></a>';
                $str .= '</div>';
            }
            if( $total_rows > $limit ){
                $str_paginator = $FUNCS->getPaginationString( $pgn_pno, $total_rows, $limit, $adjacents, $targetpage, $pagestring, $prev_text, $next_text, $simple );
                $str_paginator .= "<div class='record-count'>".$FUNCS->t('showing')." $first_record_on_page-$last_record_on_page / $total_rows</div>";
                $str .= $str_paginator;
            }
            $str .= '</div>';

            if( $tpl['gallery'] ){
                $showing_related = ( $cid && $rid ) ? 1 : 0;

                $str .= '<div id="gallery" class="group-wrapper listing">';

                // Display the immediate child folders first
                $child_folder_count = 0;
                if( $pgn_pno==1 && $has_folders && !$showing_related ){
                    $root_folder = ( $folder ) ? $folder : $folders;
                    $child_folder_count = count($root_folder->children);
                    for( $x=0; $x<$child_folder_count; $x++ ){
                        $child_folder = $root_folder->children[$x];
                        $child_folder_name = ( $child_folder->title ) ? $child_folder->title : $child_folder->name;
                        $last_class = (($x+1)%5) ? '' : ' last';
                        $str .= '<div class="item'.$last_class.'">';
                        $str .= '<div class="item_inner folder">';
                        $str .= '<a style="background-image:url(\''.K_ADMIN_URL.'theme/images/folder.gif'.'\')" class="item_image folder" title="'.$child_folder_name.'" href="'.$link.'&fid='.$child_folder->id.'" ></a>';
                        $str .= '</div>';
                        $str .= '<div class="name">'.$child_folder_name.'</div>';
                        $str_qty = ($child_folder->consolidated_count==1) ? $FUNCS->t('item') : $FUNCS->t('items');
                        $str .= '<div class="time">'.$child_folder->consolidated_count.' '.$str_qty.'</div>';
                        $str_qty = ($child_folder->total_children==1) ? $FUNCS->t('container') : $FUNCS->t('containers');
                        $str .= '<div class="size">'.$child_folder->total_children.' '.$str_qty.'</div>';
                        $str .= '</div>';
                    }
                }

                if( !$count ){
                    if( !$child_folder_count ) $str .= '<div class="empty">'.$FUNCS->t('folder_empty').'</div>';
                }
                else{
                    for( $x=0; $x<$count; $x++ ){
                        $p = $rs2[$x];

                        // Count of drafts
                        $rs3 = $DB->select( K_TBL_PAGES, array('count(id) as cnt'), "parent_id='" . $DB->sanitize( $p['id'] ). "'" );
                        $count_drafts = $rs3[0]['cnt'];

                        // calculate effective access level
                        $access_level = $p['access_level'];
                        if( $p['flevel'] || $p['tlevel'] ){ // access level at template or folders will override page level access
                            if( is_null($p['flevel']) ) $p['flevel']=0;
                            $access_level = ( $p['flevel'] > $p['tlevel'] ) ? $p['flevel'] : $p['tlevel'];
                        }
                        $can_delete = ( $access_level <= $AUTH->user->access_level ) ? 1 : 0;

                        $last_class = (($x+1+$child_folder_count)%5) ? '' : ' last';
                        $str .= '<div class="item'.$last_class.'">';
                        $str .= '<div class="item_inner">';
                        if( $thumb_field && $p['thumb'] ){
                            $thumb_img = $p['thumb'];
                            if( $thumb_img{0}==':' ){
                                $thumb_img = substr( $thumb_img, 1 );
                                if( file_exists($Config['UserFilesAbsolutePath'] . 'image/' . $thumb_img) ){
                                    $thumb_img = $Config['k_append_url'] . $Config['UserFilesPath'] . 'image/' . $thumb_img;
                                }
                                else{
                                    $thumb_img = K_ADMIN_URL.'theme/images/exclaim.gif';
                                }
                            }
                        }
                        else{
                            $thumb_img = K_ADMIN_URL.'theme/images/exclaim.gif';
                        }
                        $abbr_title = (strlen($p['page_title'])>20) ? substr($p['page_title'], 0, 20) . '&hellip;' : $p['page_title'];
                        $update_link = K_ADMIN_URL . K_ADMIN_PAGE . '?act=edit&tpl='. $tpl['id'] .'&p='. $p['id'];
                        if( $showing_related ) $update_link .= '&cid='.$cid.'&rid='.$rid;
                        $update_link .= '&nonce='.$FUNCS->create_nonce( 'edit_page_'.$p['id'] );

                        $str .= '<a href="'.$update_link.'" title="'.$p['page_title'].'" class="item_image" style="background-image:url(\''.$thumb_img.'\')">';
                        $str .= '</a>';
                        // checkbox
                        $str .= '<span class="checkbox"><input type="checkbox" value="'.$p['id'].'" class="page-selector" name="page-id[]"';
                        if( !$can_delete || $count_drafts ) $str .= ' disabled="1"';
                        $str .= '/></span>';
                        // actions
                        $str .= '<div class="actions">';
                        $str .= '<a href="'. K_SITE_URL . $tpl['name'] .'?p='. $p['id'] .'" target="_blank" title="'.$FUNCS->t('view').'"><img src="'.K_ADMIN_URL.'theme/images/magnifier.gif"/></a>';
                        if( $can_delete && !$count_drafts ){
                            $nonce = $FUNCS->create_nonce( 'delete_page_'.$p['id'] );
                            $confirm_prompt = "onclick='if( confirm(\"".$FUNCS->t('confirm_delete_page').": ".$p['page_title']."?\") ) { return true; } return false;'";
                            $qs = '?act=delete&tpl='. $tpl['id'] .'&p='. $p['id'] .'&nonce='.$nonce;
                            if( isset($_GET['fid']) ) $qs .= '&fid=' . intval($_GET['fid']);
                            if( isset($_GET['pg']) ) $qs .= '&pg=' . intval($_GET['pg']);
                            if( $showing_related ) $qs .= '&cid='.$cid.'&rid='.$rid;
                            $str .= '<a href="'.K_ADMIN_URL . K_ADMIN_PAGE.$qs.'" '.$confirm_prompt.'><img src="'.K_ADMIN_URL.'theme/images/page_white_delete.gif" title="'.$FUNCS->t('delete').'"/></a>';
                        }
                        if( $count_drafts ){
                            $a_title = ( $count_drafts > 1 ) ? ' '.$FUNCS->t('drafts') : ' '.$FUNCS->t('draft');
                            $str .= '<a title="'.$count_drafts.$a_title.'" href="'.K_ADMIN_URL . K_ADMIN_PAGE.'?o=drafts&tpl='.$tpl['id'].'&pid='.$p['id'].'"><img src="'. K_ADMIN_URL. 'theme/images/page_white_stack.gif"></a>';
                        }
                        $str .= '<a href="'.$update_link.'"><img title="'.$FUNCS->t('edit').'" src="'.K_ADMIN_URL.'theme/images/page_white_edit.gif" /></a>';
                        $str .= '</div>';
                        $str .= '</div>';
                        $str .= '<div class="name">'.$abbr_title.'</div>';
                        $str .= '<div class="time">';
                        if( $p['publish_date'] != '0000-00-00 00:00:00' ){
                            $str .= date("M jS Y", strtotime($p['publish_date']));
                        }
                        else{
                            $str .= $FUNCS->t('unpublished');
                        }
                        $str .= '</div>';
                        $file_size = $p['file_size'];
                        if( !$file_size ) $file_size = 0;
                        if( $file_size > 0 ){
                            $file_size = round( $file_size / 1024 );
                            if ( $file_size < 1 ) $file_size = 1;
                        }
                        $str .= '<div class="size">'.$file_size.' KB</div>';
                        $str .= '</div>';
                    }
                    // select all
                    $str .= '<div style="clear:both;"></div>';
                    $str .= '<div class="select_all">';
                    $str .= '<label> ';
                    $str .= '<input type="checkbox" name="check-all" onClick="$$(\'.page-selector\').set(\'checked\', this.checked);" />';
                    $str .= '<strong>'. $FUNCS->t('select-deselect') .'</strong>';
                    $str .= '</label>&nbsp;';
                    $str .= '</div>';
                }
                $str .= '</div>';
            }
            else{
                $str .= '<div class="group-wrapper listing">';
                $str .= '<table class="listing clear" cellspacing="0" cellpadding="0">';
                $str .= '<thead>';
                $str .= '<th class="checkbox"><input type="checkbox" name="check-all" onClick="$$(\'.page-selector\').set(\'checked\', this.checked);" /></th>';
                $str .= '<th>'.$FUNCS->t('title').'</th>';
                $str .= '<th>&nbsp;</th>'; // count of drafts, comments
                $str .= '<th>'.$FUNCS->t('folder').'</th>';
                $str .= '<th>'.$FUNCS->t('date').'</th>';
                $str .= '<th>'.$FUNCS->t('actions').'</th>';
                $str .= '</thead>';
                if( !$count ){
                    $str .= '<tr><td colspan="6" class="last_row" style="text-align:center">'.$FUNCS->t('no_pages_found').'</td></tr>';
                }
                else{
                    for( $x=0; $x<$count; $x++ ){
                        $p = $rs2[$x];

                        // Count of drafts
                        $rs3 = $DB->select( K_TBL_PAGES, array('count(id) as cnt'), "parent_id='" . $DB->sanitize( $p['id'] ). "'" );
                        $count_drafts = $rs3[0]['cnt'];

                        // calculate effective access level
                        $access_level = $p['access_level'];
                        if( $p['flevel'] || $p['tlevel'] ){ // access level at template or folders will override page level access
                            if( is_null($p['flevel']) ) $p['flevel']=0;
                            $access_level = ( $p['flevel'] > $p['tlevel'] ) ? $p['flevel'] : $p['tlevel'];
                        }
                        $can_delete = ( $access_level <= $AUTH->user->access_level ) ? 1 : 0;

                        $str .= '<tr>';
                        if( $x>=$count-1 ) $last_row = " last_row";

                        // checkbox
                        $str .= '<td class="checkbox'.$last_row.'">';
                            $str .= '<input type="checkbox" value="'.$p['id'].'" class="page-selector" name="page-id[]"';
                            if( !$can_delete || $count_drafts ) $str .= ' disabled="1"';
                            $str .= '/>';
                        $str .= '</td>';

                        // page name
                        $str .= '<td class="name'.$last_row.'">';
                        if( $p['is_master'] && $AUTH->user->access_level >= K_ACCESS_LEVEL_SUPER_ADMIN ) $str .= '<i>';
                        $nonce = $FUNCS->create_nonce( 'edit_page_'.$p['id'] );
                        $edit_link = K_ADMIN_URL . K_ADMIN_PAGE.'?act=edit&tpl='. $tpl['id'] .'&p='. $p['id'];
                        if( $cid && $rid ) $edit_link .= '&cid='.$cid.'&rid='.$rid;
                        $edit_link .= '&nonce='.$nonce;
                        $abbr_title = (strlen($p['page_title'])>48) ? substr($p['page_title'], 0, 48) . '&hellip;' : $p['page_title'];
                        $str .= '<a href="'.$edit_link.'" title="'.$p['page_title'].'">'. $abbr_title .'</a>';
                        if( $p['is_master'] && $AUTH->user->access_level >= K_ACCESS_LEVEL_SUPER_ADMIN ) $str .= '</i>';
                        $str .= '</td>';

                        // drafts & comments
                        $str .= '<td class="comments-count'.$last_row.'">';
                        if( $count_drafts ){
                            $a_title = ( $count_drafts > 1 ) ? ' '.$FUNCS->t('drafts') : ' '.$FUNCS->t('draft');
                            $str .= '<span class="drafts-count"><a title="'.$count_drafts.$a_title.'" href="'.K_ADMIN_URL . K_ADMIN_PAGE.'?o=drafts&tpl='.$tpl['id'].'&pid='.$p['id'].'">'.$count_drafts.'<img src="'. K_ADMIN_URL. 'theme/images/page_white_stack.gif"></a></span>';
                        }

                        if( $p['comments_count'] ){
                            $a_title = ( $p['comments_count'] > 1 ) ? ' '.$FUNCS->t('comments') : ' '.$FUNCS->t('comment');
                            $str .= '<span class="comments-count"><a title="'.$p['comments_count'].$a_title.'" href="'.K_ADMIN_URL . K_ADMIN_PAGE.'?o=comments&page_id='.$p['id'].'">'.$p['comments_count'].'<img src="'. K_ADMIN_URL. 'theme/images/comments.gif"></a></span>';
                        }

                        if( !$count_drafts && !$p['comments_count'] ){
                            $str .= '&nbsp;';
                        }
                        $str .= '</td>';

                        // folder title
                        $str .= '<td class="folder'.$last_row.'">';
                        if( $p['title'] ){
                            $str .= $p['title'];
                        }
                        else{
                            $str .= '&nbsp;';
                        }
                        $str .= '</td>';

                        // date
                        $str .= '<td class="date'.$last_row.'">';
                        if( $p['publish_date'] != '0000-00-00 00:00:00' ){
                            $str .= date("M jS Y", strtotime($p['publish_date']));
                        }
                        else{
                            $str .= $FUNCS->t('unpublished');
                        }
                        $str .= '</td>';

                        // actions
                        $str .= '<td class="actions'.$last_row.'">';
                        $str .= '<a href="'.$edit_link.'"><img src="'.K_ADMIN_URL.'theme/images/page_white_edit.gif"  title="'.$FUNCS->t('edit').'"/></a>';
                        if( $can_delete && !$count_drafts ){
                            $nonce = $FUNCS->create_nonce( 'delete_page_'.$p['id'] );
                            $confirm_prompt = "onclick='if( confirm(\"".$FUNCS->t('confirm_delete_page').": ".$p['page_title']."?\") ) { return true; } return false;'";
                            $qs = '?act=delete&tpl='. $tpl['id'] .'&p='. $p['id'] .'&nonce='.$nonce;
                            if( isset($_GET['fid']) ) $qs .= '&fid=' . intval($_GET['fid']);
                            if( isset($_GET['pg']) ) $qs .= '&pg=' . intval($_GET['pg']);
                            if( $cid && $rid ) $qs .= '&cid='.$cid.'&rid='.$rid;
                            $str .= '<a href="'.K_ADMIN_URL . K_ADMIN_PAGE.$qs.'" '.$confirm_prompt.'><img src="'.K_ADMIN_URL.'theme/images/page_white_delete.gif" title="'.$FUNCS->t('delete').'"/></a>';
                        }
                        $str .= '<a href="'. K_SITE_URL . $tpl['name'] .'?p='. $p['id'] .'" target="_blank" title="'.$FUNCS->t('view').'"><img src="'.K_ADMIN_URL.'theme/images/magnifier.gif"/></a>';

                        $str .= '</td>';
                        $str .= '</tr>';
                    }
                }
                $str .= '</table>';
                $str .= '</div>';
            }

            $str .= '<div class="wrap-paginator">';
            if( $count ){
                $str .= '<div class="bulk-actions">';
                $str .= '<a class="button" id="btn_bulk_submit" href="#"><span>'.$FUNCS->t('delete_selected').'</span></a>';
                $str .= '</div>';
            }
            else{
                if( $AUTH->user->access_level >= K_ACCESS_LEVEL_SUPER_ADMIN ){
                    if( !file_exists(K_SITE_DIR . $tpl['name']) ){
                        // make sure no drafts or pages exist before prompting for template removal
                        $rs3 = $DB->select( K_TBL_PAGES, array('id'), "template_id='" . $DB->sanitize( $tpl['id'] ). "'" );
                        if( !count($rs3) ){
                            $str .= '<a class="button" href="javascript:k_delete_template('.$tpl['id'].', \''.$FUNCS->create_nonce( 'delete_tpl_'.$tpl['id'] ).'\')" title="'.$FUNCS->t('remove_template').'"><span>'.$FUNCS->t('remove_template').'</span></a>';
                        }
                    }
                }
            }
            $str .= $str_paginator;
            $str .= '</div>';
            $str .= '<input type="hidden" id="nonce" name="nonce" value="'.$FUNCS->create_nonce( 'bulk_action_page' ).'" />';
            $str .= '<input type="hidden" id="bulk-action" name="bulk-action" value="delete" />';
            $str .= '</form>';

            // Associated JavaScript
            if( !$tpl['gallery'] ){
                $str .= k_admin_js( 0 );
            }
            else{
                $str .= k_admin_js( 2 );
            }
        }
        return $str;
    }

    // Given a clonable template record, lists all nested pages belonging to it
    function k_admin_list_nested_pages( $tpl, $no_js=0 ){
        global $DB, $AUTH, $FUNCS, $CTX;

        $limit = 50;
        $pgn_pno = 1;
        if( isset($_GET['pg']) && $FUNCS->is_non_zero_natural( $_GET['pg'] ) ){
            $pgn_pno = (int)$_GET['pg'];
        }

        $tree = $FUNCS->get_nested_pages( $tpl['id'], $tpl['name'], $tpl['access_level'], 'weightx', 'asc' ); //note 'x' in weightx .. it is the stringified integer
        $total_rows = $tree->total_children;
        $total_pages = ceil( $total_rows/$limit );
        if( $pgn_pno>$total_pages && $total_pages>0 ) $pgn_pno=$total_pages;
        $adjacents = 2;
        $targetpage = K_ADMIN_URL . K_ADMIN_PAGE . '?act=list&tpl=' . $tpl['id'];
        if( $fid ) $targetpage .= '&fid=' . $fid;
        $pagestring = "&pg=";
        $prev_text = '&#171; ' . $FUNCS->t('prev');
        $next_text = $FUNCS->t('next') . ' &#187;';
        $simple = 0;

        // record counts
        $first_record_on_page = ($limit * ($pgn_pno - 1)) + 1;
        $last_record_on_page = $first_record_on_page + $limit - 1;
        if( $last_record_on_page > $total_rows ) $last_record_on_page = $total_rows;
        $total_records_on_page = $last_record_on_page - ( $first_record_on_page-1 );

        $str .= '<div id="pages-listing">'; // AJAX container
        $str .= '<form name="frm_list_pages" id="frm_list_pages" action="" method="post">';

        // check for missing template
        if( !file_exists(K_SITE_DIR . $tpl['name']) ){
            $str .= '<div class="error" style="margin-bottom:10px;">';
            $str .= '<strong>'. $FUNCS->t('template_missing') .'</strong>';
            if( $AUTH->user->access_level >= K_ACCESS_LEVEL_SUPER_ADMIN ){
                $rs3 = $DB->select( K_TBL_PAGES, array('id'), "template_id='" . $DB->sanitize( $tpl['id'] ). "'" );
                if( count($rs3) ){
                    $str .= ' <i>('. $FUNCS->t('remove_template_completely') .')</i>';
                }
            }
            $str .= '</div>';
        }

        $str .= '<div class="wrap-paginator">';
        if( $total_rows > $limit ){
            $str_paginator = $FUNCS->getPaginationString( $pgn_pno, $total_rows, $limit, $adjacents, $targetpage, $pagestring, $prev_text, $next_text, $simple );
            $str_paginator .= "<div class='record-count'>".$FUNCS->t('showing')." $first_record_on_page-$last_record_on_page / $total_rows</div>";
            $str .= $str_paginator;
        }
        $str .= '</div>';

        $str .= '<div class="group-wrapper listing">';
        $str .= '<table class="listing clear nested" cellspacing="0" cellpadding="0">';
        $str .= '<thead>';
        $str .= '<th class="checkbox"><input type="checkbox" name="check-all" onClick="$$(\'.page-selector\').set(\'checked\', this.checked);" /></th>';
        $str .= '<th>'.$FUNCS->t('title').'</th>';
        $str .= '<th>&nbsp;</th>'; //drafts and coments
        $str .= '<th colspan="2">'.$FUNCS->t('actions').'</th>';
        $str .= '</thead>';

        // output rows
        $CTX->push( '__ROOT__' );
        $html = '';
        $param = new stdClass;
        $param->_from = $first_record_on_page;
        $param->_to = $last_record_on_page;
        $param->_total = $total_records_on_page;
        $param->_counter = 0;
        $tree->visit( '_k_visitor2', $html, $param, 0/*$depth*/, 0/*$extended_info*/, array()/*$exclude*/, 0, 0, 0, 1/*paginate*/ );
        $CTX->pop();
        if( !$param->_counter ){
            $str .= '<tr>';
            $str .= '<td colspan="5" class="last_row" style="text-align:center">';
            $str .= $FUNCS->t('no_pages_found');
            $str .= '</td>';
            $str .= '<tr>';
            }
        else{
            $str .= $html;
        }

        $str .= '</table>';
        $str .= '</div>';

        $str .= '<div class="wrap-paginator">';
        if( $param->_counter ){
            $str .= '<div class="bulk-actions">';
            $str .= '<a class="button" id="btn_bulk_submit" href="#"><span>'.$FUNCS->t('delete_selected').'</span></a>';
            $str .= '</div>';
        }
        else{
            if( $AUTH->user->access_level >= K_ACCESS_LEVEL_SUPER_ADMIN ){
                if( !file_exists(K_SITE_DIR . $tpl['name']) ){
                    // make sure no drafts or pages exist before prompting for template removal
                    $rs3 = $DB->select( K_TBL_PAGES, array('id'), "template_id='" . $DB->sanitize( $tpl['id'] ). "'" );
                    if( !count($rs3) ){
                        $str .= '<a class="button" href="javascript:k_delete_template('.$tpl['id'].', \''.$FUNCS->create_nonce( 'delete_tpl_'.$tpl['id'] ).'\')" title="'.$FUNCS->t('remove_template').'"><span>'.$FUNCS->t('remove_template').'</span></a>';
                    }
                }
            }
        }
        $str .= $str_paginator;
        $str .= '</div>';
        $str .= '<input type="hidden" id="nonce" name="nonce" value="'.$FUNCS->create_nonce( 'bulk_action_page' ).'" />';
        $str .= '</form>';
        $str .= '</div>';

        if( !$no_js ){
            // Associated JavaScript
            $str .= k_admin_js( 1 );
        }

        return $str;
    }

    function _k_visitor2( &$page, &$html, &$param ){
        global $CTX, $FUNCS;

        $cur = $param->_counter;

        $level = $CTX->get('k_level', 1);

        // if first page displayed not of top level, output all its parents first
        if( $cur==$param->_from && $level ){
            $arr_parents = $page->root->get_parents_by_id( $page->id );
            $level2 = 0;
            for( $x=count($arr_parents)-1; $x>0; $x-- ){
                $html .= _render_row( $arr_parents[$x], $level2 );
                $level2++;
            }
        }

        // output row being visited
        $tr_class = ( $cur==$param->_to ) ? 'last_row' : '';
        $html .= _render_row( $page, $level, $tr_class );

    }

    function _render_row( &$f, $level, $tr_class='' ){
        global $FUNCS, $AUTH;

        for( $x=0; $x<$level; $x++ ){
            $pad .= '- &nbsp;&nbsp;&nbsp;';
            $len_pad += 3;
        }

        $update_link = '?act=edit&tpl='. $f->template_id .'&p='. $f->id .'&nonce='.$FUNCS->create_nonce( 'edit_page_'.$f->id );
        $delete_link = '?act=delete&tpl='. $f->template_id .'&p='. $f->id .'&nonce='.$FUNCS->create_nonce( 'delete_page_'.$f->id );
        $view_link = $f->template_name .'?p='. $f->id;

        // effective access level
        $access_level = ( $template_access_level ) ? $template_access_level : $f->access_level;
        $can_delete = ( $access_level <= $AUTH->user->access_level ) ? 1 : 0;

        ob_start();
        ?>
        <tr>
            <td class="checkbox <?php echo $tr_class; ?>">
            <input type="checkbox" name="page-id[]" class="page-selector" value="<?php echo $f->id; ?>" <?php if( !$can_delete || $f->drafts_count ) echo 'disabled="1"';?>/>
            </td>
            <td class="name <?php echo $tr_class; ?> ">
                <?php
                    $avail = 60;
                    if( $len_pad+strlen($f->title) > $avail ){
                        $abbr_title = ( ($len_pad<$avail) ? substr($f->title, 0, $avail-$len_pad) : substr($pad, 0, $len_pad-$avail) ). '&hellip;';
                    }
                    else{
                        $abbr_title = $f->title;
                    }
                ?>
                <?php echo $pad; ?> <span <?php if( $f->publish_date=='0000-00-00 00:00:00' ) {echo 'class=unpublished';} ?>><a href="<?php echo $update_link; ?>" title="[<?php echo $f->weight; ?>] <?php echo $f->title; ?>"><?php echo $abbr_title; ?></a></span>
                <?php if( !$f->show_in_menu ) echo '&nbsp;<img src="'.K_ADMIN_URL .'theme/images/menu.gif" title="'.$FUNCS->t('not_shown_in_menu').'" />'; ?>
                <?php if( $f->is_pointer ){
                    if( $f->masquerades ){
                        echo '&nbsp;<a href="#" onclick="return false" title="'.$FUNCS->t('masquerades'). ': '. $f->pointer_link_detail['masterpage']. '"><img src="'.K_ADMIN_URL .'theme/images/hand.gif" /></a>';
                        $admin_link = $f->get_admin_link();
                        if( $admin_link ) echo '&nbsp;<span class="pointer_links">'. $admin_link .'</span>';
                    }
                    else{
                        echo '&nbsp;<a href="'.$f->pointer_link.'" target="_blank" title="'.$FUNCS->t('points_to'). ': '. $f->pointer_link. '"><img src="'.K_ADMIN_URL .'theme/images/hand.gif" /></a>';
                    }
                }
                ?>
            </td>

            <td class="comments-count <?php echo $tr_class; ?>">
                <?php
                if( $f->drafts_count ){
                    $a_title = ( $f->drafts_count > 1 ) ? ' '.$FUNCS->t('drafts') : ' '.$FUNCS->t('draft');
                    echo '<span class="drafts-count"><a title="'.$f->drafts_count.$a_title.'" href="'.K_ADMIN_URL . K_ADMIN_PAGE.'?o=drafts&tpl='.$f->template_id.'&pid='.$f->id.'">'.$f->drafts_count.'<img src="'. K_ADMIN_URL. 'theme/images/page_white_stack.gif"></a></span>';
                }

                if( $f->comments_count ){
                    $a_title = ( $f->comments_count > 1 ) ? ' '.$FUNCS->t('comments') : ' '.$FUNCS->t('comment');
                    echo '<span class="comments-count"><a title="'.$f->comments_count.$a_title.'" href="'.K_ADMIN_URL . K_ADMIN_PAGE.'?o=comments&page_id='.$f->id.'">'.$f->comments_count.'<img src="'. K_ADMIN_URL. 'theme/images/comments.gif"></a></span>';
                }

                if( !$f->drafts_count && !$f->comments_count ){
                    echo '&nbsp;';
                }
                ?>
            </td>

            <td class="up-down <?php echo $tr_class; ?>">
                <?php if($f->pos!=0): ?>
                    <a class="up" href="#" onclick="k_updown( <?php echo $f->id; ?>, 1 ); return false;" title="<?php echo $FUNCS->t('up'); ?>"><img src="<?php echo K_ADMIN_URL; ?>theme/images/up.gif"></a>
                <?php else: ?>
                    <a class="up" href="#" onclick="return false;"><img src="<?php echo K_ADMIN_URL; ?>theme/images/up_blank.gif"></a>
                <?php endif; ?>

                <?php if($f->pos!=$f->total_siblings-1): ?>
                    <a class="down" href="#" onclick="k_updown( <?php echo $f->id; ?>, 0 ); return false;" title="<?php echo $FUNCS->t('down'); ?>"><img src="<?php echo K_ADMIN_URL; ?>theme/images/down.gif"></a>
                <?php else: ?>
                    <a class="down" href="#" onclick="return false;"><img src="<?php echo K_ADMIN_URL; ?>theme/images/down_blank.gif"></a>
                <?php endif; ?>
            </td>

            <td class="actions <?php echo $tr_class; ?>">
                <a href="<?php echo $update_link; ?>"><img title="<?php echo $FUNCS->t('edit'); ?>" src="<?php echo K_ADMIN_URL; ?>theme/images/page_white_edit.gif"/></a>
                <?php
                    if( $can_delete && !$f->drafts_count ){
                        $confirm_prompt = 'onclick="if( confirm(\''.$FUNCS->t('confirm_delete_page').': '.$f->name.'?\') ) { return true; } return false;"';
                        $qs = $delete_link;
                        if( isset($_GET['pg']) ) $qs .= '&pg=' . $_GET['pg'];
                        echo '<a href="'.K_ADMIN_URL . K_ADMIN_PAGE.$qs.'" '.$confirm_prompt.'><img src="'.K_ADMIN_URL.'theme/images/page_white_delete.gif" title="'.$FUNCS->t('delete').'"/></a>';
                    }
                    echo '<a href="'. K_SITE_URL . $view_link .'" target="_blank" title="'.$FUNCS->t('view').'"><img src="'.K_ADMIN_URL.'theme/images/magnifier.gif"/></a>';
                ?>
            </td>
        </tr>
        <?php
        $html .= ob_get_contents();
        ob_end_clean();

        return $html;
    }

    function k_admin_js( $type ){
        global $FUNCS, $tpl_id;

        if( $type==1 ) $nested_pages= 1;
        if( $type==2 ) $gallery= 1;
        ob_start();
        ?>
        <script type="text/javascript">
            //<![CDATA[
            function k_delete_template( tpl, nonce ){
                var qs = 'ajax.php?act=delete-tpl&tpl='+tpl+'&nonce='+encodeURIComponent( nonce );
                var requestHTMLData = new Request (
                    {
                        url: qs,
                        onComplete: function(response){
                            if( response=='OK' ){
                                document.location.href = "<?php echo K_ADMIN_URL . K_ADMIN_PAGE; ?>";
                            }
                            else{
                                alert(response);
                            }
                        }
                    }
                ).send();
            }

            window.addEvent('domready', function(){
                if( $('btn_folder_submit') ){
                    $('btn_folder_submit').addEvent('click', function(e){
                        var link = this.href
                        var fid = $('f_k_folders').value;
                        if( fid != -1 ){
                            link += '&fid=' + fid;
                        }
                        document.location.href = link;
                    });
                }
            });

            function k_hook_bulk_submit(){
                if( $('btn_bulk_submit') ){
                    $('btn_bulk_submit').addEvent('click', function(e){
                        var col = $$('.page-selector');
                        for( var x=0; x<col.length; x++ ){
                            if( col[x].checked ){
                                if( confirm('<?php echo $FUNCS->t('confirm_delete_selected_pages'); ?>') ){
                                    $$('body').setStyle('cursor', 'wait');
                                    $('frm_list_pages').submit();
                                }
                                return false;
                            }
                        }
                        return false;
                        });
                }
            }
            window.addEvent( 'domready', k_hook_bulk_submit );

            <?php if( $nested_pages ): ?>
            function k_updown( id, dir ){
                new Request.HTML({
                    url: '<?php echo K_ADMIN_URL . K_ADMIN_PAGE . '?act=list&tpl='.$tpl_id; ?><?php if(isset($_GET['pg']) && $FUNCS->is_non_zero_natural( $_GET['pg'])) echo '&pg='.$_GET['pg']; ?>',
                    method: 'post',
                    data: {
                        k_updown: 1,
                        id: id,
                        dir: dir,
                        nonce: encodeURIComponent( '<?php echo $FUNCS->create_nonce( 'updown_page_'.$tpl_id ); ?>' )
                    },
                    update: $('pages-listing'),
                    onRequest: function() {
                        $("k_overlay").setStyle('display', 'block');
                    },
                    onSuccess: function() {
                        $("k_overlay").setStyle('display', 'none');
                    },
                    onComplete: function() {
                        $("k_overlay").setStyle('display', 'none');
                        k_hook_bulk_submit();
                    }
                }).send();
            }

            window.addEvent('domready', function(){
                new Element('div').setProperty('id', 'k_overlay').injectInside(document.body);
                $('k_overlay').setOpacity(0.2);

                $("k_overlay").setStyles({
                    "height": '0px',
                    "width": '0px'
                });
                $("k_overlay").setStyles({
                    "height": window.getScrollHeight() + 'px',
                    "width": window.getScrollWidth() + 'px',
                    "background-color": '#fff',
                    "z-index": 10000,
                    "position": 'absolute',
                    "top": 0,
                    "left": 0,
                    "display": 'none'
                });

                window.onresize = function(){
                    $("k_overlay").setStyles({
                        "height": window.getScrollHeight() + 'px',
                        "width": window.getScrollWidth() + 'px'
                    });
                }
            });
            <?php endif; ?>

            <?php if( $gallery ): ?>
            function k_bulk_upload_result( res ){
                TB_remove();
                res = res.trim();
                if( res.length ){
                    alert( "ERROR!\n\n" + res );
                }
                window.location.reload( true );
            }
            window.addEvent('domready', function(){
                if( $('btn_folder_submit') ){
                    $('btn_folder_submit').setStyle('display', 'none');
                    $('f_k_folders').addEvent('change', function(e){
                        var link = $('btn_folder_submit').href;
                        var fid = $('f_k_folders').value;
                        if( fid != -1 ){
                            link += '&fid=' + fid;
                        }
                        document.location.href = link;
                    });
                }
            });
            <?php endif; ?>

            //]]>
        </script>
        <?php
        $str = ob_get_contents();
        ob_end_clean();

        return $str;
    }

    function _get_rel_banner( $cid, $rid ){
        global $DB, $FUNCS;

        $rel_tables = K_TBL_PAGES . ' p' . "\r\n";
        $rel_tables .= 'inner join ' . K_TBL_TEMPLATES . ' t on t.id = p.template_id' . "\r\n";
        $rel_sql = "p.parent_id=0 AND p.id='" . $DB->sanitize( $cid ). "' limit 1";

        $rs3 = $DB->select( $rel_tables, array('page_title', 'template_id', 'name', 'title'), $rel_sql );
        if( count($rs3) ){
            $rel_tpl = ( $rs3[0]['title'] ) ? $rs3[0]['title'] : $rs3[0]['name'];
            $rel_link = K_ADMIN_URL . K_ADMIN_PAGE.'?act=edit&tpl='. $rs3[0]['template_id'] .'&p='. $cid .'&nonce='.$FUNCS->create_nonce( 'edit_page_'.$cid );
            $rel_str = $rel_tpl . ' / <a href="'.$rel_link.'" style="text-decoration:underline"> '.$rs3[0]['page_title'].'</a>';
        }
        else{
            $rel_str = '???';
        }
        $str = '<div style="margin-bottom: 10px;" class="notice">';
        $str .= '<strong>Related to: </strong>' . $rel_str . '</div>';

        return $str;
    }
