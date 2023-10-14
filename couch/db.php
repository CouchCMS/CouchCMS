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

    define( 'K_TBL_TEMPLATES', K_DB_TABLES_PREFIX . 'couch_templates' );
    define( 'K_TBL_FIELDS', K_DB_TABLES_PREFIX . 'couch_fields' );
    define( 'K_TBL_PAGES', K_DB_TABLES_PREFIX . 'couch_pages' );
    define( 'K_TBL_FOLDERS', K_DB_TABLES_PREFIX . 'couch_folders' );
    define( 'K_TBL_USERS', K_DB_TABLES_PREFIX . 'couch_users' );
    define( 'K_TBL_USER_LEVELS', K_DB_TABLES_PREFIX . 'couch_levels' );
    define( 'K_TBL_SETTINGS', K_DB_TABLES_PREFIX . 'couch_settings' );
    define( 'K_TBL_DATA_TEXT', K_DB_TABLES_PREFIX . 'couch_data_text' );
    define( 'K_TBL_DATA_NUMERIC', K_DB_TABLES_PREFIX . 'couch_data_numeric' );
    define( 'K_TBL_FULLTEXT', K_DB_TABLES_PREFIX . 'couch_fulltext' );
    define( 'K_TBL_COMMENTS', K_DB_TABLES_PREFIX . 'couch_comments' );
    define( 'K_TBL_RELATIONS', K_DB_TABLES_PREFIX . 'couch_relations' );
    define( 'K_TBL_ATTACHMENTS', K_DB_TABLES_PREFIX . 'couch_attachments' );
    define( 'K_TBL_SUB_TEMPLATES', K_DB_TABLES_PREFIX . 'couch_sub_templates' );


    class KDB{
        var $host_name = '';
        var $database = '';
        var $user_name = '';
        var $password = '';
        var $conn = 0;
        var $result;
        var $rows_affected = 0;
        var $last_insert_id = 0;

        var $db = 0;
        var $ref = 0; // reference counting of transactions

        // debug
        var $debug = 0;
        var $selects = 0;
        var $inserts = 0;
        var $updates = 0;
        var $deletes = 0;
        var $queries = 0;
        var $query_time = 0;

        function __construct( $host_name='', $database='', $user_name='', $password='' ){
            if( empty($host_name) ) $host_name = K_DB_HOST;
            if( empty($database) ) $database = K_DB_NAME;
            if( empty($user_name) ) $user_name = K_DB_USER;
            if( empty($password) ) $password = K_DB_PASSWORD;

            $this->host_name = $host_name;
            $this->database = $database;
            $this->user_name = $user_name;
            $this->password = $password;
        }

        function connect(){
            $this->conn = @mysql_connect( $this->host_name, $this->user_name, $this->password );
            if( !$this->conn ) return 0;
            $db_selected = @mysql_select_db( $this->database, $this->conn );
            if( $db_selected ){
                @mysql_query( "SET NAMES 'utf8'", $this->conn );
                @mysql_query( "SET COLLATION_CONNECTION=utf8_general_ci", $this->conn );
                @mysql_query( "SET sql_mode = ''", $this->conn );
            }
            return $db_selected;
        }

        function disconnect(){
            if( $this->conn ) mysql_close( $this->conn );
        }

        function _query( $sql ){
            $sql = trim( $sql );
            if( $sql=='' ) return;

            if( !$this->conn ){
                if( !$this->connect() ) die( "Unable to connect to database. " . mysql_error() );
            }

            $t=0;
            if( $this->debug ){ $t = microtime(true); }
            $this->result = @mysql_query( $sql, $this->conn );
            if( $this->debug ){ $this->query_time += (microtime(true) - $t); }

            if( !$this->result ){
                //die( "Could not successfully run query (".$sql.") from DB: " . mysql_error() );
                ob_end_clean();
                die( "Could not successfully run query: " . mysql_error( $this->conn ) );
            }

            $this->queries++;
            return $this->result;

        }

        function select( $tbl, $params, $clause='', $distinct='' ){
            $sep = '';
            foreach( $params as $field ){
                $fields .= $sep . $field;
                $sep = ', ';
            }
            $sql = ( $distinct ) ? 'SELECT DISTINCT ' : 'SELECT ';
            $sql .= $fields . ' FROM ' . $tbl;
            if( $clause ) $sql .= ' WHERE ' . $clause;

            $this->_query( $sql );

            $rows = array();
            while( $row = mysql_fetch_assoc($this->result) ) {
                $rows[] = $row;
            }
            mysql_free_result( $this->result );

            $this->selects++;
            return $rows;
        }

        function raw_select( $sql, $key='' ){
            $key = trim( $key );

            $this->_query( $sql );

            $rows = array();
            if( $key ){
                while( $row = mysql_fetch_assoc($this->result) ) {
                    $rows[$row[$key]] = $row;
                }
            }
            else{
                while( $row = mysql_fetch_assoc($this->result) ) {
                    $rows[] = $row;
                }
            }
            mysql_free_result( $this->result );

            $this->selects++;
            return $rows;
        }

        function insert( $tbl, $params ){
            $sep = '';
            foreach( $params as $k=>$v ){
                $fields .= $sep . $k;
                $values .= $sep. "'" . $this->sanitize( $v ) . "'";
                $sep = ', ';
            }
            $sql = 'INSERT INTO ' . $tbl . ' (' . $fields . ') VALUES(' . $values . ')';
            $this->_query( $sql );

            $this->rows_affected = mysql_affected_rows( $this->conn );
            $this->last_insert_id = mysql_insert_id( $this->conn );

            $this->inserts++;
            return $this->rows_affected;
        }

        function update( $tbl, $params, $clause ){
            $sep = '';
            foreach( $params as $k=>$v ){
                $values .= $sep. $k. " = '" . $this->sanitize( $v ) . "'";
                $sep = ', ';
            }
            $sql = 'UPDATE ' . $tbl . ' SET ' . $values . ' WHERE ' . $clause;
            $this->_query( $sql );

            $this->rows_affected = mysql_affected_rows( $this->conn );

            $this->updates++;
            return $this->rows_affected;
        }

        function delete( $tbl, $clause ){
            if( trim($clause)=='' ) return 0;

            $sql = 'DELETE FROM ' . $tbl . ' WHERE ' . $clause;
            $this->_query( $sql );

            $this->rows_affected = mysql_affected_rows( $this->conn );

            $this->deletes++;
            return $this->rows_affected;

        }

        function sanitize( $str ){
            if( function_exists('mysql_real_escape_string') ){
                if( !$this->conn ){
                    if( !$this->connect() ) die("Unable to connect to database" );
                }
                return @mysql_real_escape_string( $str, $this->conn );
            }
            else{
                return mysql_escape_string( $str );
            }
        }

        // Transaction control is pretty hackish .. but is serving my purpose for now.
        function begin(){
            //@mysql_query( "SET autocommit=0" );
            //@mysql_query( "BEGIN" );
            $this->ref++;
            if( $this->ref==1 ){
                @mysql_query( "START TRANSACTION", $this->conn );
            }
        }

        function commit( $force=0 ){
            $this->ref--;
            if( $this->ref==0 || $force ){
                @mysql_query( "COMMIT", $this->conn );
            }
        }

        function rollback( $force=0 ){
            $this->ref--;
            if( $this->ref==0|| $force ){
                @mysql_query( "ROLLBACK", $this->conn );
            }
        }

        /*
            Process level lock.
            Returns 1 if lock obtained else 0
            Obtained lock will be freed by either explictly calling 'release_lock'
            or automatically when the PHP script ends
            Note: locks are not released when transactions commit or roll back.
        */
        function get_lock( $name ){
            $name = trim( $name );
            if( $name=='' ) return 0;

            if( !$this->is_free_lock($name) ) return 0;

            $sql = "SELECT GET_LOCK('".$this->sanitize( $name )."', 0) AS lck";
            $rs = $this->raw_select( $sql );
            $ret = ( count($rs) ) ? $rs[0]['lck'] : 0;

            return (int)$ret;
        }

        function release_lock( $name ){
            $name = trim( $name );
            if( $name=='' ) return 0;

            $sql = "SELECT RELEASE_LOCK('".$this->sanitize( $name )."') AS lck";
            $rs = $this->raw_select( $sql );
            $ret = ( count($rs) ) ? $rs[0]['lck'] : 0;

            return (int)$ret;
        }

        function is_free_lock( $name ){
            $name = trim( $name );
            if( $name=='' ) return 0;

            $sql = "SELECT IS_FREE_LOCK('".$this->sanitize( $name )."') AS lck";
            $rs = $this->raw_select( $sql );
            $ret = ( count($rs) ) ? $rs[0]['lck'] : 0;

            return (int)$ret;
        }
    }
