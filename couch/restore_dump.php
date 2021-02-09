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

    if ( defined('K_COUCH_DIR') ) die( 'restore_dump.php is meant to be invoked directly' );
    define( 'K_COUCH_DIR', str_replace( '\\', '/', dirname(realpath(__FILE__)).'/' ) );

    define( 'K_ADMIN', 1 );
    require_once( K_COUCH_DIR.'header.php' );
    header( 'Content-Type: text/html; charset='.K_CHARSET );

    $limit = 1; /* max number of queries to process in a single go */

    // get params
    $offset = ( isset($_GET['offset']) && $FUNCS->is_natural($_GET['offset']) ) ? (int)$_GET['offset'] : null;
    $lines = ( isset($_GET['lines']) && $FUNCS->is_natural($_GET['lines']) ) ? (int)$_GET['lines'] : null;
    if( is_null($offset) || is_null($lines) ){ die( "'offset' and 'lines' required" ); }
    $splash = ( $offset==0 && $lines==0 && isset($_GET['splash']) && $FUNCS->is_natural($_GET['splash']) ) ? (int)$_GET['splash'] : null;
    $FUNCS->validate_nonce( 'restore_dump_'.$offset.'_'.$lines );

    if( $splash ){
        $progress = 0;
        $nonce = $FUNCS->create_nonce( 'restore_dump_'.$offset.'_'.$lines );

        $icon = K_SYSTEM_THEME_URL.'assets/open-iconic.svg#info';
        $url = K_ADMIN_URL.'restore_dump.php?offset='.$offset.'&lines='.$lines.'&nonce='.$nonce;
        $jquery = K_ADMIN_URL.'includes/jquery-3.x.min.js?v='.K_COUCH_BUILD;
        $out=<<<EOS
        <script src="$jquery"></script>
        <script language="JavaScript" type="text/javascript">
            $(function(){
                setTimeout(function(){
                    location.href="$url";
                }, 500);
            });
        </script>
        <div class="alert alert-info alert-icon" style="margin-top: 0;">
            <svg class="i"><use xlink:href="$icon"></use></svg>
            <h2>Applying SQL dump</h2>
            Please wait..: ${progress}%
        </div>
EOS;
    }
    else{
        // get to work
        $dump = new KDump_Restore( K_COUCH_DIR . 'install-ex2.php', $offset, $lines, $limit );

        // show results
        if( $dump->error  ){
            $icon = K_SYSTEM_THEME_URL.'assets/open-iconic.svg#circle-x';
            $out=<<<EOS
                <div class="alert alert-error alert-icon" style="margin-top: 0;">
                    <svg class="i"><use xlink:href="$icon"></use></svg>
                    <h2>Error!</h2>
                    $dump->err_msg
                </div>
EOS;
        }
        else{
            if( $dump->result ){
                $offset = $dump->file_offset;
                $lines = $dump->cnt_lines;
                $filesize = $dump->filesize;
                $progress = ceil( ($offset / $filesize) * 100 );
                $nonce = $FUNCS->create_nonce( 'restore_dump_'.$offset.'_'.$lines );

                $icon = K_SYSTEM_THEME_URL.'assets/open-iconic.svg#info';
                $url = K_ADMIN_URL.'restore_dump.php?offset='.$offset.'&lines='.$lines.'&nonce='.$nonce;
                $jquery = K_ADMIN_URL.'includes/jquery-3.x.min.js?v='.K_COUCH_BUILD;
                $out=<<<EOS
                <script src="$jquery"></script>
                <script language="JavaScript" type="text/javascript">
                    $(function(){
                        setTimeout(function(){
                            location.href="$url";
                        }, 500);
                    });
                </script>
                <div class="alert alert-info alert-icon" style="margin-top: 0;">
                    <svg class="i"><use xlink:href="$icon"></use></svg>
                    <h2>Applying SQL dump</h2>
                    Please wait..: ${progress}%
                </div>
EOS;
            }
            else{
                // no more queries to be read ..
                // HOOK: restore_dump_complete
                $FUNCS->dispatch_event( 'restore_dump_complete' );

                $icon = K_SYSTEM_THEME_URL.'assets/open-iconic.svg#check';
                $url =  K_ADMIN_URL . K_ADMIN_PAGE;
                $out=<<<EOS
                <div class="alert alert-success alert-icon" style="margin-top: 0;">
                    <svg class="i"><use xlink:href="$icon"></use></svg>
                    <h2>Installation successful!</h2>
                    Please <a href="$url">log in</a> using the information you provided.
                </div>
EOS;
            }
        }
    }

    $html = $FUNCS->login_header();
    $html .= $out;
    $html .= $FUNCS->login_footer();
    ob_end_clean();
    die( $html );

    ////////////////////////////////////////////////////////////////////////////////////
    class KDump_Restore{

        var $filename;
        var $fp;
        var $filesize;
        var $file_offset = 0;
        var $file_offset_orig = 0;
        var $limit; /* max number queries to process */
        var $cnt_lines = 0;
        var $cnt_lines_orig = 0;
        var $cnt_queries = 0;
        var $max_chars = 4096; //4096
        var $result;

        function __construct( $filename, $file_offset=0, $cnt_lines=0, $limit=500 ){
            global $FUNCS;

            $this->filename = $filename;
            $this->limit = $limit;
            $this->file_offset = $this->file_offset_orig = $file_offset;
            $this->cnt_lines = $this->cnt_lines_orig = $cnt_lines;

            ini_set( "auto_detect_line_endings", true );

            // open file
            if( !file_exists($this->filename) ){ $this->error=1; $this->err_msg="File ".$this->filename." not found"; return; }
            if( !($this->fp = fopen($this->filename, "rb")) ){ $this->error=1; $this->err_msg="Failed to open file ".$this->filename; return; }

            // get size
            if( @fseek($this->fp, 0, SEEK_END) === -1 ){ $this->error=1; $this->err_msg="Unable to get size of ".$this->filename; return; }
            $this->filesize = ftell( $this->fp );
            fseek( $this->fp, 0, SEEK_SET ); // rewind

            // move to the indicated offset
            if( $this->file_offset==0 ){

                // UTF BOM present?
                $bombytes = fread( $this->fp, 3 );
                if( $bombytes != chr(0xEF) . chr(0xBB) . chr(0xBF) ){
                    fseek( $this->fp, 0, SEEK_SET ); // rewind
                }
            }
            else{
                if( $this->file_offset > $this->filesize ){ $this->error=1; $this->err_msg="Cannot move to offset beyond file size"; return; }
                if( @fseek($this->fp, $this->file_offset) === -1 ){ $this->error=1; $this->err_msg="Unable to move to offset ".$this->file_offset; return; }
            }

            // get to work..
            $res = $this->begin_restore();
            if( $FUNCS->is_error($res) ){ $this->error=1; $this->err_msg=$res->err_msg; return; }

            // clean up..
            $this->result = $res;
            $this->file_offset = ftell( $this->fp );
            fclose( $this->fp );

        }

        function begin_restore(){
            global $FUNCS;

            $tbls = array();
            $tbls['K_TBL_TEMPLATES'] = K_TBL_TEMPLATES;
            $tbls['K_TBL_FIELDS'] = K_TBL_FIELDS;
            $tbls['K_TBL_PAGES'] = K_TBL_PAGES;
            $tbls['K_TBL_USERS'] = K_TBL_USERS;
            $tbls['K_TBL_USER_LEVELS'] = K_TBL_USER_LEVELS;
            $tbls['K_TBL_SETTINGS'] = K_TBL_SETTINGS;
            $tbls['K_TBL_FOLDERS'] = K_TBL_FOLDERS;
            $tbls['K_TBL_DATA_TEXT'] = K_TBL_DATA_TEXT;
            $tbls['K_TBL_DATA_NUMERIC'] = K_TBL_DATA_NUMERIC;
            $tbls['K_TBL_FULLTEXT'] = K_TBL_FULLTEXT;
            $tbls['K_TBL_COMMENTS'] = K_TBL_COMMENTS;
            $tbls['K_TBL_RELATIONS'] = K_TBL_RELATIONS;
            $tbls['K_TBL_ATTACHMENTS'] = K_TBL_ATTACHMENTS;

            // HOOK: alter_restore_dump_tables
            $FUNCS->dispatch_event( 'alter_restore_dump_tables', array(&$tbls) );

            @set_time_limit( 0 ); // make server wait
            @mysql_query( "SET autocommit=0" );
            @mysql_query( "SET FOREIGN_KEY_CHECKS=0" );
            @mysql_query( "BEGIN" );
            $start_time = time();

            while( ($query = $this->get_next_query()) !== false ){

                // normalize table names..
                $pattern = '#^INSERT INTO `{(K_TBL_(?:[A-Z_]+))}` VALUES \(#';
                $query = @preg_replace_callback(
                    $pattern,
                    function( $matches ) use ($tbls){
                        $tbl_name = $tbls[$matches[1]];
                        $replacement = 'INSERT INTO `'.$tbl_name.'` VALUES (';
                        return $replacement;
                    },
                    $query,
                    1);

                // HOOK: restore_dump_normalize_query
                $FUNCS->dispatch_event( 'restore_dump_normalize_query', array(&$query) );
                //$this->log( $query . "\n" );

                // execute query..
                @mysql_query( $query );
                if( mysql_errno() ){
                    @mysql_query( "ROLLBACK" );
                    $err = mysql_errno() . ": " . mysql_error() . "<br />" . $query;
                    return $FUNCS->raise_error( $err );
                }

                $cur_time = time();
                if( $cur_time + 25 > $start_time ){
                    header( "X-Dummy: wait" ); // make browser wait
                    $start_time = $cur_time;
                }

                if( $this->cnt_queries >= $this->limit ){
                    break;
                }
            }
            @mysql_query( "COMMIT" );

            if( $query === false ){
                return false;
            }
            else{
                return true;
            }
        }

        // Returns a query from the current file pointer position.
        // Returns false if no query could be read.
        function get_next_query(){

            $query = $buf = "";
            while( ($buf = $this->get_next_line()) !== false ){    
                $tmpbuf = trim( $buf );

                // if comments, ignore..
                if( $tmpbuf == '' || substr($tmpbuf, 0, 2) == '--' || substr($tmpbuf, 0, 3) == '/*!' || substr($tmpbuf, 0, 1) == '#' || substr($tmpbuf, 0, 5) == '<?php'){
                    continue;
                }

                $query .= $buf;
                if( substr($tmpbuf, -1, 1) != ';' ){
                    continue;
                }
                break;
            }
            
            if( $query === "" ){
                return false;
            }
            else{
                $this->cnt_queries++;
                return $query;
            }
        }

        // Returns a line from the current file pointer position.
        // Returns false if no line could be read.
        function get_next_line(){

            $line = $buf = "";
            while( ($buf = fgets($this->fp, $this->max_chars)) !== false ){
                $line .= $buf;
                if( substr($buf, -1) != "\n" && substr($buf, -1) != "\r" ){
                    continue;
                }
                break;
            }

            if( $line === "" ){
                return false;
            }
            else{
                $this->cnt_lines++;
                return $line;
            }
        }
 
        function log( $msg ){ // for debugging

            $file = 'dump-log.txt';

            $fp = @fopen( $file,'a' );
            if( $fp ){
                @flock( $fp, LOCK_EX );
                @fwrite( $fp, $msg );
                @flock( $fp, LOCK_UN );
                @fclose( $fp );
            }
        }
    } // end class
   