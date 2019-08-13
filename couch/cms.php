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

    ob_start();
    k_timer_start();

    if ( !defined('K_COUCH_DIR') ) define( 'K_COUCH_DIR', str_replace( '\\', '/', dirname(realpath(__FILE__) ).'/') );

    require_once( K_COUCH_DIR.'header.php' );

    define( 'K_IGNORE_CANONICAL_URL', '1' );
    define( 'K_IGNORE_CONTEXT', '2' );

    $script = str_replace('\\', '/', realpath($_SERVER['SCRIPT_FILENAME']));
    $script = substr( $script, strlen(K_SITE_DIR) );
    $CTX->script = $script;
    if( K_SITE_OFFLINE && $AUTH->user->access_level < K_ACCESS_LEVEL_ADMIN && $script!='404.php' && $script!='503.php'){
        ob_end_clean();
        header('HTTP/1.1 503 Service Temporarily Unavailable');
        header('Status: 503 Service Temporarily Unavailable');
        $html = '';
        if( file_exists(K_SITE_DIR . '503.php') ){
            $html = $FUNCS->file_get_contents( K_SITE_URL . '503.php' );
        }
        if( !$html ){
            $html = $FUNCS->render( 'site_offline' );
        }

        echo $html;
        die;
    }


    class COUCH{

        static function invoke( $ignore_level=0 ){
            global $DB, $FUNCS, $PAGE, $AUTH, $CTX, $k_cache_file;

            if( $ignore_level > 0 ){
                $ignore_canonical_url = 1; // if set, the url used to access page is not checked to be canonical.
                if( $ignore_level > 1 ){
                    $ignore_context = 1; // if set, all canonical GET variables are ignored. Page always remains in home-view.
                }
            }

            // $page_id, $folder_id and $archive_date are mutually exclusive.
            // If more than one are provided, $page_id will be preferred over the
            // others and $folder_id will be preferred over $archive_date.
            // All ids will be preferred over names.
            // comment_id actually resolves to becoming the page_id of
            // the associated page hence it is processed the foremost.
            $page_id = null;
            $folder_id = null;
            $archive_date = null;
            $page_name = null;
            $folder_name = null;
            $comment_id = null;
            $comment_date = '';

            if( !$ignore_context ){
                // if comment id given, find the associated page_id
                if( isset($_GET['comment']) && $FUNCS->is_non_zero_natural($_GET['comment']) ){
                    $rs = $DB->select( K_TBL_COMMENTS, array('page_id', 'date', 'approved'), "id='" . $DB->sanitize( intval($_GET['comment']) )."'" );
                    if( count($rs) ){
                        $comment_id = intval( $_GET['comment'] );
                        $comment_date = $rs[0]['date'];
                        $_GET['p'] = $rs[0]['page_id'];
                    }
                }

                if( isset($_GET['p']) && $FUNCS->is_non_zero_natural($_GET['p']) ){
                    $page_id = (int)$_GET['p'];
                }
                else if( isset($_GET['f']) && $FUNCS->is_non_zero_natural($_GET['f']) ){
                    $folder_id = (int)$_GET['f'];
                }
                else if( isset($_GET['d']) && $FUNCS->is_non_zero_natural($_GET['d']) ){
                    $date = (int)$_GET['d'];
                    // example valid values:
                    //  ?d=20080514
                    //  ?d=200805
                    //  ?d=2008
                    $len = strlen( $date );
                    if( $len >= 4){
                        $year = substr( $date, 0, 4 );
                        $archive_date = $year;
                        if( $len >= 6 ){
                            $month = substr( $date, 4, 2 );
                            $archive_date .= '-' .$month;
                            if( $len > 6 ){
                                $day = substr( $date, 6, 2 );
                                $archive_date .= '-' .$day;
                            }
                        }

                        if( $day ){
                            $next_archive_date = date( 'Y-m-d H:i:s', mktime(0, 0, 0, $month, $day+1, $year) );
                            $is_archive_day_view = 1;

                        }
                        elseif( $month ){
                            $next_archive_date = date( 'Y-m-d H:i:s', mktime(0, 0, 0, $month+1, 1, $year) );
                            $is_archive_month_view = 1;
                        }
                        else{
                            $next_archive_date = date( 'Y-m-d H:i:s', mktime(0, 0, 0, 1, 1, $year+1) );
                            $is_archive_year_view = 1;
                        }
                        $archive_date = $FUNCS->make_date( $archive_date );
                    }
                }
                else if( isset($_GET['pname']) && $FUNCS->is_title_clean($_GET['pname']) ){
                    $page_name = $_GET['pname'];
                }
                else if( isset($_GET['fname']) && $FUNCS->is_title_clean($_GET['fname']) ){
                    $folder_name = $_GET['fname'];
                }
            }
            else{
                $CTX->ignore_context=1; // necessary for nested_pages with prettyurls
            }

            if( $AUTH->user->access_level >= K_ACCESS_LEVEL_SUPER_ADMIN ){
                $DB->begin();
                // Serialize access for super-admins.. hack of a semaphore
                $DB->update( K_TBL_SETTINGS, array('k_value'=>K_COUCH_VERSION), "k_key='k_couch_version'" );
            }

            // Get the requested page.
            // for folder view and archive view, page_id would be null,
            // causing the default page to be loaded.
            //
            $CTX->folder_info = (!is_null($folder_name)) ? $folder_name : (!is_null($folder_id) ? (int)$folder_id : null); // added for 404 on non-existent folders
            if( !is_null($page_name) ){
                $PAGE = new KWebpage( null, null, $page_name );
            }
            else{
                $PAGE = new KWebpage( null, $page_id );
            }

            if( $PAGE->error ){
                ob_end_clean();
                $DB->rollback();

                if( $PAGE->err_msg == 'Page not found' ){
                    header('HTTP/1.1 404 Not Found');
                    header('Status: 404 Not Found');
                    header('Content-Type: text/html; charset='.K_CHARSET );

                    $html='';
                    if( file_exists(K_SITE_DIR . '404.php') ){
                        $html = $FUNCS->file_get_contents( K_SITE_URL . '404.php' );
                    }
                    if( !$html ) $html = 'Page not found';
                }
                else{
                    die( 'ERROR: ' . $PAGE->err_msg );
                }
            }
            else{
                $access_level = $PAGE->get_access_level( $inherited );
                $AUTH->check_access( $access_level );

                // set the requested view, if any
                if( $folder_id ){
                    $PAGE->is_folder_view = 1;
                    $PAGE->folder_id = $folder_id;
                }
                elseif( $archive_date ){
                    $PAGE->is_archive_view = 1;
                    $PAGE->archive_date = $archive_date;
                    $PAGE->next_archive_date = $next_archive_date;
                    if( $is_archive_day_view ){
                        $PAGE->is_archive_day_view = 1;
                    }
                    elseif( $is_archive_month_view ){
                        $PAGE->is_archive_month_view = 1;
                    }
                    else{
                        $PAGE->is_archive_year_view = 1;
                    }
                    $PAGE->day = $day;
                    $PAGE->month = $month;
                    $PAGE->year = $year;
                }
                elseif( $folder_name ){
                    if( !$PAGE->changed_from_folder_to_page ){ // can happen with nested pages
                        $PAGE->is_folder_view = 1;
                        $PAGE->folder_name = $folder_name;
                    }
                }
                elseif( $comment_id ){
                    // not a view but just to remind the page that it was fetched on the basis of comment id.
                    $PAGE->comment_id = $comment_id;
                    $PAGE->comment_date = $comment_date;
                }

                $html = ob_get_contents();
                ob_end_clean();

                // HOOK: pre_process_page
                $FUNCS->dispatch_event( 'pre_process_page', array(&$html, &$PAGE, &$ignore_canonical_url) );

                $parser = new KParser( $html );
                $html = $parser->get_HTML();
                //echo $parser->get_info();

                $FUNCS->post_process_page();
                if( $AUTH->user->access_level >= K_ACCESS_LEVEL_SUPER_ADMIN ) $DB->commit( 1 );

                // Verify that the url used to access this page is the page's canonical url
                if( $comment_id ){
                    // if page accessed via comment_id, rectify the url
                    $canonical_url = K_SITE_URL . $PAGE->link;
                    if( $PAGE->comment_page ){
                        $sep = ( strpos($canonical_url, '?')===false ) ? '?' : '&';
                        $canonical_url .= $sep . 'comments_pg=' . $PAGE->comment_page;
                    }
                    $redirect_url = $canonical_url . "#comment-" . $comment_id;
                }
                elseif( K_PRETTY_URLS && $_SERVER['REQUEST_METHOD']!='POST' && !$PAGE->parent_id /*&& $PAGE->tpl_is_clonable*/ && $CTX->script!='404.php' && !$ignore_canonical_url ){
                    $url = $FUNCS->get_url();
                    if( $url ){
                        if( $_GET['_nr_'] ){ //page link being masqueraded. Normalize before comparision.
                            $masq_tpl_name = $FUNCS->get_pretty_template_link( $PAGE->tpl_name ); /*masquereded name*/
                            $unmasq_tpl_name = $FUNCS->get_pretty_template_link_ex( $PAGE->tpl_name, $dummy, 0 ); /*unmasquereded name*/
                            $canonical_url = K_SITE_URL . $unmasq_tpl_name . substr( $PAGE->link, strlen($masq_tpl_name) ); //replace masquered name with unmasqueraded
                        }
                        else{
                            $canonical_url = K_SITE_URL . $PAGE->link;
                        }
                        if( $url != $canonical_url ){
                            // Redirect to canonical url
                            // append querystring params, if any
                            if( $_GET['_nr_'] ){ //page link being masqueraded
                                $redirect_url = $FUNCS->get_qs_link( K_SITE_URL . $PAGE->link );
                            }
                            else{
                                $redirect_url = $FUNCS->get_qs_link( $canonical_url );
                            }
                        }
                    }
                }
            }

            $content_type = ( $PAGE->content_type ) ? $PAGE->content_type : 'text/html';
            $content_type_header = 'Content-Type: '.$content_type.';';
            $content_type_header .= ' charset='.K_CHARSET;

            // Add our link to the document (if not commercial license)
            // Apply only to text/html, text/html-sandboxed, application/xhtml+xml mime-types
            // application/xml and text/xml can also be used to serve xhtml documents but we'll allow that.

            if( !(K_PAID_LICENSE || K_REMOVE_FOOTER_LINK) ){
                if( strpos($content_type, 'html')!==false ){
                    $_cnt = preg_match_all( "/<\/[^\S]*BODY[^\S]*>/is", $html, $matches, PREG_OFFSET_CAPTURE );
                    if( $_cnt ){
                        $_split_at = $matches[0][count($matches[0])-1][1];
                    }
                    else{
                        $_cnt = preg_match_all( "/<\/[^\S]*HTML[^\S]*>/is", $html, $matches, PREG_OFFSET_CAPTURE );
                        if( $_cnt ){
                            $_split_at = $matches[0][count($matches[0])-1][1];
                        }
                    }

                    $_link = "
                    <div style=\"clear:both; text-align: center; z-index:99999 !important; display:block !important; visibility:visible !important;\">
                        <div style=\"position:relative; top:0; margin-right:auto;margin-left:auto; z-index:99999; display:block !important; visibility:visible !important;\">
                        <center><a href=\"https://www.couchcms.com/\" title=\"CouchCMS - Simple Open-Source Content Management\" style=\"display:block !important; visibility:visible !important;\">Powered by CouchCMS</a></center><br />
                        </div>
                    </div>
                    ";

                    if( $_split_at ){
                        $_pre = substr( $html, 0, $_split_at );
                        $_post = substr( $html, $_split_at );
                        $html = $_pre . $_link . $_post;
                    }
                    else{
                        $html .= $_link;
                    }
                }
            }

            // HOOK: alter_final_page_output
            $FUNCS->dispatch_event( 'alter_final_page_output', array(&$html, &$PAGE, &$k_cache_file, &$redirect_url, &$content_type_header) );

            // See if ouput needs to be cached
            if( $k_cache_file && strlen( trim($html) ) && !$PAGE->no_cache ){
                $handle = @fopen( $k_cache_file, 'c' );
                if( $handle ){
                    if( $redirect_url ){
                        $pg['redirect_url'] = $redirect_url;
                    }
                    else{
                        $pg['mime_type'] = $content_type_header;
                        $cached_html = $html;

                        if( strpos($content_type_header, 'html')!==false ){
                            $cached_html .= "\n<!-- Cached page";
                            if( !K_PAID_LICENSE ){
                                $cached_html .= " served by CouchCMS - Simple Open-Source Content Management";
                            }
                            $cached_html .= " -->\n";
                        }
                        $pg['cached_html'] = $cached_html;

                        if( $PAGE->err_msg == 'Page not found' ){
                            $pg['res_404'] = 1;
                        }

                    }
                    if( flock($handle, LOCK_EX) ){
                        ftruncate( $handle, 0 );
                        rewind( $handle );
                        fwrite( $handle, serialize( $pg ) );
                        fflush( $handle );
                        flock( $handle, LOCK_UN );
                    }
                    fclose( $handle );
                }
            }

            if( $redirect_url ){
                header( "Location: ".$redirect_url, TRUE, 301 );
                die();
            }

            if( strpos($content_type_header, 'html')!==false ){
                if( !K_PAID_LICENSE ){
                    $html .= "\n<!-- Page generated by CouchCMS - Simple Open-Source Content Management";
                    $html .= " -->\n";
                }

                if( defined('K_IS_MY_TEST_MACHINE') ){
                    $html .= "\n<!-- in: ".k_timer_stop()." -->\n";
                    $html .= "\n<!-- Queries: ".$DB->queries." -->\n";
                }
            }

            header( $content_type_header );
            echo $html;
        }

    }

    ////////////////////////////////////////////////////////////////////////////
    function k_get_time(){
        list ($msec, $sec) = explode(' ', microtime());
        $microtime = (float)$msec + (float)$sec;
        return $microtime;
    }

    function k_timer_start(){
        global $k_time_start;
        $k_time_start = k_get_time();
        return true;
    }

    function k_timer_stop( $echo = 0 ){
        global $k_time_start, $k_time_end;
        $k_time_end = k_get_time();
        $diff = number_format( $k_time_end - $k_time_start, 3 ) . ' sec';
        if ( $echo ){ echo $diff; }
        return $diff;
    }
