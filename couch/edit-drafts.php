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

    if( isset($_GET['tpl']) && $FUNCS->is_non_zero_natural( $_GET['tpl'] ) ){
        $tpl_id = (int)$_GET['tpl'];
    }
    if( isset($_GET['pid']) && $FUNCS->is_non_zero_natural( $_GET['pid'] ) ){
        $parent_id = (int)$_GET['pid']; // show drafts only of a particular parent.
    }
    if( isset($_GET['p']) && $FUNCS->is_non_zero_natural( $_GET['p'] ) ){
        $page_id = (int)$_GET['p'];
    }
    $pgn_pno = 1;
    if( isset($_GET['pg']) && $FUNCS->is_non_zero_natural( $_GET['pg'] ) ){
        $pgn_pno = (int)$_GET['pg'];
    }

    if( isset($_GET['act']{0}) ){
        if( $_GET['act'] == 'delete' ){
            if( $page_id ){
                $_tpl_id = $tpl_id;
                if( !$_tpl_id ){
                    $rs = $DB->select( K_TBL_PAGES, array('template_id'), "id = '" . $DB->sanitize( $page_id )."'" );
                    $_tpl_id = $rs[0]['template_id'];
                }
                $FUNCS->validate_nonce( 'delete_page_' . $page_id );
                $PAGE = new KWebpage( $_tpl_id, $page_id );
                if( $PAGE->error ){
                    ob_end_clean();
                    die( 'ERROR in deletion: ' . $PAGE->err_msg );
                }

                $PAGE->delete( 1 );

                $qs = '?o=drafts';
                if( $tpl_id ) $qs .= '&tpl=' . $tpl_id;
                if( $parent_id ) $qs .= '&pid=' . $parent_id;
                if( $pgn_pno>1 ) $qs .= '&pg=' . $pgn_pno;
                header("Location: ".K_ADMIN_URL . K_ADMIN_PAGE. $qs);
                exit;
            }
        }
    }
    else{
        // Any drafts marked for deletion?
        if( isset($_POST['bulk-action']) ){
            if( isset($_POST['draft-id']) ){
                $FUNCS->validate_nonce( 'bulk_action_draft' );

                foreach( $_POST['draft-id'] as $v ){
                    if( $FUNCS->is_non_zero_natural($v) ){
                        $draft_id = intval( $v );
                        if( !$tpl_id ){
                            $rs = $DB->select( K_TBL_PAGES, array('template_id'), "id = '" . $DB->sanitize( $draft_id )."'" );
                            $_tpl_id = $rs[0]['template_id'];
                        }
                        else{
                            $_tpl_id = $tpl_id;
                        }

                        $PAGE = new KWebpage( $_tpl_id, $draft_id );
                        if( $PAGE->error ){
                            ob_end_clean();
                            die( 'ERROR in deletion: ' . $PAGE->err_msg );
                        }

                        // execute action
                        if( $_POST['bulk-action']=='delete' ){
                            $PAGE->delete( 1 );
                        }
                        elseif( $_POST['bulk-action']=='apply' ){
                            $DB->begin();
                            $res = $PAGE->update_parent();
                            if( $FUNCS->is_error($res) ){
                                ob_end_clean();
                                die( $res->err_msg );
                            }
                            $PAGE->delete( 1 );
                            $DB->commit( 1 );
                        }
                    }
                }
            }
        }

        // list all available drafts
        $_p = array();
        $_p['module'] = 'drafts';
        $_p['title'] = $FUNCS->t('drafts');
        $_p['link'] = K_ADMIN_URL . K_ADMIN_PAGE . '?o=drafts';
        $_p['subtitle'] = $FUNCS->t('list');
        $_p['content'] = k_admin_list_drafts();
        $FUNCS->render_admin_page_ex( $_p );
    }


    // Lists available drafts (can be constrained by template and parent psge)
    function k_admin_list_drafts(){
        global $DB, $AUTH, $FUNCS, $TAGS, $CTX;
        global $pgn_pno, $tpl_id, $parent_id;

        $limit = 15;

        // formulate query
        $tables = K_TBL_PAGES.' p left outer join '.K_TBL_PAGES.' p2 on p.parent_id = p2.id';
        $tables .= ' left outer join '.K_TBL_TEMPLATES.' t on p.template_id = t.id';
        $sql = "p.parent_id>0";
        if( $tpl_id ){
            $sql .= " AND t.id = '" . $DB->sanitize( $tpl_id )."'";
        }
        if( $parent_id ){
            $sql .= " AND p2.id = '" . $DB->sanitize( $parent_id )."'";
        }
        $sql .= " ORDER BY p.template_id, p.parent_id, p.modification_date desc";

        // first query for pagination
        $rs = $DB->select( $tables, array('count(p.id) as cnt'), $sql );
        $total_rows = $rs[0]['cnt'];
        $total_pages = ceil( $total_rows/$limit );

        // actual query
        if( $pgn_pno>$total_pages && $total_pages>0 ) $pgn_pno=$total_pages;
        $limit_sql = sprintf( " LIMIT %d, %d", ($pgn_pno - 1) * $limit, $limit );
        $rs2 = $DB->select( $tables, array('p.*', 'p2.page_name as parent_name', 'p2.page_title as parent_title', 't.clonable as tpl_clonable', 't.title as tpl_title', 't.name as tpl_name'), $sql . $limit_sql );
        $count = count($rs2);

        // paginator
        $adjacents = 2;
        $targetpage = K_ADMIN_URL . K_ADMIN_PAGE . '?o=drafts';
        if( $tpl_id ) $targetpage .= '&tpl=' . $tpl_id;
        if( $parent_id ) $targetpage .= '&pid=' . $parent_id;
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

        $str .= '<form name="frm_list_drafts" id="frm_list_drafts" action="" method="post">';
        $str .= '<div class="wrap-paginator">';

        // List of templates with drafts available
        $str .= '<div class="bulk-actions">';
        $rs3 = $DB->select( K_TBL_PAGES . ' p inner join ' . K_TBL_TEMPLATES . ' t on p.template_id = t.id', array('t.id', 't.name', 't.title', 'count(t.name) as cnt'), 'p.parent_id>0 GROUP BY t.name ORDER BY t.name asc' );
        $str .= '<select id="f_k_templates" name="f_k_templates">';
        $str .= '<option value="-1" >-- '.$FUNCS->t('view_all_drafts').' --</option>';
        if( count($rs3) ){
            foreach( $rs3 as $t ){
                $abbr_title = $t['title'] ? $t['title'] : $t['name'];
                $abbr_title = (strlen($abbr_title)>30) ? substr($abbr_title, 0, 30) . '&hellip;' : $abbr_title;
                $t_selected = ($t['id']==$tpl_id) ? ' SELECTED=1 ' : '';
                //$str .= '<option value="'.$t['id'].'" '.$t_selected.'>'.$abbr_title.' ('.$t['cnt'].')</option>';
                $str .= '<option value="'.$t['id'].'" '.$t_selected.'>'.$abbr_title.'</option>'; // count seemed confusing
            }
        }
        $str .= '</select>';
        $link = K_ADMIN_URL . K_ADMIN_PAGE . '?o=drafts';
        $str .= '<a class="button" id="btn_template_submit" href="'.$link.'" onclick="this.style.cursor=\'wait\'; return false;"><span>'.$FUNCS->t('filter').'</span></a>';
        $str .= '</div>';

        if( $total_rows > $limit ){
            $str_paginator = $FUNCS->getPaginationString( $pgn_pno, $total_rows, $limit, $adjacents, $targetpage, $pagestring, $prev_text, $next_text, $simple );
            $str_paginator .= "<div class='record-count'>".$FUNCS->t('showing')." $first_record_on_page-$last_record_on_page / $total_rows</div>";
            $str .= $str_paginator;
        }
        $str .= '</div>';

        $str .= '<div class="group-wrapper listing">';
        $str .= '<table class="listing clear" cellspacing="0" cellpadding="0">';
        $str .= '<thead>';
        $str .= '<th class="checkbox"><input type="checkbox" name="check-all" onClick="$$(\'.draft-selector\').set(\'checked\', this.checked);" /></th>';
        $str .= '<th>'.$FUNCS->t('original_page').'</th>';
        $str .= '<th>'.$FUNCS->t('template').'</th>';
        $str .= '<th>'.$FUNCS->t('modified').'</th>';
        $str .= '<th>'.$FUNCS->t('actions').'</th>';
        $str .= '</thead>';
        if( !$count ){
            if( $parent_id ){
                // No more drafts for this parent. Go to template listing..
                $qs = '?o=drafts';
                if( $tpl_id ) $qs .= '&tpl=' . $tpl_id;
                header("Location: ".K_ADMIN_URL . K_ADMIN_PAGE. $qs);
                exit;
            }
            elseif( $tpl_id ){
                // No more drafts for this template. Go to main listing..
                $qs = '?o=drafts';
                header("Location: ".K_ADMIN_URL . K_ADMIN_PAGE. $qs);
                exit;
            }
            else{
                $str .= '<tr><td colspan="6" class="last_row" style="text-align:center">'.$FUNCS->t('no_drafts_found').'</td></tr>';
            }
        }
        else{
            for( $x=0; $x<$count; $x++ ){
                $p = $rs2[$x];

                // calculate effective access level
                // ignoring for now


                $str .= '<tr>';
                if( $x>=$count-1 ) $last_row = " last_row";

                // checkbox
                $str .= '<td class="checkbox'.$last_row.'">';
                $str .= '<input type="checkbox" value="'.$p['id'].'" class="draft-selector" name="draft-id[]"/>';
                $str .= '</td>';

                // parent page's name and link to draft
                $str .= '<td class="name'.$last_row.'">';
                $nonce = $FUNCS->create_nonce( 'edit_page_'.$p['id'] );
                if( $p['parent_name'] ){
                    if( !$p['tpl_clonable'] ){
                        $abbr_title = $p['tpl_title'] ? $p['tpl_title'] : $p['tpl_name'];
                    }
                    else{
                        $abbr_title = $p['parent_title'];
                    }
                }
                else{
                    $abbr_title = '<font color="red">'.$FUNCS->t('original_deleted').' (id: '.$p['parent_id'].')</font>';
                }
                $abbr_title = (strlen($abbr_title)>60) ? substr($abbr_title, 0, 60) . '&hellip;' : $abbr_title;
                $str .= '<a href="'.K_ADMIN_URL . K_ADMIN_PAGE.'?act=edit&tpl='. $p['template_id'] .'&p='. $p['id'] .'&nonce='.$nonce.'">'. $abbr_title .'</a>';
                $str .= '</td>';

                // template
                $str .= '<td class="folder'.$last_row.'">';
                if( $p['tpl_clonable'] ){
                    $abbr_title = $p['tpl_title'] ? $p['tpl_title'] : $p['tpl_name'];
                    $str .= (strlen($abbr_title)>30) ? substr($abbr_title, 0, 30) . '&hellip;' : $abbr_title;
                }
                else{
                    $str .= '&nbsp;';
                }
                $str .= '</td>';

                // last modification date
                $str .= '<td class="date drafts'.$last_row.'">';
                $str .= date("M jS Y @ H:i", strtotime($p['modification_date']));
                $str .= '</td>';

                // actions
                $str .= '<td class="actions'.$last_row.'">';
                $str .= '<a href="'.K_ADMIN_URL . K_ADMIN_PAGE.'?act=edit&tpl='. $p['template_id'] .'&p='. $p['id'] .'&nonce='.$nonce.'"><img src="'.K_ADMIN_URL.'theme/images/page_white_edit.gif"  title="'.$FUNCS->t('edit').'"/></a>';
                //if( $access_level <= $AUTH->user->access_level ){
                    $nonce = $FUNCS->create_nonce( 'delete_page_'.$p['id'] );
                    $confirm_prompt = "onclick='if( confirm(\"".$FUNCS->t('confirm_delete_draft')."\") ) { return true; } return false;'";
                    $qs = '?o=drafts&act=delete';
                    $qs .= '&p='. $p['id'] .'&nonce='.$nonce;
                    if( $tpl_id ) $qs .= '&tpl=' . $tpl_id;
                    if( $parent_id ) $qs .= '&pid=' . $parent_id;
                    if( $pgn_pno>1 ) $qs .= '&pg=' . $pgn_pno;
                    //if( isset($_GET['pg']) ) $qs .= '&pg=' . $_GET['pg'];
                    $str .= '<a href="'.K_ADMIN_URL . K_ADMIN_PAGE.$qs.'" '.$confirm_prompt.'><img src="'.K_ADMIN_URL.'theme/images/page_white_delete.gif" title="'.$FUNCS->t('delete').'"/></a>';
                //}
                $str .= '<a href="'. K_SITE_URL . $p['tpl_name'] .'?p='. $p['id'] .'" target="_blank" title="'.$FUNCS->t('preview').'"><img src="'.K_ADMIN_URL.'theme/images/magnifier.gif"/></a>';

                $str .= '</td>';
                $str .= '</tr>';
            }
        }
        $str .= '</table>';
        $str .= '</div>';

        $str .= '<div class="wrap-paginator">';
        if( $count ){
            $str .= '<div class="bulk-actions">';
            $str .= '<select name="bulk-action" id="bulk-action">';
            $str .= '<option value="-" selected="selected">'. $FUNCS->t('bulk_action') .'</option>';
            $str .= '<option value="delete">'. $FUNCS->t('delete') .'</option>';
            $str .= '<option value="apply">'. $FUNCS->t('update_original') .'</option>';
            $str .= '</select>';
            $str .= '<a class="button" id="btn_bulk_submit" href="#"><span>'. $FUNCS->t('apply') .'</span></a>';
            $str .= '<input type="hidden" id="nonce" name="nonce" value="'. $FUNCS->create_nonce( 'bulk_action_draft' ) .'" />';
            $str .= '</div>';
        }
        $str .= $str_paginator;
        $str .= '</div>';
        $str .= '</form>';

        // Associated JavaScript
        ob_start();
        ?>
        <script type="text/javascript">
            //<![CDATA[
            window.addEvent('domready', function(){
                if( $('btn_template_submit') ){
                    $('btn_template_submit').addEvent('click', function(e){
                        var link = this.href
                        var tpl = $('f_k_templates').value;
                        if( tpl != -1 ){
                            link += '&tpl=' + tpl;
                        }
                        document.location.href = link;
                    });
                }
            });

            window.addEvent('domready', function(){
                if( $('btn_bulk_submit') ){
                    $('btn_bulk_submit').addEvent('click', function(e){
                        var col = $$('.draft-selector');
                        for( var x=0; x<col.length; x++ ){
                            if( col[x].checked ){
                                if($('bulk-action').value=='delete'){
                                    var msg = '<?php echo $FUNCS->t('confirm_delete_selected_drafts'); ?>';
                                }
                                else{
                                    var msg = '<?php echo $FUNCS->t('confirm_apply_selected_drafts'); ?>';
                                }
                                if( confirm(msg) ){
                                    $$('body').setStyle('cursor', 'wait');
                                    $('frm_list_drafts').submit();
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
