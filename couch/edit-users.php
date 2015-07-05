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
        $user_id = ( isset($_GET['id']) && $FUNCS->is_non_zero_natural($_GET['id']) ) ? (int)$_GET['id'] : null;

        if( ($_GET['act'] == 'edit') || ($_GET['act'] == 'create') ){

            if( $user_id || (!$user_id && ($_GET['act'] == 'create')) ){
                if( $_GET['act'] == 'create' ){
                    $FUNCS->validate_nonce( 'create_user' );
                }
                else{
                    $FUNCS->validate_nonce( 'update_user_' . $user_id );
                }

                $user = new KUser( $user_id, 1 );
                $user->populate_fields(); // get values from database into fields

                $errors = '';
                if( isset($_POST['op']) && $_POST['op']=='save' ){
                    $_POST['f_k_access_level'] = intval( $_POST['f_k_levels_list'] );
                    $_POST['f_k_disabled'] = ( isset($_POST['f_k_disabled_check']) ) ? 1 : 0;

                    // HOOK: alter_edit_user_posted_data
                    $skip = $FUNCS->dispatch_event( 'alter_edit_user_posted_data', array(&$user) );

                    if( !$skip ){
                        for( $x=0; $x<count($user->fields); $x++ ){
                            $f = &$user->fields[$x];
                            $f->store_posted_changes( $_POST['f_'.$f->name] ); // get posted values into fields
                        }
                    }

                    // HOOK: edit_user_presave
                    $FUNCS->dispatch_event( 'edit_user_presave', array(&$user) );

                    $errors = $user->save();

                    // HOOK: edit_user_saved
                    $FUNCS->dispatch_event( 'edit_user_saved', array(&$user, &$errors) );

                    if( !$errors ){
                        // if the logged-in user is the same as the user account being edited, use the user object's name in nonce as it might have changed.
                        $nonce = $FUNCS->create_nonce( 'update_user_'.$user->id, ($AUTH->user->id==$user->id)?$user->name:$AUTH->user->name );
                        header("Location: ".K_ADMIN_URL . K_ADMIN_PAGE."?o=users&act=edit&id=".$user->id."&nonce=".$nonce);
                        exit;
                    }
                }

                // start building content for output
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

                <form name="frm_edit_user" id="frm_edit_user" action="" method="post" accept-charset="<?php echo K_CHARSET; ?>">
                    <div id="admin-content">
                        <?php
                            for( $x=0; $x<count($user->fields); $x++ ){
                                echo $user->fields[$x]->render() .'<p>';
                            }
                        ?>

                        <div style="display:<?php if( $user->id == $AUTH->user->id ) {echo 'none';} else {echo 'block';} ?>">
                            <label><b><?php echo $FUNCS->t('role'); ?>:</b></label><br>
                            <?php
                               echo '<p>' . $FUNCS->access_levels_dropdown( $user->access_level, $AUTH->user->access_level - 1, 1 );
                               echo '<p>';
                            ?>
                            <label><b><?php echo $FUNCS->t('disabled'); ?>:</b></label><br>
                            <input type="checkbox" name="f_k_disabled_check" value="1"  <?php if( $user->disabled ) echo 'checked="1"'; ?>/>
                        </div>

                        <input type="hidden" name="op" value="save" />
                        <a class="button" id="btn_submit" href="#" onclick="this.style.cursor='wait'; this.fireEvent('my_submit'); window.onbeforeunload=null; $('frm_edit_user').submit(); return false;"><span><?php echo $FUNCS->t('save'); ?></span></a>
                    </div>
                </form>
                
                <script type="text/javascript">
                    //<![CDATA[
                    <?php $k_form_id='frm_edit_user'; require_once( K_COUCH_DIR.'theme/prompt_unsaved.php' );?>
                    //]]>
                </script>
                <?php

                $html = ob_get_contents();
                ob_end_clean();

                // render
                $_p = array();
                $_p['module'] = 'users';
                $_p['title'] = ucwords( $FUNCS->t('users') );
                $_p['link'] = K_ADMIN_URL . K_ADMIN_PAGE . '?o=users';
                if( $_GET['act'] != 'create' ){
                    $_p['buttons'] = '<div id="create_new"><a class="button" href="'.K_ADMIN_URL . K_ADMIN_PAGE.'?o=users&act=create&nonce='.$FUNCS->create_nonce( 'create_user' ).'" title="'.$FUNCS->t('add_new_user').'"><span>'.$FUNCS->t('add_new').'</span></a></div>';
                }
                $_p['subtitle'] = ( $_GET['act'] == 'create' ) ? $FUNCS->t('add_new') : $FUNCS->t('edit');
                $_p['show_advanced'] = 0;
                $_p['content'] = $html;
                $FUNCS->render_admin_page_ex( $_p );

            }
        }
        elseif( $_GET['act'] == 'delete' ){
            if( $user_id ){
                $FUNCS->validate_nonce( 'delete_user_' . $user_id );
                $user = new KUser( $user_id, 1 );
                $user->delete();

                $qs = '?o=users';
                if( isset($_GET['pg']) ) $qs .= '&pg=' . intval($_GET['pg']);
                header("Location: ".K_ADMIN_URL . K_ADMIN_PAGE. $qs);
                exit;
            }
        }
    }
    else{
        // Any users marked for deletion?
        if( isset($_POST['user-id']) ){
            $FUNCS->validate_nonce( 'bulk_action_user' );

            foreach( $_POST['user-id'] as $v ){
                if( $FUNCS->is_non_zero_natural($v) ){
                    $user_id = intval( $v );
                    $user = new KUser( $user_id, 1 );

                    // execute action
                    $user->delete();
                }
            }
        }

        // list users
        $_p = array();
        $_p['module'] = 'users';
        $_p['title'] = ucwords( $FUNCS->t('users') );
        $_p['link'] = K_ADMIN_URL . K_ADMIN_PAGE . '?o=users';
        $_p['buttons'] = '<div id="create_new"><a class="button" href="'.K_ADMIN_URL . K_ADMIN_PAGE.'?o=users&act=create&nonce='.$FUNCS->create_nonce( 'create_user' ).'" title="'.$FUNCS->t('add_new_user').'"><span>'.$FUNCS->t('add_new').'</span></a></div>';
        $_p['subtitle'] = $FUNCS->t('list');
        $_p['show_advanced'] = 0;
        $_p['content'] = k_admin_list_users();
        $FUNCS->render_admin_page_ex( $_p );
    }

    function k_admin_list_users(){
        global $PAGE, $DB, $AUTH, $FUNCS;

        $limit = 15;
        $pgn_pno = 1;
        if( isset($_GET['pg']) && $FUNCS->is_non_zero_natural( $_GET['pg'] ) ){
            $pgn_pno = (int)$_GET['pg'];
        }

        $tables = K_TBL_USERS .' as u, '. K_TBL_USER_LEVELS .' as lvl';
        $fields = array('u.id as id', 'u.name as name', 'u.title as title',
                                'u.email as email', 'u.system as is_system',
                                'lvl.title as level_str', 'lvl.k_level as level');
        $sql = 'u.access_level = lvl.k_level ORDER BY u.access_level DESC, u.name ASC';

        // first query for pagination
        $rs = $DB->select( $tables, array('count(u.id) as cnt'), $sql );
        $total_rows = $rs[0]['cnt'];
        $total_pages = ceil( $total_rows/$limit );

        // actual query
        if( $pgn_pno>$total_pages && $total_pages>0 ) $pgn_pno=$total_pages;
        $limit_sql = sprintf( " LIMIT %d, %d", ($pgn_pno - 1) * $limit, $limit );
        $rs = $DB->select( $tables, $fields, $sql . $limit_sql );
        $count = count($rs);

        // paginator
        $adjacents = 2;
        $targetpage = K_ADMIN_URL . K_ADMIN_PAGE.'?o=users';
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

        $str .= '<form name="frm_list_users" id="frm_list_users" action="" method="post">';
        $str .= '<div class="wrap-paginator">';
        if( $total_rows > $limit ){
            $str_paginator = $FUNCS->getPaginationString( $pgn_pno, $total_rows, $limit, $adjacents, $targetpage, $pagestring, $prev_text, $next_text, $simple );
            $str_paginator .= "<div class='record-count'>".$FUNCS->t('showing')." $first_record_on_page-$last_record_on_page / $total_rows</div>";
            $str .= $str_paginator;
        }
        $str .= '</div>';

        $str .= '<div class="group-wrapper listing">';
        $str .= '<table class="listing clear" cellspacing="0" cellpadding="0">';
        $str .= '<thead>';
        $str .= '<th class="checkbox"><input type="checkbox" name="check-all" onClick="$$(\'.user-selector\').set(\'checked\', this.checked);" /></th>';
        $str .= '<th>'.$FUNCS->t('name').'</th>';
        $str .= '<th>'.$FUNCS->t('display_name').'</th>';
        $str .= '<th>'.$FUNCS->t('email').'</th>';
        $str .= '<th>'.$FUNCS->t('role').'</th>';
        $str .= '<th>'.$FUNCS->t('actions').'</th>';
        $str .= '</thead>';
        if( !$count ){
            $str .= '<tr><td colspan="6" class="last_row" style="text-align:center">'.$FUNCS->t('no_users_found').'</td></tr>';
        }
        else{
            for( $x=0; $x<$count; $x++ ){
                $u = $rs[$x];

                $str .= '<tr>';
                if( $x>=$count-1 ) $last_row = " last_row";

                // checkbox
                $str .= '<td class="checkbox'.$last_row.'">';
                $str_disabled = '';
                if( $AUTH->user->access_level <= $u['level'] ){
                    $str_disabled = 'disabled="1"';
                }
                $str .= '<input '.$str_disabled.' type="checkbox" value="'.$u['id'].'" class="user-selector" name="user-id[]"/>';
                $str .= '</td>';

                // user name
                $str .= '<td class="user_name'.$last_row.'">';
                if( ($AUTH->user->access_level > $u['level']) || ($AUTH->user->access_level == $u['level'] && $AUTH->user->id == $u['id'])){
                    $nonce = $FUNCS->create_nonce( 'update_user_'.$u['id'] );
                    $str .= '<a href="'.K_ADMIN_URL . K_ADMIN_PAGE.'?o=users&act=edit&id='.$u['id'].'&nonce='.$nonce.'">'.$u['name'] .'</a>';
                }
                else{
                    $str .= $u['name'];
                }
                $str .= '</td>';

                // display name
                $str .= '<td class="display-name'.$last_row.'">';
                $str .= $u['title'];
                $str .= '</td>';

                // email
                $str .= '<td class="email'.$last_row.'">';
                $str .= $u['email'];
                $str .= '</td>';

                // role
                $str .= '<td class="role'.$last_row.'">';
                $str .= $u['level_str'];
                $str .= '</td>';

                // actions
                $str .= '<td class="actions'.$last_row.'">';
                if( ($AUTH->user->access_level > $u['level']) || ($AUTH->user->access_level == $u['level'] && $AUTH->user->id == $u['id'])){
                    $nonce = $FUNCS->create_nonce( 'update_user_'.$u['id'] );
                    $str .= '<a href="'.K_ADMIN_URL . K_ADMIN_PAGE.'?o=users&act=edit&id='.$u['id'].'&nonce='.$nonce.'"><img src="'.K_ADMIN_URL.'theme/images/page_white_edit.gif"  title="'.$FUNCS->t('edit').'"/></a>';
                }
                if( $AUTH->user->access_level > $u['level'] ){
                    $nonce = $FUNCS->create_nonce( 'delete_user_'.$u['id'] );
                    $confirm_prompt = 'onclick="if( confirm(\''.$FUNCS->t('confirm_delete_user').': '.$u['title'].'?\') ) { return true; } return false;"';
                    $qs = '?o=users&act=delete&id='.$u['id'].'&nonce='.$nonce;
                    if( $pgn_pno>1 ) $qs .= '&pg=' . $pgn_pno;
                    $str .= '<a href="'.K_ADMIN_URL . K_ADMIN_PAGE.$qs.'" '.$confirm_prompt.'><img src="'.K_ADMIN_URL.'theme/images/page_white_delete.gif" title="'.$FUNCS->t('delete').'"/></a>';
                }
                else{
                    $str .= '&nbsp;';
                }
                $str .= '</td>';
                $str .= '</tr>';
            }
        }
        $str .= '</table>';
        $str .= '</div>';

        $str .= '<div class="wrap-paginator">';
        if( $count ){
            $str .= '<div class="bulk-actions">';
            $str .= '<a class="button" id="btn_bulk_submit" href="#"><span>'.$FUNCS->t('delete_selected').'</span></a>';
            $str .= '</div>';
        }
        $str .= $str_paginator;
        $str .= '</div>';
        $str .= '<input type="hidden" id="nonce" name="nonce" value="'.$FUNCS->create_nonce( 'bulk_action_user' ).'" />';
        $str .= '</form>';

        // Associated JavaScript
        ob_start();
        ?>
        <script type="text/javascript">
            //<![CDATA[
            window.addEvent('domready', function(){
                if( $('btn_bulk_submit') ){
                    $('btn_bulk_submit').addEvent('click', function(e){
                        var col = $$('.user-selector');
                        for( var x=0; x<col.length; x++ ){
                            if( col[x].checked ){
                                if( confirm('<?php echo $FUNCS->t('confirm_delete_selected_users'); ?>') ){
                                    $$('body').setStyle('cursor', 'wait');
                                    $('frm_list_users').submit();
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
        $str .= ob_get_contents();
        ob_end_clean();

        return $str;
    }
