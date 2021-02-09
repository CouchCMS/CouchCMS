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

    if ( !defined('K_COUCH_DIR') ) define( 'K_COUCH_DIR', str_replace( '\\', '/', dirname(realpath(__FILE__) ).'/') );
    require_once( K_COUCH_DIR.'header.php' );

    define( 'K_ADMIN', 1 );
    if( !defined('K_SQL_DUMP_USE_EXTENDED_INSERTS') ) define( 'K_SQL_DUMP_USE_EXTENDED_INSERTS', 0 );

    if( $AUTH->user->access_level < K_ACCESS_LEVEL_ADMIN ) die( '<h3>Please login as admin.</h3>' );

    $tbls = array();
    $tbls[K_TBL_TEMPLATES] = 'K_TBL_TEMPLATES';
    $tbls[K_TBL_FIELDS] = 'K_TBL_FIELDS';
    $tbls[K_TBL_PAGES] = 'K_TBL_PAGES';
    $tbls[K_TBL_FOLDERS] = 'K_TBL_FOLDERS';
    $tbls[K_TBL_DATA_TEXT] = 'K_TBL_DATA_TEXT';
    $tbls[K_TBL_DATA_NUMERIC] = 'K_TBL_DATA_NUMERIC';
    $tbls[K_TBL_FULLTEXT] = 'K_TBL_FULLTEXT';
    $tbls[K_TBL_COMMENTS] = 'K_TBL_COMMENTS';
    $tbls[K_TBL_RELATIONS] = 'K_TBL_RELATIONS';

    $use_extended_inserts = K_SQL_DUMP_USE_EXTENDED_INSERTS || $FUNCS->dispatch_event( 'sql_dump_use_extended_inserts' );
    $filename = ( $use_extended_inserts ) ? 'install-ex2.php' : 'install-ex.php';

    /* output header */
    header( "Expires: Fri, 01 Jan 1990 00:00:00 GMT" );
    header( "Cache-Control: must-revalidate, post-check=0, pre-check=0" );
    header( "Pragma: no-cache" );
    header( "Content-Type: application/octet-stream" );
    header( "Content-Disposition: attachment; filename=".$filename );

    // HOOK: alter_gen_dump_tables
    $FUNCS->dispatch_event( 'alter_gen_dump_tables', array(&$tbls) );

    /* loop through each core table */
    @set_time_limit( 0 );
    if( !$use_extended_inserts ){
        echo '<';
        echo "?php\n";
        echo "if ( !defined('K_INSTALLATION_IN_PROGRESS') ) die(); // cannot be loaded directly\n";

        foreach( $tbls as $tbl_name=>$tbl_alias ){
            // HOOK: gen_dump_table_where
            $where = array();
            $FUNCS->dispatch_event( 'gen_dump_table_where', array(&$where, $tbl_name, $tbl_alias) );
            $where = count( $where ) ? ' where ' . implode( ' || ', $where ) : '';

            echo "\n/* " . $tbl_name . " (";
            $result = mysql_query( 'select * from '. $tbl_name . $where );
            if( !$result ){
                die('Query failed: ' . mysql_error());
            }

            /* get column metadata */
            $i = 0;
            $meta_fields = array();
            $cnt_fields = mysql_num_fields($result);
            $sep = '';
            while( $i < $cnt_fields ){
                $meta_fields[] = mysql_fetch_field( $result, $i );
                echo $sep . $meta_fields[$i]->name;
                $sep = ', ';
                $i++;
            }
            echo ") */\n";
            echo '$k_stmt_pre = "INSERT INTO ".'.$tbl_alias.'." VALUES ";'."\n";
            $stmt_pre = '$k_stmts[] = $k_stmt_pre."(';


            /* get the column values */
            $val_from = array( "\'", '$' );
            $val_to = array( "'", '\$' );
            while( $row = mysql_fetch_row($result) ){
                $sep = '';
                $stmt = $stmt_pre;
                for( $i=0; $i<$cnt_fields; $i++ ){
                    if( is_null($row[$i]) || !isset($row[$i]) ){
                        $val = 'NULL';
                    }
                    elseif( $meta_fields[$i]->numeric ){
                        $val = $row[$i];
                    }
                    else{
                        // Sanitize will add slashes to backslash, quote, doubleqoute, newline and return chars.
                        // We need to slash all of them again for PHP, except the single quote.
                        // Plus slash any dollar char that misleads PHP into thinking it is dealing with a variable.
                        $val = '\'' . str_replace($val_from, $val_to, addslashes($DB->sanitize($row[$i]))) . '\'';
                    }
                    $stmt .= $sep . $val;
                    $sep = ', ';
                }
                $stmt .= ');";' . "\n";

                /* output the complete statement  */
                echo $stmt;
            }
            /* clean up */
            mysql_free_result($result);

        }
    }
    else{
        echo '<';
        echo "?php ";
        echo "exit('Access denied'); __halt_compiler(); ?";
        echo ">\n";

        foreach( $tbls as $tbl_name=>$tbl_alias ){

            // HOOK: gen_dump_table_where
            $where = array();
            $FUNCS->dispatch_event( 'gen_dump_table_where', array(&$where, $tbl_name, $tbl_alias) );
            $where = count( $where ) ? ' where ' . implode( ' || ', $where ) : '';

            echo "\n--\n-- Dumping data for table `".$tbl_name."`\n--\n";
            $result = mysql_query( 'select * from '. $tbl_name . $where );
            if( !$result ){
                die('Query failed: ' . mysql_error());
            }

            /* get column metadata */
            $i = 0;
            $meta_fields = array();
            $cnt_fields = mysql_num_fields($result);
            while( $i < $cnt_fields ){
                $meta_fields[] = mysql_fetch_field( $result, $i );
                $i++;
            }

            /* get the column values */
            $query = '';
            $separator = ',';
            $end = ";\n";
            $insert = "INSERT INTO `{".$tbl_alias."}` VALUES ";
            while( $row = mysql_fetch_row($result) ){
                $sep = '';
                $values = '(';
                for( $i=0; $i<$cnt_fields; $i++ ){
                    if( is_null($row[$i]) || !isset($row[$i]) ){
                        $val = 'NULL';
                    }
                    elseif( $meta_fields[$i]->numeric ){
                        $val = $row[$i];
                    }
                    else{
                        $val = "'" . $DB->sanitize($row[$i]) . "'";
                    }
                    $values .= $sep . $val;
                    $sep = ',';
                }
                $values .= ')';

                if( $query=='' ){
                    $query = $insert . $values;
                }
                elseif( (strlen($query) + strlen($separator) + strlen($values) + strlen($end)) < 1000000 ){ // max size
                    $query .= $separator . $values;
                }
                else{
                    $query .= $end;
                    echo $query;
                    $query = $insert . $values;
                }
            }
            if( $query ){
                $query .= $end;
                echo $query;
            }

            /* clean up */
            mysql_free_result($result);
        }
    }
