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
    require_once( K_COUCH_DIR.'comment.php' );

    // HOOK: edit_comment
    $FUNCS->dispatch_event( 'edit_comment' );

    if( isset($_GET['act']{0}) ){
        $comment_id = ( isset($_GET['id']) && $FUNCS->is_non_zero_natural($_GET['id']) ) ? (int)$_GET['id'] : null;

        if( $_GET['act'] == 'edit' ){

            if( $comment_id ){
                $FUNCS->validate_nonce( 'update_comment_' . $comment_id );

                $comment = new KComment( $comment_id );// get values from database into fields

                $errors = '';
                if( isset($_POST['op']) && $_POST['op']=='save' ){
                    $_POST['f_k_date'] = $FUNCS->sanitize_posted_date();
                    $_POST['f_k_approved'] = ( isset($_POST['f_status']) ) ? '1' : '0';

                    // HOOK: alter_edit_comment_posted_data
                    $skip = $FUNCS->dispatch_event( 'alter_edit_comment_posted_data', array(&$comment) );

                    if( !$skip ){
                        for( $x=0; $x<count($comment->fields); $x++ ){
                            $f = &$comment->fields[$x];
                            $f->store_posted_changes( $_POST['f_'.$f->name] ); // get posted values into fields
                        }
                    }

                    // HOOK: edit_comment_presave
                    $FUNCS->dispatch_event( 'edit_comment_presave', array(&$comment) );

                    $errors = $comment->save();

                    // HOOK: edit_comment_saved
                    $FUNCS->dispatch_event( 'edit_comment_saved', array(&$comment, &$errors) );

                    if( !$errors ){
                        $FUNCS->invalidate_cache();
                        $loc = K_ADMIN_URL . K_ADMIN_PAGE.'?o=comments&act=edit&id='.$comment->id.'&nonce=' . $FUNCS->create_nonce( 'update_comment_'.$comment->id );
                        header( "Location: ".$loc );
                        exit;
                    }
                }

                // start building content for output

                // HOOK: edit_comment_prerender
                $FUNCS->dispatch_event( 'edit_comment_prerender', array(&$comment) );

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

                <form name="frm_edit_comment" id="frm_edit_comment" action="" method="post" accept-charset="<?php echo K_CHARSET; ?>">
                    <div id="admin-sidebar" >
                        <div id="submit-date" style="margin-top:10px">
                            <label><b><?php echo $FUNCS->t('submitted_on'); ?>:</b></label><br>
                            <?php
                                echo $FUNCS->date_dropdowns( $comment->date );
                            ?>
                        </div>

                        <?php
                            $checked = ( $comment->approved ) ? ' checked="checked"' : '';
                         ?>
                        <div id="comments-open" style="margin-top:10px; display:block">
                            <label><b><?php echo $FUNCS->t('status'); ?>:</b></label><br>
                            <label>
                                <input type="checkbox" value="1" <?php echo $checked; ?> name="f_status"/><?php echo $FUNCS->t('approved'); ?>
                            </label>

                        </div>

                    </div>

                    <div id="admin-content">
                    <?php
                    for( $x=0; $x<count($comment->fields); $x++ ){
                        echo $comment->fields[$x]->render() .'<p>';
                    }
                    ?>
                    <input type="hidden" name="op" value="save" />
                    <a class="button" id="btn_submit" href="#" onclick="this.style.cursor='wait'; window.onbeforeunload=null; $('frm_edit_comment').submit(); return false;"><span><?php echo $FUNCS->t('save'); ?></span></a>
                    <a class="button" href="<?php echo $comment->get_link(); ?>" target="_blank" onclick="this.blur();"><span><?php echo $FUNCS->t('view'); ?></span></a>
                    </div>
                </form>
                <script type="text/javascript">
                    //<![CDATA[
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
                    <?php $k_form_id='frm_edit_comment'; require_once( K_COUCH_DIR.'theme/prompt_unsaved.php' );?>
                    //]]>
                </script>
                <?php
                $html = ob_get_contents();
                ob_end_clean();

                $_p = array();
                $_p['module'] = 'comments';
                $_p['title'] = ucwords( $FUNCS->t('comments') );
                $_p['link'] = K_ADMIN_URL . K_ADMIN_PAGE . '?o=comments';
                $_p['subtitle'] = $FUNCS->t('edit');
                $_p['show_advanced'] = 1;
                $_p['content'] = $html;
                $FUNCS->render_admin_page_ex( $_p );
            }
        }
        elseif( $_GET['act'] == 'delete' ){
            if( $comment_id ){
                $FUNCS->validate_nonce( 'delete_comment_' . $comment_id );
                $comment = new KComment( $comment_id );
                $comment->delete();

                header("Location: " . k_create_link() );
                exit;
            }
        }
        elseif( $_GET['act'] == 'approve' ){
            if( $comment_id ){
                $FUNCS->validate_nonce( 'approve_comment_' . $comment_id );
                $comment = new KComment( $comment_id );
                $comment->approve();

                header("Location: " . k_create_link() );
                exit;
            }
        }
        elseif( $_GET['act'] == 'unapprove' ){
            if( $comment_id ){
                $FUNCS->validate_nonce( 'approve_comment_' . $comment_id );
                $comment = new KComment( $comment_id );
                $comment->approve(0);

                header("Location: " . k_create_link() );
                exit;
            }
        }
    }
    else{
        // Any comments marked for deletion?
        if( isset($_POST['bulk-action']) ){
            if( isset($_POST['comment-id']) ){
                $FUNCS->validate_nonce( 'bulk_action_comment' );

                foreach( $_POST['comment-id'] as $v ){
                    if( $FUNCS->is_non_zero_natural($v) ){
                        $comment_id = intval( $v );
                        $comment = new KComment( $comment_id );

                        // execute action
                        if( $_POST['bulk-action']=='delete' ){
                            $comment->delete();
                        }
                        elseif( $_POST['bulk-action']=='approve' ){
                            $comment->approve();
                        }
                        elseif( $_POST['bulk-action']=='unapprove' ){
                            $comment->approve(0);
                        }
                    }
                }
            }
        }

        // list comments
        ob_start();
        k_admin_list_comments();
        $html = ob_get_contents();
        ob_end_clean();

        $_p = array();
        $_p['module'] = 'comments';
        $_p['title'] = ucwords( $FUNCS->t('comments') );
        $_p['link'] = K_ADMIN_URL . K_ADMIN_PAGE . '?o=comments';
        $_p['subtitle'] = $FUNCS->t('list');
        $_p['show_advanced'] = 0;
        $_p['content'] = $html;
        $FUNCS->render_admin_page_ex( $_p );
    }

    function k_admin_list_comments(){
        global $DB, $AUTH, $FUNCS;

        $limit = 10;
        $pgn_pno = 1;
        if( isset($_GET['pg']) && $FUNCS->is_non_zero_natural( $_GET['pg'] ) ){
            $pgn_pno = (int)$_GET['pg'];
        }

        $page_id = ( isset($_GET['page_id']) && $FUNCS->is_non_zero_natural($_GET['page_id']) ) ? (int)$_GET['page_id'] : null;
        if( !is_null($page_id) ){
            $where .= " AND page_id='".intval($page_id)."'";
            $rs = $DB->select( K_TBL_PAGES, array('page_title'), "id='" . $DB->sanitize( $page_id ). "'" );
            if( count($rs) ){
                $page_title = $rs[0]['page_title'];
            }
        }

        $approved = ( isset($_GET['status']) && $FUNCS->is_natural($_GET['status']) ) ? (int)$_GET['status'] : null;
        if( !is_null($approved) ){
            if( $approved==0 || $approved==1 ){
                $where .= " AND approved='".intval($approved)."'";
            }
            else{
                $approved = null;
            }
        }
        ?>
        <ul class="filter">
            <li><a <?php if(is_null($approved)) echo 'class="current"'; ?> href="<?php echo k_create_link(array('status', 'pg')); ?>"><?php echo $FUNCS->t('all'); ?></a>&nbsp;|&nbsp;</li>
            <li><a <?php if($approved===0) echo 'class="current"'; ?> href="<?php echo k_create_link(array('status', 'pg')); ?>&status=0"><?php echo $FUNCS->t('unapproved'); ?> </a>&nbsp;|&nbsp;</li>
            <li><a <?php if($approved==1) echo 'class="current"'; ?> href="<?php echo k_create_link(array('status', 'pg')); ?>&status=1"><?php echo $FUNCS->t('approved'); ?></a></li>
            <?php
            if( $page_title ){
                echo '<li>&nbsp;&nbsp;&nbsp;(of <b><i>'.$page_title.'</i></b>)</li>';
            }
            ?>
        </ul>
        <div class="clear"></div>
        <?php
        $sql_tables = K_TBL_COMMENTS. " cc
                    inner join ".K_TBL_PAGES." cp on cp.id=cc.page_id
                    inner join ".K_TBL_TEMPLATES." ct on ct.id=cp.template_id";
        $sql_fields = array('cc.*, cp.page_name, ct.name tpl_name, ct.clonable');
        $sql = "1=1". $where ." ORDER BY cc.date DESC";

        // first query for pagination
        $rs = $DB->select( $sql_tables, array('count(cc.id) as cnt'), $sql );
        $total_rows = $rs[0]['cnt'];
        $total_pages = ceil( $total_rows/$limit );

        // actual query
        if( $pgn_pno>$total_pages && $total_pages>0 ) $pgn_pno=$total_pages;
        $limit_sql = sprintf( " LIMIT %d, %d", ($pgn_pno - 1) * $limit, $limit );
        $rs = $DB->select( $sql_tables, $sql_fields, $sql . $limit_sql );
        $count = count($rs);
        if( $count ){
            // paginator
            $adjacents = 2;
            $targetpage = K_ADMIN_URL . K_ADMIN_PAGE . '?o=comments';
            foreach( $_GET as $qk=>$qv ){
                if( $qk=='o' || $qk=='pg' ) continue; //'pg'
                $targetpage .= "&" . $qk . '=' . urlencode($qv);
            }
            $pagestring = "&pg=";
            $prev_text = '&#171; ' . $FUNCS->t('prev');
            $next_text = $FUNCS->t('next') . ' &#187;';
            $simple = 0;

            // record counts
            $first_record_on_page = ($limit * ($pgn_pno - 1)) + 1;
            $total_records_on_page = ( $count<$limit ) ? $count : $limit;
            $last_record_on_page = $first_record_on_page + $total_records_on_page - 1;

            echo '<form name="frm_list_comments" id="frm_list_comments" action="" method="post">';
            echo '<div class="wrap-paginator">';

            ?>
            <div class="bulk-actions">
                <label>
                    <input type="checkbox" name="check-all" onClick="$$('.comment-selector').set('checked', this.checked);" />
                    <strong><?php echo $FUNCS->t('select-deselect'); ?></strong>
                </label>
                &nbsp;

            </div>
            <?php
            $str_paginator = $FUNCS->getPaginationString( $pgn_pno, $total_rows, $limit, $adjacents, $targetpage, $pagestring, $prev_text, $next_text, $simple );
            $str_paginator .= "<div class='record-count'>".$FUNCS->t('showing')." $first_record_on_page-$last_record_on_page / $total_rows</div>";
            echo $str_paginator;
            echo '</div>';
        ?>
            <table class="comments clear" cellspacing=o >
                <?php foreach( $rs as $rec ){  ?>
                <?php
                    $parent_link = ( K_PRETTY_URLS ) ? $FUNCS->get_pretty_template_link( $rec['tpl_name'] ) : $rec['tpl_name'];
                    $comment_link = K_SITE_URL . $parent_link . "?comment=" . $rec['id'];
                    $edit_link = K_ADMIN_URL . K_ADMIN_PAGE . '?o=comments&act=edit&id='.$rec['id'].'&nonce=' . $FUNCS->create_nonce( 'update_comment_'.$rec['id'] );
                    $delete_link = k_create_link() . '&act=delete&id='.$rec['id'].'&nonce=' . $FUNCS->create_nonce( 'delete_comment_'.$rec['id'] );
                    $approve = ($rec['approved']) ? 'unapprove' : 'approve';
                    $class_approved = ( $rec['approved'] ) ? 'approved' : 'unapproved';
                    $text_approve = ( $rec['approved'] ) ? $FUNCS->t('unapprove') : $FUNCS->t('approve');
                    $approve_link = k_create_link() . '&act='.$approve.'&id='.$rec['id'].'&nonce=' . $FUNCS->create_nonce( 'approve_comment_'.$rec['id'] ) . '#comment-' . $rec['id'];
                ?>
                <tr class="comment <?php echo $class_approved; ?>" >
                    <td class="comment-checkbox">
                        <input type="checkbox" value="<?php echo $rec['id']; ?>" class="comment-selector" name="comment-id[]"/>
                    </td>
                    <td class="comment-author">
                        <?php
                            $str = '<a name="comment-' . $rec['id'] . '">' . $FUNCS->get_gravatar($rec['email'], 32) . '</a><br><b>' . $rec['name'] . '</b>';
                            if(strlen($rec['link'])){
                                $str = '<a href="'.trim($rec['link']).'">' . $str . '</a>';
                            }
                            echo $str;
                        ?>
                        <?php if( strlen($rec['email']) ){ ?>
                            <br>
                            <a href="<?php echo $rec['email']; ?>"><?php echo $rec['email']; ?></a>
                        <?php } ?>
                        <br>
                        <?php echo $rec['ip_addr']; ?>
                    </td>
                    <td class="comment-body">
                        <p class="comment-meta">
                            <span><a target="_blank" href="<?php echo $comment_link; ?>"><?php echo $rec['page_name']; ?></a></span><br>
                            <span class="comment-date"><?php echo date("M jS Y", strtotime($rec['date'])); ?> at <?php echo date("h:iA", strtotime($rec['date'])); ?></span>
                        </p>

                        <p class="comment-content">
                            <?php echo $rec['data']; ?>
                        </p>

                        <p class="comment-actions">
                            <a title="<?php echo $text_approve; ?>" href="<?php echo $approve_link; ?>"><?php echo $text_approve; ?></a> |
                            <?php if( $rec['approved'] ){ ?>
                            <a title="<?php echo $FUNCS->t('view'); ?>" href="<?php echo $comment_link; ?>" target="_blank"><?php echo $FUNCS->t('view'); ?></a> |
                            <?php } ?>
                            <a title="<?php echo $FUNCS->t('edit'); ?>" href="<?php echo $edit_link; ?>"><?php echo $FUNCS->t('edit'); ?></a> |
                            <a title="<?php echo $FUNCS->t('delete'); ?>" onclick="return confirm('<?php echo $FUNCS->t('confirm_delete_comment'); ?>');" href="<?php echo $delete_link; ?>"><?php echo $FUNCS->t('delete'); ?></a>
                        </p>

                    </td>
                </tr>
                <?php } ?>
            </table>
        <?php
        echo '<div class="wrap-paginator">';
        ?>
        <div class="bulk-actions">
            <select name="bulk-action" id="bulk-action">
                <option value="-" selected="selected"><?php echo $FUNCS->t('bulk_action'); ?></option>
                <option value="delete"><?php echo $FUNCS->t('delete'); ?></option>
                <option value="approve"><?php echo $FUNCS->t('approve'); ?></option>
                <option value="unapprove"><?php echo $FUNCS->t('unapprove'); ?></option>
            </select>
            <a class="button" id="btn_bulk_submit" href="#"><span><?php echo $FUNCS->t('apply'); ?></span></a>
            <input type="hidden" id="nonce" name="nonce" value="<?php echo $FUNCS->create_nonce( 'bulk_action_comment' ); ?>" />
        </div>
        <?php
        echo $str_paginator;
        echo '</div>';
        echo '</form>';
        }
        // Associated JavaScript
        ?>
        <script type="text/javascript">
            //<![CDATA[
            window.addEvent('domready', function(){
                if( $('btn_bulk_submit') ){
                    $('btn_bulk_submit').addEvent('click', function(e){
                        if($('bulk-action').value!='-'){
                            var col = $$('.comment-selector');
                            for( var x=0; x<col.length; x++ ){
                                if( col[x].checked ){
                                    if($('bulk-action').value=='delete'){
                                        if( confirm('<?php echo $FUNCS->t('confirm_delete_selected_comments'); ?>') ){
                                            $$('body').setStyle('cursor', 'wait');
                                            $('frm_list_comments').submit();
                                        }
                                    }
                                    else{
                                        $$('body').setStyle('cursor', 'wait');
                                        $('frm_list_comments').submit();
                                    }
                                    return false;
                                }
                            }
                        }
                        return false;
                        });
                }
            });

            //]]>
        </script>
        <?php
    }

    function k_create_link( $skip_params='' ){
        $arr_actions = array( 'o', 'act', 'id', 'nonce' );
        if( is_array($skip_params) ){
            $arr_actions = array_merge( $arr_actions, $skip_params );
        }

        $page_link = K_ADMIN_URL . K_ADMIN_PAGE . "?o=comments";
        // append querystring params, if any
        foreach( $_GET as $qk=>$qv ){
            if( in_array($qk, $arr_actions) ) continue;
            $qs .= '&' . $qk . '=' . urlencode($qv);
        }
        return $page_link . $qs;
    }
