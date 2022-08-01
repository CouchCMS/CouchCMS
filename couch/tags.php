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

    class KTags{

        function test( $params, $node ){
            global $FUNCS, $CTX;

            extract( $FUNCS->get_named_vars(
                        array(
                               'ignore'=>'0',
                               'hide'=>'0',
                              ),
                        $params)
                   );
            $ignore = ( $ignore==1 ) ? 1 : 0;
            if( $ignore ) return;
            $hide = ( $hide==1 ) ? 1 : 0;

            foreach( $node->children as $child ){
                $html .= $child->get_HTML();
            }
            return ( $hide )? '' : $html;
        }

        // Dumps all variables present in all the contexts starting from root upto the context the tag was invoked from.
        function dump_all( $params, $node ){
            global $CTX;
            if( count($node->children) ) {die("ERROR: Tag \"".$node->name."\" is a self closing tag");}

            $html = '<UL>';
            for( $x=0; $x<count($CTX->ctx); $x++ ){
                if( isset($CTX->ctx[$x]['_scope_']) ){
                    $html .= '<LI>';
                    $html .= '<b><font color="red">'.$CTX->ctx[$x]['name'].'</font></b>';
                    $html .= '<UL>';
                    foreach( $CTX->ctx[$x]['_scope_'] as $k=>$v ){
                        $html .= '<LI>';
                        $html .= '<b>'.$k.': </b>'.$v;
                        $html .= '</LI>';
                    }
                    $html .= '</UL>';
                    $html .= '</LI>';
                }
            }
            $html .= '</UL>';

            return $html;
        }

        // Dumps all variables present in the local context the tag was invoked from.
        function dump( $params, $node ){
            global $CTX;
            if( count($node->children) ) {die("ERROR: Tag \"".$node->name."\" is a self closing tag");}

            $html = '<UL>';
            for( $x=count($CTX->ctx)-1; $x>=0; $x-- ){
                if( isset($CTX->ctx[$x]['_scope_']) ){
                    $html .= '<LI>';
                    $html .= '<b><font color="red">'.$CTX->ctx[$x]['name'].'</font></b>';
                    $html .= '<UL>';
                    foreach( $CTX->ctx[$x]['_scope_'] as $k=>$v ){
                        $html .= '<LI>';
                        $html .= '<b>'.$k.': </b>'.$v;
                        $html .= '</LI>';
                    }
                    $html .= '</UL>';
                    $html .= '</LI>';
                    break;
                }
            }
            $html .= '</UL>';

            return $html;
        }

        function show( $params, $node ){
            global $FUNCS, $CTX;
            if( count($node->children) ) {die("ERROR: Tag \"".$node->name."\" is a self closing tag");}

            extract( $FUNCS->get_named_vars(
                        array( 'var'=>'', /*placeholder*/
                               'scope'=>'',
                               'as_json'=>'0',
                              ),
                        $params)
                   );

            $value = $params[0]['rhs'];
            $scope = strtolower( trim($scope) );
            $as_json = ( $as_json==1 ) ? 1 : 0;

            // If scope set and first param is a variable, return variable only from the specified scope scope.
            if( $scope != '' && $node->attributes[0]['value_type']==K_VAL_TYPE_VARIABLE ){
                if( $scope!='1' && $scope!='2' && $scope!='global' && $scope!='local'  ){
                    die("ERROR: Tag \"".$node->name."\" has unknown scope '" . $scope. "'. Only 'global (2)' or 'local (1)' are valid.");
                }
                $scope = ( $scope=='global' || $scope=='2' ) ? 2 : 1;

                $value = $CTX->get( $node->attributes[0]['value'], $scope );
            }

            if( $as_json && is_array($value) ){ $value = $FUNCS->json_encode( $value ); }
            return $value;
        }

        function set( $params, $node ){
            global $FUNCS, $CTX;
            if( count($node->children) ) {die("ERROR: Tag \"".$node->name."\" is a self closing tag");}

            extract( $FUNCS->get_named_vars(
                        array(
                               'scope'=>'',
                               'is_json'=>'0',
                              ),
                        $params)
                   );

            $varname = $params[0]['lhs'];
            $value = $params[0]['rhs'];
            $scope = strtolower( trim($scope) );
            $is_json = ( $is_json==1 ) ? 1 : 0;

            if( $varname ){
                if( $scope != '' && ($scope!='parent' && $scope!='global' && $scope!='local') ){
                    die("ERROR: Tag \"".$node->name."\" has unknown scope '" . $scope. "'. Only 'global', 'local' or 'parent' are valid.");
                }

                if( substr($varname, 0, 2)!='k_' ){
                    if( $is_json && !is_array($value) ){ $value = $FUNCS->json_decode( $value ); }
                    $CTX->set( $varname, $value, $scope );
                }
                else{
                    die("ERROR: \"".$varname."\" cannot be set because it begins with 'k_' (considered system variable)");
                }
            }
        }

        // Gets a variable's value given its name as a string.
        function get( $params, $node ){
            global $CTX, $FUNCS;
            if( count($node->children) ) {die("ERROR: Tag \"".$node->name."\" is a self closing tag");}

            extract( $FUNCS->get_named_vars(
                        array( 'var'=>'',
                               'local_only'=>'',
                               'default'=>'',
                               'scope'=>null,
                               'as_json'=>'0',
                               'into'=>'',
                               'into_scope'=>'',
                              ),
                        $params)
                   );

            $var = trim($var);
            $tmp = ( $local_only==1 ) ? 1 : 0;
            $has_default = ( strlen($default) ) ? 1 : 0;
            $as_json = ( $as_json==1 ) ? 1 : 0;
            $into = trim( $into );
            $into_scope = strtolower( trim($into_scope) );
            if( $into_scope != '' && ($into_scope!='parent' && $into_scope!='global' && $into_scope!='local') ){
                die("ERROR: Tag \"".$node->name."\" has unknown into_scope '" . $into_scope. "'. Only 'global', 'local' or 'parent' are valid.");
            }

            // v2.0 - this new parameter, if set, overrides 'local_only'
            if( !is_null($scope) ){
                $scope = strtolower( trim($scope) );
                if( $scope != '' ){
                    if( $scope!='global' && $scope!='local' ){
                        die("ERROR: Tag \"".$node->name."\" has unknown scope '" . $scope. "'. Only 'global' or 'local' are valid.");
                    }
                    $tmp = ( $scope=='global' ) ? 2 : 1;
                }
            }
            $scope = $tmp;

            if( $var ){
                $value = $CTX->get( $var, $scope );
                if( $has_default ){
                    $has_content = is_array($value) ? count($value) : strlen($value);
                    if( !$has_content )$value = $default;
                }

                if( $into!='' ){
                    $CTX->set( $into, $value, $into_scope );
                }
                else{
                    if( $as_json && is_array($value) ){ $value = $FUNCS->json_encode( $value ); }
                    return $value;
                }
            }
        }

        // Sets a variable's value given its name as a string.
        function put( $params, $node ){
            global $CTX, $FUNCS;
            if( count($node->children) ) {die("ERROR: Tag \"".$node->name."\" is a self closing tag");}

            extract( $FUNCS->get_named_vars(
                        array( 'var'=>'',
                               'value'=>'',
                               'scope'=>'',
                               'is_json'=>'0',
                              ),
                        $params)
                   );

            $varname = trim( $var );
            $scope = strtolower( trim($scope) );
            $is_json = ( $is_json==1 ) ? 1 : 0;

            if( $varname ){
                if( $scope != '' && ($scope!='parent' && $scope!='global' && $scope!='local') ){
                    die("ERROR: Tag \"".$node->name."\" has unknown scope " . $scope);
                }

                if( substr($varname, 0, 2)!='k_' ){
                    if( $is_json && !is_array($value) ){ $value = $FUNCS->json_decode( $value ); }
                    $CTX->set( $varname, $value, $scope );
                }
                else{
                    die("ERROR: \"".$varname."\" cannot be set because it begins with 'k_' (considered system variable)");
                }
            }
        }

        // Used to get a field by specifying the template name and page name.
        // If no page name specified, the 'default' page will be used (suits non-clonable pages.
        // If no masterpage specified, the current template will be used.
        function get_field( $params, $node ){
            global $CTX, $FUNCS, $PAGE;
            static $cache = array();

            $attr = $FUNCS->get_named_vars(
                        array( 'var'=>'',
                               'masterpage'=>'',
                               'page'=>'',
                               'id'=>''
                              ),
                        $params);
            extract( $attr );

            $var = trim($var);
            if( $var ){
                if( !$masterpage ){
                    // use the current template
                    $masterpage = $PAGE->tpl_name;
                }

                // check cache first
                unset( $attr['var'] );
                $key = md5( serialize($attr) );
                if( isset($cache[$key]) ){
                    $pg = $cache[$key];
                }
                else{
                    if( $page!=='' ){
                        $pg = new KWebpage( $masterpage, 0, $page );
                    }
                    elseif( $id ){
                        $pg = new KWebpage( $masterpage, $id );
                    }
                    else{
                        $pg = new KWebpage( $masterpage ); //if no page specified, use the default page
                    }

                    // cache last accessed 25 objects
                    if( count($cache) >= 25 ){
                        reset( $cache );
                        $orig_key = key( $cache );
                        $cache[$orig_key]->destroy();
                        unset( $cache[$orig_key] );
                    }
                    $cache[$key] = $pg;
                }

                if( $pg->error ) return;
                if( isset($pg->_fields[$var]) ){
                    $data = $pg->_fields[$var]->get_data( 1 );
                }

                if( count($node->children) ){
                    $CTX->set( 'k_field_name', $var );
                    $CTX->set( 'k_field_val', $data );

                    foreach( $node->children as $child ){
                        $html .= $child->get_HTML();
                    }
                    return $html;
                }
                else{
                    return $data;
                }
            }
        }

        // deprecated.. piggybacks for now on cms:get_field
        function get_custom_field( $params, $node ){
            return $this->get_field( $params, $node );
        }

        function capture( $params, $node ){
            global $CTX, $FUNCS;

            extract( $FUNCS->get_named_vars(
                        array( 'into'=>'',
                               'scope'=>'',
                               'trim'=>'0',
                               'is_json'=>'0',
                              ),
                        $params)
                   );
            $varname = trim( $into );
            $scope = strtolower( trim($scope) );
            if( $scope=='' ) $scope='global';
            $trim = ( $trim==1 ) ? 1 : 0;
            $is_json = ( $is_json==1 ) ? 1 : 0;

            if( $varname ){
                if( $scope!='parent' && $scope!='global' ){ //local scope makes no sense
                    die("ERROR: Tag \"".$node->name."\" has unknown scope " . $scope);
                }

                if( substr($varname, 0, 2)!='k_' ){
                    $children = $node->children;
                    foreach( $children as $child ){
                        $html .= $child->get_HTML();
                    }

                    if( $trim ){ $html = trim( $html ); }
                    if( $is_json ){ $html = $FUNCS->json_decode( $html ); }
                    $CTX->set( $varname, $html, $scope );
                }
                else{
                    die("ERROR: \"".$varname."\" cannot be set because it begins with 'k_' (considered system variable)");
                }
            }
        }

        function concat( $params, $node ){
            for( $x=0; $x<count($params); $x++ ){
                if( $params[$x]['rhs'] == '\r\n' || $params[$x]['rhs'] == '\r' || $params[$x]['rhs'] == '\n' ){
                    $html .= "\r\n";
                }
                elseif( $params[$x]['rhs'] == '\t' ){
                    $html .= "\t";
                }
                else{
                    $html .= $params[$x]['rhs'];
                }
            }
            return $html;
        }

        // Splits up a string (var) along seprator (sep) returning each value in a loop as item (as)
        function each( $params, $node ){
            global $FUNCS, $CTX;
            extract( $FUNCS->get_named_vars(
                        array( 'var'=>'',
                               'as'=>'',
                               'sep'=>'|',
                               'key'=>'',
                               'startcount'=>'0',
                               'token'=>'',
                               'is_json'=>'0',
                               'is_regex'=>'0',
                              ),
                        $params)
                   );

            $as = trim( $as ); if( $as=='' ){ $as='item'; }
            $key = trim( $key ); if( $key=='' ){ $key='key'; }
            $startcount = $FUNCS->is_int( $startcount ) ? intval( $startcount ) : 1;
            $token = trim( $token );
            $is_json = ( $is_json==1 ) ? 1 : 0;
            $is_regex = ( $is_regex==1 ) ? 1 : 0;

            if( $is_json && !is_array($var) ){ $var = $FUNCS->json_decode( $var ); }

            if( !is_array($var) ){
                if( $is_regex ){
                    $regex = $sep;
                }
                else{
                    if( !$sep ) $sep = '|';
                    if( $sep == '\r\n' || $sep == '\r' || $sep == '\n' ){
                        $var = str_replace( array("\r\n", "\r", "\n" ), "\n", $var );
                        $sep = "\n";
                    }
                    elseif( $sep == '\t' ){
                        $sep = "\t";
                    }
                    else{
                        $use_preg=1;
                    }
                }

                if( $var ){
                    if( $regex ){
                        $arr_vars = array_map( "trim", preg_split( $regex, $var ) );
                    }
                    elseif( $use_preg ){
                        $arr_vars = array_map( "trim", preg_split( "/(?<!\\\)".preg_quote($sep, '/')."/", $var ) ); // allows escaping of separator with a backslash
                    }
                    else{
                        $arr_vars = array_map( "trim", explode( $sep, $var ) );
                    }
                }

                if( is_array($arr_vars) ){
                    $cnt_arr = count($arr_vars);
                    $CTX->set( 'k_total_items', $cnt_arr );
                    $children = $node->children;

                    for( $x=0; $x<count($arr_vars); $x++ ){
                        $CTX->set( 'k_count', $x + $startcount );
                        $CTX->set( 'k_first_item', ($x==0) ? '1' : '0' );
                        $CTX->set( 'k_last_item', ($x==$cnt_arr-1) ? '1' : '0' );
                        $CTX->set( $key, $x );
                        if( $use_preg ){
                            $CTX->set( $as, str_replace( '\\'.$sep, $sep, $arr_vars[$x] ) ); //unescape separator
                        }
                        else{
                            $CTX->set( $as, $arr_vars[$x] );
                        }

                        // setup a way for the child nodes to signal 'break' or 'continue'
                        $arr_config = array( 'break'=>0, 'continue'=>0 );
                        $CTX->set_object( '__config', $arr_config );

                        // HOOK: each_alter_ctx_xxx
                        if( $token ){
                            $FUNCS->dispatch_event( 'each_alter_ctx_'.$token, array($x /*key*/, $arr_vars[$x] /*value*/, $params, $node) );
                        }

                        foreach( $children as $child ){
                            $html .= $child->get_HTML();

                            if( $child->type==K_NODE_TYPE_CODE){
                                if( $arr_config['break'] ){ $count++; break 2; }
                                if( $arr_config['continue'] ){ $count++; continue 2; }
                            }
                        }
                    }
                }
            }
            else{
                $cnt_arr = count($var);
                $CTX->set( 'k_total_items', $cnt_arr );
                $children = $node->children;

                $count = 0;
                foreach( $var as $k=>$v ){
                    $CTX->set( 'k_count', $count + $startcount );
                    $CTX->set( 'k_first_item', ($count==0) ? '1' : '0' );
                    $CTX->set( 'k_last_item', ($count==$cnt_arr-1) ? '1' : '0' );
                    $CTX->set( $key, $k );
                    $CTX->set( $as, $v );

                    // setup a way for the child nodes to signal 'break' or 'continue'
                    $arr_config = array( 'break'=>0, 'continue'=>0 );
                    $CTX->set_object( '__config', $arr_config );

                    // HOOK: each_alter_ctx_xxx
                    if( $token ){
                        $FUNCS->dispatch_event( 'each_alter_ctx_'.$token, array($k /*key*/, $v /*value*/, $params, $node) );
                    }

                    foreach( $children as $child ){
                        $html .= $child->get_HTML();

                        if( $child->type==K_NODE_TYPE_CODE){
                            if( $arr_config['break'] ){ $count++; break 2; }
                            if( $arr_config['continue'] ){ $count++; continue 2; }
                        }
                    }

                    $count++;
                }
            }

            return $html;
        }

        function k_break( $params, $node ){
            global $CTX;
            if( count($node->children) ) {die("ERROR: Tag \"break\" is a self closing tag");}

            // get the 'config' object supplied by 'cms:each' tag
            $arr_config = &$CTX->get_object( '__config', 'each' );
            if( is_array($arr_config) && isset($arr_config['break']) ){
                $arr_config['break']=1;
            }
        }

        function k_continue( $params, $node ){
            global $CTX;
            if( count($node->children) ) {die("ERROR: Tag \"continue\" is a self closing tag");}

            // get the 'config' object supplied by 'cms:each' tag
            $arr_config = &$CTX->get_object( '__config', 'each' );
            if( is_array($arr_config) && isset($arr_config['continue']) ){
                $arr_config['continue']=1;
            }
        }

        function is_array( $params, $node ){
            global $CTX;
            if( count($node->children) ) {die("ERROR: Tag \"".$node->name."\" is a self closing tag");}

            return is_array( $params[0]['rhs'] ) ? '1' : '0';
        }

        function array_count( $params, $node ){
            global $CTX;
            if( count($node->children) ) {die("ERROR: Tag \"".$node->name."\" is a self closing tag");}

            return is_array( $params[0]['rhs'] ) ? count($params[0]['rhs']) : '0';
        }

        function embed( $params, $node ){
            global $CTX;

            $act = strtolower( trim($params[0]['lhs']) );
            $snippet = $params[0]['rhs'];
            if( $snippet ){

                // if cms:embed used as a tag-pair, add context to it
                if( count($node->children) && $node->name=='embed' ){ //do_shortcodes also masquerdes as embed
                    $CTX->push( '__embed__' );
                    $has_context = 1;

                    foreach( $node->children as $child ){
                        $_content .= $child->get_HTML();
                    }

                    $CTX->set( 'k_content', $_content );
                }

                // set any passed params into Context
                for( $x=1; $x<count($params); $x++ ){
                    if( $params[$x]['op']=='=' && $params[$x]['lhs']) $CTX->set( $params[$x]['lhs'], $params[$x]['rhs'] );
                }

                // default is embed 'file' from snippet folder
                if( $act=='code' ){
                    $html = $snippet;
                }
                else{
                    if( defined('K_SNIPPETS_DIR') ){ // always defined relative to the site
                        $base_snippets_dir = K_SITE_DIR . K_SNIPPETS_DIR . '/';
                    }
                    else{
                        $base_snippets_dir = K_COUCH_DIR . 'snippets/';
                    }

                    $filepath = $base_snippets_dir . ltrim( trim($snippet), '/\\' );
                    $html = @file_get_contents( $filepath );
                    if( $html===FALSE ){
                        if( $has_context ) $CTX->pop();
                        return 'Error embedding file: ' . htmlspecialchars( $filepath );
                    }
                }

                if( $html ){
                    $parser = new KParser( $html, $node->line_num, 0, '', $node->ID );
                    if( $act!='code' && K_CACHE_OPCODES ){
                        $html = $parser->get_cached_HTML( $filepath );
                    }
                    else{
                        $html = $parser->get_HTML();
                    }
                }
                if( $has_context ) $CTX->pop();

                return $html;
            }
        }

        /*
         * As opposed to 'embed', this tag takes only the folder name, and not the filename, as parameter.
         * It demonstrates its 'smartness' by figuring out the filename to be embedded depending upon the view of the current page.
         */
        function smart_embed( $params, $node ){
            global $CTX, $FUNCS, $PAGE;
            if( count($node->children) ) {die("ERROR: Tag \"".$node->name."\" is a self closing tag");}

            extract( $FUNCS->get_named_vars(
                        array( 'folder'=>'',
                               'debug'=>'0'
                              ),
                        $params)
                   );

            // sanitize params
            $debug = trim( $debug );
            if( defined('K_SNIPPETS_DIR') ){ // always defined relative to the site
                $base_snippets_dir = K_SITE_DIR . K_SNIPPETS_DIR;
            }
            else{
                $base_snippets_dir = K_COUCH_DIR . 'snippets';
            }
            $folder = trim( trim($folder), '/\\' );
            $folder_name = $folder;
            if( !$folder_name ) $folder_name = '/';
            $folder = $base_snippets_dir . ( ($folder) ? '/'.$folder : '' ); //full path

            // What are the files available in the specified folder?
            // First check if info available from cache
            if( array_key_exists($folder_name, $FUNCS->cached_files) ){
                $available_files = $FUNCS->cached_files[$folder_name];
            }
            else{
                $available_files = array();
                if( is_dir($folder) && $fp=opendir($folder) ){
                    while( ($file = readdir($fp))!== false ){
                        if( is_file($folder . '/' . $file) ){
                            $pi = $FUNCS->pathinfo( $file );
                            if( $pi['filename'] ){
                                $available_files[$pi['filename']] = $pi['basename'];
                            }
                        }
                    }
                    closedir( $fp );

                    // cache results
                    $FUNCS->cached_files[$folder_name] = $available_files;
                }
                else{
                    if( !$debug ) return;
                }
            }

            // What are the candidate file names for the current view?
            // First check cache
            if( property_exists($FUNCS, 'cached_valid_files_for_view') ){
                $valid_files = $FUNCS->cached_valid_files_for_view;
            }
            else{
                // What is the current view?
                if( $PAGE->tpl_is_clonable ){ //views associated only with clonable templates
                    if( $PAGE->is_master ){
                        if( $PAGE->is_folder_view ){
                            $view = 'folder';
                        }
                        elseif( $PAGE->is_archive_view ){
                            $view = 'archive';
                        }
                        else{
                            $view = 'home';
                        }
                    }
                    else{
                        $view = 'page';
                    }
                }

                $valid_files = array();
                $tplname = $PAGE->tpl_name;
                $pos = strrpos( $tplname, '.' );
                if( $pos !== false ){
                    $tplname = substr( $tplname, 0, $pos );
                    //$tplext = substr( $tplname, $pos+1 );
                }
                $tplname = str_replace( '/', '-', $tplname );

                if( $view ){ // clonable template
                    switch( $view ){
                        case 'page':
                            if( $PAGE->nested_page_obj ){
                                $arr = &$PAGE->nested_page_obj->root->get_parents_by_id( $PAGE->id );
                                if( is_array($arr) ){
                                    for( $x=0; $x<count($arr); $x++ ){
                                        if( $x==0 ) $valid_files[] = $tplname . '-page_ex-' . $arr[$x]->name;
                                        $valid_files[] = $tplname . '-page-' . $arr[$x]->name;
                                    }
                                }
                            }
                            else{
                                $valid_files[] = $tplname . '-page-' . $PAGE->page_name;
                            }
                            $valid_files[] = $tplname . '-page';
                            $valid_files[] = $tplname . '-default';
                            $valid_files[] = 'page';
                            $valid_files[] = 'default';
                            break;

                        case 'folder':
                            $folders = &$PAGE->folders;
                            $arr = $folders->get_parents_by_id( $PAGE->folder_id );
                            if( is_array($arr) ){
                                for( $x=0; $x<count($arr); $x++ ){
                                    if( $x==0 ) $valid_files[] = $tplname . '-folder_ex-' . $arr[$x]->name;
                                    $valid_files[] = $tplname . '-folder-' . $arr[$x]->name;
                                }
                            }
                            $valid_files[] = $tplname . '-folder';
                            $valid_files[] = $tplname . '-list';
                            $valid_files[] = $tplname . '-default';
                            $valid_files[] = 'folder';
                            $valid_files[] = 'list';
                            $valid_files[] = 'default';
                            break;

                        case 'archive':
                            $valid_files[] = $tplname . '-archive';
                            $valid_files[] = $tplname . '-list';
                            $valid_files[] = $tplname . '-default';
                            $valid_files[] = 'archive';
                            $valid_files[] = 'list';
                            $valid_files[] = 'default';
                            break;

                        case 'home':
                            $valid_files[] = $tplname . '-home';
                            $valid_files[] = $tplname . '-list';
                            $valid_files[] = $tplname . '-default';
                            $valid_files[] = 'home';
                            $valid_files[] = 'list';
                            $valid_files[] = 'default';
                    }
                }
                else{
                    // non-clonable template
                    $valid_files[] = $tplname . '-default';
                    $valid_files[] = 'default';
                }

                // HOOK: alter_smart_embed_valid_files
                $FUNCS->dispatch_event( 'alter_smart_embed_valid_files', array(&$valid_files, $tplname, $view) );

                // Cache results
                $FUNCS->cached_valid_files_for_view = $valid_files;
            }

            // Choose the first candidate file present within the available files
            foreach( $valid_files as $valid_file ){
                if( array_key_exists($valid_file, $available_files) ){
                    $chosen_file = $available_files[$valid_file];
                    break;
                }
            }

            // Embed chosen file
            if( !$debug ){
                if( $chosen_file ){
                    $html = @file_get_contents( $folder . '/' . $chosen_file );
                    if( $html ){
                        $parser = new KParser( $html, $node->line_num, 0, '', $node->ID );
                        return $parser->get_HTML();
                    }
                }
            }
            else{
                // output debug info
                if( $view ) $html = '<h2>'.$view . '-view </h2>';
                $folder = str_replace( K_SITE_DIR, '', $folder );
                $html .= 'Looking for files in folder <i>'.$folder.'</i>: ';
                $html .= '<ul>';
                foreach( $valid_files as $valid_file ){
                    $html .= '<li>';
                    $html .= ($chosen_file && $available_files[$valid_file]==$chosen_file) ? '<b>'.$valid_file.' * </b>' : $valid_file;
                    $html .= '</li>';
                }
                $html .= '</ul><b>';
                if( $chosen_file ){
                    $html .= 'Chosen file: ' . $chosen_file;
                }
                else{
                    $html .= 'No suitable file found';
                }
                $html .= '</b><br /><br />';

                return $html;
            }
        }

        function ignore( $params, $node ){
            return;
        }

        function hide( $params, $node ){
            foreach( $node->children as $child ){
                $html .= $child->get_HTML();
            }
            return;

        }

        // checks for the existence of a file relative to the 'snippets' folder
        function exists( $params, $node ){
            global $CTX;
            if( count($node->children) ) {die("ERROR: Tag \"".$node->name."\" is a self closing tag");}

            if( defined('K_SNIPPETS_DIR') ){ // always defined relative to the site
                $base_snippets_dir = K_SITE_DIR . K_SNIPPETS_DIR . '/';
            }
            else{
                $base_snippets_dir = K_COUCH_DIR . 'snippets/';
            }

            $filename = $base_snippets_dir . ltrim( trim($params[0]['rhs']), '/\\' );
            $res = ( @file_exists($filename) ) ? '1' : '0';

            return $res;

        }

        // checks for the existence of a page cloned from a particular template
        function page_exists( $params, $node ){
            global $FUNCS, $DB, $PAGE;
            if( count($node->children) ) {die("ERROR: Tag \"".$node->name."\" is a self closing tag");}

            extract( $FUNCS->get_named_vars(
                        array( 'name'=>'',
                               'id'=>'',
                               'masterpage'=>''
                              ),
                        $params)
                   );

            // sanitize params
            $masterpage = trim( $masterpage );
            if( $masterpage=='' ){ $masterpage = $PAGE->tpl_name; }
            $name = trim( $name );
            $id = trim( $id );
            if( strlen($id) && !$FUNCS->is_non_zero_natural($id) ) $id='';


            if( $name!='' ){
                $sql = "t.id = p.template_id and t.name='" . $DB->sanitize( $masterpage ) . "' and page_name='" . $DB->sanitize( $name ). "'";
            }
            else{ //id
                $sql = "t.id = p.template_id and t.name='" . $DB->sanitize( $masterpage ) . "' and p.id='" . $DB->sanitize( $id ). "'";
            }
            $rs = $DB->select( K_TBL_TEMPLATES . ' t, ' . K_TBL_PAGES . ' p ', array('p.id'), $sql );
            if( count($rs) ){
                return 1;
            }

            return 0;

        }

        // checks for the existence of a folder belonging to a particular template
        function folder_exists( $params, $node ){
            global $FUNCS, $DB, $PAGE;
            if( count($node->children) ) {die("ERROR: Tag \"".$node->name."\" is a self closing tag");}

            extract( $FUNCS->get_named_vars(
                        array( 'name'=>'',
                               'id'=>'',
                               'masterpage'=>''
                              ),
                        $params)
                   );

            // sanitize params
            $masterpage = trim( $masterpage );
            if( $masterpage=='' ){ $masterpage = $PAGE->tpl_name; }
            $name = trim( $name );
            $id = trim( $id );
            if( strlen($id) && !$FUNCS->is_non_zero_natural($id) ) $id='';

            if( $name!='' ){
                $sql = "t.id = f.template_id and t.name='" . $DB->sanitize( $masterpage ) . "' and f.name='" . $DB->sanitize( $name ). "'";
            }
            else{
                $sql = "t.id = f.template_id and t.name='" . $DB->sanitize( $masterpage ) . "' and f.id='" . $DB->sanitize( $id ). "'";
            }
            $rs = $DB->select( K_TBL_TEMPLATES . ' t, ' . K_TBL_FOLDERS . ' f ', array('f.id'), $sql );
            if( count($rs) ){
                return 1;
            }

            return 0;

        }

        // Get links of the various views.
        // The masterpage should exist to get back a link.
        function link( $params, $node ){
            global $FUNCS, $DB, $PAGE, $CTX;
            if( count($node->children) ) {die("ERROR: Tag \"".$node->name."\" is a self closing tag");}

            extract( $FUNCS->get_named_vars(
                        array(
                               'masterpage'=>'',
                               'page'=>'',
                               'page_id'=>'',
                               'folder'=>'',
                               'year'=>'',
                               'month'=>'',
                               'day'=>''
                              ),
                        $params)
                   );

            // sanitize params
            $masterpage = trim( $masterpage );
            if( $masterpage=='' ){ return; } // No masterpage, no link

            $page = trim( $page );
            $page_id = trim( $page_id );
            $folder = trim( $folder );
            $year = trim( $year );
            $month = trim( $month );
            $day = trim( $day );

            if( $page!='' || $page_id!='' ){
                // page-view
                if( $page!='' ){
                    $sql = "t.id = p.template_id and t.name='" . $DB->sanitize( $masterpage ) . "' and p.page_name='" . $DB->sanitize( $page ). "'";
                }
                else{
                    $sql = "t.id = p.template_id and t.name='" . $DB->sanitize( $masterpage ) . "' and p.id='" . $DB->sanitize( $page_id ). "'";
                }
                $rs = $DB->select( K_TBL_TEMPLATES . ' t, ' . K_TBL_PAGES . ' p ', array('t.id as tid', 'p.id as pid'), $sql );
                if( count($rs) ){
                    $tid = $rs[0]['tid'];
                    $pid = $rs[0]['pid'];
                    if( K_PRETTY_URLS ){
                        $pg = new KWebpage( $tid, $pid, 0, 0, 1 );
                        if( $pg->error ){ return; }
                        $pg->set_context();
                        return $CTX->get( 'k_page_link', 1 );
                    }
                    else{
                        return K_SITE_URL . $masterpage . '?p=' . $pid;
                    }
                }
            }
            elseif( $folder!='' ){
                // folder-view
                $sql = "t.id = f.template_id and t.name='" . $DB->sanitize( $masterpage ) . "' and f.name='" . $DB->sanitize( $folder ). "'";
                $rs = $DB->select( K_TBL_TEMPLATES . ' t, ' . K_TBL_FOLDERS . ' f ', array('t.id as tid', 'f.id as fid', 'f.pid as pid'), $sql );
                if( count($rs) ){
                    $tid = $rs[0]['tid'];
                    $fid = $rs[0]['fid'];
                    $pid = $rs[0]['pid']; //parent folder id
                    if( K_PRETTY_URLS ){
                        if( $pid=='-1' ){
                            return K_SITE_URL . $FUNCS->get_pretty_template_link( $masterpage ) . $folder . '/';
                        }
                        else{
                            // get all the folders of this template
                            $folders = &$FUNCS->get_folders_tree( $tid, $masterpage );
                            $folder = &$folders->find_by_id( $fid );
                            if( !$folder ) return;
                            return K_SITE_URL . $folder->get_link();
                        }
                    }
                    else{
                        return K_SITE_URL . $masterpage . '?f=' . $fid;
                    }
                }
            }
            elseif( $year ){
                // archive-view
                $sql = "t.name='" . $DB->sanitize( $masterpage ) . "'";
                $rs = $DB->select( K_TBL_TEMPLATES . ' t', array('t.id as tid'), $sql );
                if( count($rs) ){
                    return K_SITE_URL . $FUNCS->get_archive_link( $masterpage, $year, $month, $day );
                }
            }
            else{
                // home-view
                $sql = "t.name='" . $DB->sanitize( $masterpage ) . "'";
                $rs = $DB->select( K_TBL_TEMPLATES . ' t', array('t.id as tid'), $sql );
                if( count($rs) ){
                    if( K_PRETTY_URLS ){
                        return K_SITE_URL . $FUNCS->get_pretty_template_link( $masterpage );
                    }
                    else{
                        return K_SITE_URL . $masterpage;
                    }
                }
            }

            return;
        }

        // returns the admin panel edit link of page in context.
        function admin_link( $params, $node ){
            global $FUNCS, $CTX, $AUTH, $PAGE;
            if( count($node->children) ) {die("ERROR: Tag \"".$node->name."\" is a self closing tag");}

            // If current user is not an administrator, return.
            if( $AUTH->user->access_level < K_ACCESS_LEVEL_ADMIN ) return;

            if( $CTX->get('k_is_page') ){/* Cloned page */
                $nonce = $FUNCS->create_nonce( 'edit_page_'.$CTX->get('k_page_id') );
                $link = K_ADMIN_URL . K_ADMIN_PAGE . '?o='. $CTX->get('k_template_name') .'&q=edit/'. $nonce .'/' . $CTX->get('k_page_id');
            }
            elseif( $CTX->get('k_is_list_page') ){ /* Non-clonable page */
                $nonce = $FUNCS->create_nonce( 'edit_page_'.$CTX->get('k_template_id') );
                $link = K_ADMIN_URL . K_ADMIN_PAGE . '?o='. $CTX->get('k_template_name') .'&q=edit/'. $nonce .'/';
            }
            else{ /* List view */
                $link = K_ADMIN_URL . K_ADMIN_PAGE . '?o='. $CTX->get('k_template_name') .'&q=list';
            }

            return $link;
        }

        // returns the admin panel delete link of page in context.
        function admin_delete_link( $params, $node ){
            global $FUNCS, $CTX, $AUTH, $PAGE;
            if( count($node->children) ) {die("ERROR: Tag \"".$node->name."\" is a self closing tag");}

            // If current user is not an administrator, return.
            if( $AUTH->user->access_level < K_ACCESS_LEVEL_ADMIN ) return;

            if( $CTX->get('k_is_page') ){/* Cloned page */
                $nonce = $FUNCS->create_nonce( 'delete_page_'.$CTX->get('k_page_id') );
                $link = K_ADMIN_URL . K_ADMIN_PAGE . '?act=delete&tpl='. $CTX->get('k_template_id') .'&p='. $CTX->get('k_page_id') .'&nonce='.$nonce;
            }

            return $link;
        }

        // Adds the querystring to the given link. The only useful thing that this function does is to
        // properly separate the two with either an '&' or a '?' depending on the link.
        function add_querystring( $params, $node ){
            global $FUNCS, $DB, $PAGE, $CTX;
            if( count($node->children) ) {die("ERROR: Tag \"".$node->name."\" is a self closing tag");}

            extract( $FUNCS->get_named_vars(
                        array(
                               'link'=>'',
                               'querystring'=>''
                              ),
                        $params)
                   );

            // sanitize params
            $link = trim( $link );
            $querystring = trim( $querystring );
            if( $querystring ){
                $sep = ( strpos($link, '?')===false ) ? '?' : '&';
            }
            return $link . $sep . $querystring;
        }

        function k_if( $params, $node ){
            global $FUNCS;
            $children = $node->children;

            if( $FUNCS->resolve_condition( $node->attributes ) ){
                foreach( $children as $child ){
                    if( $child->type == K_NODE_TYPE_CODE && ($child->name == 'else' || $child->name == 'else_if') ){ break; }
                    $html .= $child->get_HTML();
                }
            }
            else{
                $ok = false;
                foreach( $children as $child ){
                    if( $child->type == K_NODE_TYPE_CODE && ($child->name == 'else' || $child->name == 'else_if') ){
                        if( $ok ){
                            break;
                        }
                        else{
                            if( $child->name == 'else' ){
                                $ok = true;
                            }
                            else{
                                if( $FUNCS->resolve_condition( $child->attributes ) ){
                                    $ok = true;
                                }
                            }
                        }
                    }
                    if( $ok ){
                        $html .= $child->get_HTML();
                    }
                }
            }
            return $html;
        }

        function not( $params, $node ){
            global $FUNCS;
            if( count($node->children) ) {die("ERROR: Tag \"".$node->name."\" is a self closing tag");}

            return ( $FUNCS->resolve_condition( $node->attributes ) ) ? 0 : 1;
        }

        function k_else( $params, $node ){
            if( count($node->children) ) {die("ERROR: Tag \"".$node->name."\" is a self closing tag");}
        }

        function else_if( $params, $node ){
            if( count($node->children) ) {die("ERROR: Tag \"".$node->name."\" is a self closing tag");}
        }

        function k_while( $params, $node ){
            global $FUNCS, $CTX;
            $children = $node->children;

            $safety = 0;
            $cond = $FUNCS->resolve_condition( $node->attributes );
            while( $cond ){
                if( ++$safety > 1000 ){ die("Infinite while loop"); }

                $CTX->set( 'k_count', $safety );
                foreach( $children as $child ){
                    $html .= $child->get_HTML();
                }
                $cond = $FUNCS->resolve_condition( $node->attributes );
            }
            return $html;
        }

        function repeat( $params, $node ){
            global $FUNCS, $CTX;
            extract( $FUNCS->get_named_vars(
                        array( 'count'=>'0',
                               'startcount'=>'0'
                              ),
                        $params)
                   );

            $count = is_numeric( $count ) ? intval( $count ) : 0;
            $startcount = is_numeric( $startcount ) ? intval( $startcount ) : 0;
            $end = $startcount + $count;
            $safety = 0;

            $children = $node->children;
            for( $x=$startcount; $x<$end; $x++ ){
                if( ++$safety > 1000 ){ die("Infinite repeat loop"); }

                $CTX->set( 'k_count', $x );
                foreach( $children as $child ){
                    $html .= $child->get_HTML();
                }
            }
            return $html;
        }

        function incr( $params, $node ){
            global $CTX;

            if( count($params)<1 ) die( "ERROR: Tag \"".$node->name."\": requires atleast one parameter" );
            if( $node->attributes[0]['value_type']!=K_VAL_TYPE_VARIABLE ) die( "ERROR: Tag \"".$node->name."\": First parameter should be a variable" );
            $var = $node->attributes[0]['value'];
            $p0 = $params[0]['rhs'];
            if( is_array($p0) ) return;

            $p1 = isset($params[1]['rhs']) ? $params[1]['rhs'] : 1;
            $CTX->set( $var, $p0+$p1, 'parent' );

            return;
        }

        function decr( $params, $node ){
            global $CTX;

            if( count($params)<1 ) die( "ERROR: Tag \"".$node->name."\": requires atleast one parameter" );
            if( $node->attributes[0]['value_type']!=K_VAL_TYPE_VARIABLE ) die( "ERROR: Tag \"".$node->name."\": First parameter should be a variable" );
            $var = $node->attributes[0]['value'];
            $p0 = $params[0]['rhs'];
            if( is_array($p0) ) return;

            $p1 = isset($params[1]['rhs']) ? $params[1]['rhs'] : 1;
            $CTX->set( $var, $p0-$p1, 'parent' );

            return;
        }

        function mul( $params, $node ){
            if( count($params)<2 ) die( "ERROR: Tag \"".$node->name."\": requires two parameters" );
            $p0 = $params[0]['rhs'];
            $p1 = $params[1]['rhs'];

            if( is_array($p0) || is_array($p1) ) return;
            return $p0 * $p1;
        }

        function div( $params, $node ){
            if( count($params)<2 ) die( "ERROR: Tag \"".$node->name."\": requires two parameters" );
            $p0 = $params[0]['rhs'];
            $p1 = $params[1]['rhs'];

            if( is_array($p0) || is_array($p1) ) return;
            return $p0 / $p1;
        }

        function add( $params, $node ){
            if( count($params)<2 ) die( "ERROR: Tag \"".$node->name."\": requires two parameters" );
            $p0 = $params[0]['rhs'];
            $p1 = $params[1]['rhs'];

            if( is_array($p0) || is_array($p1) ) return;
            return $p0 + $p1;
        }

        function sub( $params, $node ){
            if( count($params)<2 ) die( "ERROR: Tag \"".$node->name."\": requires two parameters" );
            $p0 = $params[0]['rhs'];
            $p1 = $params[1]['rhs'];

            if( is_array($p0) || is_array($p1) ) return;
            return $p0 - $p1;
        }

        function mod( $params, $node ){
            if( count($params)<2 ) die( "ERROR: Tag \"".$node->name."\": requires two parameters" );
            $p0 = $params[0]['rhs'];
            $p1 = $params[1]['rhs'];

            if( is_array($p0) || is_array($p1) ) return;
            return $p0 % $p1;
        }

        function zebra( $params, $node ){
            global $CTX, $FUNCS;
            if( count($node->children) ) {die("ERROR: Tag \"".$node->name."\" is a self closing tag");}

            $var = 'z' . $node->ID;
            $loop_num = $CTX->get_zebra( $var );
            if( is_null($loop_num) ) $loop_num=0;
            if( count($params) > $loop_num ){
                $html = $params[$loop_num]['rhs'];
            }
            if( ++$loop_num >= count($params) ) $loop_num=0;
            $CTX->set_zebra($var, $loop_num);

            return $html;
        }

        function editable( $params, $node, $called_from_repeatable=0 ){
            global $CTX, $FUNCS, $PAGE, $DB, $AUTH;
            $is_udf = 0;

            if( defined('K_ADMIN') ) return; // nop within admin panel

            $core_params = array(
                'name'=>'',
                'label'=>'',
                'desc'=>'',
                'type'=>'textarea',
                'hidden'=>'0',
                'search_type'=>'',
                'searchable'=>'1',
                'order'=>'0',
                'required'=>'0',
                'validator'=>'',
                'validator_msg'=>'',
                'separator'=>'',
                'val_separator'=>'',
                'opt_values'=>'',
                'opt_selected'=>'',
                'toolbar'=>'',
                'custom_toolbar'=>'',
                'css'=>'',
                'custom_styles'=>'',
                'maxlength'=>'',
                'height'=>'',
                'width'=>'',
                'group'=>'',
                'collapsed'=>'',
                'assoc_field'=>'',
                'crop'=>'',
                'enforce_max'=>'',
                'quality'=>'80',
                'show_preview'=>'',
                'preview_width'=>'',
                'preview_height'=>'',
                'no_xss_check'=>'0',
                'rtl'=>'',
                'body_id'=>'',
                'body_class'=>'',
                'disable_uploader'=>'0',
                'dynamic'=>'',
                'class'=>'',
                'not_active'=>null,

                /* used only by repeatable */
                'col_width'=>'',
                'input_width'=>'' /* for 'image' & 'file' regions */,
               );
            $attr = $FUNCS->get_named_vars(
                        $core_params,
                        $params);

            // HOOK: alter_editable_start
            $FUNCS->dispatch_event( 'alter_editable_start', array(&$attr, $params, $node, &$called_from_repeatable) );

            $attr['name'] = trim( $attr['name'] );
            $attr['type'] = strtolower( trim($attr['type']) );
            if( !$attr['type'] ){ $attr['type']='textarea'; }

            $is_udf = 0;
            $attr_udf = array();
            if( !$FUNCS->is_core_type($attr['type']) ){
                // is it a udf?
                if( array_key_exists($attr['type'], $FUNCS->udfs) ){
                    $is_udf = 1;
                    $classname = $FUNCS->udfs[$attr['type']]['handler'];
                    $attr_udf = call_user_func( array($classname, 'handle_params'), $params );
                    if( !is_array($attr_udf) ) $attr_udf=array();

                    // remove core parameters if any set as custom params
                    $core_params = array_merge( array('id'=>'', 'deleted'=>'', 'template_id'=>'', 'custom_params'=>'', 'k_desc'=>'', 'k_type'=>'',
                                                      'k_order'=>'', 'k_group'=>'', 'k_separator'=>'', 'page'=>'', 'siblings'=>'', 'processed'=>'',
                                                      'system'=>'', 'err_msg'=>'', 'modified'=>'', 'udf'=>'',
                                                      'cached'=>'', 'refresh_form'=>'', 'err_msg_refresh'=>'', 'requires_multipart'=>'', 'trust_mode'=>'', 'no_js'=>'',
                                                      'available_validators'=>'', 'available_buttons'=>'', 'k_inactive'=>'' ), $core_params );
                    foreach( $attr_udf as $k=>$v ){
                        if( array_key_exists($k, $core_params) ){
                            unset( $attr_udf[$k] );
                        }
                    }
                }
                else{
                    die("ERROR: Tag \"".$node->name."\" has unknown type \"".$attr['type']."\"");
                }
            }

            $attr['hidden'] = abs( (int)$attr['hidden']);
            $attr['search_type'] = strtolower( trim($attr['search_type']) );
            if( !$attr['search_type'] ){
                $attr['search_type'] ='text';
            }
            elseif( !in_array($attr['search_type'], array('text', 'integer', 'decimal')) ){
                die("ERROR: Tag \"".$node->name."\" has unknown search_type \"".$attr['search_type']."\"");
            }
            // only text, radio and dropdown can have numeric search types
            if( $attr['search_type']!='text' &&
               !in_array($attr['type'], array('text', 'radio', 'dropdown')) && $is_udf==0 ){
                {die("ERROR: Tag \"".$node->name."\" cannot have \"".$attr['search_type']."\" as search_type with \"".$attr['type']."\" as field type");}
            }

            $attr['order'] = (int)$attr['order'];
            $attr['required'] = abs( (int)$attr['required'] );
            $attr['validator'] = strtolower( trim($attr['validator']) );
            $attr['separator'] = trim( $attr['separator'] );
            $attr['val_separator'] = trim( $attr['val_separator'] );
            $attr['opt_values'] = trim( $attr['opt_values'] );
            $attr['opt_selected'] = trim( $attr['opt_selected'] );
            $attr['toolbar'] = strtolower( trim($attr['toolbar']) );
            $attr['custom_toolbar'] = trim( $attr['custom_toolbar'] );
            $attr['css'] = trim( $attr['css'] );
            $attr['custom_styles'] = trim( $attr['custom_styles'] );
            $attr['maxlength'] = abs( (int)$attr['maxlength'] );
            $attr['height'] = abs( (int)$attr['height'] );
            $attr['width'] = abs( (int)$attr['width'] );
            $attr['group'] = trim($attr['group']);
            $attr['collapsed'] = trim( $attr['collapsed'] );
            if( $attr['collapsed']!='1' && $attr['collapsed']!='0' ) $attr['collapsed']='-1';
            $attr['assoc_field'] = trim( $attr['assoc_field'] );
            $attr['crop'] = abs( (int)$attr['crop'] );
            $attr['enforce_max'] = trim( $attr['enforce_max'] );
            if( ($attr['type']=='image' || $attr['type']=='securefile') && ($attr['enforce_max']!='1' && $attr['enforce_max']!='0') ) $attr['enforce_max']=1;
            if( $attr['type']=='thumbnail' && ($attr['enforce_max']!='1' && $attr['enforce_max']!='0') ) $attr['enforce_max']=0;
            $attr['enforce_max'] = abs( (int)$attr['enforce_max'] );
            $attr['quality'] = (int)$attr['quality'];
            if( $attr['quality']<=0 ){ $attr['quality']='80'; } elseif( $attr['quality']>100 ){ $attr['quality']='100'; }
            $attr['show_preview'] = abs( (int)$attr['show_preview'] );
            $attr['preview_width'] = abs( (int)$attr['preview_width'] );
            $attr['preview_height'] = abs( (int)$attr['preview_height'] );
            $attr['no_xss_check'] = abs( (int)$attr['no_xss_check'] );
            $attr['rtl'] = abs( (int)$attr['rtl'] );
            $attr['body_id'] = trim( $attr['body_id'] );
            $attr['body_class'] = trim( $attr['body_class'] );
            $attr['disable_uploader'] = abs( (int)$attr['disable_uploader'] );
            $attr['dynamic'] = trim( $attr['dynamic'] );
            $attr['searchable'] = abs( (int)$attr['searchable'] );
            $attr['class'] = trim( $attr['class'] );
            if( !is_null($attr['not_active']) ){
                $attr['not_active'] =  base64_encode( serialize(array('val'=>$attr['not_active'])) );
            }

            // Save a backup of all the parameters used to create this field.
            $tag = '<cms:editable';
            foreach( $params as $p ){
                $tag .= "\r\n" . $p['lhs'] . $p['op'] . '\'' . str_replace("'", "\'", $p['rhs']) . '\'';
            }
            $tag .= '/>';

            if( $called_from_repeatable ){
                $attr['k_desc'] = $attr['desc']; unset( $attr['desc'] );
                $attr['k_type'] = $attr['type']; unset( $attr['type'] );
                $attr['k_order'] = $attr['order']; unset( $attr['order'] );
                $attr['k_separator'] = $attr['separator']; unset( $attr['separator'] );
                $attr['k_group'] = ($attr['type']=='group') ? '' : $attr['group']; unset( $attr['group'] );
                $attr['_html'] = $tag;

                $attr['col_width'] = abs( (int)$attr['col_width'] );
                $attr['input_width'] = abs( (int)$attr['input_width'] );
                $attr['custom_params'] = ( $is_udf ) ? $FUNCS->serialize($attr_udf) : '';
                return $attr;
            }

            // HOOK: alter_editable
            $skip = $FUNCS->dispatch_event( 'alter_editable', array(&$attr, &$attr_udf, $params, $node, $called_from_repeatable) );
            if( $skip ) return;

            extract( $attr );

            if( !$name ) {die("ERROR: Tag \"".$node->name."\" needs a 'name' attribute");}
            if( !$FUNCS->is_title_clean($name) ){
                die( "ERROR: Tag \"".$node->name."\": 'name' attribute ({$name}) contains invalid characters. (Only lowercase[a-z], numerals[0-9], hyphen and underscore permitted.)" );
            }
            $found = 0;
            if( isset($PAGE->_fields[$name]) ){
                $found = 1;
                $field = $PAGE->_fields[$name];
            }

            // if type 'group' used as a tag-pair, find all immediate child editable regions and set this as their parent
            if( $type=='group' && count($node->children) ){
                $attr_not_active = null;
                foreach( $node->attributes as $node_attr ){
                    if( $node_attr['name']=='not_active' ){
                        $attr_not_active = $node_attr;
                        break;
                    }
                }

                for( $x=0; $x<count($node->children); $x++ ){
                    $child = &$node->children[$x];

                    if( $child->type==K_NODE_TYPE_CODE && ($child->name=='editable' || $child->name=='repeatable' || $child->name=='mosaic') ){
                        $arr_tmp = array();
                        $child_attr_not_active = null;

                        foreach( $child->attributes as $child_attr ){
                            if( $child_attr['name']!='group' ){
                                if( $child_attr['name']=='not_active' ){
                                    $child_attr_not_active = 1;
                                }
                                elseif( $child_attr['name']=='type' && $child_attr['value']=='group' ){
                                    die( "ERROR: Type 'group' editable cannot have another 'group' nested within it." );
                                }
                                $arr_tmp[] = $child_attr;
                            }
                        }
                        $arr_tmp[] = array( 'name'=>'group', 'op'=>'=', 'quote_type'=>"'", 'value'=>$name, 'value_type'=>K_VAL_TYPE_LITERAL);
                        if( $attr_not_active && !$child_attr_not_active ){
                            $arr_tmp[] = $attr_not_active;
                        }
                        $child->attributes = $arr_tmp;
                    }
                    unset( $child );
                }
            }

            foreach( $node->children as $child ){
                $html .= $child->get_HTML();
            }

            // check for failure in the past run of this routine
            $lock_file = K_COUCH_DIR . 'cache/' . '$$'.$PAGE->tpl_id.'-'.$attr['name'];
            $prev_failed = file_exists( $lock_file ); // presence of this file indicates previous failure

            if( $found && !$prev_failed ){
                if( !$field->system ){

                    if( $AUTH->user->access_level >= K_ACCESS_LEVEL_SUPER_ADMIN ){
                        // Note the existing type of the field. This could get modified by the code that follows.
                        $orig_field_type = $field->k_type;

                        // Check if any core attribute has been modified
                        $modified = $prev_value = $prev_udf_values = array();
                        foreach( $attr as $k=>$v ){
                            if( $k=='desc' || $k=='type' || $k=='order' || $k=='group' || $k=='separator') $k = 'k_'.$k; //MySQL has problems with these names
                            if( $field->$k != trim($v) ){
                                if( $attr['type']=='group' && $k=='k_group' ){
                                    //group of type 'group' will always be blank
                                    $v = '';
                                    if( $field->$k == $v ) continue;
                                }
                                $prev_value[$k] = $field->$k;
                                $modified[$k] = $field->$k = trim($v);
                            }
                        }

                        // Check if any udf attribute has been modified
                        if( $is_udf ){
                            foreach( $attr_udf as $k=>$v ){
                                $v = trim( $v );
                                if( $field->$k != $v ){
                                    $prev_udf_values[$k] = $field->$k;
                                    $field->$k = $v;
                                }
                            }
                            if( count($prev_udf_values) ){
                                $modified['custom_params'] = $FUNCS->serialize($attr_udf);
                            }
                        }
                        // Check if the default text (if any) has changed
                        if( $field->default_data != $html ){
                            $prev_value['default_data'] = $field->default_data;
                            $modified['default_data'] = $field->default_data = $html;
                        }
                        // Check if deleted field has been recreated
                        if( $field->deleted ){
                            $modified['deleted'] = $field->deleted = "0";
                        }

                        // HOOK: alter_editable_modifications
                        $FUNCS->dispatch_event( 'alter_editable_modifications', array(&$modified, &$prev_value, &$prev_udf_values, $field, $params, $node) );

                        if( count($modified) ){
                            $DB->begin();

                            // HOOK: alter_field_update
                            $FUNCS->dispatch_event( 'alter_field_update', array(&$modified, &$prev_value, &$prev_udf_values, $field, $params, $node) );

                            if( array_key_exists('custom_params', $modified) ){
                                // Call udf to do something for 'update_schema' event
                                if( $field->k_type == $orig_field_type ){
                                    $field->_update_schema( $prev_udf_values );
                                }
                            }

                            // Check if search_type of custom field changed
                            if( array_key_exists('search_type', $modified) ){
                                $new_type = $modified['search_type'];
                                $old_type = $prev_value['search_type'];
                                if( $old_type && ($old_type != $new_type) ){
                                    if( $new_type=='decimal' ){
                                        if( $old_type=='integer' ){
                                            // If converting from integer to decimal, nothing is required
                                        }
                                        elseif( $old_type=='text' ){
                                            // Converting from text to decimal
                                            $FUNCS->change_field_type( $old_type, $new_type, $field->id );
                                        }
                                    }
                                    elseif( $new_type=='integer' ){
                                        if( $old_type=='decimal' ){
                                            // Converting from decimal to integer will require stripping off all fractional parts
                                            $sql = 'UPDATE ' . K_TBL_DATA_NUMERIC . ' SET value = TRUNCATE(value,0) WHERE field_id='. $DB->sanitize( $field->id );
                                            $DB->_query( $sql );
                                            $rs = $DB->rows_affected = mysql_affected_rows( $DB->conn );
                                            if( $rs==-1 ) die( "ERROR: Unable to save modified decimal to integer values" );
                                        }
                                        elseif( $old_type=='text' ){
                                            // Converting from text to integer
                                            $FUNCS->change_field_type( $old_type, $new_type, $field->id );
                                        }
                                    }
                                    elseif( $new_type=='text' ){
                                        // Converting from numeric to text
                                        $FUNCS->change_field_type( $old_type, $new_type, $field->id );
                                    }
                                }
                            }

                            // Persist changes
                            $modified['_html'] = $tag;
                            $rs = $DB->update( K_TBL_FIELDS, $modified, "id='" . $DB->sanitize( $field->id ). "'" );
                            if( $rs==-1 ) die( "ERROR: Unable to save modified editable field" );

                            // HOOK: field_updated
                            $FUNCS->dispatch_event( 'field_updated', array(&$modified, &$prev_value, &$prev_udf_values, $field, $params, $node) );

                            $DB->commit();
                        }

                        $field->processed = 1;
                    }

                    if( !$hidden && !($PAGE->is_master && $PAGE->tpl_is_clonable) ){
                        if( !($field->k_type=='hidden' || $field->k_type=='message' || $field->k_type=='group') ){
                            return $field->get_data();
                        }
                    }
                }
            }
            else{
                if( $AUTH->user->access_level >= K_ACCESS_LEVEL_SUPER_ADMIN ){
                    if( !$FUNCS->is_variable_clean($attr['name']) ){
                        die( "ERROR: Tag \"".$node->name."\": 'name' contains invalid characters. (Only lowercase[a-z], numerals[0-9] and underscore permitted. The first character cannot be a numeral)" );
                    }
                    if( substr($attr['name'], 0, 2)=='k_' ){
                        die("ERROR: Tag \"".$node->name."\": 'name' cannot begin with 'k_'. Reserved for system fields.");
                    }
                    $DB->begin();

                    // first check if any previous run of this routine ended abnormally
                    $skip_insert = $fp = 0;
                    if( $prev_failed ){
                        // did the failure occurr before record for the field was inserted in K_TBL_FIELDS?
                        $rs = $DB->select( K_TBL_FIELDS, array('*'), "template_id='" . $DB->sanitize( $PAGE->tpl_id ). "' and name='" . $DB->sanitize( $attr['name'] ) . "'" );
                        if( count($rs) ){
                            $skip_insert=1;
                            $field_id = $rs[0]['id'];
                        }
                    }
                    else{
                        // leave a tell-tale sign in case this routine fails mid-way processing the code to follow
                        $fp = @fopen( $lock_file, 'x+b' );
                        if( $fp===FALSE ){} // do what??
                    }

                    if( !$skip_insert ){
                        // Create a new record for this field in K_TBL_FIELDS. This stores only the meta.
                        $fields = array(
                                       'template_id'=>$PAGE->tpl_id,
                                       'name'=>$attr['name'],
                                       'label'=>$attr['label'],
                                       'k_desc'=>$attr['desc'],
                                       'k_type'=>$attr['type'],
                                       'hidden'=>$attr['hidden'],
                                       'search_type'=>$attr['search_type'],
                                       'searchable'=>$attr['searchable'],
                                       'k_order'=>$attr['order'],
                                       'default_data'=>$html,
                                       'required'=>$attr['required'],
                                       'deleted'=>'0',
                                       'validator'=>$attr['validator'],
                                       'validator_msg'=>$attr['validator_msg'],
                                       'k_separator'=>$attr['separator'],
                                       'val_separator'=>$attr['val_separator'],
                                       'opt_values'=>$attr['opt_values'],
                                       'opt_selected'=>$attr['opt_selected'],
                                       'toolbar'=>$attr['toolbar'],
                                       'custom_toolbar'=>$attr['custom_toolbar'],
                                       'css'=>$attr['css'],
                                       'custom_styles'=>$attr['custom_styles'],
                                       'maxlength'=>$attr['maxlength'],
                                       'height'=>$attr['height'],
                                       'width'=>$attr['width'],
                                       'k_group'=>($attr['type']=='group') ? '' : $attr['group'],
                                       'collapsed'=>$attr['collapsed'],
                                       'assoc_field'=>$attr['assoc_field'],
                                       'crop'=>$attr['crop'],
                                       'enforce_max'=>$attr['enforce_max'],
                                       'quality'=>$attr['quality'],
                                       'show_preview'=>$attr['show_preview'],
                                       'preview_width'=>$attr['preview_width'],
                                       'preview_height'=>$attr['preview_height'],
                                       'no_xss_check'=>$attr['no_xss_check'],
                                       'rtl'=>$attr['rtl'],
                                       'body_id' => $attr['body_id'],
                                       'body_class' => $attr['body_class'],
                                       'disable_uploader' => $attr['disable_uploader'],
                                       '_html' => $tag,
                                       'dynamic' => $attr['dynamic'],
                                       'class' => $attr['class'],
                                       'not_active' => $attr['not_active'],

                                      );
                        if( $is_udf && count($attr_udf) ){
                            $fields['custom_params'] = $FUNCS->serialize($attr_udf);
                        }

                        // HOOK: alter_field_insert
                        $FUNCS->dispatch_event( 'alter_field_insert', array(&$fields, $attr, $is_udf, $params, $node) );

                        $rs = $DB->insert( K_TBL_FIELDS, $fields );
                        if( $rs==-1 ) die( "ERROR: Unable to insert record in K_TBL_FIELDS" );
                        $field_id = $DB->last_insert_id;
                        $rs = $DB->select( K_TBL_FIELDS, array('*'), "id='" . $DB->sanitize( $field_id ) . "'" );
                        if( !count($rs) ) die( "ERROR: Failed to insert record in K_TBL_FIELDS" );

                    }

                    if( $is_udf ){
                        $classname = $FUNCS->udfs[$attr['type']]['handler'];
                        $f = new $classname( $rs[0], $PAGE, $PAGE->fields );
                    }
                    else{
                        $f = new KField( $rs[0], $PAGE, $PAGE->fields );
                    }
                    $f->processed = 1;
                    $PAGE->fields[] = $f;
                    $PAGE->_fields[$attr['name']] = $f;

                    // ** Following portion of the code can cause the script to timeout if there are too many existing pages to add the data fields to **

                    // Create a field record for each existing page. This is for storage.
                    $to_table = ( $f->search_type=='text' ) ? K_TBL_DATA_TEXT : K_TBL_DATA_NUMERIC;

                    @set_time_limit( 0 ); // make server wait
                    $start_time = time();

                    if( $prev_failed ){
                        $stagger_limit = 500; // number of pages processed in a single run. Might require tweaking if script still times out
                        $rs = $DB->select( K_TBL_PAGES . " p LEFT OUTER JOIN " . $to_table . " d ON (p.id = d.page_id AND d.field_id='". $DB->sanitize( $field_id ) ."')", array('*'), "p.template_id = '". $DB->sanitize( $PAGE->tpl_id ). "' AND d.page_id IS NULL LIMIT 0, ".$stagger_limit );
                    }
                    else{
                        $rs = $DB->select( K_TBL_PAGES, array('*'), "template_id='" . $DB->sanitize( $PAGE->tpl_id ). "'" );
                    }

                    if( count($rs) ){
                        foreach( $rs as $rec ){
                            $arr_to_fields = array('page_id'=>$rec['id'],
                                        'field_id'=>$field_id,
                                        'value'=>''
                                        );
                            if( $f->search_type=='text' ){
                                $arr_to_fields['search_value'] = '';
                            }

                            // HOOK: alter_datafield_insert_for_existingpage
                            $FUNCS->dispatch_event( 'alter_datafield_insert_for_existingpage', array($rec, &$arr_to_fields, &$to_table, &$f, &$PAGE, $params, $node) );

                            $rs2 = $DB->insert( $to_table, $arr_to_fields );
                            if( $rs2==-1 ) die( "ERROR: Failed to insert record for K_TBL_FIELDS in" . $to_table );

                            if( $is_udf ){
                                // Call udf to do something for 'create' event
                                $f->_create( $rec['id'], 1 );
                            }

                            $cur_time = time();
                            if( $cur_time + 25 > $start_time ){
                                header( "X-Dummy: wait" ); // make browser wait
                                $start_time = $cur_time;
                            }
                        }

                        if( $prev_failed ){
                            // end script and refresh current page to process next batch of records in a staggered manner
                            ob_end_clean();
                            $DB->commit( 1 );

                            $cnt = ( isset($_GET['__cnt__']) && $FUNCS->is_non_zero_natural($_GET['__cnt__']) ) ? (int)$_GET['__cnt__'] : 0;
                            $dst = $CTX->get( 'k_page_link' );
                            $sep = ( strpos($dst, '?')===false ) ? '?' : '&';
                            $dst = $dst . $sep . '__cnt__=' . ++$cnt;
                            $html="
                                <script language=\"JavaScript\" type=\"text/javascript\">
                                    window.setTimeout( 'location.href=\"".$dst."\";', 100 );
                                </script>
                                Modifying schema. Could take some time. Please wait...(processed: ".$cnt*$stagger_limit." pages)
                            ";
                            die( $html );
                        }
                    }

                    // HOOK: field_inserted
                    $FUNCS->dispatch_event( 'field_inserted', array( &$f, $is_udf, &$PAGE, $params, $node) );

                    $DB->commit();

                    if( $prev_failed ){
                        $rs = @unlink( $lock_file );
                        if( $rs ){ $AUTH->redirect( $CTX->get('k_page_link') ); }
                    }
                    else{
                        if( $fp ){
                            fclose( $fp );
                            @unlink( $lock_file );
                        }
                    }

                    if( !$hidden && !($PAGE->is_master && $PAGE->tpl_is_clonable) ){
                        if( !($field->k_type=='hidden' || $field->k_type=='message' || $field->k_type=='group') ){
                            return $f->default_data;
                        }
                    }
                }
            }

        }

        function template( $params, $node ){
            global $FUNCS, $PAGE, $DB, $AUTH;

            if( $AUTH->user->access_level >= K_ACCESS_LEVEL_SUPER_ADMIN ){

                if( defined('K_ADMIN') ) return; // nop within admin panel

                $core_params = array(
                                   'title'=>'',
                                   'clonable'=>'0',
                                   'access_level'=>'0',
                                   'executable'=>'1',
                                   'commentable'=>'0',
                                   'hidden'=>'0',
                                   'order'=>'0',
                                   'dynamic_folders'=>'0',
                                   'nested_pages'=>'0',
                                   'gallery'=>'0',
                                   'handler'=>'',
                                   'type'=>'',
                                   'parent'=>'',
                                   'icon'=>'',
                                  );
                $attr = $FUNCS->get_named_vars(
                            $core_params,
                            $params);

                // HOOK: alter_template_tag_params
                $FUNCS->dispatch_event( 'alter_template_tag_params', array(&$attr, $params, $node) );

                $attr['clonable'] = $FUNCS->is_natural( $attr['clonable'] ) ? intval( $attr['clonable'] ) : 0;
                if( $attr['clonable']!= 0 ) $attr['clonable'] = 1;
                $attr['access_level'] = $FUNCS->is_natural( $attr['access_level'] ) ? intval( $attr['access_level'] ) : 0;
                $attr['executable'] = $FUNCS->is_natural( $attr['executable'] ) ? intval( $attr['executable'] ) : 1;
                if( $attr['executable']!= 0 ) $attr['executable'] = 1;
                $attr['commentable'] = $FUNCS->is_natural( $attr['commentable'] ) ? intval( $attr['commentable'] ) : 0;
                if( $attr['commentable']!= 0 ) $attr['commentable'] = 1;
                $attr['hidden'] = $FUNCS->is_natural( $attr['hidden'] ) ? intval( $attr['hidden'] ) : 0;
                if( $attr['hidden']!= 0 ) $attr['hidden'] = 1;
                $attr['k_order'] = intval( $attr['order'] );
                unset( $attr['order'] );
                $attr['dynamic_folders'] = $FUNCS->is_natural( $attr['dynamic_folders'] ) ? intval( $attr['dynamic_folders'] ) : 0;
                if( $attr['dynamic_folders']!= 0 ) $attr['dynamic_folders'] = 1;
                $attr['nested_pages'] = $FUNCS->is_natural( $attr['nested_pages'] ) ? intval( $attr['nested_pages'] ) : 0;
                if( $attr['nested_pages']!= 0 ) $attr['nested_pages'] = 1;
                $attr['gallery'] = $FUNCS->is_natural( $attr['gallery'] ) ? intval( $attr['gallery'] ) : 0;
                if( $attr['gallery']!= 0 ){
                    $attr['gallery'] = 1;
                    $attr['clonable'] = 1; // gallery works only with clonable templates
                    $attr['nested_pages'] = 0; // gallery cannot work with nested-pages
                }
                $attr['handler'] = strtolower( trim($attr['handler']) );
                $attr['type'] = strtolower( trim($attr['type']) );
                $attr['parent'] = trim($attr['parent'] );
                $attr['icon'] = trim($attr['icon'] );

                // HOOK: add_template_params
                // give modules a chance to add their custom params
                $attr_custom = array();
                $FUNCS->dispatch_event( 'add_template_params', array(&$attr_custom, $params, $node) );
                foreach( $attr_custom as $k=>$v ){
                    if( array_key_exists($k, $core_params) ){
                        unset( $attr_custom[$k] );
                    }
                }

                // HOOK: alter_template
                $FUNCS->dispatch_event( 'alter_template', array(&$attr, &$attr_custom, $params, $node) );
                if( array_key_exists('custom_params', $attr) ) unset( $attr['custom_params'] );

                $rs = $DB->select( K_TBL_TEMPLATES, array('*'), "id='" . $DB->sanitize( $PAGE->tpl_id ). "'" );
                if( count($rs) ){
                    $rec = $rs[0];
                    $modified = array();
                    foreach( $attr as $k=>$v ){
                        if( $rec[$k] != trim($v) ){
                            if( $k=='clonable' && $rec[$k]=='1' ){
                                // Making a clonable template non-clonable..
                                // Test if no cloned pages already exist (apart from the default page)
                                $rs2 = $DB->select( K_TBL_PAGES, array('*'), "template_id='" . $DB->sanitize( $PAGE->tpl_id ). "' AND is_master<>'1'" );
                                if( count($rs2) ){
                                    die( 'ERROR: Tag: '.$node->name.' Cannot make template non-clonable. Cloned pages exist.' );
                                };
                            }

                            if( $k=='dynamic_folders' ) $PAGE->tpl_dynamic_folders=trim($v);
                            if( $k=='nested_pages' ){
                                $PAGE->tpl_nested_pages=trim($v);
                                if( $PAGE->tpl_nested_pages ) $reset_weights=1;
                            }

                            $modified[$k] = trim($v);
                        }
                    }

                    // any custom params added, removed or modified?
                    $custom_params = $rec['custom_params'];
                    if( strlen($custom_params) ){
                        $custom_params = $FUNCS->unserialize($custom_params);
                    }
                    if( !is_array($custom_params) ) $custom_params=array();
                    $prev_custom_values = array();
                    foreach( $attr_custom as $k=>$v ){
                        if( !array_key_exists($k, $custom_params) || $custom_params[$k]!=$v ){
                            $prev_custom_values[$k] = $custom_params[$k];
                        }
                    }
                    foreach( $custom_params as $k=>$v ){
                        if( !array_key_exists($k, $attr_custom) ){
                            $prev_custom_values[$k] = $v;
                        }
                    }
                    if( count($prev_custom_values) ){
                        $modified['custom_params'] = $FUNCS->serialize($attr_custom);
                    }

                    // HOOK: alter_template_modified
                    $FUNCS->dispatch_event( 'alter_template_modified', array($rec, $attr, $prev_custom_values, $attr_custom, &$modified, $params, $node) );

                    if( count($modified) ){
                        $rs2 = $DB->update( K_TBL_TEMPLATES, $modified, "id='" . $DB->sanitize( $PAGE->tpl_id ). "'" );
                        if( $rs2==-1 ) die( "ERROR: Tag: '.$node->name.' Unable to save modified template attribute" );

                        if( $reset_weights ){
                            // serialize access.. lock template before getting tree
                            $DB->update( K_TBL_TEMPLATES, array('description'=>$DB->sanitize( $rs[0]['description'] )), "id='" . $DB->sanitize( $PAGE->tpl_id ) . "'" );
                            $PAGE->reset_weights_of(); // entire tree
                        }

                        // HOOK: template_modified
                        $FUNCS->dispatch_event( 'template_modified', array($rec, $attr, $prev_custom_values, $attr_custom, $modified, $params, $node) );
                    }
                }
                foreach( $node->children as $child ){
                    $child->get_HTML();
                }

                // HOOK: template_tag_end
                $FUNCS->dispatch_event( 'template_tag_end', array($params, $node) );
            }
        }

        function templates( $params, $node ){
            global $CTX, $FUNCS, $DB;

            $attr = $FUNCS->get_named_vars(
                        array(
                               'show_hidden'=>'0',
                               'orderby'=>'', /* 'name', 'title' or 'order'(default)*/
                               'order'=>'',
                              ),
                        $params);
            extract( $attr );

            // sanitize params
            $show_hidden = ( $show_hidden==1 ) ? 1 : 0;
            $orderby = strtolower(trim($orderby));
            if( !in_array($orderby, array('name', 'title', 'order')) ) $orderby = 'order';
            if( $orderby == 'order' ) $orderby='k_order';
            $order = strtolower(trim($order));
            if( $order!='desc' && $order!='asc' ) $order = 'asc';

            // query
            if( !$show_hidden ) $sql = 'hidden < 1 and ';
            $sql .= '(ISNULL(type) || type=\'\') ORDER BY '.$orderby.' '.$order.', id '. $order;
            $rs = $DB->select( K_TBL_TEMPLATES, array('*'), $sql );
            if( count($rs) ){
                $count = count($rs);
                foreach( $rs as $rec ){
                    $CTX->set( 'k_total', $count );
                    $CTX->set( 'k_template_id', $rec['id'] );
                    $CTX->set( 'k_template_name', $rec['name'] );
                    $CTX->set( 'k_template_title', $rec['title'] );
                    $CTX->set( 'k_template_desc', $rec['description'] );
                    $CTX->set( 'k_template_access_level', $rec['access_level'] );
                    $CTX->set( 'k_template_is_clonable', $rec['clonable'] );
                    $CTX->set( 'k_template_is_commentable', $rec['commentable'] );
                    $CTX->set( 'k_template_is_executable', $rec['executable'] );
                    $CTX->set( 'k_template_is_hidden', $rec['hidden'] );
                    $CTX->set( 'k_template_order', $rec['k_order'] );
                    $CTX->set( 'k_template_has_dynamic_folders', $rec['dynamic_folders'] );
                    $CTX->set( 'k_template_has_nested_pages', $rec['nested_pages'] );
                    $CTX->set( 'k_template_is_gallery', $rec['gallery'] );
                    if( K_PRETTY_URLS ){
                        $CTX->set( 'k_template_link', K_SITE_URL . $FUNCS->get_pretty_template_link( $rec['name'] ) );
                    }
                    else{
                        $CTX->set( 'k_template_link', K_SITE_URL . $rec['name'] );
                    }

                    // call the children
                    foreach( $node->children as $child ){
                        $html .= $child->get_HTML();
                    }
                }
            }
            return $html;
        }

        function pages( $params, $node, $mode=0 ){
            global $CTX, $FUNCS, $PAGE, $DB, $AUTH;

            $attr = $FUNCS->get_named_vars(
                        array(
                               'masterpage'=>'',
                               'id'=>'',
                               'page_name'=>'',
                               'page_title'=>'',
                               'is_master'=>'0',
                               'limit'=>'',
                               'offset'=>'0',
                               'startcount'=>'',
                               'folder'=>'',
                               'include_subfolders'=>'1',
                               'start_on'=>'',
                               'stop_before'=>'',
                               'show_future_entries'=>'0',
                               'orderby'=>'',
                               'order'=>'',
                               'paginate'=>'0', /*ignores $_GET['pg'] if 0*/
                               'custom_field'=>'',
                               'skip_custom_fields'=>'0',

                               'keywords'=>'', /* search tag */
                               'page_id'=>'', /* comments tag */
                               'approved'=>null, /* -do- */
                               'pid'=>'', /* related_pages tag */
                               'cid'=>'', /* reverse_related_pages tag */
                               'fid'=>'', /* related_pages, reverse_related_pages tag */

                               'qs_param'=>'', /* custom var in querystring that denotes paginated page */
                               'count_only'=>'0',
                               'ids_only'=>'0',

                               'sql'=>'', /* query tag */
                               'fetch_pages'=>'0', /* do */
                               'return_sql'=>'0',

                               'show_unpublished'=>'0', /* only for admins */
                               'aggregate_by'=>'',    /* the relation field to aggregate for count */

                               'base_link'=>'', /* replaces the default $PAGE->link used for paginator crumb links */
                               'token'=>'',
                               'adjust'=>'0', /* to handle non-uniform pagination limit */
                              ),
                        $params);

            // HOOK: alter_page_tag_params
            $FUNCS->dispatch_event( 'alter_page_tag_params', array(&$attr, $params, $node, &$mode, $token) );

            extract( $attr );

            // sanitize params
            $masterpage = trim( $masterpage );
            $id = trim( $id );
            $page_name = trim( $page_name );
            $page_title = trim( $page_title );
            $limit = $FUNCS->is_non_zero_natural( $limit ) ? intval( $limit ) : 1000; //Practically unlimited.
            $offset = $FUNCS->is_natural( $offset ) ? intval( $offset ) : 0;
            $startcount = $FUNCS->is_int( $startcount ) ? intval( $startcount ) : 1;
            if( $mode==1 ) $paginate=1; //pagination always on for 'search' tag
            if( $mode==3 ) $paginate=0; //pagination always off for 'calendar' tag
            $paginate = ( $paginate==1 ) ? 1 : 0;
            $pgn_pno = 1;
            $skip_custom_fields = ( $skip_custom_fields==1 ) ? 1 : 0;
            $count_only = ( $count_only==1 ) ? 1 : 0;
            $ids_only = ( $ids_only==1 ) ? 1 : 0;
            $raw_sql = trim( $sql );
            $fetch_pages = ( $fetch_pages==1 ) ? 1 : 0;
            $return_sql = ( $return_sql==1 || $return_sql==2 ) ? intval( $return_sql ) : 0;
            $show_unpublished = ( $show_unpublished==1 ) ? 1 : 0;
            $aggregate_by = trim( $aggregate_by );
            $base_link = trim( $base_link );
            $token = trim( $token );
            $adjust = $FUNCS->is_int( $adjust ) ? intval( $adjust ) : 0;

            $qs_param = trim( $qs_param );
            if( $qs_param=='' ){
                $qs_param = ( $mode==2 ) ? 'comments_pg' : 'pg';
            }
            if( $paginate ){
                if( isset($_GET[$qs_param]) && $FUNCS->is_non_zero_natural( $_GET[$qs_param] ) ){
                    $pgn_pno = (int)$_GET[$qs_param];
                }
            }
            $show_future_entries = ( $show_future_entries==1 ) ? 1 : 0;
            $hide_future_entries = !$show_future_entries;

            $sql = '';
            $order_sql = '';
            $limit_sql = '';
            $distinct = 0;
            $group_by = array();
            $having = array();

            $count_query_field_as = 'cnt';
            $rec_tpl = array();

            if( $mode==0 || $mode==3 || $mode==4 ){ // 3 if called from calender tag, 4 if called from related_pages/reverse_related_pages tag
                $query_table = K_TBL_PAGES . ' p';
                $default_orderby = 'publish_date';
                if( $mode==4 ){ // related pages
                    if( $pid ){
                        $query_table .= "\r\n" .'inner join '.K_TBL_RELATIONS.' rel on rel.cid = p.id';
                        $sql .= "rel.pid=".$DB->sanitize($pid)." AND rel.fid=".$DB->sanitize($fid)." AND\r\n";
                    }
                    elseif( $cid ){ // reverse related
                        $query_table .= "\r\n" .'inner join '.K_TBL_RELATIONS.' rel on rel.pid = p.id';
                        $sql .= "rel.cid=".$DB->sanitize($cid)." AND rel.fid=".$DB->sanitize($fid)." AND\r\n";
                    }
                    else{ //huh?
                        return;
                    }
                    $default_orderby = 'rel.weight';
                    $mode=0; // convert to the normal 'pages' tag
                }

                $query_fields = array('p.id', 'p.template_id');
                if( $mode==3 ) $query_fields[] = 'p.publish_date';
                $count_query_field = 'p.id';

                if( $masterpage=='' ){ $masterpage = $PAGE->tpl_name; }

                $include_subfolders = ( $include_subfolders==0 ) ? 0 : 1;

                $start_on = trim( $start_on );
                if( $start_on ) $start_on = $FUNCS->make_date( $start_on );
                $stop_before = trim( $stop_before );
                if( $stop_before ) $stop_before = $FUNCS->make_date( $stop_before );


                // build the sql where clause using the supplied params.
                // masterpage
                $rs = $DB->select( K_TBL_TEMPLATES, array('*'), "name='" . $DB->sanitize( $masterpage ). "'" );
                if( !count($rs) ){
                    die( "ERROR: Tag \"".$node->name."\": masterpage '".$FUNCS->cleanXSS($masterpage)."' not found" );
                }
                $rec_tpl = $rs[0];
                $tpl_id = $rs[0]['id'];
                $tpl_is_gallery = $rs[0]['gallery'];
                $sql .= "p.template_id='" . $DB->sanitize( $tpl_id )."'";

                // id?
                if( strlen($id) ){
                    $sql .= $FUNCS->gen_sql( $id, 'p.id', 1);
                }

                // name?
                if( $page_name!='' ){
                    $sql .= $FUNCS->gen_sql( $page_name, 'p.page_name');
                }

                // title?
                if( $page_title!='' ){
                    $sql .= $FUNCS->gen_sql( $page_title, 'p.page_title');
                }

                // folder?
                $folder = trim( $folder );
                if( $folder!='' ){

                    $arr_folders = array();
                    // get all the folders of the masterpage
                    if( $masterpage==$PAGE->tpl_name ){
                        $folders = &$PAGE->folders;
                    }
                    else{
                        $folders = &$FUNCS->get_folders_tree( $tpl_id, $masterpage );
                    }

                    // Negation?
                    $neg = 0;
                    $pos = strpos( strtoupper($folder), 'NOT ' );
                    if( $pos!==false && $pos==0 ){
                        $neg = 1;
                        $folder = trim( substr($folder, strpos($folder, ' ')) );
                    }

                    // multiple folders specified?
                    $arr_parent_folders = array_map( "trim", explode( ',', $folder ) );
                    foreach( $arr_parent_folders as $parent_folder ){
                        if( $parent_folder ){
                            // locate the folder
                            $f = &$folders->find( $parent_folder );
                            if( $f ){
                                if( $include_subfolders ){
                                    // get all the child folders of it
                                    $sub_folders = $f->get_children(); //includes the parent folder too
                                    foreach( $sub_folders as $sf ){
                                        if( !array_key_exists( $sf->name, $arr_folders ) ){
                                            $arr_folders[$sf->name] = $sf->id;
                                        }
                                    }
                                }
                                else{
                                    if( !array_key_exists( $f->name, $arr_folders ) ){
                                        $arr_folders[$f->name] = $f->id;
                                    }
                                }
                            }
                        }
                    }

                    if( count($arr_folders) ){
                        $sql .= " AND ";
                        if( $neg ) $sql .= "NOT";
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
                    if( !$include_subfolders ){
                        $sql .= " AND p.page_folder_id='-1'";
                    }
                }

                // is_master?
                if( $is_master ) $sql .= " AND p.is_master = '1'";

                // dates
                if( $start_on ) $sql .= " AND p.publish_date >= '".$DB->sanitize( $start_on )."'";
                if( $hide_future_entries ){
                    $cur_time = $FUNCS->get_current_desktop_time();
                    $stop_before = $FUNCS->smaller_date( $stop_before, $cur_time );
                }
                if( $stop_before ){
                    $sql .= " AND p.publish_date < '".$DB->sanitize( $stop_before )."'";
                }
                if( /*$AUTH->user->access_level < K_ACCESS_LEVEL_ADMIN ||*/ !$show_unpublished ){
                    $sql .= " AND NOT p.publish_date = '0000-00-00 00:00:00'";
                }
                $sql .= " AND p.parent_id=0"; //skip drafts

                // orderby
                // canonical orderby will be prefixed with 'p.' in generated sql
                // TODO: order by folder_name, folder_title, recent_comment, relation fields
                $arr_canonical_orderby = array( 'publish_date', 'page_name', 'page_title', 'modification_date', 'comments_count' );
                if( $tpl_is_gallery ){
                    $arr_canonical_orderby = array_merge( $arr_canonical_orderby, array('file_name', 'file_ext', 'file_size') );
                }
                $arr_acceptable_orderby = array('random', 'weight');
                if( $aggregate_by ) $arr_acceptable_orderby[]='k_rel_count';

                // HOOK: alter_valid_orderby
                $FUNCS->dispatch_event( 'alter_valid_orderby', array(&$arr_acceptable_orderby, &$arr_canonical_orderby, $params, $node, $rec_tpl, $token) );

                $arr_acceptable_orderby = array_merge( $arr_canonical_orderby, $arr_acceptable_orderby );

                $arr_custom_orderby = array(); // custom fields in 'order_by' clause (points to the entry in $arr_orderby and $arr_order below)

                if( $mode==3 ){
                    // for calendar, these two params are always fixed
                    $order = 'asc';
                    $orderby = 'publish_date';
                }
                $arr_order = array_map( "strtolower", array_map( "trim", explode(',', $order)) );
                $arr_orderby = array_map( "strtolower", array_map( "trim", explode(',', $orderby)) );
                for( $i=0; $i<count($arr_orderby); $i++ ){
                    $orderby = $arr_orderby[$i];
                    if( $orderby ){
                        if( !in_array($orderby, $arr_acceptable_orderby) ){
                            $arr_custom_orderby[$arr_orderby[$i]] = $i;
                        }
                    }
                    else{
                        $arr_orderby[$i] = $default_orderby; //'publish_date';
                    }
                    $order = $arr_order[$i];
                    if( $order!='desc' && $order!='asc' ) $arr_order[$i] = 'desc';

                }

                if( $mode==0 ){
                    // custom fields
                    // e:g custom_field='my_intro=East | my_price>100000'
                    $arr_ops = array( '\!==', '\!=', '\<=', '\>=', '==', '=', '\<\>', '\<', '\>' );
                    $arr_params = array_map( "trim", preg_split("/(?<!\\\)\|/", $custom_field) ); // split at unescaped pipe char
                    $arr_custom_fields = array();
                    foreach( $arr_params as $param ){
                        if( !$param ) continue;
                        foreach( $arr_ops as $op ){
                            $pattern = "/(?<!\\\)".$op."/"; //split at unescaped ops
                            if( preg_match($pattern, $param, $matches) ){
                                $arr_tmp = array_map( "trim", preg_split($pattern, $param) );
                                $key = $arr_tmp[0];
                                $val = array_map( "trim", preg_split("/(?<!\\\),/", $arr_tmp[1]) ); //split at unescaped comma
                                for( $x=0; $x<count($val); $x++ ) $val[$x] = $DB->sanitize( trim(stripslashes($val[$x]), "'\"") );

                                $arr_custom_fields[] = array('name'=>$key, 'val'=>$val, 'op'=>stripslashes($op));
                                break;
                            }
                        }
                    }

                    // if 'aggregate_by' specified, set it as a custom relational field
                    if( $aggregate_by ) $arr_custom_fields[]=array('name'=>$aggregate_by,'op'=>'=','val'=>array(),'is_aggregate'=>1);

                    // HOOK: alter_page_tag_fields
                    $FUNCS->dispatch_event( 'alter_page_tag_fields', array(&$arr_custom_fields, &$arr_orderby, &$arr_order, &$arr_custom_orderby, $params, $node, $rec_tpl, $token) );

                    $arr_rel_fields = array();
                    $arr_rel_types = array();

                    // HOOK: alter_relational_types
                    // modules can add their custom types that store data in relation table
                    $FUNCS->dispatch_event( 'alter_relational_types', array(&$arr_rel_types, $params, $node, $rec_tpl, $token) );

                    $arr_rel_types = array_merge( array('relation'), $arr_rel_types );

                    if( count($arr_custom_fields) || count($arr_custom_orderby) ){
                        // resolve custom field names to ids
                        $rs_cf = $DB->select( K_TBL_FIELDS, array('*'), "template_id='" . $DB->sanitize( $tpl_id ). "'" );
                        $arr_tables = array();
                        $count = 0;
                        for( $x=0; $x<count($arr_custom_fields); $x++ ){

                            if( $arr_custom_fields[$x]['processed'] ) continue; // can be set from hook

                            // is it a 'relation' field with template name ( e.g. 'courses.php::taken' )?
                            if( strpos($arr_custom_fields[$x]['name'], '::')!==false ){
                                list( $rel_field_tpl, $rel_field_name ) = array_map( "trim", explode( '::', $arr_custom_fields[$x]['name'] ) );
                                if( $rel_field_tpl != $masterpage ){
                                    $arr_rel_fields[$rel_field_tpl][] = array( 'name'=>$rel_field_name, 'op'=>$arr_custom_fields[$x]['op'], 'val'=>$arr_custom_fields[$x]['val'], 'is_aggregate'=>$arr_custom_fields[$x]['is_aggregate'] );
                                    $arr_custom_fields[$x]['processed'] = 1;
                                    continue;
                                }
                                else{
                                    $arr_custom_fields[$x]['name'] = $rel_field_name;
                                }
                            }
                            // is it the 'k_rel_count' field that becomes available with 'aggregate_by'?
                            if( $aggregate_by && $arr_custom_fields[$x]['name']=='k_rel_count' ){
                                $rel_op = $arr_custom_fields[$x]['op'];
                                if( $rel_op=='==' ){
                                    $rel_op='=';
                                }
                                elseif( $rel_op=='!==' || $rel_op=='!=' ){
                                    $rel_op='<>';
                                }
                                $rel_val = $arr_custom_fields[$x]['val'][0];
                                if( $FUNCS->_validate_natural($rel_val) ){
                                    $having[]="k_rel_count $rel_op $rel_val";
                                    $count_query_field_as='k_rel_count';
                                }
                                $arr_custom_fields[$x]['processed'] = 1;
                                continue;
                            }

                            for( $i=0; $i<count($rs_cf); $i++ ){
                                $f = $rs_cf[$i];
                                if( $f['name']==$arr_custom_fields[$x]['name'] ){

                                    // 'relation' field?
                                    if( in_array($f['k_type'], $arr_rel_types) ){
                                        $arr_rel_fields[$masterpage][] = array( 'related_field'=>$f, 'name'=>$f['name'], 'op'=>$arr_custom_fields[$x]['op'], 'val'=>$arr_custom_fields[$x]['val'], 'id'=>$f['id'], 'is_aggregate'=>$arr_custom_fields[$x]['is_aggregate'] );
                                        $arr_custom_fields[$x]['processed'] = 1;
                                        continue 2;
                                    }

                                    $arr_custom_fields[$x]['id'] = $f['id'];
                                    $arr_custom_fields[$x]['type'] = $f['search_type'];
                                    $arr_custom_fields[$x]['field_name'] = ( $f['search_type']=='text' ) ? 'search_value' : 'value';

                                    if( $f['search_type']=='text' ){
                                        if( $arr_custom_fields[$x]['op']=='=' || $arr_custom_fields[$x]['op']=='!=' ){
                                            if( $arr_custom_fields[$x]['op']=='=' ) $arr_custom_fields[$x]['op'] = 'LIKE';
                                            else $arr_custom_fields[$x]['op'] = 'NOT LIKE';
                                            for( $c=0; $c<count($arr_custom_fields[$x]['val']); $c++ ){
                                                $arr_custom_fields[$x]['val'][$c] = "%" . $arr_custom_fields[$x]['val'][$c] . "%";
                                            }
                                        }
                                    }
                                    if( $arr_custom_fields[$x]['op']=='==' ) $arr_custom_fields[$x]['op'] = '=';
                                    elseif( $arr_custom_fields[$x]['op']=='!==' ) $arr_custom_fields[$x]['op'] = '!=';

                                    if( !array_key_exists($f['name'], $arr_tables) ){
                                        $arr_tables[$f['name']]['id'] = $f['id'];
                                        $arr_tables[$f['name']]['tbl_name'] = ( $f['search_type']=='text' ) ? K_TBL_DATA_TEXT : K_TBL_DATA_NUMERIC;
                                        $arr_tables[$f['name']]['alias'] = sprintf( "t%d", $count++ );

                                    }
                                    $arr_custom_fields[$x]['table_name'] = $arr_tables[$f['name']]['alias'];
                                    break;
                                }
                            }
                            if( !array_key_exists('id', $arr_custom_fields[$x]) ){
                                die("ERROR: Custom Field \"".$arr_custom_fields[$x]['name']."\" does not exist in '" . $FUNCS->cleanXSS($masterpage) . "'" );
                            }
                        }

                        // resolve relation fields
                        if( count($arr_rel_fields) ){
                            foreach( $arr_rel_fields as $rel_field_tpl=>$rel_fields ){// for each masterpage

                                $rel_field_tpl_id = 0;
                                $rel_count = 0;
                                foreach( $rel_fields as $rel_field ){ // for each relation field in the masterpage

                                    if( $rel_field['op']!=='=' && $rel_field['op']!=='!=' ){
                                        die("ERROR: Tag \"".$node->name."\": custom field '".$FUNCS->cleanXSS($rel_field['name'])."' does not support '".$rel_field['op']."' operator");
                                    }

                                    //  id of the field itself
                                    if( ( $rel_field_tpl!=$masterpage ) ){
                                        if( !$rel_field_tpl_id ){
                                            // get template_id of masterpage
                                            $rs = $DB->select( K_TBL_TEMPLATES, array('id'), "name='" . $DB->sanitize( $rel_field_tpl ). "'" );
                                            if( count($rs) ){
                                                $rel_field_tpl_id = $rs[0]['id'];
                                            }
                                            else{
                                                die("ERROR: Tag \"".$node->name."\": masterpage '".$FUNCS->cleanXSS($rel_field_tpl)."' not found for custom field '". $FUNCS->cleanXSS( $rel_field['name'] )."'" );
                                            }
                                        }

                                        // get relation_field_id using template_id
                                        $rs = $DB->select( K_TBL_FIELDS, array('*'), "template_id='" . $DB->sanitize( $rel_field_tpl_id ). "'".$FUNCS->gen_sql( implode(",", $arr_rel_types), 'k_type' )." AND name='" . $DB->sanitize( $rel_field['name'] ) . "'" );

                                        if( count($rs) ){
                                            $arr_rel_fields[$rel_field_tpl][$rel_count]['id'] = $rs[0]['id'];
                                        }
                                        else{
                                            die("ERROR: Tag \"".$node->name."\": custom field '".$FUNCS->cleanXSS($rel_field['name'])."' not defined in ".$FUNCS->cleanXSS($rel_field_tpl));
                                        }
                                    }

                                    // ids of the field's values
                                    if( count($rel_field['val']) ){
                                        // .. check if values are already ids (e.g. 'id(373,372,371)')
                                        $val1 = trim( $rel_field['val'][0] );
                                        $val2 = trim( $rel_field['val'][count($rel_field['val'])-1] );
                                        if( (stripos($val1, 'id(')===0) && (substr($val2, -1)==')') ){
                                            $val1 = substr( $val1, 3 );
                                            $arr_rel_fields[$rel_field_tpl][$rel_count]['val'][0] = $val1;
                                            if( count($rel_field['val'])==1 ) $val2=$val1;
                                            $val2 = substr( $val2, 0, -1 );
                                            $arr_rel_fields[$rel_field_tpl][$rel_count]['val'][count($rel_field['val'])-1] = $val2;
                                        }
                                        else{
                                            if( $rel_field_tpl!=$masterpage ){
                                                // reverse related - the related pages should belong to this very template
                                            }
                                            else{
                                                // get the related template
                                                $obj_field = new Relation( $rel_field['related_field'], new KError('dummy'), new KError('dummy') );
                                                $related_template_name = trim( $obj_field->masterpage );
                                                unset( $obj_field );
                                                $rs = $DB->select( K_TBL_TEMPLATES, array('id'), "name='" . $DB->sanitize( $related_template_name ). "'" );
                                                if( count($rs) ){
                                                    $rel_field_tpl_id = $rs[0]['id'];
                                                }
                                                else{
                                                    die("ERROR: Tag \"".$node->name."\": masterpage '".$FUNCS->cleanXSS($related_template_name)."' not found for related field '". $FUNCS->cleanXSS( $rel_field['name'] )."'" );
                                                }

                                            }

                                            // convert related page_names to ids (if not 'ANY')
                                            if( count($rel_field['val'])==1 && trim($rel_field['val'][0])=='ANY' ){
                                                $arr_rel_fields[$rel_field_tpl][$rel_count]['skip_ids'] = 1;
                                                $arr_rel_fields[$rel_field_tpl][$rel_count]['val'] = array();
                                            }
                                            else{
                                                $str_names = implode( ",", $rel_field['val'] );
                                                $rs = $DB->select( K_TBL_PAGES, array('id'), "template_id='" . $DB->sanitize( $rel_field_tpl_id ). "'" . $FUNCS->gen_sql( $str_names, 'page_name' ) );
                                                $arr_vals = array();
                                                foreach( $rs as $rec ){
                                                    $arr_vals[]=$rec['id'];
                                                }
                                                $arr_rel_fields[$rel_field_tpl][$rel_count]['val'] = $arr_vals;
                                            }

                                        }
                                    }
                                    $rel_count++;
                                }
                            }

                        }

                        // resolve custom_fields used as order_by
                        foreach( $arr_custom_orderby as $k=>$v ){
                            $cf_found = 0;
                            for( $i=0; $i<count($rs_cf); $i++ ){
                                $f = $rs_cf[$i];
                                if( $f['name']==$k ){
                                    // 'relation' field?
                                    if( in_array($f['k_type'], $arr_rel_types) ){
                                        unset( $arr_orderby[$v] );
                                        unset( $arr_order[$v] );
                                        continue 2;
                                    }
                                    $cf_found = 1;
                                    if( !array_key_exists($f['name'], $arr_tables) ){
                                        $arr_tables[$f['name']]['id'] = $f['id'];
                                        $arr_tables[$f['name']]['tbl_name'] = ( $f['search_type']=='text' ) ? K_TBL_DATA_TEXT : K_TBL_DATA_NUMERIC;
                                        $arr_tables[$f['name']]['alias'] = sprintf( "t%d", $count++ );
                                    }
                                    $arr_orderby[$v] = $arr_tables[$f['name']]['alias'] . "." . ( ($f['search_type']=='text') ? 'search_value' : 'value' );
                                    break;
                                }
                            }
                            if( !$cf_found ) die("ERROR: Unknown orderby clause \"".$FUNCS->cleanXSS( $k )."\"");
                        }

                        // generate sql to query custom fields
                        if( count($arr_rel_fields) ){

                            if( count($arr_rel_fields) > 1 ) $distinct = 1;
                            $rel_suffix = 1;

                            foreach( $arr_rel_fields as $rel_field_tpl=>$rel_fields ){// for each masterpage
                                foreach( $rel_fields as $rel_field ){ // for each relation field in the masterpage

                                    if( count($rel_field['val']) > 1 || ($rel_field['skip_ids'] && $rel_field['op']=='=') ) $distinct = 1;

                                    $rel_tbl = 'rel' . $rel_suffix;
                                    $join_field = ($rel_field_tpl==$masterpage) ? 'pid' : 'cid';
                                    $where_field = ($rel_field_tpl==$masterpage) ? 'cid' : 'pid';

                                    if( $rel_field['is_aggregate'] ){

                                        $query_fields[]='count(p.id) as k_rel_count';
                                        $group_by[]='p.id';

                                        foreach( $arr_rel_fields[$rel_field_tpl] as $rfld ){
                                            if( $rfld['id']==$rel_field['id'] && !$rfld['is_aggregate'] ){
                                                continue 2; // skip if table already joined
                                            }
                                        }
                                    }

                                    if( $rel_field['op']=='!=' ){// Negation in N:M relation requires special consideration
                                        $str_ids = $FUNCS->gen_sql( implode(",", $rel_field['val']), "$where_field", 1 );
                                        if( $str_ids || $rel_field['skip_ids'] ){
                                            $rs = $DB->select( K_TBL_RELATIONS, array("$join_field as id"), "fid='".$DB->sanitize($rel_field['id'])."'".$str_ids, 1 );
                                            $arr_vals = array();
                                            foreach( $rs as $rec ){
                                                $arr_vals[]=$rec['id'];
                                            }
                                            if( count($arr_vals) ){
                                                $sql .= " \r\n" . 'AND p.id NOT IN(' . implode( ",", $arr_vals ) .')';
                                            }
                                        }
                                    }
                                    else{
                                        $str_ids = '';
                                        if( !$rel_field['is_aggregate'] && !$rel_field['skip_ids'] ){
                                            $str_ids = $FUNCS->gen_sql( implode(",", $rel_field['val']), "$rel_tbl.$where_field", 1 );
                                            if( !$str_ids ) $str_ids = " AND $rel_tbl.$where_field=-1";
                                        }

                                        $query_table .= "\r\n inner join ".K_TBL_RELATIONS." $rel_tbl on $rel_tbl.$join_field = p.id";
                                        $sql .= " \r\n" . $str_ids . " AND $rel_tbl.fid=".$DB->sanitize($rel_field['id']);
                                    }

                                    $rel_suffix++;
                                }
                            }
                        }

                        if( count($arr_tables) ){
                            $where = ' AND ' ."\r\n" . '(' ."\r\n";
                            $sep = '';
                            foreach( $arr_tables as $k=>$tbl ){
                                $join .= ' inner join '.$tbl['tbl_name'].' '.$tbl['alias'].' on '.$tbl['alias'].'.page_id = p.id' . "\r\n";
                                $where .= $sep . $tbl['alias'].'.field_id=' . $DB->sanitize( $tbl['id'] );
                                $sep = ' AND' . "\r\n";
                            }
                            $where .= "\r\n" . ')' . "\r\n";
                        }

                        if( count($arr_custom_fields) ){

                            // skip processed custom_fields
                            $arr_tmp = $arr_custom_fields;
                            $arr_custom_fields = array();
                            foreach( $arr_tmp as $cf ){
                                if( $cf['processed'] ) continue;
                                $arr_custom_fields[] = $cf;
                            }
                            if( count($arr_custom_fields) ){
                                $where .= ' AND ' ."\r\n" . '(' ."\r\n";
                                $sep = '';
                                foreach( $arr_custom_fields as $cf ){
                                    $where .= $sep;
                                    if( count($cf['val']) > 1 ) $where .= '(';
                                    $sep2 = '';
                                    foreach( $cf['val'] as $val ){
                                        $where .= $sep2 . $cf['table_name'].'.'.$cf['field_name'].' '.$cf['op'].' \''.$val.'\'';
                                        $sep2 = ' OR ';
                                    }
                                    if( count($cf['val']) > 1 ) $where .= ')';
                                    $sep = ' AND' . "\r\n";
                                }
                                $where .= "\r\n" . ')' . "\r\n";
                            }
                        }

                        // append to original sql
                        $query_table .= "\r\n" .$join;
                        $sql .= $where;
                    }
                }

                // orderby
                $sep = '';
                for( $i=0; $i<count($arr_orderby); $i++ ){
                    $orderby = $arr_orderby[$i];

                    if( in_array($orderby, $arr_canonical_orderby) ){ $orderby = 'p.'.$orderby; }
                    elseif( $orderby == 'weight' ){ $orderby = 'p.k_order'; }
                    elseif( $orderby == 'random' ){
                        if( $paginate ){
                            if(!session_id()) @session_start();
                            if(empty($_SESSION['k_seed'])) $_SESSION['k_seed'] = rand();
                            $orderby = 'RAND(' .$_SESSION['k_seed']. ')';
                        }
                        else{
                            $orderby = 'RAND()';
                        }
                        $PAGE->no_cache=1;
                    }
                    else{
                        $FUNCS->dispatch_event( 'set_orderby_clause', array(&$orderby, $params, $node, $rec_tpl, $token) );
                    }

                    $order_sql .= $sep . $DB->sanitize( $orderby );

                    $order = $arr_order[$i];
                    $order_sql .= " " . $DB->sanitize( $order );

                    $sep = ", ";
                }

            }// end mode 0 or 3 or 4
            elseif( $mode==1 ){ // called from 'Search' tag
                $keywords = trim( $keywords );
                if( !strlen($keywords) ) return;

                // add the '+' for boolean search
                $sep = "";
                $keywords = explode( ' ', $keywords );
                foreach( $keywords as $kw ){
                    $kw = trim( $kw );
                    if( !$kw ) continue;
                    $bool_keywords .= $sep . "+" . $kw;
                    $sep = " ";
                }

                $query_table = K_TBL_FULLTEXT ." cf
                                inner join  ".K_TBL_PAGES." cp on cp.id=cf.page_id
                                inner join ".K_TBL_TEMPLATES." ct on ct.id=cp.template_id";
                $score_field = "((MATCH(cf.content) AGAINST ('".$DB->sanitize($bool_keywords)."') * 1) + (MATCH(cf.title) AGAINST ('".$DB->sanitize($bool_keywords)."') * 1.25)) as score";
                $query_fields = array( 'cp.template_id', 'cp.id', 'cf.title', 'cf.content', $score_field );
                $count_query_field = 'cf.page_id';

                $sql = " ((MATCH(cf.content) AGAINST ('".$DB->sanitize($bool_keywords)."' IN BOOLEAN MODE) * 1) + (MATCH(cf.title) AGAINST ('".$DB->sanitize($bool_keywords)."' IN BOOLEAN MODE) * 1.25))";
                // search within which template(s)?
                if( $masterpage ){
                    // masterpage="NOT blog.php, testimonial.php"
                    $sql .= $FUNCS->gen_sql( $masterpage, 'ct.name');
                }
                if( $hide_future_entries ){
                    $sql .= " AND cp.publish_date < '".$FUNCS->get_current_desktop_time()."'";
                }
                if( !$show_unpublished ){
                    $sql .= " AND NOT cp.publish_date = '0000-00-00 00:00:00'";
                }
                $sql .= " AND cp.access_level<='".$AUTH->user->access_level."'";
                $sql .= " AND ct.executable=1";

                $order_sql = 'score DESC';

            }
            elseif( $mode==2 ){ // called from 'Comments' tag
                $query_table = K_TBL_COMMENTS . " cc";
                $query_table .= "\n inner join ".K_TBL_TEMPLATES." ct on ct.id=cc.tpl_id";
                $query_table .= "\n inner join ".K_TBL_PAGES." cp on cp.id=cc.page_id";
                $query_fields = array('cp.page_title, cp.page_name, ct.name tpl_name, ct.clonable, cc.*');
                $count_query_field = 'cc.id';
                if( is_null($approved) ){
                    $sql = "cc.approved=1"; // backward compatibility - if 'approved' parameter missing, assume only approved comments to be fetched
                }
                else{
                    $approved = trim( $approved );
                    if( $approved==='0' || $approved==='1' ){
                        $sql = "cc.approved='".intval($approved)."'";
                    }
                    else{
                        $sql = "1=1";
                    }
                }

                // comments of which template(s)?
                if( $masterpage ){
                    // masterpage="NOT blog.php, testimonial.php"
                    $sql .= $FUNCS->gen_sql( $masterpage, 'ct.name');
                }

                // comments of which page(s)?
                if( $page_id ){
                    $sql .= $FUNCS->gen_sql( $page_id, 'cc.page_id', 1);
                }
                if( $page_name!='' ){
                    //$query_table .= "\n inner join couch_pages cp on cp.id=cc.page_id";
                    $sql .= $FUNCS->gen_sql( $page_name, 'cp.page_name');
                }
                if( /*$AUTH->user->access_level < K_ACCESS_LEVEL_ADMIN ||*/ !$show_unpublished ){
                    $sql .= " AND NOT cp.publish_date = '0000-00-00 00:00:00'";
                }

                // Order?
                $order = strtolower( trim($order) );
                if( $order!='desc' && $order!='asc' ) $order = 'desc';
                $order_sql = "cc.date " . $order;

                // if being invoked on a page which has been loaded via comment_id,
                // need to calculate the page (of paginated comments) where this comment will appear
                if( $PAGE->comment_id && $page_id==$PAGE->id ){
                    $parent_id = $PAGE->id;
                    $tmp_sql = "page_id='" . $DB->sanitize( $parent_id )."' and ";
                    $tmp_sql .= "approved=1 and ";
                    if( $order == 'desc' ){
                        $tmp_sql .= "date>='".$DB->sanitize($PAGE->comment_date)."' ";
                    }
                    else{
                        $tmp_sql .= "date<='".$DB->sanitize($PAGE->comment_date)."' ";
                    }

                    $rs = $DB->select( K_TBL_COMMENTS, array('count(id) as cnt'), $tmp_sql );
                    $total_rows = $rs[0]['cnt'];
                    $total_rows -= $offset;
                    if( $total_rows > $limit ){
                        $PAGE->comment_page = ceil( $total_rows/$limit );
                    }

                    // no need to process further. Redirection is imminent.
                    return;
                }

            } // end mode==2 (comments)

            // limit
            $limit_sql = sprintf( "%d, %d", (($pgn_pno - 1) * $limit)+$offset+$adjust, $limit );


            // We have the sql query here..
            if( $mode==5 ){ // raw query .. called from 'query' tag
                $raw_sql = rtrim( $raw_sql, ' ;' );

                $pattern = '/^(\s*\(?\s*)(select\s)/i';
                $replacement = '${1}${2}SQL_CALC_FOUND_ROWS ';

                // is SELECT?
                if( !preg_match($pattern, $raw_sql) ){
                    ob_end_clean();
                    die( "ERROR: Tag \"query\" can process only SELECT statements" );
                }
                $raw_sql = preg_replace( $pattern, $replacement, $raw_sql );

                // retained for backward compatibility where
                // ORDER BY clause containing calculated fields could mess up the count(*) query.
                // For such cases the 'orderby' param was used to provide the raw ORDER BY statement.
                // Now, the raw query itself can contain any orderby clause.
                $orderby = trim( $orderby );
                if( $orderby ){
                    if( !preg_match("/^order\s/is", $orderby) ){
                        $raw_sql .= ' ORDER BY';
                    }
                    $raw_sql .= ' ' . $orderby;
                }

                $raw_sql = rtrim( $raw_sql, ' ;' );
                $raw_sql .= ' LIMIT ' . $limit_sql;
                $rs = $DB->raw_select( $raw_sql );

                // get count for pagination
                $rs2 = $DB->raw_select( 'SELECT FOUND_ROWS() as cnt;' );
                $total_rows = $rs2[0]['cnt'];

                // return if only count asked for
                if( $count_only ) return $total_rows;

                if( $fetch_pages && count($rs) ){
                    // do some sanity checks to make sure the query can fetch pages like cms:pages does
                    if( !(array_key_exists('id', $rs[0]) && array_key_exists('template_id', $rs[0])) ){
                        ob_end_clean();
                        die( "ERROR: 'fetch_pages' param of tag \"query\" requires 'id' and 'template_id' fields in the SQL statement" );
                    }
                }

            }
            else{

                // HOOK: alter_page_tag_query
                // called routine should check for $node->name to know which tag is being executed.
                // rs_tpl (masterpage info) will be filled only for mode 0 (i.e. pages/related_pages/reverse_related_pages tags)
                $FUNCS->dispatch_event( 'alter_page_tag_query', array(&$distinct, &$count_query_field, &$count_query_field_as, &$query_fields, &$query_table, &$sql, &$group_by, &$having, &$order_sql, &$limit_sql, &$mode, $params, $node, $rec_tpl, $token) );

                $orig_sql = $sql;

                $group_by = array_filter( array_map("trim", $group_by) );
                if( count($group_by) ){
                    $group_by = 'GROUP BY ' . implode( ",", $group_by );
                    $sql .= "\r\n" . $group_by;
                    $distinct=0;
                }
                else{
                    $group_by = '';
                }

                $having = array_filter( array_map("trim", $having) );
                if( count($having) ){
                    $having = 'HAVING ' . implode( " AND ", $having );
                    $sql .= "\r\n" . $having;
                }
                else{
                    $having = '';
                }

                // HOOK: alter_page_tag_query_ex
                $FUNCS->dispatch_event( 'alter_page_tag_query_ex', array(&$distinct, &$count_query_field, &$count_query_field_as, &$query_fields, &$query_table, &$orig_sql, &$sql, &$group_by, &$having, &$order_sql, &$limit_sql, &$mode, $params, $node, $rec_tpl, $token) );

                // first query for pagination
                if( $distinct ){
                    $rs = $DB->select( $query_table, array('count(DISTINCT '.$count_query_field.') as '.$count_query_field_as), $sql );
                }
                else{
                    $rs = $DB->select( $query_table, array('count('.$count_query_field.') as '.$count_query_field_as), $sql );
                }
                $total_rows = $rs[0][$count_query_field_as];

                // Return if only count asked for
                if( $count_only ) return $total_rows;

                // actual query
                if( $return_sql ){
                    $sep = $fields = $html = '';
                    foreach( $query_fields as $field ){
                        $fields .= $sep . $field;
                        $sep = ', ';
                    }

                    if( $return_sql==2 ){
                        // return query parts
                        $CTX->set( 'k_sql_distinct', $distinct ); // distinct
                        $CTX->set( 'k_sql_select', $fields ); // fields
                        $CTX->set( 'k_sql_from', $query_table ); // tables
                        $CTX->set( 'k_sql_where', $orig_sql ); // where
                        $CTX->set( 'k_sql_group_by', $group_by ); // group_by
                        $CTX->set( 'k_sql_having', $having ); // having
                        $CTX->set( 'k_sql_order', $order_sql ); // order
                        $CTX->set( 'k_sql_limit', $limit_sql ); // limit
                        foreach( $node->children as $child ){
                            $html .= $child->get_HTML();
                        }
                    }
                    else{
                        // return complete query
                        $html = ( $distinct ) ? 'SELECT DISTINCT ' : 'SELECT ';
                        $html .= $fields . ' FROM ' . $query_table . ' WHERE ' . $sql . ' ORDER BY ' . $order_sql . ' LIMIT ' . $limit_sql;
                    }

                    return $html;
                }
                else{
                    if( strlen($order_sql) ) $sql .= ' ORDER BY ' . $order_sql;
                    $sql .= ' LIMIT ' . $limit_sql;
                    $rs = $DB->select( $query_table, $query_fields, $sql, $distinct );
                }
            }

            // If only ids asked for, return with them
            if( $ids_only ){
                $sep = '';
                $html = '';
                foreach( $rs as $rec ){
                    $html .= $sep . $rec['id'];
                    $sep = ',';
                }
                return $html;
            }

            // if called from calendar tag, return results.
            if( $mode==3 ){
                return $rs;
            }

            $total_rows -= $offset;
            $total_rows -= $adjust;
            $total_pages = ceil( $total_rows/$limit );

            $count = count($rs);
            $page_link = ( strlen($base_link) ) ?  $base_link : K_SITE_URL . $PAGE->link;

            // append querystring params, if any
            $page_link = $FUNCS->get_qs_link( $page_link, array($qs_param) );

            if( $total_rows > $limit ){
                $paginated = 1;
                $sep = ( strpos($page_link, '?')===false ) ? '?' : '&';

                // 'Prev' link
                if( $pgn_pno > 1 ){
                    if( $pgn_pno==2 ){
                        $pgn_prev_link = $page_link;
                    }
                    else{
                        $pgn_prev_link = sprintf( "%s%s%s=%d", $page_link, $sep, $qs_param, $pgn_pno-1 );
                    }
                }
                // 'Next' link
                if( $pgn_pno < $total_pages ){
                    $pgn_next_link = sprintf( "%s%s%s=%d", $page_link, $sep, $qs_param, $pgn_pno+1 );
                }

                // Current paginated link
                $pgn_cur_link = ( $pgn_pno==1 ) ? $page_link : sprintf( "%s%s%s=%d", $page_link, $sep, $qs_param, $pgn_pno );
            }

            if( $count ){
                for( $x=0; $x<$count; $x++ ){
                    $rec = $rs[$x];

                    // HOOK: pre_alter_page_tag_context
                    $FUNCS->dispatch_event( 'pre_alter_page_tag_context', array($rec, $mode, $params, $node, $rec_tpl, $token, $x, $count) );

                    if( $mode==2 ){ //Comments
                        $CTX->set( 'k_comment_id', $rec['id'] );
                        $CTX->set( 'k_comment_page_id', $rec['page_id'] );
                        $CTX->set( 'k_comment_page_title', $rec['page_title'] );
                        $CTX->set( 'k_comment_page_name', $rec['page_name'] );
                        $CTX->set( 'k_comment_template_name', $rec['tpl_name'] );
                        $CTX->set( 'k_comment_author_id', $rec['user_id'] ); // 0 for unregistered
                        $CTX->set( 'k_comment_author', $rec['name'] );
                        $CTX->set( 'k_comment_author_email', $rec['email'] );
                        $CTX->set( 'k_comment_author_website', $rec['link'] );
                        $CTX->set( 'k_comment_author_ip_addr', $rec['ip_addr'] );
                        $CTX->set( 'k_comment_date', $rec['date'] );
                        $CTX->set( 'k_comment', $rec['data'] );
                        $CTX->set( 'k_comment_anchor', "comment-" . $rec['id'] ); //anchor name
                        $CTX->set( 'k_comment_approved', ( $rec['approved'] ) ? '1' : '0' );

                        $parent_link = ( K_PRETTY_URLS ) ? $FUNCS->get_pretty_template_link( $rec['tpl_name'] ) : $rec['tpl_name'];
                        $CTX->set( 'k_comment_link', K_SITE_URL . $parent_link . "?comment=" . $rec['id'] );
                    }
                    else{ // pages, search, query
                        if( $mode==5 ){ // raw query
                            $CTX->reset();
                            $rec_vars = array();
                            foreach( $rec as $rec_k=>$rec_v ){
                                $rec_vars[$rec_k] = $rec_v;
                            }
                            $CTX->set_all( $rec_vars );
                        }

                        if( $mode!=5 || ($mode==5 && $fetch_pages) ){ //Pages & Search
                            $pg = new KWebpage( $rec['template_id'], $rec['id'], 0, 0, $skip_custom_fields );
                            if( $pg->error ){
                                ob_end_clean();
                                die( 'ERROR: ' . $pg->err_msg );
                            }
                            $pg->set_context();
                            $pg->destroy(); // release the memory held by fields

                            if( $aggregate_by ) $CTX->set( 'k_rel_count', $rec['k_rel_count'] );
                        }

                        if( $mode==1 ){ // Search
                            if( $pg->tpl_is_clonable ){
                                $hilited = $FUNCS->hilite_search_terms( $keywords, $rec['title'], 1 );
                            }
                            else{
                                $hilited = $pg->tpl_title ? $pg->tpl_title : $pg->tpl_name;
                            }
                            $CTX->set( 'k_search_title', $hilited );
                            $CTX->set( 'k_search_content', $rec['content'] ); //entire content searched

                            $hilited = $FUNCS->hilite_search_terms( $keywords, $rec['content'] );
                            $CTX->set( 'k_search_excerpt', $hilited ); //hilighted excerpt of searched content
                        }
                    }

                    // Pagination related variables
                    $first_record_on_page = ($limit * ($pgn_pno - 1)) + $startcount;
                    if( $adjust ){ $first_record_on_page += $adjust; };
                    $total_records_on_page = ( $count<$limit ) ? $count : $limit;
                    $CTX->set( 'k_count', $x + $startcount );
                    $CTX->set( 'k_total_records', ( $adjust ) ? $total_rows+$adjust : $total_rows );
                    $CTX->set( 'k_total_records_for_pagination', $total_rows );
                    $CTX->set( 'k_total_records_on_page', $total_records_on_page );
                    $CTX->set( 'k_current_record', $first_record_on_page + $x );
                    $CTX->set( 'k_absolute_count', $first_record_on_page + $x ); //same as current record
                    $CTX->set( 'k_record_from', $first_record_on_page );
                    $CTX->set( 'k_record_to', $first_record_on_page + $total_records_on_page - 1 );
                    $CTX->set( 'k_total_pages', $total_pages );
                    $CTX->set( 'k_current_page', $pgn_pno );
                    $CTX->set( 'k_paginate_limit', $limit );


                    if( $x==0 ){
                        $CTX->set( 'k_paginated_top', 1 );
                    }
                    else{
                        $CTX->set( 'k_paginated_top', 0 );
                    }

                    if( $x==$count-1 ){
                        $CTX->set( 'k_paginated_bottom', 1 );
                    }
                    else{
                        $CTX->set( 'k_paginated_bottom', 0 );
                    }

                    if( /*($x==0 || $x==$count-1) &&*/ $paginate && $paginated ){
                        $CTX->set( 'k_paginator_required', 1 );
                        $CTX->set( 'k_page_being_paginated', $page_link );
                        $CTX->set( 'k_qs_param', $qs_param );
                        $CTX->set( 'k_paginate_link_next', $pgn_next_link );
                        $CTX->set( 'k_paginate_link_prev', $pgn_prev_link );
                        $CTX->set( 'k_paginate_link_cur', $pgn_cur_link );
                    }
                    else{
                        $CTX->set( 'k_paginator_required', 0 );
                        $CTX->set( 'k_paginate_link_next', '' );
                        $CTX->set( 'k_paginate_link_prev', '' );
                        $CTX->set( 'k_paginate_link_cur', '' );
                    }

                    // HOOK: alter_page_tag_context
                    $FUNCS->dispatch_event( 'alter_page_tag_context', array($rec, $mode, $params, $node, $rec_tpl, $token) );

                    // call the children
                    foreach( $node->children as $child ){
                        $html .= $child->get_HTML();
                    }

                    // HOOK: post_alter_page_tag_context
                    $FUNCS->dispatch_event( 'post_alter_page_tag_context', array($rec, $mode, $params, $node, $rec_tpl, $token, $x, $count) );
                }
            }
            else{
                $html = $this->_no_results( $node );
            }

            return $html;

        }

        function search( $params, $node ){
            global $CTX, $FUNCS;

            extract( $FUNCS->get_named_vars(
                       array(
                             'keywords'=>'',
                            ),
                       $params) );

            if( $keywords ){
                $keywords = trim( $keywords );
            }
            else{
                $keywords = trim( $_GET['s'] );
            }
            // is something being searched?
            if( !$keywords ) return;

            // get the keywords being searched
            $keywords = strip_tags( $keywords );
            $orig_keywords = $keywords;
            $keywords = str_replace( array(",", "+", "-", "(", ")"), ' ', $keywords );
            $keywords = implode (' ', array_filter(array_map("trim", explode(' ', $keywords))) );
            if( !strlen($keywords) ) return;

            // delegate to 'pages' tag
            for( $x=0; $x<count($params); $x++ ){
                $param = &$params[$x];
                if( strtolower($param['lhs'])=='keywords' ){
                    $param['rhs'] = $keywords;
                    $added = 1;
                    break;
                }
            }
            if( !$added ){
                $params[] = array('lhs'=>'keywords', 'op'=>'=', 'rhs'=>$keywords);
            }

            $CTX->set( 'k_search_query', $orig_keywords, 'global' );
            $html = $this->pages( $params, $node, 1 );

            return $html;
        }

        function comments( $params, $node ){

            // delegate to 'pages' tag
            return $this->pages( $params, $node, 2 );
        }

        function query( $params, $node ){

            // delegate to 'pages' tag
            return $this->pages( $params, $node, 5 );
        }

        function no_results( $params, $node ){

            return;
        }

        function _no_results( $node, $execute_tag=1 ){
            $html = '';

            if( $execute_tag ){

                // find and execute 'no_results' tag
                foreach( $node->children as $child ){
                    if( $child->type == K_NODE_TYPE_CODE && $child->name == 'no_results' ){
                        // call the children of no_results
                        foreach( $child->children as $grand_child ){
                            $html .= $grand_child->get_HTML();
                        }
                        break;
                    }
                }
            }

            return $html;
        }

        function search_form( $params, $node ){
            global $CTX, $FUNCS;
            extract( $FUNCS->get_named_vars(
                       array(
                             'msg'=>'Search&hellip;',
                             'processor'=>''
                            ),
                       $params) );

            $query = strip_tags( trim($_GET['s']) );
            $text = ( $query ) ? $query : $msg;
            $charset = K_CHARSET;
            $html = <<<FORM
            <form id="k_search_form" method="get" action="$processor" accept-charset="$charset">
                <p><input type="text" class="search_field" name="s" id="s" value="$text" onfocus="if (document.forms['k_search_form'].s.value === '$msg') document.forms['k_search_form'].s.value=''" onblur="if (document.forms['k_search_form'].s.value == '') document.forms['k_search_form'].s.value='$msg'" />
                <input type="submit" class="search_button" value="Search" /></p>
                <input type="hidden" name="nc" value="1" />
            </form>
FORM;
            return $html;
        }

        function excerptHTML($params, $node ){
            global $CTX, $FUNCS;

            extract( $FUNCS->get_named_vars(
                        array( 'count'=>'',
                               'ignore'=>'',
                               'trail'=>'&hellip;'
                              ),
                        $params) );

            $count = $FUNCS->is_non_zero_natural( $count ) ? intval( $count ) : 50;
            if( $ignore!='' ){
                $ignore = explode( ",", $ignore );
                $ignore = array_map( "trim", $ignore );
            }

            foreach( $node->children as $child ){
                $html .= $child->get_HTML();
            }

            $arr = explode( ' ', $html, $count+1 );
            if( count($arr) > $count ){
                $sep = $trail;
                $arr = array_slice( $arr, 0, -1 );
            }
            $html = implode( ' ', $arr ) . $sep;
            $parser = new KHTMLParser( $html, $ignore );
            $html = $parser->get_HTML();

            return $html;
        }

        function excerpt($params, $node ){
            global $CTX, $FUNCS;

            extract( $FUNCS->get_named_vars(
                        array( 'count'=>'',
                              'allow'=>'',
                              'trail'=>'&hellip;',
                              'truncate_chars'=>'0'
                             ),
                        $params) );

            $count = $FUNCS->is_non_zero_natural( $count ) ? intval( $count ) : 50;

            $allowed_tags = '';
            $truncate_chars = ( intval($truncate_chars)==1 ) ? 1 : 0;
            if( !$truncate_chars ){ // $allowed_tags have to be ignored for chars
                if( $allow!='' ){
                    $allow = explode( ",", $allow );
                    $allow = array_map( "trim", $allow );
                }
                if( is_array($allow) ){
                    foreach( $allow as $tag ){
                        if( $tag!='') $allowed_tags .= '<'.$tag.'>';
                    }
                }
            }

            // call the children
            foreach( $node->children as $child ){
                $html .= $child->get_HTML();
            }

            $str_utf = strip_tags( $html, $allowed_tags );

            // Replace all '&nbsp;' occurances with spaces
            $str_utf = str_replace( "&nbsp;", " ", $str_utf );

            // Coalese multiple 'white-space characters' (space, tabs, linebreaks etc.) into single spaces
            $str_utf = preg_replace( "/\s+/m", " ", $str_utf );

            // Trim off leading and trailing spaces
            $str_utf = trim( $str_utf );

            if( $truncate_chars ){
                $html = $FUNCS->excerpt( $str_utf, $count, $trail );
            }
            else{ // truncate whole words (default)
                $arr = explode( ' ', $str_utf, $count+1 );
                if( count($arr) > $count ){
                    $sep = $trail;
                    $arr = array_slice( $arr, 0, -1 );
                }
                $html = implode( ' ', $arr );

                if( $sep ){ // Trim off trailing punctuation
                    $html = rtrim( $html, ',:;!?.' );
                }
            }

            return $html . $sep;
        }

        function php( $params, $node ){
            global $FUNCS;
            extract( $FUNCS->get_named_vars(
                        array( 'debug'=>'0'
                             ),
                        $params) );
            $debug = ($debug==1) ? '1' : '0';

            foreach( $node->children as $child ){
                $code .= $child->get_HTML();
            }

            if( $debug ){
                $result = $code;
            }
            else{
                ob_start();
                echo eval($code);
                $result = ob_get_contents();
                ob_end_clean();
            }

            return $result;
        }

        function folder( $params, $node ){
            global $CTX, $FUNCS, $PAGE, $DB, $AUTH;

            if( $AUTH->user->access_level < K_ACCESS_LEVEL_SUPER_ADMIN ) return;
            if( $PAGE->tpl_dynamic_folders ) return; // nothing doing if folders are dynamic
            if( $PAGE->tpl_nested_pages ) return; // nothing doing if template supports nested_pages (they don't have folders)

            $attr = $FUNCS->get_named_vars(
                        array( 'name'=>'',
                               'title'=>'',
                               'desc'=>'',
                               'access_level'=>'0',
                               'weight'=>'0'
                              ),
                        $params);

            $attr['name'] = trim( $attr['name'] );
            $attr['access_level'] = is_numeric( $attr['access_level'] ) ? intval( $attr['access_level'] ) : 0;
            if( $attr['access_level']< 0 ) $attr['access_level'] = 0;
            $attr['weight'] = $FUNCS->is_int( $attr['weight'] ) ? intval( $attr['weight'] ) : 0;
            extract( $attr );

            if( !$name ) {die("ERROR: Tag \"".$node->name."\" needs a 'name' attribute");}
            $folder = &$PAGE->folders->find( $name );
            $parent_id = $CTX->get( 'k_parent_folder_id' );
            $parent_name = $CTX->get( 'k_parent_folder_name' );
            if( !$parent_id ) $parent_id = -1;

            if( $folder ){
                // Check if any attribute has been modified
                $modified = array();
                foreach( $attr as $k=>$v ){
                    if( $k=='desc' ) $k = 'k_'.$k; //MySQL has problems with this name
                    if( $folder->$k != trim($v) ){
                        $modified[$k] = $folder->$k = trim($v);
                    }
                }
                // Check if parent changed
                if( $folder->pid != $parent_id ){
                    $modified['pid'] = $parent_id;
                }
                // Check if deleted folder has been recreated
                if( $folder->deleted ){
                    //$modified['deleted'] = '0'; //defunct feature
                }

                if( count($modified) ){

                    $rs = $DB->update( K_TBL_FOLDERS, $modified, "id='" . $DB->sanitize( $folder->id ). "'" );
                    if( $rs==-1 ) die( "ERROR: Unable to save modified folder" );

                    // if folder hierarchy changed
                    if( $modified['pid'] ){
                        // remove folder from under the old parent
                        $PAGE->folders->find_and_remove( $folder->name );

                        // place it beneath the new parent
                        $parent = &$PAGE->folders->find( $parent_name );
                        if( $parent ){
                            $parent->add_child( $folder );
                        }
                        else{
                            $PAGE->folders->add_child( $folder );
                        }
                    }
                }

                $folder->processed = 1;

            }
            else{
                // Create a new record for this folder
                if( !$FUNCS->is_title_clean($name) ){
                    die( "ERROR: Name of folder contains invalid characters. (Only lowercase[a-z], numerals[0-9] hyphen and underscore permitted" );
                }
                $fields = array(
                               'template_id'=>$PAGE->tpl_id,
                               'pid'=>$parent_id,
                               'name'=>$attr['name'],
                               'title'=>$attr['title'],
                               'k_desc'=>$attr['desc'],
                               'weight'=>$attr['weight']
                              );
                $rs = $DB->insert( K_TBL_FOLDERS, $fields );
                if( $rs==-1 ) die( "ERROR: Unable to create folder" );
                $rs = $DB->select( K_TBL_FOLDERS, array('*'), "id='" . $DB->sanitize( $DB->last_insert_id ). "'" );
                if( !count($rs) ) die( "ERROR: Failed to insert record in K_TBL_FOLDERS" );

                // add folder to existing tree
                $folder = new KFolder( $rs[0], $PAGE->tpl_name, $PAGE->folders );
                $folder->processed = 1;
                if( $parent_id==-1 ){
                    $PAGE->folders->add_child( $folder ); //add to root
                }
                else{
                    $parent_folder = &$PAGE->folders->find( $parent_name );
                    if( $parent_folder ){
                        $parent_folder->add_child( $folder );
                    }
                }

            }

            $CTX->set( 'k_parent_folder_id', $folder->id );
            $CTX->set( 'k_parent_folder_name', $folder->name );

            // Process child folders
            foreach( $node->children as $child ){
                $child->get_HTML();
            }

        }

        function folders( $params, $node, $list=0 ){
            global $CTX, $FUNCS, $PAGE, $DB;

            $attr = $FUNCS->get_named_vars(
                        array( 'masterpage'=>'',
                               'root'=>'',
                               'childof'=>'',
                               'hierarchical'=>'0',
                               'depth'=>'0',
                               'orderby'=>'', /* name, title, id, count */
                               'order'=>'',
                               'exclude'=>'',
                               'show_count'=>'0',
                               'extended_info'=>'0',
                               'prompt'=>'', /* for dropdown */
                               'id'=>'', /* do */
                               'name'=>'', /* do */
                               'selected_id'=>'', /* do */

                               'paginate'=>0,
                               'limit'=>'',
                               'offset'=>'0',
                               'startcount'=>'',
                               'qs_param'=>'', /* custom var in querystring that denotes paginated page */
                               'base_link'=>'', /* replaces the default $PAGE->link used for paginator crumb links */
                            ),
                        $params);

            // HOOK: alter_folders_tag_params
            $FUNCS->dispatch_event( 'alter_folders_tag_params', array(&$attr, $params, $node, &$list) );

            extract( $attr );

            $masterpage = trim( $masterpage );
            $root = trim( $root );
            $childof = trim( $childof );
            $hierarchical = ( $hierarchical==1 ) ? 1 : 0;
            $depth = $FUNCS->is_natural( $depth ) ? intval( $depth ) : 0;
            $orderby = strtolower( trim($orderby) );
            if( !in_array($orderby, array('name', 'title', 'id', 'count', 'weight')) ) $orderby ='name';
            $order = strtolower($order);
            if( $order!='desc' && $order!='asc' ) $order = 'asc';
            $exclude = ( $exclude ) ? array_map( "trim", explode( ",", $exclude ) ) : array();
            $show_count = ( $show_count==1 ) ? 1 : 0;
            $extended_info = ( $extended_info==1 ) ? 1 : 0;
            if( $list ) $extended_info = 1;
            $prompt = trim( $prompt );
            if( !$prompt ) $prompt = '-- '.$FUNCS->t('select_folder').' --';
            $id = trim( $id );
            if( !$id ) $id = 'f_k_folders';
            $name = trim( $name );
            if( !$name ) $name = $id;
            $selected_id = trim( $selected_id );

            $paginate = ( $list ) ? 0 : ( ( $paginate==1 && $hierarchical ) ? 1 : 0 );
            if( $paginate ) $extended_info = 0;
            $limit = $FUNCS->is_non_zero_natural( $limit ) ? intval( $limit ) : 1000; //Practically unlimited.
            $offset = $FUNCS->is_natural( $offset ) ? intval( $offset ) : 0;
            $startcount = $FUNCS->is_int( $startcount ) ? intval( $startcount ) : 1;
            $startcount--;
            $qs_param = trim( $qs_param );
            if( $qs_param=='' ){ $qs_param = 'pg'; }
            $base_link = trim( $base_link );

            // see if masterpage exists
            if( $masterpage=='' ){
                $folders = &$PAGE->folders;
                if( $folders->cmp_field!=$orderby || $folders->cmp_order!=$order ){
                    $folders->set_sort( $orderby, $order );
                    $folders->sort( 1 );
                }
                $tpl_name = $PAGE->tpl_name;
            }
            else{
                $rs = $DB->select( K_TBL_TEMPLATES, array('*'), "name='" . $DB->sanitize( $masterpage ). "'" ); //SANITIZE!
                if( !count($rs) ) return;
                $tpl_id = $rs[0]['id'];
                $tpl_name = $rs[0]['name'];

                // get the folders
                $folders = &$FUNCS->get_folders_tree( $tpl_id, $tpl_name, $orderby, $order );
            }

            // root takes precedence over childof
            if( $root!='' ){
                $f = &$folders->find( $root );
                if( !$f ) return $this->_no_results( $node, $paginate );
                unset( $folders );
                $folders = new KFolder( array('id'=>'-1', 'name'=>'_root_', 'pid'=>'-1'), $tpl_name, new KError('dummy') );
                $folders->set_sort( $orderby, $order );
                $folders->add_child( $f );
                unset( $f );
            }
            elseif( $childof!='' ){
                $f = &$folders->find( $childof );
                if( !$f || !count($f->children) ) return $this->_no_results( $node, $paginate );
                unset( $folders );
                $folders = new KFolder( array('id'=>'-1', 'name'=>'_root_', 'pid'=>'-1'), $tpl_name, new KError('dummy') );
                $folders->set_sort( $orderby, $order );
                for( $x=0; $x<count($f->children); $x++ ){
                    $folders->add_child( $f->children[$x] );
                }
                unset( $f );
            }

            $CTX->set( 'k_total_folders', $folders->set_dynamic_count( $depth, $exclude ) );

            // pagination.. goes into normal 'pages' listing mode
            if( $paginate ){
                $pgn_pno = 1;

                if( isset($_GET[$qs_param]) && $FUNCS->is_non_zero_natural( $_GET[$qs_param] ) ){
                    $pgn_pno = (int)$_GET[$qs_param];
                }

                $total_rows = $folders->total_children_ex - $offset;
                if( $total_rows < 1 ){
                    return $this->_no_results( $node, $paginate );
                }

                $total_pages = ceil( $total_rows/$limit );
                if( $pgn_pno>$total_pages && $total_pages>0 ) $pgn_pno=$total_pages;

                $first_record_on_page = ($limit * ($pgn_pno - 1)) + 1 + $offset;
                $last_record_on_page = $first_record_on_page + $limit - 1;
                if( $last_record_on_page > ($total_rows + $offset) ) $last_record_on_page = $total_rows + $offset;
                $total_records_on_page = $last_record_on_page - ( $first_record_on_page-1 );

                // calculate the required links
                $page_link = ( strlen($base_link) ) ?  $base_link : K_SITE_URL . $PAGE->link;
                $page_link = $FUNCS->get_qs_link( $page_link, array($qs_param) );

                if( $total_rows > $limit ){
                    $paginated = 1;
                    $sep = ( strpos($page_link, '?')===false ) ? '?' : '&';

                    // 'Prev' link
                    if( $pgn_pno > 1 ){
                        if( $pgn_pno==2 ){
                            $pgn_prev_link = $page_link;
                        }
                        else{
                            $pgn_prev_link = sprintf( "%s%s%s=%d", $page_link, $sep, $qs_param, $pgn_pno-1 );
                        }
                    }
                    // 'Next' link
                    if( $pgn_pno < $total_pages ){
                        $pgn_next_link = sprintf( "%s%s%s=%d", $page_link, $sep, $qs_param, $pgn_pno+1 );
                    }
                }

                // pass data on to callback function to be set in context
                $node->_paginate = 1;
                $node->_total_records = $total_rows;
                $node->_from = $first_record_on_page;
                $node->_to = $last_record_on_page;
                $node->_total = $total_records_on_page;
                $node->_offset = $offset;
                $node->_startcount = $startcount;
                $node->_total_pages = $total_pages;
                $node->_current_page = $pgn_pno;
                $node->_paginate_limit = $limit;
                $node->_counter = 0;
                $node->_page_link = $page_link;
                $node->_qs_param = $qs_param;
                $node->_paginated = $paginated;
                $node->_pgn_next_link = $pgn_next_link;
                $node->_pgn_prev_link = $pgn_prev_link;
            }

            $html = '';
            if( $hierarchical ){
                $visitor = ( !$list ) ? '_folders_visitor' : (($list==1) ? '_folders_visitor_list' : '_folders_visitor_dropdown');
                $CTX->set( 'k_show_folder_count', $show_count );
                $CTX->set( 'k_show_extended_info', $extended_info );
                if( $selected_id && $list ) $node=$selected_id;
                $folders->visit( array($this, $visitor), $html, $node, $depth, $extended_info, $exclude, 0, 0, 0, $paginate );
                $CTX->set( 'k_show_extended_info', $extended_info );
                $CTX->set( 'k_show_folder_count', 0 );
            }
            else{
                $root = new KFolder( array('id'=>'-1', 'name'=>'_root_', 'pid'=>'-1'), $tpl_name, new KError('dummy') );
                $folders->visit( array($this, '_folders_visitor_flat'), $root, $node, $depth, 0 /*extended info */, $exclude );
                $root->set_sort( $orderby, $order );
                $root->sort( 1 );
                foreach( $root->children as $f ){
                    if( !$list ){
                        $CTX->set( 'k_folder', 1 );
                        $CTX->set( 'k_element_start', 1 );
                        $f->set_in_context();

                        foreach( $node->children as $child ){
                            $html .= $child->get_HTML();
                        }
                    }
                    elseif( $list==1 ){
                        $html .= '<li class="">';
                        $html .= '<a href="'. K_SITE_URL . $f->get_link().'">'.$f->title.'</a>';
                        if( $show_count ) $html .= ' (' . $f->count . ')';
                        $html .= '</li>';
                    }
                    elseif( $list==2 ){
                        if( $selected_id ){
                            $f_selected = ( $selected_id==$f->id ) ? ' SELECTED=1 ' : '';
                        }
                        else{
                            $f_selected = ((!$PAGE->is_master)&&($PAGE->page_folder_id==$f->id)) ? ' SELECTED=1 ' : '';
                        }
                        $html .= '<option class="folder-'.$f->name.'" value="'. $f->id .'" '.$f_selected.'>';
                        $html .= $f->title;
                        if( $show_count ) $html .= ' (' . $f->count . ')';
                        $html .= '</option>';
                    }
                }
                if( $list==1 ){
                    $html = '<ul class="" >' .$html . '</ul>';
                }
            }
            if( $list==2 ){
                $html = '<select id="'.$id.'" name="'.$name.'"><option value="-1" >'.$prompt.'</option>' .$html . '</select>';
            }

            return $html;

        }

        function listfolders( $params, $node ){
            if( count($node->children) ) {die("ERROR: Tag \"".$node->name."\" is a self closing tag");}
            return $this->folders( $params, $node, 1 );
        }

        function dropdownfolders( $params, $node ){
            if( count($node->children) ) {die("ERROR: Tag \"".$node->name."\" is a self closing tag");}
            return $this->folders( $params, $node, 2 );
        }

        // callback auxillary function
        function _folders_visitor( &$folder, &$html, &$node ){
            global $CTX;

            if( $node->_paginate ){
                $this->_set_pagination_context( $node );
            }

            foreach( $node->children as $child ){
                $html .= $child->get_HTML();
            }
        }

        function _set_pagination_context( &$node ){
            global $CTX;

            $cur = $node->_counter;

            // set pagination variables
            $x = $cur - ($node->_paginate_limit * ($node->_current_page -  1)) - $node->_offset;
            $CTX->set( 'k_count', $x + $node->_startcount );
            $CTX->set( 'k_record_from', ($node->_from - $node->_offset) + $node->_startcount );
            $CTX->set( 'k_current_record', ($cur - $node->_offset) + $node->_startcount );
            $CTX->set( 'k_record_to', ($node->_to - $node->_offset) + $node->_startcount );
            $CTX->set( 'k_total_pages', $node->_total_pages );

            // set vars for 'paginator tag'
            $CTX->set( 'k_current_page', $node->_current_page );
            $CTX->set( 'k_total_records', $node->_total_records );
            $CTX->set( 'k_total_records_for_pagination', $node->_total_records );
            $CTX->set( 'k_paginate_limit', $node->_paginate_limit );
            if( $x==1 ){
                $CTX->set( 'k_paginated_top', 1 );
            }
            else{
                $CTX->set( 'k_paginated_top', 0 );
            }

            if( $x==$node->_total ){
                $CTX->set( 'k_paginated_bottom', 1 );
            }
            else{
                $CTX->set( 'k_paginated_bottom', 0 );
            }

            if( ($x==1 || $x==$node->_total) && $node->_paginated ){
                $CTX->set( 'k_paginator_required', 1 );
                $CTX->set( 'k_page_being_paginated', $node->_page_link );
                $CTX->set( 'k_qs_param', $node->_qs_param );
                $CTX->set( 'k_paginate_link_next', $node->_pgn_next_link );
                $CTX->set( 'k_paginate_link_prev', $node->_pgn_prev_link );
            }
            else{
                $CTX->set( 'k_paginator_required', 0 );
                $CTX->set( 'k_paginate_link_next', '' );
                $CTX->set( 'k_paginate_link_prev', '' );
            }
        }

        // callback auxillary function for listfolders
        function _folders_visitor_list( &$folder, &$html, &$node ){
            global $CTX;
            $show_count = $CTX->get( 'k_show_folder_count', 1 );

            if( $CTX->get('k_level_start', 1) ){
                $class = ( $folder->id==-1 ) ? 'root' : 'children';
                $html .= '<ul class="'.$class.'">';
            }
            elseif( $CTX->get('k_element_start', 1) ){
                $class = 'folder-item folder-item-'.$folder->name;
                if( is_numeric($node) && $node==$folder->id ){ // explicit folder id provided
                    $class .= ' selected';
                }
                $html .= '<li class="'.$class.'">';
            }
            elseif( $CTX->get('k_folder', 1) ){
                $html .= '<a href="'. K_SITE_URL . $folder->get_link().'">'.$folder->title.'</a>';
                if( $show_count ) $html .= ' (' . $folder->count . ')';
            }
            elseif( $CTX->get('k_element_end', 1) ){
                $html .= '</li>';
            }
            elseif( $CTX->get('k_level_end', 1) ){
                $html .= '</ul>';
            }
        }

        // callback auxillary function for dropdownfolders
        function _folders_visitor_dropdown( &$folder, &$html, &$node ){
            global $CTX, $PAGE;
            $show_count = $CTX->get( 'k_show_folder_count', 1 );

            if( $CTX->get('k_element_start', 1) ){
                $level = $CTX->get('k_level', 1);
                $f_id = $CTX->get('k_folder_id', 1);
                $f_name = $CTX->get('k_folder_name', 1);
                $f_title = $CTX->get('k_folder_title', 1);

                if( is_numeric($node) ){ // explicit folder id provided
                    $f_selected = ($node==$f_id) ? ' SELECTED=1 ' : '';
                }
                else{
                    $f_selected = ((!$PAGE->is_master)&&($PAGE->page_folder_id==$f_id)) ? ' SELECTED=1 ' : '';
                }

                for( $x=0; $x<$level*4; $x++ ){
                    $pad .= '&nbsp;';
                }
                $html .= '<option class="level-'.$level.' folder-'.$f_name.'" value="'. $f_id .'" '.$f_selected.'>';
                $html .= $pad . $f_title;
                if( $show_count ) $html .= ' (' . $folder->count . ')';
                $html .= '</option>';
            }
        }

        // callback auxillary function
        function _folders_visitor_flat( &$folder, &$root, &$node ){
            $root->add_child( $folder );
        }

        function is_ancestor( $params, $node ){
            global $CTX, $FUNCS, $PAGE, $DB;
            if( count($node->children) ) {die("ERROR: Tag \"".$node->name."\" is a self closing tag");}

            extract( $FUNCS->get_named_vars(
                        array( 'masterpage'=>'',
                               'parent'=>'0',
                               'child'=>''
                              ),
                        $params)
                   );

            $parent = trim($parent);
            $child = trim($child);

            // see if masterpage exists
            if( $masterpage=='' ){
                $folders = &$PAGE->folders;
            }
            else{
                $rs = $DB->select( K_TBL_TEMPLATES, array('*'), "name='" . $DB->sanitize( $masterpage ). "'" ); //SANITIZE!
                if( !count($rs) ) return '0';
                $tpl_id = $rs[0]['id'];
                $tpl_name = $rs[0]['name'];

                // get the folders
                $folders = &$FUNCS->get_folders_tree( $tpl_id, $tpl_name );
            }

            // find parent
            $p = &$folders->find( $parent );
            if( !$p ) return '0';

            // if self?
            if( $p->name == $child ) return '1';
            if( !count($p->children) ) return '0';

            // find child
            $c = &$p->find( $child );
            if( !$c ) return '0';

            return '1';
        }

        function parentfolders( $params, $node ){
            global $CTX, $FUNCS, $PAGE, $DB;

            extract( $FUNCS->get_named_vars(
                        array(
                              'folder'=>'',
                              'masterpage'=>'',
                              'skip_child'=>'0',
                              ),
                        $params)
                   );

            $skip_child = ( $skip_child==1 ) ? 1 : 0;

            // see if masterpage exists
            if( $masterpage=='' ){
                $folders = &$PAGE->folders;
            }
            else{
                $rs = $DB->select( K_TBL_TEMPLATES, array('*'), "name='" . $DB->sanitize( $masterpage ). "'" );
                if( !count($rs) ) return;
                $tpl_id = $rs[0]['id'];
                $tpl_name = $rs[0]['name'];

                // get the folders
                $folders = &$FUNCS->get_folders_tree( $tpl_id, $tpl_name );
            }

            $arr = $folders->get_parents( $folder );
            if( is_array($arr) ){
                $level = 0;
                for( $x=count($arr)-1; $x>=$skip_child; $x-- ){
                    $arr[$x]->set_in_context();
                    $CTX->set( 'k_level', $level++ );

                    foreach( $node->children as $child ){
                        $html .= $child->get_HTML();
                    }
                }

            }

            return $html;
        }

        // Returns the parent hierarchy of the folder (if folder view) or the folder the page is in (if page view)
        // If include_template set, adds name of the template as first item.
        // If include_template, the template's name will appear even on non-folder-view and non-page-view pages.
        // The calling script will need to check that before invoking this tag.
        function breadcrumbs( $params, $node ){
            global $CTX, $FUNCS, $PAGE, $DB;
            if( count($node->children) ) {die("ERROR: Tag \"".$node->name."\" is a self closing tag");}

            //if( $PAGE->is_archive_view ) return;

            extract( $FUNCS->get_named_vars(
                        array(
                              'separator'=>'&nbsp;&raquo;&nbsp;',
                              /*'position'=>'0',*/ /* 0-before, 1-after*/
                              'include_template'=>'0'
                              ),
                        $params)
                   );

            $separator = trim( $separator );
            //$position = ( $position==1 ) ? 1 : 0;
            $position = 1;
            $include_template = ( $include_template==1 ) ? 1 : 0;
            $sep = ($position) ? '' : $separator;

            if( $include_template ){
                $root = ( $PAGE->tpl_title ) ? $PAGE->tpl_title : $PAGE->tpl_name;
                if( K_PRETTY_URLS ){
                    $breadcrumbs = $sep .'<a href="'. K_SITE_URL . $FUNCS->get_pretty_template_link( $PAGE->tpl_name ).'">'.$root.'</a>';
                }
                else{
                    $breadcrumbs = $sep .'<a href="'. K_SITE_URL . $PAGE->tpl_name.'">'.$root.'</a>';
                }
                $sep = $separator;
            }


            // Get the current folder to generate breadcrumbs for
            // Does so only for folder_view or when a page_view page resides in a folder
            $folder_id = ($PAGE->is_folder_view) ? $PAGE->folder_id :
                          (((!$PAGE->is_master)&&($PAGE->page_folder_id!=-1)) ? $PAGE->page_folder_id : '');

            if( $folder_id && $folder_id!=-1 ){
                $folders = &$PAGE->folders;
                $arr = $folders->get_parents_by_id( $folder_id );
                if( is_array($arr) ){
                    for( $x=count($arr)-1; $x>=0; $x-- ){
                        $breadcrumbs .= $sep . '<a href="'. K_SITE_URL . $arr[$x]->get_link().'">'.$arr[$x]->title.'</a>';
                        $sep = $separator;
                    }
                }
            }

            return $breadcrumbs;
        }

        // Enumerates nested_pages in an hierarchical manner.
        // Also used internally by 'menu' and 'nested_crumbs' tags.
        // Code is a modification of 'folders' tag
        function nested_pages( $params, $node, $variation=0 ){
            global $CTX, $FUNCS, $PAGE, $DB;

            $attr = $FUNCS->get_named_vars(
                        array( 'masterpage'=>'',
                               'root'=>'',
                               'childof'=>'',
                               'depth'=>'0',
                               'orderby'=>'', /* name, title, id, weight */
                               'order'=>'',
                               'exclude'=>'',
                               'extended_info'=>'0',
                               'ignore_show_in_menu'=>'0',
                               'include_custom_fields'=>'0',
                               'paginate'=>0,
                               'limit'=>'',
                               'offset'=>'0',
                               'startcount'=>'',
                               'qs_param'=>'', /* custom var in querystring that denotes paginated page */
                               'base_link'=>'', /* replaces the default $PAGE->link used for paginator crumb links */
                               'ignore_active_status'=>'0',
                              ),
                        $params);
            extract( $attr );

            $masterpage = trim( $masterpage );
            $root = trim( $root );
            $childof = trim( $childof );
            $hierarchical = 1; // always 1
            $depth = $FUNCS->is_natural( $depth ) ? intval( $depth ) : 0;
            $orderby = strtolower( trim($orderby) );
            if( !in_array($orderby, array('name', 'title', 'id', 'weight')) ) $orderby ='weight';
            if( $orderby =='weight' ) $orderby ='weightx';
            $order = strtolower( trim($order) );
            if( $order!='desc' && $order!='asc' ) $order = 'asc';
            $exclude = ( $exclude!='' ) ? array_map( "trim", explode( ",", $exclude ) ) : array();
            $extended_info = ( $extended_info==1 ) ? 1 : 0;
            if( $variation==1 ) $extended_info = 1; // always 1 with 'menu'
            $ignore_show_in_menu = ( $ignore_show_in_menu==1 ) ? 1 : 0;
            $ignore_active_status = ( $ignore_active_status==1 ) ? 1 : 0; // show unpublished pages or not
            $include_custom_fields = ( $variation ) ? 0 : ( ( $include_custom_fields==1 ) ? 1 : 0 );
            $paginate = ( $variation ) ? 0 : ( ( $paginate==1 ) ? 1 : 0 );
            if( $paginate ) $extended_info = 0;
            $limit = $FUNCS->is_non_zero_natural( $limit ) ? intval( $limit ) : 1000; //Practically unlimited.
            $offset = $FUNCS->is_natural( $offset ) ? intval( $offset ) : 0;
            $startcount = $FUNCS->is_int( $startcount ) ? intval( $startcount ) : 1;
            $startcount--;
            $qs_param = trim( $qs_param );
            if( $qs_param=='' ){ $qs_param = 'pg'; }
            $base_link = trim( $base_link );

            // see if masterpage exists
            if( $masterpage=='' ){
                if( !$PAGE->tpl_nested_pages ){ return; }
                $tree = &$FUNCS->get_nested_pages( $PAGE->tpl_id, $PAGE->tpl_name, $PAGE->tpl_access_level, $orderby, $order );
                $tpl_name = $PAGE->tpl_name;
            }
            else{
                $rs = $DB->select( K_TBL_TEMPLATES, array('*'), "name='" . $DB->sanitize( $masterpage ). "'" );
                if( !count($rs) ) return;
                if( !$rs[0]['nested_pages'] ){ return; }
                $tpl_id = $rs[0]['id'];
                $tpl_name = $rs[0]['name'];
                $tpl_access_level = $rs[0]['access_level'];

                // get the tree of nested pages
                $tree = &$FUNCS->get_nested_pages( $tpl_id, $tpl_name, $tpl_access_level, $orderby, $order );
            }

            // mark the active trail in the tree.. also creates the crumbs.
            $tree->mark_current( !$ignore_show_in_menu );

            // if called from 'nested_crumbs', return crumbs and exit ..
            if( $variation==2 ){ // crumbs.. only $masterpage and $ignore_show_in_menu are the valid params here
                return $tree->crumbs;
            }

            // Check if either 'root' or 'childof' set to special keywords
            // keywords:
            // @1, @2 etc. - start from x level parent of the most current item.
            // @current - start from most current item.
            // @current-1, @current-2 etc. - start from parent of most current item at x level above it.
            if( $root || $childof ){ // root takes precedence over childof
                $special = ( $root ) ? $root : $childof;
                if( $special[0]=='@' ){
                    $special = substr( $special, 1 );

                    // find the most current item
                    for( $x=0; $x<count($tree->crumbs); $x++ ){
                        $item = $tree->crumbs[$x];
                        if( $item->most_current ) break;
                    }
                    if( $x==count($tree->crumbs) ) return;

                    if( $FUNCS->is_non_zero_natural($special) ){ // 1, 2, etc.
                        $special = $special - 1;

                        // Find the requested parent at the requested level, if any
                        if( $special > $x ) return; // cannot go past the current item's level
                        $item = $tree->crumbs[$special];
                        ( $root ) ? $root=$item->name : $childof=$item->name;
                    }
                    else{
                        if( $special=='current' ){
                            ( $root ) ? $root=$item->name : $childof=$item->name;
                        }
                        else{
                            $arr = array_map( "trim", explode('-', $special) );
                            if( $arr[0]=='current' && $FUNCS->is_non_zero_natural($arr[1]) ){
                                // Find parent at requested level above the current item
                                $special = $x - $arr[1];
                                if( $special < -1 ) return;
                                if( $special== -1 ){ // _ROOT_
                                    if( $root ){ return; /* cannot add _ROOT_ to returned array */ } else { $childof=''; }
                                }
                                else{
                                    $item = $tree->crumbs[$special];
                                    ( $root ) ? $root=$item->name : $childof=$item->name;
                                }
                            }
                            else{
                                // unknown value given
                                return;
                            }

                        }
                    }
                }
            }

            // root takes precedence over childof
            if( $root!='' ){
                $f = &$tree->find( $root );
                if( !$f ) return $this->_no_results( $node, $paginate );
                unset( $tree );
                $tree = new KNestedPage( array('id'=>'-1', 'name'=>'_root_', 'pid'=>'-1'), $tpl_name, new KError()/*dummy*/ );
                $tree->set_sort( $orderby, $order );
                $tree->add_child( $f );
                unset( $f );
            }
            elseif( $childof!='' ){
                $f = &$tree->find( $childof );
                if( !$f || !count($f->children) ) return $this->_no_results( $node, $paginate );
                unset( $tree );
                $tree = new KNestedPage( array('id'=>'-1', 'name'=>'_root_', 'pid'=>'-1'), $tpl_name, new KError()/*dummy*/ );
                $tree->set_sort( $orderby, $order );
                $count = count($f->children);
                for( $x=0; $x<$count; $x++ ){
                    $tree->add_child( $f->children[$x] );
                }
                unset( $f );
            }

            $tree->set_dynamic_count( $depth, $exclude, !$ignore_show_in_menu, $ignore_active_status );

            // pagination.. goes into normal 'pages' listing mode
            if( $paginate ){
                $pgn_pno = 1;
                if( $paginate ){
                    if( isset($_GET[$qs_param]) && $FUNCS->is_non_zero_natural( $_GET[$qs_param] ) ){
                        $pgn_pno = (int)$_GET[$qs_param];
                    }
                }

                $total_rows = $tree->total_children_ex - $offset;
                if( $total_rows < 1 ){
                    return $this->_no_results( $node, $paginate );
                }

                $total_pages = ceil( $total_rows/$limit );
                if( $pgn_pno>$total_pages && $total_pages>0 ) $pgn_pno=$total_pages;

                $first_record_on_page = ($limit * ($pgn_pno - 1)) + 1 + $offset;
                $last_record_on_page = $first_record_on_page + $limit - 1;
                if( $last_record_on_page > ($total_rows + $offset) ) $last_record_on_page = $total_rows + $offset;
                $total_records_on_page = $last_record_on_page - ( $first_record_on_page-1 );

                // calculate the required links
                $page_link = ( strlen($base_link) ) ?  $base_link : K_SITE_URL . $PAGE->link;
                $page_link = $FUNCS->get_qs_link( $page_link, array($qs_param) );

                if( $total_rows > $limit ){
                    $paginated = 1;
                    $sep = ( strpos($page_link, '?')===false ) ? '?' : '&';

                    // 'Prev' link
                    if( $pgn_pno > 1 ){
                        if( $pgn_pno==2 ){
                            $pgn_prev_link = $page_link;
                        }
                        else{
                            $pgn_prev_link = sprintf( "%s%s%s=%d", $page_link, $sep, $qs_param, $pgn_pno-1 );
                        }
                    }
                    // 'Next' link
                    if( $pgn_pno < $total_pages ){
                        $pgn_next_link = sprintf( "%s%s%s=%d", $page_link, $sep, $qs_param, $pgn_pno+1 );
                    }
                }

                // pass data on to callback function to be set in context
                $node->_paginate = 1;
                $node->_total_records = $total_rows;
                $node->_from = $first_record_on_page;
                $node->_to = $last_record_on_page;
                $node->_total = $total_records_on_page;
                $node->_offset = $offset;
                $node->_startcount = $startcount;
                $node->_total_pages = $total_pages;
                $node->_current_page = $pgn_pno;
                $node->_paginate_limit = $limit;
                $node->_counter = 0;
                $node->_page_link = $page_link;
                $node->_qs_param = $qs_param;
                $node->_paginated = $paginated;
                $node->_pgn_next_link = $pgn_next_link;
                $node->_pgn_prev_link = $pgn_prev_link;
            }

            $html = '';
            if( isset($node->__visitor__) ){
                $visitor = $node->__visitor__;
            }
            else{
                $visitor = ( !$variation ) ? '_nested_pages_visitor' : '_nested_pages_visitor_menu';
                $visitor = array($this, $visitor);
            }
            $CTX->set( 'k_show_extended_info', $extended_info );
            if( !$variation ){
                $node->_include_custom_fields = $include_custom_fields;
            }
            $tree->visit( $visitor, $html, $node, $depth, $extended_info, $exclude, 0, !$ignore_show_in_menu, !$ignore_active_status, $paginate );

            return $html;

        }

        // callback auxillary function
        function _nested_pages_visitor( &$item, &$html, &$node ){
            global $CTX;

            if( $node->_paginate ){
                $this->_set_pagination_context( $node );
            }

            $extended_info = $CTX->get( 'k_show_extended_info', 1 );
            //if( !$extended_info ) $item->set_in_context(); //if extended_info set, the calling 'visit' function will have set the item in context.

            if( $node->_include_custom_fields ){
                $ok = ( $extended_info ) ? $CTX->get('k_element_start', 1) : $CTX->get('k_folder', 1);
                if( $ok ){
                    $pg = new KWebpage( $item->template_id, $item->id );
                    if( $pg->error ){
                        ob_end_clean();
                        die( 'ERROR: ' . $pg->err_msg );
                    }
                    $pg->set_context();
                }
            }

            foreach( $node->children as $child ){
                $html .= $child->get_HTML();
            }
        }

        // A wrapper around 'nested_pages' for quick creation of menu
        function menu( $params, $node, $variation=0 ){
            global $CTX, $FUNCS, $PAGE, $DB;
            if( count($node->children) ) {die("ERROR: Tag \"".$node->name."\" is a self closing tag");}

            $attr = $FUNCS->get_named_vars(
                        array( 'menu_class'=>'',
                               'menu_id'=>'',
                               'first_class'=>'first',
                               'last_class'=>'last',
                               'no_selected'=>'0', /* 0 or 1 */
                               'selected_class'=>'current',
                               'no_active_trail'=>'0', /* 0 or 1 */
                               'active_trail_class'=>'active',
                               'list_type'=>'ul',
                              ),
                        $params);
            extract( $attr );

            $extra = array();
            $extra['menu_class'] = trim( $menu_class );
            $extra['menu_id'] = trim( $menu_id );
            $extra['first_class'] = trim( $first_class );
            $extra['last_class'] = trim( $last_class );
            $extra['no_selected'] = ( $no_selected==1 ) ? 1 : 0;
            $extra['selected_class'] = trim( $selected_class );
            $extra['no_active_trail'] = ( $no_active_trail==1 ) ? 1 : 0;
            $extra['active_trail_class'] = trim( $active_trail_class );
            $list_type = trim( strtolower($list_type) );
            if( $list_type!='ol' && $list_type!='ul' ) $list_type = 'ul';
            $extra['list_type'] = $list_type;
            $node->extra = $extra;

            if( $node->name=='menu' ){
                // delegate to 'nested_pages'
                $html = $this->nested_pages( $params, $node, 1 );
            }
            else{
                // delegate to 'admin_menuitems'
                $html = $this->admin_menuitems( $params, $node, 1 );
            }

            return $html;

        }

        // callback auxillary function
        function _nested_pages_visitor_menu( &$item, &$html, &$node ){
            global $CTX;

            // retrieve the extra info passed
            $extra = $node->extra;


            if( $CTX->get('k_level_start', 1) ){
                $level = $CTX->get('k_level', 1);
                if( $level==0 ){
                    if( $extra['menu_class'] ) $class .= $extra['menu_class'] . ' ';
                    if( $extra['menu_id'] ) $id = 'id="' . $extra['menu_id'] . '" ';
                }
                $class .= 'level-' . $level;
                $html .= '<'.$extra['list_type'].' ' . $id .'class="'.$class.'">';
            }
            elseif( $CTX->get('k_element_start', 1) ){
                $id = 'item-' . $item->name;
                $class .= 'level-' . $CTX->get('k_level', 1) . ' ';
                if( $item->total_children_ex ) $class .= 'has-submenu ';
                if( $item->first_pos ) $class .= $extra['first_class'] . ' ';
                if( $item->last_pos ) $class .= $extra['last_class'] . ' ';
                if( !$extra['no_active_trail'] && $item->is_current ) $class .= $extra['active_trail_class'] . ' ';
                if( !$extra['no_selected'] && $item->most_current ) $class .= $extra['selected_class'] . ' ';
                $html .= '<li id="'.$id.'" class="'.$class.'">';

                $title = trim( $item->menu_text );
                if( !$title ) $title = $item->title;
                $link = ( $item->is_pointer && !$item->masquerades ) ? $item->pointer_link : K_SITE_URL.$item->get_link();
                $link = ( $link ) ? ' href="' . $link . '"' : ' href="#" onClick="return false"';
                if( $item->open_external ) $target = ' target="_blank"';
                $html .= '<a' . $link . $target . '>' . $title . '</a>';
            }
            elseif( $CTX->get('k_element_end', 1) ){
                $html .= '</li>'."\r\n";
            }
            elseif( $CTX->get('k_level_end', 1) ){
                $html .= '</'.$extra['list_type'].'>';
            }
        }

        function parent_nested_pages( $params, $node ){
            global $CTX, $FUNCS, $PAGE, $DB;

            extract( $FUNCS->get_named_vars(
                        array(
                              'page_name'=>'',
                              'masterpage'=>'',
                              'orderby'=>'', /* name, title, id, weight */
                              'order'=>'',
                              'include_custom_fields'=>'0',
                              'skip_child'=>'1',
                              ),
                        $params)
                   );

            $page_name = trim( $page_name );
            if( $page_name=='' ) return;

            $orderby = strtolower( trim($orderby) );
            if( !in_array($orderby, array('name', 'title', 'id', 'weight')) ) $orderby ='weight';
            if( $orderby =='weight' ) $orderby ='weightx';
            $order = strtolower( trim($order) );
            if( $order!='desc' && $order!='asc' ) $order = 'asc';
            $include_custom_fields = ( $include_custom_fields==1 ) ? 1 : 0;
            $skip_child = ( $skip_child==0 ) ? 0 : 1;

            // see if masterpage exists
            $masterpage = trim( $masterpage );
            if( $masterpage=='' ){
                if( !$PAGE->tpl_nested_pages ){ return; }
                $tree = &$FUNCS->get_nested_pages( $PAGE->tpl_id, $PAGE->tpl_name, $PAGE->tpl_access_level, $orderby, $order );
            }
            else{
                $rs = $DB->select( K_TBL_TEMPLATES, array('*'), "name='" . $DB->sanitize( $masterpage ). "'" );
                if( !count($rs) ) return;
                if( !$rs[0]['nested_pages'] ){ return; }
                $tpl_id = $rs[0]['id'];
                $tpl_name = $rs[0]['name'];
                $tpl_access_level = $rs[0]['access_level'];

                // get the tree of nested pages
                $tree = &$FUNCS->get_nested_pages( $tpl_id, $tpl_name, $tpl_access_level, $orderby, $order );
            }

            $arr = $tree->get_parents( $page_name );
            if( is_array($arr) ){
                $level = 0;
                for( $x=count($arr)-1; $x>=$skip_child; $x-- ){
                    $arr[$x]->set_in_context();
                    $CTX->set( 'k_level', $level++ );

                    if( $include_custom_fields ){
                        $pg = new KWebpage( $arr[$x]->template_id, $arr[$x]->id );
                        if( $pg->error ){
                            ob_end_clean();
                            die( 'ERROR: ' . $pg->err_msg );
                        }
                        $pg->set_context();
                    }

                    foreach( $node->children as $child ){
                        $html .= $child->get_HTML();
                    }
                }
            }

            return $html;
        }

        function nested_crumbs( $params, $node ){
            global $CTX, $FUNCS;

            $attr = $FUNCS->get_named_vars(
                        array(
                                'prepend'=>'',
                                'append'=>''
                              ),
                        $params);
            extract( $attr );

            // delegate to 'nested_pages'
            $crumbs = $this->nested_pages( $params, $node, 2 );
            if( is_array($crumbs) ){
                if( count($node->children) ){
                    $arr_vars = array();

                    if( count($crumbs) ) $html .= $prepend;
                    for( $x=0; $x<count($crumbs); $x++ ){
                        $crumb = $crumbs[$x];
                        $class = strtolower( get_class($crumb) );
                        if( $class=='knestedpage' ){
                            $title = trim( $crumb->menu_text );
                            if( !$title ) $title = $crumb->title;
                            $arr_vars['k_crumb_text'] = $title;
                            $arr_vars['k_crumb_link'] = ( $crumb->is_pointer && !$crumb->masquerades ) ? $crumb->pointer_link : K_SITE_URL.$crumb->get_link();
                            $arr_vars['k_crumb_is_nested_page'] = 1;
                            $arr_vars['k_crumb_is_folder'] = 0;
                            $arr_vars['k_crumb_open_external'] = $crumb->open_external;
                        }
                        elseif( $class=='kfolder' ){
                            $arr_vars['k_crumb_text'] = $crumb->title;
                            $arr_vars['k_crumb_link'] = K_SITE_URL.$crumb->get_link();
                            $arr_vars['k_crumb_is_nested_page'] = 0;
                            $arr_vars['k_crumb_is_folder'] = 1;
                            $arr_vars['k_crumb_open_external'] = 0;
                        }
                        $arr_vars['k_crumb_id'] = $crumb->id;
                        $arr_vars['k_crumb_name'] = $crumb->name;
                        $arr_vars['k_crumb_is_last'] = ( $x==count($crumbs)-1 ) ? 1 : 0;

                        $CTX->set_all( $arr_vars );

                        foreach( $node->children as $child ){
                            $html .= $child->get_HTML();
                        }
                    }
                    if( count($crumbs) ) $html .= $append;
                }
                else{ // if used as self-closing tag, create the complete list
                    $sep = '';
                    foreach( $crumbs as $crumb ){
                        $class = strtolower( get_class($crumb) );
                        if( $class=='knestedpage' ){
                            $title = trim( $crumb->menu_text );
                            if( !$title ) $title = $crumb->title;
                            $link = ( $crumb->is_pointer && !$crumb->masquerades ) ? $crumb->pointer_link : K_SITE_URL.$crumb->get_link();
                            $link = ( $link ) ? ' href="' . $link . '"' : ' href="#" onClick="return false"';
                            $html .= $sep . '<a'.$link.'>'. $title . '</a>';
                            $sep = ' &raquo; ';
                        }
                        elseif( $class=='kfolder' ){
                            $html .= $sep . '<a href="'. K_SITE_URL.$crumb->get_link() .'">'. $crumb->title . '</a>';
                            $sep = ' &raquo; ';
                        }

                    }
                }
            }
            return $html;
        }

        function admin_menuitems( $params, $node, $variation=0 ){
            global $CTX, $FUNCS, $PAGE, $DB;

            $attr = $FUNCS->get_named_vars(
                        array( 'root'=>'',
                               'childof'=>'',
                               'depth'=>'0',
                               'orderby'=>'', /* name, title, id, weight */
                               'order'=>'',
                               'exclude'=>'',
                               'extended_info'=>'0',
                               'ignore_show_in_menu'=>'0',
                              ),
                        $params);
            extract( $attr );

            $root = trim( $root );
            $childof = trim( $childof );
            $hierarchical = 1; // always 1
            $depth = $FUNCS->is_natural( $depth ) ? intval( $depth ) : 0;
            $orderby = strtolower( trim($orderby) );
            if( !in_array($orderby, array('name', 'title', 'id', 'weight')) ) $orderby ='weight';
            $order = strtolower( trim($order) );
            if( $order!='desc' && $order!='asc' ) $order = 'asc';
            $exclude = ( $exclude ) ? array_map( "trim", explode( ",", $exclude ) ) : array();
            $extended_info = ( $extended_info==1 ) ? 1 : 0;
            if( $variation==1 ) $extended_info = 1;
            $ignore_show_in_menu = ( $ignore_show_in_menu==1 ) ? 1 : 0;
            $ignore_active_status = 0; // unpublished pages will always be ignored
            $paginate = 0;

            // get the tree of nested pages
            $func = ( isset($node->__func_get_tree__) ) ? $node->__func_get_tree__ : 'get_admin_menu';
            $tree = &$FUNCS->$func( $orderby, $order );

            // mark the active trail in the tree.. also creates the crumbs.
            $tree->mark_current( !$ignore_show_in_menu );

            // if called from 'admin_breadcrumbs', return crumbs and exit ..
            if( $variation==2 ){ // crumbs.. only $ignore_show_in_menu is the valid param here
                return $tree->crumbs;
            }

            // Check if either 'root' or 'childof' set to special keywords
            // keywords:
            // @1, @2 etc. - start from x level parent of the most current item.
            // @current - start from most current item.
            // @current-1, @current-2 etc. - start from parent of most current item at x level above it.
            if( $root || $childof ){ // root takes precedence over childof
                $special = ( $root ) ? $root : $childof;
                if( $special[0]=='@' ){
                    $special = substr( $special, 1 );

                    // find the most current item
                    for( $x=0; $x<count($tree->crumbs); $x++ ){
                        $item = $tree->crumbs[$x];
                        if( $item->most_current ) break;
                    }
                    if( $x==count($tree->crumbs) ) return;

                    if( $FUNCS->is_non_zero_natural($special) ){ // 1, 2, etc.
                        $special = $special - 1;

                        // Find the requested parent at the requested level, if any
                        if( $special > $x ) return; // cannot go past the current item's level
                        $item = $tree->crumbs[$special];
                        ( $root ) ? $root=$item->name : $childof=$item->name;
                    }
                    else{
                        if( $special=='current' ){
                            ( $root ) ? $root=$item->name : $childof=$item->name;
                        }
                        else{
                            $arr = array_map( "trim", explode('-', $special) );
                            if( $arr[0]=='current' && $FUNCS->is_non_zero_natural($arr[1]) ){
                                // Find parent at requested level above the current item
                                $special = $x - $arr[1];
                                if( $special < -1 ) return;
                                if( $special== -1 ){ // _ROOT_
                                    if( $root ){ return; /* cannot add _ROOT_ to returned array */ } else { $childof=''; }
                                }
                                else{
                                    $item = $tree->crumbs[$special];
                                    ( $root ) ? $root=$item->name : $childof=$item->name;
                                }
                            }
                            else{
                                // unknown value given
                                return;
                            }

                        }
                    }
                }
            }

            // root takes precedence over childof
            if( $root!='' ){
                $f = &$tree->find( $root );
                if( !$f ) return;
                unset( $tree );
                $tree = new KAdminMenuItem( array('id'=>'-1', 'name'=>'_root_'), new KError()/*dummy*/ );
                $tree->set_sort( $orderby, $order );
                $tree->add_child( $f );
                unset( $f );
            }
            elseif( $childof!='' ){
                $f = &$tree->find( $childof );
                if( !$f || !count($f->children) ) return;
                unset( $tree );
                $tree = new KAdminMenuItem( array('id'=>'-1', 'name'=>'_root_'), new KError()/*dummy*/ );
                $tree->set_sort( $orderby, $order );
                $count = count($f->children);
                for( $x=0; $x<$count; $x++ ){
                    $tree->add_child( $f->children[$x] );
                }
                unset( $f );
            }

            $tree->set_dynamic_count( $depth, $exclude, !$ignore_show_in_menu );

            $html = '';
            $CTX->set( 'k_show_extended_info', $extended_info );
            if( $node->name=='admin_menuitems' && $extended_info ) $node->__optimize = 1;

            $visitor = ( isset($node->__visitor__) ) ? $node->__visitor__ : array($this, '_admin_menuitems_visitor');
            $tree->visit( $visitor, $html, $node, $depth, $extended_info, $exclude, 0, !$ignore_show_in_menu, !$ignore_active_status, $paginate, array($this, '_skip_menu') );

            return $html;

        }

        // A wrapper around 'admin_menuitems' for quick creation of menu
        function admin_menu( $params, $node ){
            global $FUNCS;

            extract( $FUNCS->get_named_vars(
                array(
                      'callback'=>null,
                     ),
                $params) );

            if( !is_null($callback) ){
                if( !($callback=$FUNCS->is_callable($callback)) ){
                    die( "ERROR: Tag \"".$node->name."\" - 'callback' is not callable" );
                }
            }

            // delegate to cms:menu which will delegate to cms:admin_menuitems
            $node->__visitor__ = is_null( $callback ) ? array( $this, '_admin_menu_visitor' ) : $callback;
            $html = $this->menu( $params, $node );

            return $html;
        }

        // callback auxillary function
        function _admin_menuitems_visitor( &$item, &$html, &$node ){
            global $CTX;

            if( $node->__optimize ){ // only when called by cms:admin_menuitems tag with extended_info
                $block = null;

                // isolate relevant blocks
                if( !is_array($node->__optimize) ){
                    $arr_keys = array( 'k_level_start', 'k_element_start', 'k_element_end', 'k_level_end' );
                    $arr_blocks = array();
                    foreach( $node->children as $block ){
                        if( $block->name == 'if' && ($block->attributes[0]['value_type']==K_VAL_TYPE_VARIABLE && in_array($block->attributes[0]['value'], $arr_keys)) ){
                            $arr_blocks[$block->attributes[0]['value']] = $block;
                        }
                    }
                    $node->__optimize = $arr_blocks;
                }

                // select the right block to execute
                if( $CTX->get('k_level_start', 1) ){
                    if( isset($node->__optimize['k_level_start']) ){
                        $block = $node->__optimize['k_level_start'];
                    }
                }
                elseif( $CTX->get('k_element_start', 1) ){
                    if( isset($node->__optimize['k_element_start']) ){
                        $block = $node->__optimize['k_element_start'];
                    }
                }
                elseif( $CTX->get('k_element_end', 1) ){
                    if( isset($node->__optimize['k_element_end']) ){
                        $block = $node->__optimize['k_element_end'];
                    }
                    else{
                        $html .= '</li>'."\r\n";
                        return;
                    }
                }
                elseif( $CTX->get('k_level_end', 1) ){
                    if( isset($node->__optimize['k_level_end']) ){
                        $block = $node->__optimize['k_level_end'];
                    }
                    else{
                        $html .= '</'.$extra['list_type'].'>';
                        return;
                    }
                }

                if( !is_null($block) ){
                    $html .= $block->get_HTML();
                }
            }
            else{
                foreach( $node->children as $child ){
                    $html .= $child->get_HTML();
                }
            }
        }

        // callback auxillary function
        function _admin_menu_visitor( &$item, &$html, &$node ){
            global $CTX, $FUNCS;

            // retrieve the extra info passed
            $extra = $node->extra;

            if( $CTX->get('k_level_start', 1) ){
                $html .= $FUNCS->render( 'menu_ul', $item, $extra );
            }
            elseif( $CTX->get('k_element_start', 1) ){
                $html .= $FUNCS->render( 'menu_li', $item, $extra );
            }
            elseif( $CTX->get('k_element_end', 1) ){
                $html .= '</li>'."\r\n";
            }
            elseif( $CTX->get('k_level_end', 1) ){
                $html .= '</'.$extra['list_type'].'>';
            }
        }

        // callback auxillary function
        function _skip_menu( &$item ){
            global $FUNCS;

            if( !is_null($item->access_callback) ){
                $callback = $FUNCS->is_callable( $item->access_callback );
                if( !$callback ){
                    ob_end_clean(); die( "ERROR: skip_menu callback function for menu-item '".$item->name."' is not callable." );
                }
                return !call_user_func_array( $callback, array($item, $item->access_callback_params) );
            }
        }

        function admin_breadcrumbs( $params, $node ){
            global $CTX, $FUNCS;

            $attr = $FUNCS->get_named_vars(
                        array(
                                'prepend'=>'',
                                'append'=>''
                              ),
                        $params);
            extract( $attr );

            // delegate to 'admin_menu'
            $crumbs = $this->admin_menuitems( $params, $node, 2 );
            if( is_array($crumbs) ){
                if( count($node->children) ){
                    $arr_vars = array();

                    if( count($crumbs) ) $html .= $prepend;
                    for( $x=0; $x<count($crumbs); $x++ ){
                        $crumb = $crumbs[$x];

                        $arr_vars['k_crumb_id'] = $crumb->id;
                        $arr_vars['k_crumb_name'] = $crumb->name;
                        $arr_vars['k_crumb_title'] = $arr_vars['k_crumb_text'] = $crumb->title;
                        $arr_vars['k_crumb_link'] = $crumb->get_link();
                        $arr_vars['k_crumb_open_external'] = ( $crumb->href )? '1' : '0';
                        $arr_vars['k_crumb_is_last'] = ( $x==count($crumbs)-1 ) ? 1 : 0;

                        $CTX->set_all( $arr_vars );

                        foreach( $node->children as $child ){
                            $html .= $child->get_HTML();
                        }
                    }
                    if( count($crumbs) ) $html .= $append;
                }
            }
            return $html;
        }

        function admin_sub_menuitems( $params, $node ){
            global $CTX, $FUNCS;

            // delegate to 'admin_menu'
            $node->__func_get_tree__ = 'get_admin_submenu';
            $html = $this->admin_menuitems( $params, $node );

            return $html;
        }

        function admin_actions( $params, $node ){
            global $CTX, $FUNCS;

            // delegate to 'admin_menu'
            $node->__func_get_tree__ = 'get_admin_actions';
            $html = $this->admin_menuitems( $params, $node );

            return $html;
        }

        function admin_form_fields( $params, $node ){
            global $CTX, $FUNCS;

            // delegate to 'admin_menu'
            $node->__func_get_tree__ = 'get_admin_form_fields';
            $html = $this->admin_menuitems( $params, $node );

            return $html;
        }

        function admin_list_fields( $params, $node ){
            global $CTX, $FUNCS;

            extract( $FUNCS->get_named_vars(
                    array( 'startcount'=>'',
                           'headers'=>'0',
                          ),
                $params)
            );
            $startcount = $FUNCS->is_int( $startcount ) ? intval( $startcount ) : 1;
            $headers = ( $headers==1 ) ? 1 : 0;
            if( $headers ){
                $order = ( $CTX->get('k_selected_order')=='desc') ? 'desc' : 'asc';
                $filter_link = $FUNCS->get_qs_link($CTX->get('k_route_link'), array('order','orderby'));
            }

            $items = &$FUNCS->get_admin_list_fields();
            $total_items = count($items);
            $arr_vars = array();

            for( $x=0; $x<$total_items; $x++ ){
                $item = $items[$x];

                $CTX->reset();
                $arr_vars['k_field_name']           = $item['name'];
                $arr_vars['k_field_class']          = strlen( $item['class'] ) ? $item['class'] : $item['name'];
                if( $headers ){
                    $arr_vars['k_field_sortable']       = $item['sortable'];
                    $arr_vars['k_field_sortname']       = $item['sort_name'];
                    $arr_vars['k_field_is_current']     = ( $item['is_current'] ) ? '1' : '0';
                    if( $item['is_current'] ){
                        $dir = ( $order=='desc' )? 'asc' : 'desc';
                    }
                    else{
                        $dir = $order;
                    }
                    $arr_vars['k_filter_link'] = $filter_link .'&orderby='.$item['sort_name'].'&order='.$dir;
                    $arr_vars['k_field_content'] = '';
                    $content_field = 'header';
                }
                else{
                    $arr_vars['k_field_header'] = '';
                    $content_field = 'content';
                }

                $arr_vars['k_field_is_last'] = ( $x==$total_items-1 ) ? 1 : 0;
                $arr_vars['k_field_count'] = $x + $startcount;
                $arr_vars['k_total_fields'] = $total_items;

                $CTX->set_all( $arr_vars );

                //$arr_vars['k_field_header'] = $item['header'];
                //$arr_vars['k_field_content'] = $item['content'];
                $arr = $item[$content_field];
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
                $CTX->set( 'k_field_'.$content_field, $content );

                // and call the children providing each row's data
                foreach( $node->children as $child ){
                    $html .= $child->get_HTML();
                }
            }

            return $html;
        }

        function admin_load_js( $params, $node ){
            global $FUNCS, $DB, $PAGE, $CTX;
            if( count($node->children) ) {die("ERROR: Tag \"".$node->name."\" is a self closing tag");}

            extract( $FUNCS->get_named_vars(
                        array(
                               'path'=>'',
                              ),
                        $params)
                   );

            $FUNCS->load_js( $path );
        }

        function admin_add_js( $params, $node ){
            global $FUNCS;

            foreach( $node->children as $child ){
                $code .= $child->get_HTML();
            }

            $FUNCS->add_js( $code );
        }

        function admin_load_css( $params, $node ){
            global $FUNCS, $DB, $PAGE, $CTX;
            if( count($node->children) ) {die("ERROR: Tag \"".$node->name."\" is a self closing tag");}

            extract( $FUNCS->get_named_vars(
                        array(
                               'path'=>'',
                              ),
                        $params)
                   );

            $FUNCS->load_css( $path );
        }

        function admin_add_css( $params, $node ){
            global $FUNCS;

            foreach( $node->children as $child ){
                $code .= $child->get_HTML();
            }

            $FUNCS->add_css( $code );
        }

        function admin_add_html( $params, $node ){
            global $FUNCS;

            foreach( $node->children as $child ){
                $code .= $child->get_HTML();
            }

            $FUNCS->add_html( $code );
        }

        function admin_add_meta( $params, $node ){
            global $FUNCS;

            foreach( $node->children as $child ){
                $code .= $child->get_HTML();
            }

            $FUNCS->add_meta( $code );
        }

        function admin_js( $params, $node ){
            global $FUNCS;

            return $FUNCS->get_js();
        }

        function admin_js_files( $params, $node ){
            global $FUNCS, $CTX;

            $html = '';
            foreach( $FUNCS->scripts as $k=>$v ){
                $CTX->set( 'k_js_file', $v );
                foreach( $node->children as $child ){
                    $html .= $child->get_HTML();
                }
            }

            return $html;
        }

        function admin_css_files( $params, $node ){
            global $FUNCS, $CTX;

            // load current theme's css file, if present
            static $done=0;
            if( !$done ){
                if( K_THEME_DIR && file_exists(K_THEME_DIR . 'styles.css') ){
                    $FUNCS->load_css( K_THEME_URL . 'styles.css' );
                }
                $done=1;
            }

            $html = '';
            foreach( $FUNCS->styles as $k=>$v ){
                $CTX->set( 'k_css_file', $v );
                foreach( $node->children as $child ){
                    $html .= $child->get_HTML();
                }
            }

            return $html;
        }

        function admin_css( $params, $node ){
            global $FUNCS;

            return $FUNCS->get_css();
        }

        function admin_html( $params, $node ){
            global $FUNCS;

            return $FUNCS->get_html();
        }

        function admin_meta( $params, $node ){
            global $FUNCS;

            return $FUNCS->get_meta();
        }

        function admin_route_link( $params, $node ){
            global $FUNCS;

            if( count($node->children) ) {die("ERROR: Tag \"".$node->name."\" is a self closing tag");}

            $attr = $FUNCS->get_named_vars(
                array(
                    'name'=>'',
                    'masterpage'=>'',
                ),
                $params
            );
            extract( $attr );

            $masterpage = trim( $masterpage );
            $name = trim( $name );

            $values = array();
            for( $x=0; $x<count($params); $x++ ){
                $k = trim( $params[$x]['lhs'] );
                if( substr($k, 0, 3)=='rt_' ){
                    $values[substr($k, 3)] = trim( $params[$x]['rhs'] );
                }
            }

            $link = $FUNCS->generate_route( $masterpage, $name, $values );

            return $link;
        }

        function config_list_view( $params, $node ){
            global $CTX, $FUNCS, $AUTH, $PAGE, $DB;
            if( $AUTH->user->access_level < K_ACCESS_LEVEL_SUPER_ADMIN ){ return; }

            $attr = $FUNCS->get_named_vars(
                        array(
                               'orderby'=>'',
                               'order'=>'',
                               'limit'=>'',
                               'exclude'=>'',
                               'searchable'=>'0',
                              ),
                        $params);
            extract( $attr );

            $rs = $DB->select( K_TBL_TEMPLATES, array('config_list'), "id='" . $DB->sanitize( $PAGE->tpl_id ). "'" );
            if( !count($rs) ){ return; }
            $orig_value = $rs[0]['config_list'];

            // setup objects to be filled by child cms:field, cms:script, cms:style, cms:html tags
            $arr_config = array( 'arr_fields'=>array(), 'js'=>'', 'css'=>'', 'html'=>'' );
            $CTX->set_object( '__config', $arr_config );

            // invoke child tags
            foreach( $node->children as $child ){
                $child->get_HTML();
            }

            $arr_config['orderby'] = trim( $orderby );
            $arr_config['order'] = trim( $order );
            $arr_config['limit'] = trim( $limit );
            $arr_config['exclude'] = trim( $exclude );
            $arr_config['searchable'] = ( $searchable==1 ) ? 1 : 0;

            // if array modified, serialize and save it
            $modified = array();
            $modified['config_list'] = base64_encode( serialize($arr_config) );
            if( $orig_value !== $modified['config_list'] ){
                $rs = $DB->update( K_TBL_TEMPLATES, $modified, "id='" . $DB->sanitize( $PAGE->tpl_id ). "'" );
                if( $rs==-1 ) die( "ERROR: Tag: '.$node->name.' Unable to save modified list" );
            }
        }

        function config_form_view( $params, $node ){
            global $CTX, $FUNCS, $AUTH, $PAGE, $DB;
            if( $AUTH->user->access_level < K_ACCESS_LEVEL_SUPER_ADMIN ){ return; }

            $rs = $DB->select( K_TBL_TEMPLATES, array('config_form'), "id='" . $DB->sanitize( $PAGE->tpl_id ). "'" );
            if( !count($rs) ){ return; }
            $orig_value = $rs[0]['config_form'];

            // setup objects to be filled by child cms:field, cms:script, cms:style, cms:html tags, cms:persist
            $arr_config = array( 'arr_fields'=>array(), 'js'=>'', 'css'=>'', 'html'=>'', 'params'=>'' );
            $CTX->set_object( '__config', $arr_config );

            // invoke child tags
            foreach( $node->children as $child ){
                $child->get_HTML();
            }

            // if array modified, serialize and save it
            $modified = array();
            $modified['config_form'] = base64_encode( serialize($arr_config) );
            if( $orig_value !== $modified['config_form'] ){
                $rs = $DB->update( K_TBL_TEMPLATES, $modified, "id='" . $DB->sanitize( $PAGE->tpl_id ). "'" );
                if( $rs==-1 ) die( "ERROR: Tag: '.$node->name.' Unable to save modified list" );
            }
        }

        function field( $params, $node ){ // for use within cms:config_list_view/config_form_view
            global $CTX, $FUNCS, $AUTH;
            static $order = -10;

            if( $AUTH->user->access_level < K_ACCESS_LEVEL_SUPER_ADMIN ){ return; }

            // get the 'config' object supplied by 'cms:config_list_view' or 'cms:config_form_view' tag
            $arr_config = &$CTX->get_object( '__config', 'config_list_view' );
            if( !is_array($arr_config) ){
                $arr_config = &$CTX->get_object( '__config', 'config_form_view' );
                if( !is_array($arr_config) ){ return; }
                $parent_tag = 'config_form_view';
            }
            else{
                $parent_tag = 'config_list_view';
            }

            if( $parent_tag == 'config_list_view' ){
                extract( $FUNCS->get_named_vars(
                           array(
                                'name'=>'',
                                'sortable'=>null,
                                'sort_name'=>'',
                                'class'=>null,
                                'header'=>null,
                                ),
                           $params) );

                $name = trim( $name );
                if( !$name ){ die("ERROR: Tag \"".$node->name."\" needs a 'name' attribute"); }

                // create field info..
                $field = array();
                $field['name'] = $name;
                $field['weight'] = $order;
                if( !is_null($sortable) ){ $field['sortable'] = $sortable; }
                if( !is_null($sort_name) ){
                    $sort_name = trim( $sort_name );
                    if( $sort_name!='' ) $field['sort_name'] = $sort_name;
                }
                if( !is_null($class) ){ $field['class'] = $class; }
                if( !is_null($header) ){
                    foreach( $node->attributes as $attr ){
                        if( $attr['name']=='header' ){
                            switch( $attr['value_type'] ){
                                case K_VAL_TYPE_LITERAL:
                                    $field['header'] = $attr['value'];
                                    break 2;
                                case K_VAL_TYPE_VARIABLE:
                                    $field['header'] = trim( $CTX->get($attr['value']) );
                                    break 2;
                                case K_VAL_TYPE_SPECIAL:
                                    $field['header'] = $attr['value']->children;
                                    break 2;
                            }
                        }
                    }
                }
                if( count($node->children) ){
                    $field['content'] = $node->children;
                }

                // ..and add it to the '$arr_config' array
                $arr_config['arr_fields'][$name] = $field;
                $order = $order + 10;
            }
            else{ // parent_tag is 'config_form_view'
                $valid_params = array(
                    'name'=>'',
                    'label'=>null,
                    'desc'=>null,
                    'order'=>null,
                    'group'=>'',
                    'class'=>null,
                    'icon'=>null,
                    'no_wrapper'=>null,
                    'skip'=>null,
                    'hide'=>null,
                    'required'=>null,
                    'is_compound'=>null,
                );

                $valid_params = $FUNCS->get_named_vars( $valid_params, $params);

                $name = $valid_params['name'] = trim( $valid_params['name'] );
                if( !$name ){ die("ERROR: Tag \"".$node->name."\" needs a 'name' attribute"); }
                if( trim($valid_params['group'])=='' ){ $valid_params['group']=null; }

                // create field info..
                $field = array();
                foreach( $valid_params as $k=>$v ){
                    if( !is_null($v) ){ $field[$k] = $v; }
                }

                if( count($node->children) ){
                    $field['content'] = $node->children;
                }

                // ..and add it to the '$arr_config' array
                $arr_config['arr_fields'][$name] = $field;
            }
        }

        function script( $params, $node ){ // for use within cms:config_list_view/config_form_view
            global $CTX, $FUNCS, $AUTH;
            if( $AUTH->user->access_level < K_ACCESS_LEVEL_SUPER_ADMIN ){ return; }

            // get the 'config' object supplied by 'cms:config_list_view' tag or 'cms:config_form_view' tag
            $arr_config = &$CTX->get_object( '__config', 'config_list_view' );
            if( !is_array($arr_config) ){
                $arr_config = &$CTX->get_object( '__config', 'config_form_view' );
                if( !is_array($arr_config) ){ return; }
            }

            if( count($node->children) ){
                $code = $node->children;
            }

            $key = ( isset($node->__array_key__) ) ? $node->__array_key__ : 'js';
            $arr_config[$key] = $code;
        }

        function style( $params, $node ){ // for use within cms:config_list_view/config_form_view
            // delegate to 'cms:script'
            $node->__array_key__ = 'css';
            $this->script( $params, $node );
        }

        function html( $params, $node ){ // for use within cms:config_list_view/config_form_view
            // delegate to 'cms:script'
            $node->__array_key__ = 'html';
            $this->script( $params, $node );
        }

        function persist( $params, $node ){ // for use within config_form_view only
            global $CTX, $FUNCS, $AUTH;
            if( $AUTH->user->access_level < K_ACCESS_LEVEL_SUPER_ADMIN ){ return; }

            $arr_config = &$CTX->get_object( '__config', 'config_form_view' );
            if( !is_array($arr_config) ){ return; }

            $arr_config['params'] = $node->attributes;
        }

        /*
            Note: does not support dates before 1st Jan 1970

            If the format parameter contains % sign, strftime is used instead of date.
                <cms:date format='F j, Y' /> is the same as
                <cms:date format='%B %d, %Y'/>

            Unlike date function, strftime supports the setting of locale -
                <cms:php> setlocale(LC_ALL, "french"); </cms:php>
                <cms:date format='%B %d, %Y'/>

            However strftime does not output utf8. So for exotic locales, we'll need to convert
            the character set.
            The following PHP code -
                <?php
                setlocale(LC_ALL, 'greek').': ';
                echo iconv('windows-1253', 'UTF-8', strftime('%B %d, %Y', time()))."\n";
                ?>
            is equivalent to -
                <cms:php> setlocale(LC_ALL, "greek"); </cms:php>
                <cms:php>echo iconv('windows-1253', 'UTF-8', '<cms:date format='%B %d, %Y' />');</cms:php>

            The snippet above will need PHP5 or PHP4 with iconv support.

            Overall a big mess. Not what designers need.

        */
        function date( $params, $node ){
            global $CTX, $FUNCS, $PAGE;
            if( count($node->children) ) {die("ERROR: Tag \"".$node->name."\" is a self closing tag");}

            extract( $FUNCS->get_named_vars(
                        array(
                              'date'=>'',
                              'format'=>'F d, Y',
                              'gmt'=>'0',
                              'locale'=>'',
                              'charset'=>'' /*charset to be converted to utf8 */
                              ),
                        $params)
                   );

            $date = $FUNCS->date( $date, $format, $locale, $charset, $gmt );

            return $date;
        }

        // Given a string containing an English date format in 'str', this tag will parse it and return a date in 'Y-m-d H:i:s' format
        // relative to the date given in the second parameter (usually given in Y-m-d H:i:s' format).
        function calc_date( $params, $node ){
            global $CTX, $FUNCS;
            if( count($node->children) ) {die("ERROR: Tag \"".$node->name."\" is a self closing tag");}

            extract( $FUNCS->get_named_vars(
                        array(
                              'str'=>'',
                              'relative_to'=>'',
                              ),
                        $params)
                   );

            $str = trim( $str );
            if( $str=='' ) $str = 'now';
            $relative_to = trim( $relative_to );
            if( $relative_to=='' ) $relative_to = $FUNCS->get_current_desktop_time();

            $ts_now = @strtotime( $relative_to );
            $ts_end = @strtotime( $str, $ts_now );

            $date = date( 'Y-m-d H:i:s', $ts_end );

            return $date;
        }

        function archives( $params, $node ){
            global $CTX, $FUNCS, $PAGE, $DB;

            extract( $FUNCS->get_named_vars(
                        array(
                              'masterpage'=>'',
                              'order'=>'',
                              'limit'=>'',
                              'start_on'=>'',
                              'stop_before'=>'',
                              'show_future_entries'=>'0',
                              'type'=>'', /* yearly, monthly, daily */
                              'startcount'=>'1'
                              ),
                        $params)
                   );

            // sanitize params
            $masterpage = trim( $masterpage );
            if( $masterpage=='' ){ $masterpage = $PAGE->tpl_name; }
            $order = trim( $order );
            if( $order!='desc' && $order!='asc' ) $order = 'desc';
            $limit = is_numeric( $limit ) ? intval( $limit ) : '';
            $start_on = trim( $start_on );
            if( $start_on ) $start_on = $FUNCS->make_date( $start_on );
            $stop_before = trim( $stop_before );
            if( $stop_before ) $stop_before = $FUNCS->make_date( $stop_before );
            $show_future_entries = ( $show_future_entries==1 ) ? 1 : 0;
            $hide_future_entries = !$show_future_entries;
            $type = strtolower( trim($type) );
            if( !in_array($type, array('yearly', 'monthly', 'daily')) ){
                $type = 'monthly';
            }
            $startcount = is_numeric( $startcount ) ? intval( $startcount ) : 1;


            $sql = "t.name='" . $DB->sanitize( $masterpage )."'";

            // dates
            if( $start_on ) $sql .= " AND publish_date >= '".$DB->sanitize( $start_on )."'";
            if( $hide_future_entries ){
                $cur_time = $FUNCS->get_current_desktop_time();
                $stop_before = $FUNCS->smaller_date( $stop_before, $cur_time );
            }
            if( $stop_before ){
                $sql .= " AND publish_date < '".$DB->sanitize( $stop_before )."'";
            }
            $sql .= " AND NOT publish_date = '0000-00-00 00:00:00'";

            $sql .= " GROUP BY YEAR(publish_date)";
            if( $type=='monthly' ){
                $sql .= ', MONTH(publish_date)';
            }
            elseif( $type=='daily' ){
                $sql .= ', MONTH(publish_date), DAY(publish_date)';
            }
            $sql .= " ORDER BY publish_date";
            $sql .= " " . $order;
            if( $limit ) $sql .= " LIMIT " . $limit;
            $tables = K_TBL_TEMPLATES.' t inner join '.K_TBL_PAGES.' p on t.id = p.template_id';
            $rs = $DB->select( $tables, array('publish_date','count(p.id) as k_count'), $sql );

            if( count($rs) ){
                $cur = 0;
                for( $x=0; $x<count($rs); $x++ ){
                    $rec = $rs[$x];

                    $yy = substr( $rec['publish_date'], 0, 4 );
                    $mm = substr( $rec['publish_date'], 5, 2 );
                    $dd = substr( $rec['publish_date'], 8, 2 );
                    if( $type=='yearly' ){
                        $CTX->set( 'k_archive_date', date('Y-m-d', mktime(0, 0, 0, 1, 1, $yy)) );
                        $CTX->set( 'k_next_archive_date', date('Y-m-d', mktime(0, 0, 0, 1, 1, $yy+1)) );
                        $CTX->set( 'k_archive_link', K_SITE_URL . $FUNCS->get_archive_link($masterpage, $yy, 0, 0 ) );
                    }
                    elseif( $type=='monthly' ){
                        $CTX->set( 'k_archive_date', date('Y-m-d', mktime(0, 0, 0, $mm, 1, $yy)) );
                        $CTX->set( 'k_next_archive_date', date('Y-m-d', mktime(0, 0, 0, $mm+1, 1, $yy)) );
                        $CTX->set( 'k_archive_link', K_SITE_URL . $FUNCS->get_archive_link($masterpage, $yy, $mm, 0) );
                    }
                    elseif( $type=='daily' ){
                        $CTX->set( 'k_archive_date', date('Y-m-d', mktime(0, 0, 0, $mm, $dd, $yy)) );
                        $CTX->set( 'k_next_archive_date', date('Y-m-d', mktime(0, 0, 0, $mm, $dd+1, $yy)) );
                        $CTX->set( 'k_archive_link', K_SITE_URL . $FUNCS->get_archive_link($masterpage, $yy, $mm, $dd) );
                    }

                    $CTX->set( 'k_archive_count', $rec['k_count'] );
                    $CTX->set( 'k_count', $cur + $startcount );
                    $cur++;

                    // call the children
                    foreach( $node->children as $child ){
                        $html .= $child->get_HTML();
                    }
                }
            }
            return $html;
        }

        function form( $params, $node ){
            global $CTX, $FUNCS, $PAGE, $DB;
            if( !is_object($PAGE) ){ $PAGE = new stdClass(); }

            $html = '<form ';

            $name = '';
            $method = '';
            $action = '';
            $separator = '|'; //separator for error messages upon submission
            $anchor = '1';
            $charset = K_CHARSET; //accept-charset
            $add_security_token = '';
            for( $x=0; $x<count($params); $x++ ){
                $attr = strtolower(trim($params[$x]['lhs']));
                if( $attr=='name' ){
                    $name = trim( $params[$x]['rhs'] );
                    if( !$name ){ continue; }
                }
                elseif( $attr=='method' ){
                    $method = strtolower( trim($params[$x]['rhs']) );
                    if( $method!='get' && $method!='post' ) $method = $params[$x]['rhs'] = 'get';
                }
                elseif( $attr=='action' ){
                    $action = $params[$x]['rhs'];
                    continue;
                }
                elseif( $attr=='separator' ){
                    $separator = trim($params[$x]['rhs']);
                    continue;
                }
                elseif( $attr=='anchor' ){
                    if( $FUNCS->is_natural($params[$x]['rhs']) ){
                        $anchor = intval( $params[$x]['rhs'] );
                    }
                    continue;
                }
                elseif( $attr=='charset' ){
                    $charset = trim($params[$x]['rhs']);
                    continue;
                }
                elseif( $attr=='masterpage' || $attr=='page_id' || $attr=='mode' || $attr=='token' || $attr=='validate_bound' ){ // Data-bound form's parameters
                    $$attr = trim($params[$x]['rhs']);
                    continue;
                }
                elseif( $attr=='add_security_token' ){
                    $add_security_token = trim($params[$x]['rhs']);
                    continue;
                }

                $html .= ' '.$params[$x]['lhs'] . '="' . $params[$x]['rhs'] . '"';
            }
            if( !$name ){
                $name = 'kformname' . $PAGE->form_num++;
                $html .= ' ' .  'name="' . $name . '"';
            }
            if( !$method ) $method = 'get';

            if( $anchor ){
                if( ($pos = strpos($action, '#'))!==false ){
                    $action = substr( $action, 0, $pos ); //strip off anchors. We'll add our own.
                }
                $action .= '#'.$name;
            }
            $html .= ' ' .  'action="' . $action . '"';
            $html .= ' ' .  'accept-charset="' . $charset . '"';

            if( !$FUNCS->is_title_clean($name) ){
                die( "ERROR: Tag \"".$node->name."\": 'name' attribute ({$name}) contains invalid characters. (Only lowercase[a-z], numerals[0-9], hyphen and underscore permitted.)" );
            }
            $CTX->set( 'k_cur_form', $name );
            $CTX->set( 'k_cur_form_method', $method );
            $CTX->set( 'k_cur_form_separator', $separator ); // cue for any create or update child tag
            $html .= '>';
            if( $anchor ){
                $html = '<a name="'.$name.'"></a>' . $html; //anchor for return
            }

            $pg = null;
            $page_id = ( isset($page_id) && $FUNCS->is_non_zero_natural($page_id) ) ? (int)$page_id : null;
            $validate_bound = ( $validate_bound==='1' ) ? 1 : 0;

            // check if the form is data-bound
            if( $masterpage ){
                $mode = strtolower( $mode );
                if( !($mode=='edit' || $mode=='create' || $mode=='auto') ){
                    die( "ERROR: Tag \"".$node->name."\" - unknown value for 'mode' parameter (only 'edit', 'create' and 'auto' supported)" );
                }

                // check if masterpage registered for special consideration
                if( $FUNCS->is_spl_template( $masterpage ) ){
                    $pg = $FUNCS->handle_spl_template( $masterpage, array($page_id, '', &$mode) );
                    if( $FUNCS->is_error($pg) ){
                        die( "ERROR: Tag \"".$node->name."\" - " . $pg->err_msg );
                    }
                }
                else{ // default processing
                    $rs = $DB->select( K_TBL_TEMPLATES, array('id', 'clonable'), "name='" . $DB->sanitize( $masterpage ). "'" );
                    if( !count($rs) ){
                        die( "ERROR: Tag \"".$node->name."\" - masterpage does not exist" );
                    }

                    if( $mode=='auto' ){ // will make the form bind to the global $PAGE object
                        if( $PAGE->tpl_name !== $masterpage ){
                            die( "ERROR: Tag \"".$node->name."\" - 'masterpage' does not match the current page's template" );
                        }
                        $tpl_id = $PAGE->tpl_id;
                        $pg = &$PAGE;
                        $mode = ( $pg->id==-1 ) ? 'create' : 'edit';
                        if( $mode=='create' && !$pg->tpl_is_clonable ){
                            die( "ERROR: Tag \"".$node->name."\" - cannot create page of non-clonable template" );
                        }
                    }
                    else{
                        if( $mode=='edit' ){
                            if( $rs[0]['clonable'] && !$page_id ){
                                die( "ERROR: Tag \"".$node->name."\" - page_id required" );
                            }
                        }
                        else{
                            if( !$rs[0]['clonable'] ){
                                die( "ERROR: Tag \"".$node->name."\" - cannot create page of non-clonable template" );
                            }
                            $page_id = -1;
                        }

                        // get the page being bound to and set it in context for the fields to use
                        $tpl_id = $rs[0]['id'];
                        $pg = new KWebpage( $tpl_id, $page_id );
                        if( $pg->error ){
                            die( "ERROR: Tag \"".$node->name."\" - " . $pg->err_msg );
                        }
                    }
                }

                $count = count( $pg->fields );
                for( $x=0; $x<$count; $x++ ){
                    $f = &$pg->fields[$x];
                    $f->resolve_dynamic_params();
                    unset( $f );
                }
                $CTX->set_object( 'k_bound_page', $pg );
                $CTX->set( 'k_cur_form_mode', $mode );

                // Add CSRF token? (default is yes)
                $add_security_token = ($add_security_token==='0') ? 0 : 1;
                if( $add_security_token ){
                    if( $mode=='edit' ){
                        $obj_id = ( $page_id ) ? $page_id : $masterpage;
                        $nonce = 'edit_page_' . $obj_id;
                    }
                    else{
                        $nonce = 'create_page_' . $masterpage;
                    }
                }
            }

            if( $method=='post' ){
                $submitted = isset($_POST['k_hid_'.$name]);
            }
            else{
                $submitted = isset($_GET['k_hid_'.$name]);
            }

            if( $submitted && $token ){
                // HOOK: form_posted_xxx
                $FUNCS->dispatch_event( 'form_posted_'.$token, array($method, &$pg) );
            }

            // call the children (this will create the fields)
            foreach( $node->children as $child ){
                $sub_html .= $child->get_HTML();
            }

            // validate the fields if form submitted
            if( $submitted ){
                $CTX->set( 'k_submitted', 1 );

                if( isset($PAGE->forms[$name]) ){

                    // check security token?
                    if( $add_security_token ){
                        $submitted_nonce = ( $method=='post' ) ? $_POST['k_nonce'] : $_GET['k_nonce'];
                        $FUNCS->validate_nonce( $nonce, $submitted_nonce );
                    }

                    $form = &$PAGE->forms[$name];
                    $errors = array();

                    // loop through fields to see if any has requested a refresh of form
                    $refresh_form = 0; $refresh_errors = array();

                    if( $token ){
                        // HOOK: form_alter_posted_data_xxx
                        $FUNCS->dispatch_event( 'form_alter_posted_data_'.$token, array(&$form, &$refresh_form, &$refresh_errors, &$pg) );
                    }

                    foreach( $form as $k=>$v ){
                        $f = &$form[$k];
                        if( $f->refresh_form ) $refresh_form = 1;
                        if( $f->err_msg_refresh ){
                            $CTX->set( 'k_error_'.$f->name, $f->err_msg_refresh );
                            $refresh_errors[] = '<b>' . (($f->label) ? $f->label : $f->name) . ':</b> ' .$f->err_msg_refresh;
                        }
                        unset( $f );
                    }

                    if( !$refresh_form ){ // process submission as usual

                        if( $token ){
                            // HOOK: form_prevalidate_xxx
                            $FUNCS->dispatch_event( 'form_prevalidate_'.$token, array(&$form, &$pg) );
                        }

                        foreach( $form as $k=>$v ){
                            $f = &$form[$k];
                            if( !$f->module || $validate_bound ){ // if nor explicitly asked to, skip bound fields as they will be validated by the owner modules while subsequently saving the page
                                if( !$f->validate() ){
                                    $CTX->set( 'k_error_'.$f->name, $f->err_msg );
                                    $errors[] = '<b>' . (($f->label) ? $f->label : $f->name) . ':</b> ' .$f->err_msg;
                                }
                            }
                            unset( $f );
                        }

                        if( $token ){
                            // HOOK: form_postvalidate_xxx
                            $FUNCS->dispatch_event( 'form_postvalidate_'.$token, array(&$form, &$errors, &$pg) );
                        }

                        $sep = '';
                        if( count($errors) ){
                            $str_err = '';
                            foreach( $errors as $e ){
                                $str_err .= $sep . $e;
                                $sep = $separator;
                            }
                            $CTX->set( 'k_error', $str_err );
                            $CTX->set( 'k_error_count', count($errors) );
                        }
                        else{
                            $str_success = '';
                            foreach( $form as $k=>$v ){
                                $f = &$form[$k];
                                if( $f->k_type=='submit' || $f->name=='k_hid_'.$name ) continue;

                                $data = $f->get_data( 1 );
                                if( $f->k_type=='checkbox' ){
                                    if( $data ){
                                        $chk_separator = ( $f->k_separator ) ? $f->k_separator : '|';
                                        //$data = str_replace( $chk_separator, ', ', $data );
                                        $data = preg_replace( "/(?<!\\\)\\".$chk_separator."/", ', ', $data );
                                        $data = str_replace( '\\'.$chk_separator, $chk_separator, $data ); //unescape
                                    }
                                }
                                $CTX->set( 'frm_'.$f->name, $data );
                                $str_success .= $sep . (($f->label) ? $f->label : $f->name) . ': ' .$data ."\n";
                                //$sep = $separator;

                                // code above - for checkbox types and k_success, instead of the '|', we use ',' and '\n' respectively
                                // for the contained values.

                                unset( $f );
                            }
                            if( strlen(trim($str_success))==0 ) $str_success = '1'; // To handle cases where the form consists of only a submit button
                            $CTX->set( 'k_success', $str_success );
                        }
                    }
                    else{
                        if( count($refresh_errors) ){
                            $str_err = $sep = '';
                            foreach( $refresh_errors as $e ){
                                $str_err .= $sep . $e;
                                $sep = $separator;
                            }
                            $CTX->set( 'k_error', $str_err );
                            $CTX->set( 'k_error_count', count($refresh_errors) );
                        }
                    }
                }

                // call the child nodes again to react to 'k_error' or 'k_success'
                $sub_html='';
                foreach( $node->children as $child ){
                    $sub_html .= $child->get_HTML();
                }

            }

            $html .= $sub_html;

            // decoupled this hidden field from the submit button to accomodate 'button' html tag and other ways of submission
            $html .= '<input type="hidden" name="k_hid_'.$name.'" id="k_hid_'.$name.'" value="'.$name.'" />';

            // add csrf token if any
            if( $nonce ){
                $html .= '<input type="hidden" name="k_nonce" id="k_nonce" value="'.$FUNCS->create_nonce( $nonce ).'" />';
            }

            // if method is 'get', make it uncacheable
            if( $method=='get' ){
                $html .= '<input type="hidden" name="nc" value="1" />';
            }
            $html .= '</form>';
            return $html;

        }

        function input( $params, $node ){
            global $CTX, $FUNCS, $PAGE;

            extract( $FUNCS->get_named_vars(
                       array(
                             'type'=>''
                            ),
                       $params) );
            $type = strtolower( trim($type) );
            if( !$type ){ die("ERROR: Tag \"".$node->name."\" needs a 'type' attribute"); }

            $is_udf = 0;
            if( !$FUNCS->is_core_formfield_type($type) ){
                // is it a user-defined-field type?
                if( array_key_exists($type, $FUNCS->udform_fields) ){
                    $is_udf = 1;
                    $udf_classname = $FUNCS->udform_fields[$type]['handler'];
                    $attr_udf = call_user_func( array($udf_classname, 'handle_params'), $params, $node );
                    if( !is_array($attr_udf) ) $attr_udf=array();
                    if( array_key_exists('type', $attr_udf) ){ unset( $attr_udf['type'] ); } //type cannot be overridden
                }
                else{
                    die("ERROR: Tag \"".$node->name."\" has unknown type \"".$type."\"");
                }
            }

            $core_attr = array( 'name', 'label', 'type', 'desc', 'id', 'value', 'required',
                                 'validator', 'validator_msg', 'checked',
                                 'separator', 'val_separator', 'opt_values', 'opt_selected',
                                 'html_before', 'html_after',
                                 'width', 'height', 'style',
                                 'format', 'reload_text',
                                 'allowed_html_tags',
                                 'trust_mode', 'no_js', 'simple_mode',
                                 'strip_tags', 'not_active',
                                 );
            $extra = '';
            $name = '';
            $id = '';
            for( $x=0; $x<count($params); $x++ ){
                $attr = strtolower(trim($params[$x]['lhs']));
                if( in_array($attr, $core_attr) ){
                    $val = ($attr=='not_active') ? $params[$x]['rhs'] : trim( $params[$x]['rhs'] );
                    $$attr = $val;
                    continue;
                }

                if( $is_udf && array_key_exists($attr, $attr_udf) ) continue;
                if( strlen($params[$x]['lhs']) ){
                    $extra .= ' '.$params[$x]['lhs'] . '="' . $params[$x]['rhs'] . '"';
                }
                else{
                    $extra .= ' '.$params[$x]['rhs'];
                }
            }
            if( $is_udf ){
                foreach( $attr_udf as $k=>$v ){
                    if( in_array($k, $core_attr) ){
                        $$k = $v; // value set by udf overrides
                    }
                }
            }
            if( !$name ){ die("ERROR: Tag \"".$node->name."\" needs a 'name' attribute"); }
            if( !$FUNCS->is_title_clean($name) ){
                die( "ERROR: Tag \"".$node->name."\": 'name' attribute ({$name}) contains invalid characters. (Only lowercase[a-z], numerals[0-9], hyphen and underscore permitted.)" );
            }
            if( !$id ){ $id = $name; }

            if( $type == 'captcha' ){
                if( !$width ) $width=140;
                if( !$format ) $format='t-i-r'; //textbox, image, reload
            }
            $style= $FUNCS->set_style( $style, $width, $height );
            if( $style ) $extra .= ' '.$style;

            // get the form currently in context
            $form = $CTX->get('k_cur_form');
            $form_method = $CTX->get('k_cur_form_method');

            // check if field already present (will be so in second iteration)
            if( isset($PAGE->forms[$form]) && array_key_exists($name, $PAGE->forms[$form]) ){
                $f = &$PAGE->forms[$form][$name];
            }
            else{
                // create the field in form
                $f = null;

                // Data bound field?
                if( $type=='bound' ){
                    // first check if form is data bound to any page..
                    // if so and a matching field found, use that
                    $pg = &$CTX->get_object( 'k_bound_page', 'form' );
                    if( is_null($pg) ){
                        die("ERROR: Tag \"".$node->name."\" of type 'bound' needs to be within a Data-bound form");
                    }

                    $count = count( $pg->fields );
                    for( $x=0; $x<$count; $x++ ){
                        $f = &$pg->fields[$x];
                        if( $f->name==$name ){

                            // turn off trusted_mode unless specified otherwise
                            $f->trust_mode = ( $trust_mode==1 ) ? 1 : 0;

                            // no JavaScript if inline editing
                            $f->no_js = ( $no_js==1 ) ? 1 : 0;

                            $PAGE->forms[$form][$name] = &$f;
                            break;
                        }
                        unset( $f );
                    }

                    if( !$f ){
                        die("ERROR: Tag \"".$node->name."\" - No matching field of name '".$FUNCS->cleanXSS($name, 0, 'none')."' found in database for data-binding");
                    }

                }
                else{
                    if( $type == 'textarea' /*|| $type == 'richtext'*/ ){
                        $value = '';
                        foreach( $node->children as $child ){
                            $value .= $child->get_HTML();
                        }
                    }

                    $field_info = array(
                        'id' => -1,
                        'name' => $name,
                        'label' => $label,
                        'k_desc' => $desc,
                        'k_type' => strtolower( $type ),
                        'hidden' => '0',
                        'data' => $value,
                        'required' => $required,
                        'validator' => $validator,
                        'validator_msg' => $validator_msg,
                        'k_separator' => $separator,
                        'val_separator' => $val_separator,
                        'opt_values' => $opt_values,
                        'opt_selected' => $opt_selected,
                        'html_before' => $html_before,
                        'html_after' => $html_after,
                        'system' => '0',
                        'allowed_html_tags' => $allowed_html_tags,
                        'trust_mode' => $strip_tags ? 0 : 1,
                        'not_active' => $not_active,
                    );

                    if( $is_udf ){
                        $field_info = array_merge( $field_info, $attr_udf );
                        $PAGE->forms[$form][$name] = new $udf_classname( $field_info, $PAGE->forms[$form] );
                    }
                    else{
                        $PAGE->forms[$form][$name] = new KFieldForm( $field_info, $PAGE->forms[$form] );
                    }
                    $f = &$PAGE->forms[$form][$name];

                    if( $f->k_type== 'captcha' ){
                        $f->captcha_num = intval( $PAGE->captcha_num++ );
                        $f->captcha_format = strtolower( trim($format) );
                        $f->captcha_reload_text = $reload_text ? $reload_text : 'Reload Image';
                    }
                }

                // render in simple mode if not in admin-panel (except if explicitly instructed otherwise)
                if( is_null($simple_mode) ){
                    $f->simple_mode = ( defined('K_ADMIN') ) ? 0 : 1;
                }
                else{
                    $f->simple_mode = ( $simple_mode==1 ) ? 1 : 0;
                }

                // form submitted?
                $submitted = ( $form_method=='post' ) ? isset($_POST['k_hid_'.$form]) : isset($_GET['k_hid_'.$form]);

                // field conditionally inactive? (also generates JS for conditional logic)
                $f->k_inactive = !$FUNCS->resolve_active( $f, $form, $submitted );

                if( $submitted ){
                    $var_name = $name;
                    if( $type=='bound' ){
                        $var_name = 'f_'.$var_name; // hack for admin-panel's unfortunate naming of fields
                    }

                    ( $form_method=='post' ) ? $f->store_posted_changes($_POST[$var_name]) : $f->store_posted_changes($_GET[$var_name]);
                }
            }

            // render
            if( is_null($f->cached) || $f->err_msg ){
                if( $type=='bound' ){
                    $name = $id = 'f_'.$name; // hack for admin-panel's unfortunate naming of fields
                }
                $f->cached = $f->_render( $name, $id, $extra );
            }
            return $f->cached;

        }

        function fieldset( $params, $node ){
            global $CTX, $FUNCS, $PAGE;

            $label = '';
            for( $x=0; $x<count($params); $x++ ){
                $attr = strtolower(trim($params[$x]['lhs']));
                if( $attr=='label' ){
                    $label = trim( $params[$x]['rhs'] );
                    continue;
                }
                $extra .= ' '.$params[$x]['lhs'] . '="' . $params[$x]['rhs'] . '"';
            }

            $html .= "<fieldset ".$extra.">\n";
            if( $label ){
                $html .= "<legend>" . $label ."</legend>\n";
            }
            $html .= "<dl>\n";

            $CTX->set( 'k_wrapper', 1 );
            foreach( $node->children as $child ){
                $sub_html .= $child->get_HTML();
            }
            $CTX->set( 'k_wrapper', 0 );

            $html .= $sub_html . "</dl>\n</fieldset>";
            return $html;
        }

        function send_mail( $params, $node ){
            global $FUNCS, $CTX;

            extract( $FUNCS->get_named_vars(
                        array(
                              'from'=>'',
                              'to'=>'',
                              'cc'=>'',
                              'bcc'=>'',
                              'reply_to'=>'',
                              'sender'=>'',
                              'charset'=>'',
                              'subject'=>'',
                              'debug'=>'0',
                              'logfile'=>'',
                              'html'=>'0'
                              ),
                        $params)
                   );

            $from = trim( $from );
            $to = trim( $to );
            $cc = trim( $cc );
            $bcc = trim( $bcc );
            $reply_to = trim( $reply_to );
            $sender = trim( $sender );
            $charset = trim( $charset );
            if( $charset=='' ) $charset=K_CHARSET;
            $debug = ( $debug==1 ) ? 1 : 0;
            $logfile = trim( $logfile );
            $html = ( $html==1 ) ? 1 : 0;

            //  setup objects to be filled by child cms:attachment and cms:alt_body tags
            $arr_config = array( 'att'=>array(), 'alt_body'=>null );
            $CTX->set_object( '__config', $arr_config );

            foreach( $node->children as $child ){
                $msg .= $child->get_HTML();
            }

            $headers = array();
            if( $cc ) $headers['Cc']=$cc;
            if( $bcc ) $headers['Bcc']=$bcc;
            if( $reply_to ) $headers['Reply-To']=$reply_to;
            if( $sender ) $headers['Sender']=$sender;
            $headers['MIME-Version']='1.0';
            if( $html ){
                $headers['Content-Type']='text/html; charset='.$charset;
            }
            else{
                $headers['Content-Type']='text/plain; charset='.$charset;

                $msg = $FUNCS->unhtmlentities( $msg, K_CHARSET ); // resurrecting (decoding) the entities. Shouldn't be required in HTML mails.
                $msg = strip_tags($msg);
            }

            $rs = $FUNCS->send_mail( $from, $to, $subject, $msg, $headers, $arr_config, $debug );
            if( $debug ){
                $log = "From: $from\r\nTo: $to\r\n";
                foreach( $headers as $k=>$v ){
                    $log .= $k .': '.$v."\r\n";
                }
                $log .= "Subject: $subject\r\nMessage: $msg\r\n\r\n";
                ( $rs ) ? $log .= "Delivery Success" : $log .= "Delivery Failed";
                $FUNCS->log( $log, $logfile );
            }
            return;
        }

        function google_map( $params, $node ){
            global $CTX, $FUNCS, $PAGE;
            if( count($node->children) ) {die("ERROR: Tag \"".$node->name."\" is a self closing tag");}

            $custom_attr = array( 'name', 'id', 'address',
                                 'longitude', 'latitude', 'zoom', 'message',
                                 'width', 'height', 'style'
                                 );
            $extra = '';
            for( $x=0; $x<count($params); $x++ ){
                $attr = strtolower(trim($params[$x]['lhs']));
                $val = trim( $params[$x]['rhs'] );
                $$attr = $val;

                if( in_array($attr, $custom_attr) ) continue;
                $extra .= ' '.$params[$x]['lhs'] . '="' . $params[$x]['rhs'] . '"';
            }
            if( !$name ){ die("ERROR: Tag \"".$node->name."\" needs a 'name' attribute"); }
            if( !$FUNCS->is_title_clean($name) ){
                die( "ERROR: Tag \"".$node->name."\" 'name' contains invalid characters. (Only lowercase[a-z], numerals[0-9] hyphen and underscore permitted" );
            }
            if( !$id ){ $id = $name; }

            $zoom = intval( $zoom );
            if( !$zoom ) $zoom=14;

            if( $longitude != '' && $latitude != '' ){
                $longitude = floatval( $longitude );
                $latitude = floatval( $latitude );

                $func = "display_by_coordinates( new google.maps.LatLng($latitude, $longitude), $zoom, \"$message\" );";
            }
            elseif( $address ){
                $func = "display_by_address( \"$address\", $zoom, \"$message\" );";
            }
            else{
                die("ERROR: Tag \"".$node->name."\" needs either an 'address' attribute or both 'longitude' and 'latitude' attributes");
            }
            if( !K_GOOGLE_KEY ){ die( "ERROR: Tag \"".$node->name."\" requires a valid Google API key in config.php" ); }

            $width = intval( $width );
            if( !$width ) $width=400;
            $height = intval( $height );
            if( !$height ) $height=300;
            $key = K_GOOGLE_KEY;
            $style= $FUNCS->set_style( $style, $width, $height );

            $html = "";
            if( $PAGE->google_map == 0 ){ $html .= "<script src=\"//maps.googleapis.com/maps/api/js?v=3&amp;key=$key\"></script>"; }

            // render
            $html .= <<<MAP
<script>
    google.maps.event.addDomListener( window, 'load',
        function(){
            var map = new google.maps.Map( document.getElementById("$id") );

            $func

            function display_by_coordinates( latlng, zoom, html ){
                map.setCenter( latlng );
                map.setZoom( zoom );
                mark( latlng, html, 1 );
            }

            function display_by_address( addr, zoom, msg ){
                // Use Geocoder to get coordinates of the address
                var gc = new google.maps.Geocoder();
                if( gc ){
                    if( !msg ){ msg = addr; }

                    gc.geocode({ 'address': addr },
                        function( response, status ){
                            if( !response || status != google.maps.GeocoderStatus.OK ){
                                alert( "Sorry, we were unable to geocode that address." );
                            }
                            else{
                                latlng = response[0].geometry.location;
                                display_by_coordinates( latlng, zoom, msg );
                            }
                        }
                    );
                }
            }

            function mark( latlng, msg, open ){
                var marker = new google.maps.Marker( { position: latlng, map: map } );
                var infowindow = new google.maps.InfoWindow( { content: msg } );

                if( msg ){
                    if( open ){
                        infowindow.open( map, marker );
                    }

                    google.maps.event.addListener( marker, "click",
                        function(){
                            infowindow.open( map, marker );
                        }
                    );
                }
            }
        }
    );
</script>
MAP;
            $html .= '<div id="'.$id.'" '.$style.' '.$extra.'>';
            $html .= '</div>';

            $PAGE->google_map=1;
            return $html;
        }

        //////Will try and move the following to 'plugins', once the architecture is in place
        function paypal_button( $params, $node ){
            global $CTX, $FUNCS, $PAGE;
            if( count($node->children) ) {die("ERROR: Tag \"".$node->name."\" is a self closing tag");}

            extract( $FUNCS->get_named_vars(
                        array(
                              'image'=>'',
                              'processor'=>'',
                              'show_shipping'=>'0',
                              'return_url'=>'',
                              'cancel_url'=>'',
                              'custom'=>''
                              ),
                        $params)
                   );
            $image = trim( $image );
            $processor = trim( $processor );
            $show_shipping = ( $show_shipping==1 ) ? 1 : 0;
            $return_url = trim( $return_url );
            $cancel_url = trim( $cancel_url );

            $item_name = $CTX->get('k_page_title');
            $item_number = $CTX->get('k_page_id');
            $amount = $CTX->get('pp_price');
            $downloads = $CTX->get('pp_download'); //defunct?..will prevent paypal from asking the shipping address

            $return_url = ( $return_url ) ? $return_url : K_SITE_URL . $PAGE->link;
            $cancel_url = ( $cancel_url ) ? $cancel_url : $return_url;
            $processor = ( $processor ) ? $processor : $return_url;
            $sep = ( strpos($processor, '?')===false ) ? '?' : '&';
            $notify_url = $processor . $sep . 'paypal_ipn=1';

            // Paypal does not support zero cost transactions. Don't display buttom if amount not given.
            if( $amount ){
                if( K_PAYPAL_USE_SANDBOX ){
                    $paypal_url = 'https://www.sandbox.paypal.com/cgi-bin/webscr';
                }
                else{
                    $paypal_url = 'https://www.paypal.com/cgi-bin/webscr';
                }

                if( !$image ) $image=0;
                if( $FUNCS->is_natural($image) ){
                    $arr_btns = array( 'x-click-but23.gif','x-click-but9.gif', 'x-click-but01.gif',
                                      'x-click-but3.gif', 'x-click-butcc.gif', 'x-click-but5.gif',
                                      'btn_buynow_SM.gif', 'btn_buynow_LG.gif', 'btn_buynowCC_LG.gif' );
                    $image_src = 'https://www.paypal.com/en_US/i/btn/'.$arr_btns[$image];
                }
                else{
                    $image_src = $image;
                }

                $html .= '<form action="'.$paypal_url.'" method="post">';
                $html .= '<input type="hidden" name="cmd" value="_xclick"/>';
                $html .= '<input type="hidden" name="business" value="'.K_PAYPAL_EMAIL.'"/>';
                $html .= '<input type="hidden" name="item_name" value="'.$item_name.'"/>';
                $html .= '<input type="hidden" name="item_number" value="'.$item_number.'"/>';
                $html .= '<input type="hidden" name="amount" value="'.$amount.'"/>';
                $html .= '<input type="hidden" name="undefined_quantity" value="1"/>';
                //if( strlen(trim($downloads)) ){
                if( !$show_shipping ){
                    //don't prompt customer for shipping address if product is downloadable
                    $html .= '<input type="hidden" name="no_shipping" value="1"/>';
                }
                $html .= '<input type="hidden" name="no_note" value="1"/>';
                $html .= '<input type="hidden" name="currency_code" value="'.K_PAYPAL_CURRENCY.'"/>';
                $html .= '<input type="hidden" name="rm" value="2"/>';
                $html .= '<input type="hidden" name="custom" value="'.$custom.'">';
                $html .= '<input type="hidden" name="return" value="'.$return_url.'"/>';
                $html .= '<input type="hidden" name="cancel_return" value="'.$cancel_url.'"/>';
                $html .= '<input type="hidden" value="'.$notify_url.'" name="notify_url"/>';
                $html .= '<input type="image" border="0" alt="Make payments with PayPal - it\'s fast, free and secure!"';
                $html .= ' name="submit" src="'.$image_src.'"/>';
                $html .= '<img width="1" height="1" border="0" src="https://www.paypal.com/en_US/i/scr/pixel.gif" alt=""/>';
                $html .= '</form>';

            }

            return $html;
        }

        function paypal_processor( $params, $node ){
            global $CTX, $FUNCS, $PAGE;

            if( !isset($_GET['paypal_ipn'][0]) ) return; //not being called from PayPal with IPN

            extract( $FUNCS->get_named_vars(
                        array(
                              'debug'=>'0',
                              'logfile'=>''
                              ),
                        $params)
                   );
            $debug = ( $debug==1 ) ? 1 : 0;
            $logfile = trim( $logfile );

            if( $debug ){
                $msg = "Received paypal IPN: \r\n\r\n";
                foreach( $_POST as $key => $value ){
                    $msg .= "$key = $value\r\n";
                }
                $FUNCS->log( $msg, $logfile );
            }

            // Send back the posted values to PayPal for verification
            if( $debug ) $FUNCS->log( 'Connecting to paypal for verification..', $logfile );
            $req = 'cmd=_notify-validate';
            foreach( $_POST as $key => $value ){
                $value = urlencode( stripslashes($value) );
                $req .= "&$key=$value";
            }
            $header .= "POST /cgi-bin/webscr HTTP/1.1\r\n";
            $header .= "Content-Type: application/x-www-form-urlencoded\r\n";
            if( K_PAYPAL_USE_SANDBOX ){
                $host = 'www.sandbox.paypal.com';
            }
            else{
                $host = 'www.paypal.com';
            }
            $header .= "Host: " . $host . "\r\n";
            $header .= "Connection: close\r\n";
            $header .= "User-Agent: abc-company-name\r\n";
            $header .= "Content-Length: " . strlen($req) . "\r\n\r\n";
            if( $debug ) $FUNCS->log( $header, $logfile );

            $fp = fsockopen( 'ssl://'.$host, 443, $errno, $errstr, 30 );
            if( !$fp ){
                if( $debug ) $FUNCS->log( "Error connecting to paypal: " . $errstr, $logfile );
            }
            else{
                if( $debug ) $FUNCS->log( 'Connected', $logfile );
                fputs( $fp, $header . $req );
                $res_headers = "Response headers: \r\n";
                while( !feof($fp) ){
                    $res = fgets( $fp, 1024 );
                    if( $debug ) $res_headers .= $res;
                    if( strcmp(trim($res), "VERIFIED")==0 ){
                        if( $debug ) $FUNCS->log( 'VERIFIED', $logfile );
                        if( $_POST['payment_status'] == 'Completed' ){

                            $_POST = $FUNCS->sanitize_deep( $_POST );

                            // Set paypal variables in context
                            $CTX->set( 'pp_item_name', $_POST['item_name'] );
                            $CTX->set( 'pp_item_number', $_POST['item_number'] );
                            $CTX->set( 'pp_quantity', $_POST['quantity'] );
                            $CTX->set( 'pp_mc_gross', $_POST['mc_gross'] );
                            $CTX->set( 'pp_mc_currency', $_POST['mc_currency'] );
                            $CTX->set( 'pp_txn_id', $_POST['txn_id'] );
                            $CTX->set( 'pp_receiver_email', $_POST['receiver_email'] );
                            $CTX->set( 'pp_payer_email', $_POST['payer_email'] );
                            $CTX->set( 'pp_first_name', $_POST['first_name'] );
                            $CTX->set( 'pp_last_name', $_POST['last_name'] );
                            $CTX->set( 'pp_payer_business_name', $_POST['payer_business_name'] );
                            $CTX->set( 'pp_custom', $_POST['custom'] );

                            // Validate that the transaction was valid before flagging success.
                            if( $debug ) $FUNCS->log( 'Couch validating transaction..', $logfile );
                            $pg = '';
                            $rc = $FUNCS->validate_transaction( $_POST['item_name'], $_POST['item_number'], $_POST['quantity'], $_POST['mc_gross'], $_POST['mc_currency'], $_POST['receiver_email'], $pg );
                            if( $FUNCS->is_error($rc) ){
                                if( $debug ) $FUNCS->log( 'ERROR: '.$rc->err_msg, $logfile );
                                $CTX->set( 'k_paypal_error', $rc->err_msg );
                            }
                            else{
                                if( $debug ) $FUNCS->log( 'Transaction OK', $logfile );
                                $CTX->set( 'k_paypal_success', 1 );
                                $pg->set_context();
                            }

                            // Invoke the child nodes to handle success or failure
                            foreach( $node->children as $child ){
                                $html .= $child->get_HTML();
                            }
                        }
                        else{
                            if( $debug ) $FUNCS->log( 'Unsupported payment status: '.$_POST['payment_status'], $logfile );
                        }

                    }
                    else if( strcmp(trim($res), "INVALID")==0 ){
                        //Some error on our part while sendng back POST variables for verification.
                        if( $debug ) $FUNCS->log( 'Paypal says IPN is INVALID', $logfile );
                    }
                }
                fclose( $fp );
                if( $debug ) $FUNCS->log( $res_headers, $logfile );
            }

            // Clear buffer and end script execution
            if( $debug ) $FUNCS->log( 'Exiting', $logfile );
            ob_end_clean();
            die;
        }

        function cloak_url( $params, $node ){
            global $CTX, $FUNCS;
            if( count($node->children) ) {die("ERROR: Tag \"".$node->name."\" is a self closing tag");}

            extract( $FUNCS->get_named_vars(
                        array(
                              'link'=>'',
                              'expiry'=>'0',
                              'access_level'=>'0',
                              'prompt_login'=>'0',
                              'redirect'=>'0',
                              'force_download'=>'0',
                              'thumbnail'=>'0', /* valid for 'securefile' attachments only */
                              'cache_for'=>'0', /* -do- */
                              'count_hits'=>'0', /* -do- */
                              'user_id'=>'0', /* takes precedence over access_level */
                              ),
                        $params)
                   );

            $link = trim( $link );
            if( !$link ) return;
            if( !$FUNCS->is_natural($expiry) ) $expiry=0; // In seconds
            if( !$FUNCS->is_natural($access_level) ) $access_level=0;
            $prompt_login = ( $prompt_login==1 ) ? 1 : 0;
            if( !$FUNCS->is_natural($redirect) ) $redirect=0;
            if( !$FUNCS->is_natural($force_download) ) $force_download=0;
            if( !$FUNCS->is_natural($thumbnail) ) $thumbnail=0;
            if( !$FUNCS->is_natural($cache_for) ) $cache_for=0;
            $count_hits = ( $count_hits==1 ) ? 1 : 0;
            $user_id = trim( $user_id );
            if( !$FUNCS->is_non_zero_natural($user_id) ) $user_id=0;
            if( $user_id ){ $access_level = 'i'.$user_id; }

            $action = 0;
            if( $redirect ) $action = 1;
            if( $force_download ) $action = 2;
            if( $expiry ){ $expiry = time() + $expiry; }

            if( $FUNCS->is_non_zero_natural($link) ){
                // Attachment id for securefile
                $key = ( $thumbnail ) ? '1' : '0';
                if( $action==1 ) $action=0; // no redirects for attachments
            }
            else{
                // Encrypt the link and base64 encode it
                $key = $FUNCS->generate_key( 32 );
                $link = $FUNCS->encrypt( $link, $key );
                $link = base64_encode( $link );
            }

            // Concatenate all params and produce a hash
            $data = $link . '|' . $key . '|' . $expiry . '|' . $access_level . '|' . $prompt_login . '|' . $action . '|' . $cache_for . '|' . $count_hits;
            $key = $FUNCS->hash_hmac( $data, $FUNCS->get_secret_key() );
            $hash = $FUNCS->hash_hmac( $data, $key );

            // Create the cloaked link
            $link = K_ADMIN_URL . 'download.php?auth=' . urlencode( $data . '|' . $hash );

            // Send it back
            return $link;
        }

        // Creates a JavaScript encrypted mailto link. No validation of the email is done.
        // If text is not provided, the email is used as the link text.
        function cloak_email( $params, $node ){
            global $CTX, $FUNCS;
            if( count($node->children) ) {die("ERROR: Tag \"".$node->name."\" is a self closing tag");}

            extract( $FUNCS->get_named_vars(
                        array(
                              'email'=>'',
                              'title'=>'',
                              'msg'=>''
                              ),
                        $params)
                   );

            $email = trim( $email );
            if( !$email ) return;
            $title = trim( $title );
            if( !$title ) $title=$email;
            $msg = trim( $msg );
            if( !$msg ) $msg='(Please enable JavaScript to view this email address)';

            $var_email = 'v' . $FUNCS->generate_key( 15 );
            if( $FUNCS->utf8_check($email) ){
                $email = $FUNCS->utf2code( $email );
            }
            else{
                $tmp = array();
                for( $x=0, $len=strlen($email); $x<$len; $x++ ){
                    $tmp[] = ord( substr($email, $x, 1) );
                }
                $email = $tmp;
            }
            $email = array_merge( array(109, 97, 105, 108, 116, 111, 58), $email ); // prepend 'mailto:'

            $var_title = 'v' . $FUNCS->generate_key( 15 );
            if( $FUNCS->utf8_check($title) ){
                $title = $FUNCS->utf2code( $title );
            }
            else{
                $tmp = array();
                for( $x=0, $len=strlen($title); $x<$len; $x++ ){
                    $tmp[] = ord( substr($title, $x, 1) );
                }
                $title = $tmp;
            }

            $var_span = 'v' . $FUNCS->generate_key( 15 );
            $var_output = 'v' . $FUNCS->generate_key( 15 );
            $o = '<span id="'.$var_span.'">'.$msg.'</span>';
            $o .= "<script>\r\n";
            $o .= "(function() {\r\n";
            $o .= 'var '.$var_email.' = [';
            for( $x=0, $len=count($email), $sep=''; $x<$len; $x++ ){
                $val = ( mt_rand(0, 1) ) ? sprintf( "x%x", $email[$x] ) : $email[$x];
                $o .= $sep.'"'.$val.'"';
                $sep = ',';
            }
            $o .= '];';
            $o .= "\r\n";
            $o .= 'var '.$var_title.' = [';
            for( $x=0, $len=count($title), $sep=''; $x<$len; $x++ ){
                $val = ( mt_rand(0, 1) ) ? sprintf( "x%x", $title[$x] ) : $title[$x];
                $o .= $sep.'"'.$val.'"';
                $sep = ',';
            }
            $o .= '];';
            $o .= "\r\n";
            $o .= 'var '.$var_output.' = "<a hr" + "ef=\"";' . "\r\n";
            $o .= "for (var i = 0; i < ".$var_email.".length; i++) ".$var_output." += '&#' + ".$var_email."[i] + ';';\r\n";
            $o .= $var_output." += '\">';\r\n";
            $o .= "for (var i = 0; i < ".$var_title.".length; i++) ".$var_output." += '&#' + ".$var_title."[i] + ';';\r\n";
            $o .= $var_output." += '</a>';\r\n";
            $o .= "document.getElementById('".$var_span."').innerHTML = ".$var_output.";\r\n";
            $o .= "})();\r\n";
            $o .= "</script>";

            return $o;
        }

        // Utility function for debugging purposes
        function log( $params, $node ){
            global $CTX, $FUNCS;
            if( count($node->children) ) {die("ERROR: Tag \"".$node->name."\" is a self closing tag");}

            extract( $FUNCS->get_named_vars(
                        array(
                              'msg'=>'',
                              'file'=>''
                              ),
                        $params)
                   );

            $FUNCS->log( $msg, trim($file) );
            return;
        }

        function paginator( $params, $node ){
            global $CTX, $FUNCS, $PAGE;

            if( !$CTX->get('k_paginator_required') ) return;

            extract( $FUNCS->get_named_vars(
                        array(
                              'simple'=>'0',
                              'position'=>'0',
                              'adjacents'=>'1',
                              'prev_text'=>'',
                              'next_text'=>''
                              ),
                        $params)
                   );

            $simple = ( $simple==1 ) ? 1 : 0;
            $position = ( $position==1 ) ? 1 : 0;
            $adjacents = $FUNCS->is_non_zero_natural( $adjacents ) ? intval( $adjacents ) : 1;
            if( !strlen($prev_text) ) $prev_text = '&#171; prev';
            if( !strlen($next_text) ) $next_text = 'next &#187;';

            $ok = ( $position ) ? $CTX->get( 'k_paginated_top' ) : $CTX->get( 'k_paginated_bottom' );
            if( !$ok ) return;

            $page_link = $CTX->get( 'k_page_being_paginated' );
            $sep = ( strpos($page_link, '?')===false ) ? '?' : '&';
            $qs_param = $CTX->get( 'k_qs_param' );

            $page = $CTX->get( 'k_current_page' );
            $totalitems = $CTX->get( 'k_total_records_for_pagination' );
            $limit = $CTX->get( 'k_paginate_limit' );
            $targetpage = $page_link;
            $pagestring = $sep . $qs_param . "=";

            if( !count($node->children) ){
                return $FUNCS->getPaginationString( $page, $totalitems, $limit, $adjacents, $targetpage, $pagestring, $prev_text, $next_text, $simple );
            }
            else{
                $arr_items = $FUNCS->getPaginationArray( $page, $totalitems, $limit, $adjacents, $targetpage, $pagestring, $prev_text, $next_text, $simple );

                foreach( $arr_items as $item ){
                    $vars = array();
                    $vars['k_crumb_type'] = $item['crumb_type']; // one of these 4: 'next', 'prev', 'ellipsis' or 'page'
                    $vars['k_crumb_link'] = $item['link'];
                    $vars['k_crumb_text'] = $item['text'];
                    $vars['k_crumb_disabled'] = $item['disabled']; // pertinent for only 'next' and 'prev'
                    $vars['k_crumb_current'] = $item['current']; // pertinent for only 'page'
                    $CTX->set_all( $vars );

                    foreach( $node->children as $child ){
                        $html .= $child->get_HTML();
                    }
                }
                return $html;
            }
        }

        function process_comment( $params, $node ){
            global $CTX, $FUNCS, $PAGE;
            if( count($node->children) ) {die("ERROR: Tag \"".$node->name."\" is a self closing tag");}

            $rs = $FUNCS->insert_comment( $params, $node );
            if( $FUNCS->is_error($rs) ){
                $CTX->set( 'k_process_comment_error', $rs->err_msg );
                $CTX->set( 'k_process_comment_success', 0 );
                return;
            }
            $CTX->set( 'k_process_comment_success', 1 );
            $CTX->set( 'k_process_comment_error', 0 );
        }

        // Returns a complete image tag with either the gravatar or default image
        function gravatar( $params, $node ){
            global $CTX, $FUNCS;
            if( count($node->children) ) {die("ERROR: Tag \"".$node->name."\" is a self closing tag");}

            extract( $FUNCS->get_named_vars(
                        array(
                              'email'=>'',
                              'size'=>'',
                              'default'=>'',
                              'link_only'=>'0'
                              ),
                        $params)
                   );

            $email = trim( $email );
            $size = trim( $size );
            $default = trim( $default );
            $link_only = ( $link_only==0 ) ? 0 : 1;

            return $FUNCS->get_gravatar( $email, $size, $default, $link_only );
        }

        function content_type( $params, $node ){
            global $FUNCS, $PAGE;
            if( count($node->children) ) {die("ERROR: Tag \"".$node->name."\" is a self closing tag");}

            extract( $FUNCS->get_named_vars(
                        array(
                              'value'=>''
                              ),
                        $params)
                   );

            $PAGE->content_type = trim( $value );
        }

        function no_cache( $params, $node ){
            global $FUNCS, $PAGE;
            if( count($node->children) ) {die("ERROR: Tag \"".$node->name."\" is a self closing tag");}

            $PAGE->no_cache = 1;
        }

        function gpc( $params, $node ){
            global $FUNCS;
            if( count($node->children) ) {die("ERROR: Tag \"".$node->name."\" is a self closing tag");}

            extract( $FUNCS->get_named_vars(
                        array(
                              'var'=>'',
                              'method'=>'', /* get/post/cookie */
                              'strip_tags'=>'1',
                              'default'=>'',
                              ),
                        $params)
                   );

            $var = trim( $var );
            $method = strtolower( trim($method) );
            if( !in_array($method, array('get', 'post', 'cookie')) ){ $method=''; }
            $strip_tags = ( $strip_tags==0 ) ? 0 : 1;
            $has_default = ( strlen($default) ) ? 1 : 0;

            switch( $method ){
                case 'get':
                    $method = $_GET;
                    $no_xss_check = 1;
                    break;
                case 'post':
                    $method = $_POST;
                    break;
                case 'cookie':
                    $method = $_COOKIE;
                    break;
                default:
                    $method = $_REQUEST;
            }

            if( isset($method[$var]) ){
                $val = $method[$var];
                if( !$no_xss_check ){
                    $val = $FUNCS->cleanXSS( $val );
                    if( $strip_tags ){ $val = strip_tags($val); }
                }
            }
            if( $has_default && !strlen($val) ){ $val = $default; }

            return $val;
        }

        function html_encode( $params, $node ){
            global $FUNCS;

            foreach( $node->children as $child ){
                $html .= $child->get_HTML();
            }
            return htmlspecialchars( $html, ENT_QUOTES, K_CHARSET );
        }

        function not_empty( $params, $node ){
            global $FUNCS;

            if( count($node->children) ) {die("ERROR: Tag \"".$node->name."\" is a self closing tag");}
            return $FUNCS->strlen( trim(@strip_tags($params[0]['rhs'])) ) ? 1 : 0;
        }

        function strlen( $params, $node ){
            global $FUNCS;

            if( count($node->children) ) {die("ERROR: Tag \"".$node->name."\" is a self closing tag");}
            return $FUNCS->strlen( @trim($params[0]['rhs']) );
        }

        function trim( $params, $node ){
            global $FUNCS;

            if( count($node->children) ){
                foreach( $node->children as $child ){
                    $html .= $child->get_HTML();
                }
            }
            else{
                $html = $params[0]['rhs'];
            }

            return trim( $html );
        }

        // Expects a full URL to redirect to (querystring should be urlencoded)
        function redirect( $params, $node ){
            global $FUNCS, $PAGE, $DB;
            if( count($node->children) ) {die("ERROR: Tag \"".$node->name."\" is a self closing tag");}

            extract( $FUNCS->get_named_vars(
                        array(
                              'url'=>'',
                              'permanently'=>'0',
                              'no_external'=>'0'
                              ),
                        $params)
                   );

            $permanently = ( $permanently==1 ) ? 1 : 0;
            $no_external = ( $no_external==1 ) ? 1 : 0;

            $default_dest = K_SITE_URL;
            $url = $FUNCS->sanitize_url( $url, $default_dest, $no_external  );

            ob_get_contents(); // not neccessary but just in case..
            ob_end_clean();
            $DB->commit( 1 ); // force commit, we are redirecting.

            if( $permanently ){
                header( "Location: ".$url, TRUE, 301 );
            }
            else{
                header( "Location: ".$url );
            }
            die();
        }

        function masquerade( $params, $node ){
            global $FUNCS, $PAGE, $DB;
            if( count($node->children) ) {die("ERROR: Tag \"".$node->name."\" is a self closing tag");}

            extract( $FUNCS->get_named_vars(
                        array(
                              'url'=>''
                              ),
                        $params)
                   );

            $url = $FUNCS->sanitize_url( trim($url) );
            if( !preg_match('/^\s*(?:http:\/\/|https:\/\/)/i', $url) ){die("ERROR: Tag \"".$node->name."\" - url must begin with http:// or https://");}
            $FUNCS->masquerade( $url );
        }

        // Sets its enclosed contents as the output of the current page.
        // If has nested 'abort' tags, the output of the deepest one will be used.
        // If multiple 'abort' tags on the same page, the first encountered tag's output will be used.
        // Content is never cached.
        //
        // Output for 404 is explicit 'msg' param or '404.php' file in root or 'Page not found' string.
        function abort( $params, $node ){
            global $FUNCS, $PAGE, $DB;

            extract( $FUNCS->get_named_vars(
                    array(
                          'msg'=>'',
                          'is_404'=>'0',
                          'no_commit'=>'0',
                        ),
                    $params)
                );
            $is_404 = ( $is_404==1 ) ? 1 : 0;
            $no_commit = ( $no_commit==1 ) ? 1 : 0;

            ob_end_clean(); // discard all previous content..

            $html = '';
            if( count($node->children) ){
                foreach( $node->children as $child ){
                    $html .= $child->get_HTML();
                }
            }
            else{ // self-closing
                $html = $msg;
            }

            if( !$no_commit ){
                $DB->commit( 1 ); // force commit, we are finished.
            }

            if( $is_404 ){
                header('HTTP/1.1 404 Not Found');
                header('Status: 404 Not Found');
                if( !strlen(trim($html)) ){
                    $html='';
                    if( file_exists(K_SITE_DIR . '404.php') ){
                        $html = $FUNCS->file_get_contents( K_SITE_URL . '404.php' );
                    }
                    if( !$html ) $html = 'Page not found';
                }
            }
            $content_type = ( $PAGE->content_type ) ? $PAGE->content_type : 'text/html';
            $content_type_header = 'Content-Type: '.$content_type.';';
            $content_type_header .= ' charset='.K_CHARSET;
            header( $content_type_header );
            echo $html;

            die;

        }

        function calendar( $params, $node ){
            global $FUNCS, $CTX;
            extract( $FUNCS->get_named_vars(
                        array('masterpage'=>'',
                              'date'=>'',
                              'week_starts'=>'0' /* 0(Sun) to 6(Sat) */
                              ),
                        $params)
                   );

            // sanitize params
            $masterpage = trim( $masterpage );
            $date = trim($date);
            if( $date=='' ){
                $date = $FUNCS->get_current_desktop_time();
            }
            else{
                $date = $FUNCS->make_date( $date );
            }

            $week_starts = trim( $week_starts );
            if( !$FUNCS->is_natural( $week_starts ) || $week_starts > 6 ){
                die("ERROR: Parameter 'week_starts' in Tag \"".$node->name."\" has to be value from '0' to '6' where '0' is 'Sunday'");
            }
            $week_starts = intval( $week_starts );

            // Split date into components
            $date = @strtotime( $date );
            $year = date( 'Y', $date );
            $month = date( 'm', $date );
            $day = date( 'd', $date );

            $start_date = date('Y-m-d', @mktime(0, 0, 0, $month, 1, $year) );
            $end_date = date('Y-m-d', @mktime(0, 0, 0, $month+1, 1, $year) );

            $arr_recs = array();
            if( $masterpage ){
                for( $x=0; $x<count($params); $x++ ){
                    $param = &$params[$x];
                    $param_name = strtolower($param['lhs']);
                    if( $param_name=='start_on' ){
                        $param['rhs']=$start_date;
                        $start_date_done=1;
                    }
                    elseif( $param_name=='stop_before' ){
                        $param['rhs']=$end_date;
                        $stop_date_done=1;
                    }
                    unset( $param );
                }
                if( !$start_date_done ){
                    $params[] = array('lhs'=>'start_on', 'op'=>'=', 'rhs'=>$start_date);
                }
                if( !$stop_date_done ){
                    $params[] = array('lhs'=>'stop_before', 'op'=>'=', 'rhs'=>$end_date);
                }

                // delegate to 'pages' tag to get records
                $recs = $this->pages( $params, $node, 3 );

                if( count($recs) ){
                    foreach( $recs as $rec ){
                        $rec_day = date( 'd', @strtotime($rec['publish_date']) );
                        if( !array_key_exists($rec_day, $arr_recs) ){
                            $arr_recs[$rec_day] = array();
                        }
                        $arr_recs[$rec_day][count($arr_recs[$rec_day])] = $rec;
                    }
                }
            }

            // Get first date of the month we are building the calander
            $first_date = @mktime(0, 0, 0, $month, 1, $year);

            // Which day of the week (in number) does this first date fall on?
            $first_day = date( 'w', $first_date );
            // Adjust it according to the 'week_starts' parameter
            $first_day = $first_day - $week_starts;
            if( $first_day < 0 ) $first_day = 7 + $first_day;

            // How many days are there in this month?
            $days_in_month = date( 't', $first_date );

            $weeks = array();

            // Start the first week. Handle days before the first day of this month
            $weeks[0] = array();
            $week = &$weeks[0];
            $week['days'] = array();
            for( $week_day=0; $week_day<$first_day; $week_day++ ){
                $day_date = @mktime(0, 0, 0, $month, -($first_day-($week_day+1)), $year);
                $this->_add_day_to_week( $week, $week_day, $day_date, -1, $arr_recs );
            }

            // Handle actual days of the month
            for( $x=1; $x<=$days_in_month; $x++ ){
                $day_date = @mktime(0, 0, 0, $month, $x, $year);
                $this->_add_day_to_week( $week, $week_day, $day_date, 0, $arr_recs );

                $week_day++;
                if( $week_day==7 ){
                    $week_day=0;

                    // Start a new week
                    $week_num = count($weeks);
                    $weeks[$week_num] = array();
                    $week = &$weeks[$week_num];
                    $week['days'] = array();
                }
            }

            // Fill up days remaining in last week after the last day of the month
            if( $week_day ){
                $days_remaining = 7-$week_day;
                for( $x=1; $x<=$days_remaining; $x++ ){
                    $day_date = @mktime(0, 0, 0, $month, $days_in_month+$x, $year);
                    $this->_add_day_to_week( $week, $week_day, $day_date, 1, $arr_recs );
                    $week_day++;
                }
            }

            // Time to invoke the child tags
            $CTX->set_object( 'weeks', $weeks );
            $CTX->set( 'k_count_weeks', count($weeks) );

            $cur_date = @mktime(0, 0, 0, $month, 1, $year);
            $CTX->set( 'k_calendar_date', date('Y-m-d', $cur_date ) );
            $CTX->set( 'k_calendar_year', date( 'Y', $cur_date ) );
            $CTX->set( 'k_calendar_month', date( 'm', $cur_date ) );

            $next_date = @mktime(0, 0, 0, $month+1, 1, $year);
            $CTX->set( 'k_next_calendar_date', date('Y-m-d', $next_date ) );
            $CTX->set( 'k_next_calendar_year', date( 'Y', $next_date ) );
            $CTX->set( 'k_next_calendar_month', date( 'm', $next_date ) );

            $prev_date = @mktime(0, 0, 0, $month-1, 1, $year);
            $CTX->set( 'k_prev_calendar_date', date('Y-m-d', $prev_date ) );
            $CTX->set( 'k_prev_calendar_year', date( 'Y', $prev_date ) );
            $CTX->set( 'k_prev_calendar_month', date( 'm', $prev_date ) );

            foreach( $node->children as $child ){
                $html .= $child->get_HTML();
            }

            return $html;
        }

        function _add_day_to_week( &$week, $week_day, $day_date, $status, &$recs ){
            // Add day
            $week['days'][$week_day] = array();
            $day = &$week['days'][$week_day];

            // Add info to it
            $day['status'] = $status; // belongs to next month
            $day['date'] = date('Y-m-d', $day_date );
            $day['year'] = date( 'Y', $day_date );
            $day['month'] = date( 'm', $day_date );
            $day['day'] = date( 'd', $day_date );
            $day['day_of_week'] = date( 'w', $day_date );
            $day['entries'] = array();
            if( $status==0 && count($recs) ){
                if( array_key_exists($day['day'], $recs) ){
                    $day['entries'] = $recs[$day['day']];
                }

            }
        }

        function weeks( $params, $node ){
            global $FUNCS, $CTX;

            // get the weeks array object supplied by calendar tag
            $weeks = &$CTX->get_object( 'weeks', 'calendar' );

            if( is_array($weeks) ){
                $count = count( $weeks );
                for( $x=0; $x<$count; $x++ ){
                    $CTX->set_object( 'days', $weeks[$x]['days'] );
                    $CTX->set( 'k_week_num', $x+1 );

                    foreach( $node->children as $child ){
                        $html .= $child->get_HTML();
                    }
                }
            }
            return $html;
        }

        function days( $params, $node ){
            global $FUNCS, $CTX;

            extract( $FUNCS->get_named_vars(
                        array(
                              'pad_with_zeroes'=>'0'
                              ),
                        $params)
                   );
            $pad_with_zeroes = ( $pad_with_zeroes==1 ) ? 1 : 0;

            // get the days array object supplied by weeks tag
            $days = &$CTX->get_object( 'days', 'weeks' );

            if( is_array($days) ){

                // Today's date for timeline
                $date = @strtotime( $FUNCS->get_current_desktop_time() ); //desktop time
                $cur_year = date( 'Y', $date );
                $cur_month = date( 'm', $date );
                $cur_day = date( 'd', $date );
                $today = mktime( 0, 0, 0, $cur_month, $cur_day, $cur_year );

                $count = count( $days );
                for( $x=0; $x<$count; $x++ ){
                    $day = &$days[$x];
                    $CTX->set( 'k_date', $day['date'] );
                    if( !$pad_with_zeroes ){
                        $CTX->set( 'k_day', intval($day['day']) );
                    }
                    else{
                        $CTX->set( 'k_day', $day['day'] );
                    }
                    $CTX->set( 'k_month', $day['month'] );
                    $CTX->set( 'k_year', $day['year'] );
                    $CTX->set( 'k_day_of_week', $day['day_of_week'] );
                    switch( $day['status'] ){
                        case -1:
                            $status = 'previous_month';
                            break;
                        case 0:
                            $status = 'current_month';
                            break;
                        case 1:
                            $status = 'next_month';
                    }
                    $CTX->set( 'k_position', $status );
                    $CTX->set( 'k_count_entries', count($day['entries']) );
                    $CTX->set_object( 'entries', $day['entries'] );

                    // position of day in timelime (relative to today)
                    $dday = mktime( 0, 0, 0, $day['month'], $day['day'], $day['year'] );
                    if( $today > $dday ){
                        $pos = 'past';
                    }
                    elseif( $today < $dday ){
                        $pos = 'future';
                    }
                    else{
                        $pos = 'present';
                    }
                    $CTX->set( 'k_timeline_position', $pos );
                    unset( $day );

                    foreach( $node->children as $child ){
                        $html .= $child->get_HTML();
                    }

                }
            }
            return $html;
        }

        function entries( $params, $node ){
            global $FUNCS, $CTX;

            extract( $FUNCS->get_named_vars(
                        array(
                              'limit'=>'',
                              'skip_custom_fields'=>'0'
                              ),
                        $params)
                   );

            $limit = $FUNCS->is_non_zero_natural( $limit ) ? intval( $limit ) : 1000; //Practically unlimited.
            $skip_custom_fields = ( $skip_custom_fields==1 ) ? 1 : 0;

            // get the entries array object supplied by days tag
            $entries = &$CTX->get_object( 'entries', 'days' );

            if( is_array($entries) ){
                $count = count( $entries );
                $limit = ($limit < $count) ? $limit: $count;
                for( $x=0; $x<$limit; $x++ ){
                    $entry = &$entries[$x];
                    $pg = new KWebpage( $entry['template_id'], $entry['id'], 0, 0, $skip_custom_fields );
                    if( $pg->error ){
                        ob_end_clean();
                        die( 'ERROR: ' . $pg->err_msg );
                    }
                    $pg->set_context();

                    foreach( $node->children as $child ){
                        $html .= $child->get_HTML();
                    }
                }
            }
            return $html;
        }

        function number_format( $params, $node ){
            global $FUNCS;

            if( count($node->children) ) {die("ERROR: Tag \"".$node->name."\" is a self closing tag");}

            extract( $FUNCS->get_named_vars(
                        array(
                              'number'=>'',
                              'decimal_precision'=>'2', /* default 2 digit after decimal point */
                              'decimal_character'=>'.', /* char used to denote decimal */
                              'thousands_separator'=>','
                              ),
                        $params)
                   );
            $number = trim( $number );
            $decimal_precision = trim( $decimal_precision );
            if( !is_numeric($decimal_precision) ) $decimal_precision = 2;
            $decimal_character = trim( $decimal_character );

            $html = number_format( (float)$number, $decimal_precision, $decimal_character, $thousands_separator );

            return $html;
        }

        function size_format( $params, $node ){
            global $FUNCS;
            if( count($node->children) ) {die("ERROR: Tag \"".$node->name."\" is a self closing tag");}

            extract( $FUNCS->get_named_vars(
                        array(
                              'bytes'=>''
                              ),
                        $params)
                   );
            $bytes = abs( intval($bytes) );

            if( $bytes <= 1024 ){
                $html = $bytes.' Bytes';
            }
            elseif( $bytes <= (1024*1024) ){
                $html = sprintf( '%d KB',(int)($bytes/1024) );
            }
            elseif( $bytes <= (1024*1024*1024) ){
                $html = sprintf( '%.2f MB',($bytes/(1024*1024)) );
            }
            else{
                $html = sprintf( '%.2f GB',($bytes/(1024*1024*1024)) );
            }

            return $html;
        }

        // Sets a 'http_only' cookie with the given name, value and positive expiration time.
        // The 'path' is always hard-coded to the site being managed by Couch and so is the domain.
        // The expiration value is in seconds. It cannot be negative and hence
        // this function cannot be used to delete a cookie (use delete_cookie for that).
        function set_cookie( $params, $node, $create=0 ){
            global $FUNCS, $AUTH;
            if( count($node->children) ) {die("ERROR: Tag \"".$node->name."\" is a self closing tag");}

            extract( $FUNCS->get_named_vars(
                        array(
                               'name'=>'',
                               'value'=>'',
                               'expire'=>'0'
                              ),
                        $params)
                   );

            // sanitize params
            $name = trim( $name );
            $value = trim( $value );
            if( $value=='' ) $value='0'; // empty value will trigger deletion
            $expire = $FUNCS->is_natural( $expire ) ? $expire : 0;
            if( $expire != 0 ) $expire = time() + $expire;

            if( version_compare(phpversion(), '5.2.0', '>=') ) {
                setcookie( $name, $value, $expire, $AUTH->cookie_path, null, K_HTTPS ? true : null, true );
            }
            else{
                header( "Set-Cookie: ".rawurlencode($name)."=".rawurlencode($value)."; path=$AUTH->cookie_path; httpOnly".(K_HTTPS ? "; Secure" : "") );
            }
        }

        function get_cookie( $params, $node, $create=0 ){
            global $FUNCS;
            if( count($node->children) ) {die("ERROR: Tag \"".$node->name."\" is a self closing tag");}

            extract( $FUNCS->get_named_vars(
                        array(
                               'name'=>'',
                               'default'=>'',
                              ),
                        $params)
                   );
            $name = trim( $name );
            $has_default = ( strlen($default) ) ? 1 : 0;

            if( isset($_COOKIE[$name][0]) ){
                $val = $FUNCS->cleanXSS( $_COOKIE[$name] );
            }
            if( $has_default && !strlen($val) ){ $val = $default; }

            return $val;
        }

        function delete_cookie( $params, $node, $create=0 ){
            global $FUNCS, $AUTH;
            if( count($node->children) ) {die("ERROR: Tag \"".$node->name."\" is a self closing tag");}

            extract( $FUNCS->get_named_vars(
                        array(
                               'name'=>''
                              ),
                        $params)
                   );
            $name = trim( $name );

            if( version_compare(phpversion(), '5.2.0', '>=') ) {
                setcookie( $name, ' ', time() - (3600 * 24 * 365), $AUTH->cookie_path, null, K_HTTPS ? true : null, true );
            }
            else{
                setcookie( $name, ' ', time() - (3600 * 24 * 365), $AUTH->cookie_path, null, K_HTTPS ? true : null );
            }
        }

        function nl2br( $params, $node ){
            global $FUNCS;

            // call the children
            foreach( $node->children as $child ){
                $html .= $child->get_HTML();
            }

            return nl2br( $html );
        }

        function addslashes( $params, $node ){
            global $FUNCS;
            extract( $FUNCS->get_named_vars(
                        array(
                               'quote'=>''
                              ),
                        $params)
                   );
            $quote = strtolower( trim($quote) );

            // call the children
            foreach( $node->children as $child ){
                $html .= $child->get_HTML();
            }

            if( $quote=='single' ){
                $html = str_replace( "'", "\'", $html );
            }
            else{ // default is double-quote
                $html = str_replace( '"', '\"', $html );
            }
            return $html;
        }

        function localize( $params, $node ){
            global $FUNCS;
            extract( $FUNCS->get_named_vars(
                        array(
                               'term'=>''
                              ),
                        $params)
                   );
            $term = trim( $term );

            if( count($node->children) ) {die("ERROR: Tag \"".$node->name."\" is a self closing tag");}
            return $FUNCS->t( $term );
        }

        function translate_icon( $params, $node ){ // translate icon name to current theme's icon-set
            global $FUNCS;
            extract( $FUNCS->get_named_vars(
                        array(
                               'name'=>''
                              ),
                        $params)
                   );
            $name = trim( $name );

            if( count($node->children) ) {die("ERROR: Tag \"".$node->name."\" is a self closing tag");}
            return $FUNCS->ti( $name );
        }

        function show_icon( $params, $node ){ // show rendered icon
            global $FUNCS;
            extract( $FUNCS->get_named_vars(
                        array(
                               'name'=>''
                              ),
                        $params)
                   );
            $name = trim( $name );

            if( count($node->children) ) {die("ERROR: Tag \"".$node->name."\" is a self closing tag");}
            return $FUNCS->get_icon( $name );
        }

        function show_info( $params, $node ){
            global $FUNCS;
            extract( $FUNCS->get_named_vars(
                        array(
                            'heading'=>'',
                            'center'=>'',
                            ),
                        $params)
                   );
            $heading = trim( $heading );

            $content = '';
            foreach( $node->children as $child ){
                $content .= $child->get_HTML();
            }

            // alert type
            if( $node->name=='show_success' ){
                $type = 'success';
            }
            elseif($node->name=='show_error' ){
                $type = 'error';
            }
            elseif($node->name=='show_warning' ){
                $type = 'warning';
            }
            else{
                $type = 'info';
            }

            return $FUNCS->show_alert( $heading, $content, $type, $center );
        }

        function show_success( $params, $node ){
            return $this->show_info( $params, $node ); // delegate to 'cms:show_info' tag
        }

        function show_error( $params, $node ){
            return $this->show_info( $params, $node ); // delegate to 'cms:show_info' tag
        }

        function show_warning( $params, $node ){
            return $this->show_info( $params, $node ); // delegate to 'cms:show_info' tag
        }

        function do_shortcodes( $params, $node ){
            global $FUNCS, $CTX;

            // call the children
            foreach( $node->children as $child ){
                $html .= $child->get_HTML();
            }

            $CTX->set_object( 'obj_sc', $node ); // store self for $FUNCS->embed() to use.
            $parser = new KBBParser( $html );
            return $parser->get_HTML();
            //return $parser->get_info();
        }

        function exif( $params, $node ){
            global $FUNCS, $CTX;

            // get the compound exif data array from CTX
            $meta = &$CTX->get_object( 'k_file_meta' );

            // and set each item as a simple variable
            if( $meta ){
                $arr_meta = $FUNCS->unserialize( $meta );
                if( is_array($arr_meta) ){
                    $vars = array();
                    foreach( $arr_meta as $k=>$v ){
                        $vars['exif_'.strtolower($k)] = $FUNCS->cleanXSS( $v );
                    }

                    if( !count($node->children) ){
                        $CTX->set_all( $vars, 'global' ); // if self-closing - set in global scope.
                        return;
                    }
                    else{
                        $CTX->set_all( $vars ); // if tag-pair - set variables in self scope.
                    }
                }
            }

            // call the children
            foreach( $node->children as $child ){
                $html .= $child->get_HTML();
            }

            return $html;
        }

        // A workaround to compensate for the lack of support to 'thumbnail' editable region in 'repeatable' tag
        function thumbnail( $params, $node ){
            global $FUNCS, $Config;

            require_once( K_COUCH_DIR.'includes/timthumb.php' );

            extract( $FUNCS->get_named_vars(
                        array(
                               'src'=>'',
                               'width'=>'',
                               'height'=>'',
                               'enforce_max'=>'0',
                               'quality'=>'80',
                              ),
                        $params)
                   );

            $src = trim( $src );
            if( !$src ) return;
            $dest = null;
            $width = abs( (int)$width );
            $height = abs( (int)$height );
            $enforce_max = ( $enforce_max==1 ) ? 1 : 0;
            $crop = !$enforce_max;
            $quality = (int)$quality;
            if( $quality<=0 ){ $quality='80'; } elseif( $quality>100 ){ $quality='100'; }

            // Make sure the source image lies within our upload image folder
            $domain_prefix = $Config['k_append_url'] . $Config['UserFilesPath'] . 'image/';
            if( strpos($src, $domain_prefix)===0 ){ // process image only if local
                $orig_src = $src;
                $src = substr( $src, strlen($domain_prefix) );
                if( $src ){
                    $src = $Config['UserFilesAbsolutePath'] . 'image/' . $src;

                    // Call timthumb to create thumbnail
                    $thumbnail = k_resize_image( $src, $dest, $width, $height, $crop, $enforce_max, $quality, 'middle' /*crop from*/, 1/*check if thumb exists*/ );
                    if( $FUNCS->is_error($thumbnail) ){
                        return 'ERROR: ' . $thumbnail->err_msg;
                    }
                    $path_parts = $FUNCS->pathinfo( $orig_src );
                    return  $path_parts['dirname'] . '/' . $thumbnail;
                }
            }
            else{
                return 'ERROR: Can only create thumbnails of images that are found within or below '. $domain_prefix;
            }
        }

        function validate( $params, $node ){
            global $FUNCS;

            extract( $FUNCS->get_named_vars(
                        array(
                               'value'=>'',
                               'validator'=>'',
                               'separator'=>'',
                               'val_separator'=>'',
                               'case_sensitive'=>'0',
                              ),
                        $params)
                   );

            $case_sensitive = ( $case_sensitive==1 ) ? 1 : 0;
            $validator = trim( $validator );
            if( !$case_sensitive ){ $validator = strtolower( $validator ); }
            if( !strlen($validator) ) {die("ERROR: Tag \"".$node->name."\" requires a 'validator' parameter");}
            $separator = trim( $separator );
            if( !strlen($separator) ) $separator = '|';
            $val_separator = trim( $val_separator );
            if( !strlen($val_separator) ) $val_separator = '=';

            // multiple validators?
            $arr_validator_elems = array_map( "trim", explode( $separator, $validator ) );
            foreach( $arr_validator_elems as $validator_elem ){
                $args = array_map( "trim", explode( $val_separator, $validator_elem ) );
                $arr_validators[$args[0]] = $args[1];
            }

            // get down to business..
            // validator routines in $FUNCS expect a field as param so we create a dummy field.
            $field_info = array(
                'id' => -1,
                'name' => 'dummy',
                'k_type' => 'text',
                'data' => $value,
            );
            $f = new KFieldForm( $field_info, new stdClass() );

            foreach( $arr_validators as $validator=>$validator_args ){
                if( array_key_exists($validator, $f->available_validators) ){
                    $validator_func = $f->available_validators[$validator];
                }
                elseif( array_key_exists($validator, $FUNCS->validators) ){
                    $validator_func = $FUNCS->validators[$validator];
                }
                else{
                    $validator_func = trim( $validator ); // allow user defined validator
                }

                $validator_func = $FUNCS->is_callable( $validator_func );
                if( !$validator_func ){
                    die( "ERROR: Tag \"".$node->name."\" - Validator '".$validator."' not found" );
                }
                $err = call_user_func_array( $validator_func, array(&$f, $validator_args) );

                if( $FUNCS->is_error($err) ){
                    return '0';
                }
            }

            return '1';
        }

        function random_name( $params, $node ){
            global $AUTH;
            if( count($node->children) ) {die("ERROR: Tag \"".$node->name."\" is a self closing tag");}

            return md5( $AUTH->hasher->get_random_bytes(16) );
        }

        function is_ajax( $params, $node ){
            global $FUNCS;

            if( $FUNCS->is_ajax() ){
                return '1';
            }
            return '0';
        }

        function k_extends( $params, $node ){
            global $FUNCS, $CTX;

            if( !$node->processed ) return;

            $level = count($CTX->ctx)-1;

            // if not called from another cms:extends, create a scope for storing blocks
            if( $level<2 || $CTX->ctx[$level-2]['name']!='extends' ){
                $CTX->ctx[$level]['_obj_'] = array();
                $is_root = 1;
            }

            $act = strtolower( trim($params[0]['lhs']) );
            $snippet = $params[0]['rhs'];
            if( $snippet ){

                // default is embed 'file' from snippet folder
                if( $act=='code' ){
                    $html = $snippet;
                }
                else{
                    if( defined('K_SNIPPETS_DIR') ){ // always defined relative to the site
                        $base_snippets_dir = K_SITE_DIR . K_SNIPPETS_DIR . '/';
                    }
                    else{
                        $base_snippets_dir = K_COUCH_DIR . 'snippets/';
                    }

                    $filepath = $base_snippets_dir . ltrim( trim($snippet), '/\\' );
                    $html = @file_get_contents( $filepath );
                    if( $html===FALSE ){
                        if( $has_context ) $CTX->pop();
                        return 'Error embedding file: ' . htmlspecialchars( $filepath );
                    }
                }

                if( $html ){
                    // set blocks in object scope before parsing the template being inherited
                    $blocks_set = array();
                    foreach( $node->children as $block ){

                        $block_params = $FUNCS->resolve_parameters( $block->attributes );
                        $block_name = trim( $block_params[0]['rhs'] );
                        if( $block_name=='' ) die( "ERROR: Tag \"block\": requires a 'name' parameter" );
                        if( in_array($block_name, $blocks_set) ) die( "ERROR: block '$block_name' already defined." );

                        if( $is_root ){
                            $CTX->ctx[$level]['_obj_'][$block_name] = array( $block );
                        }
                        else{
                            $obj = &$CTX->get_object( $block_name, 'extends', 1 );
                            if( is_null($obj) ){
                                $obj = array($block);
                                $CTX->set_object( $block_name, $obj );
                            }
                            else{
                                $obj[count($obj)-1]->parent_block = $block;
                                $obj[] = $block;
                            }
                            unset( $obj );
                        }
                        $blocks_set[] = $block_name;
                    }

                    // now call parent template
                    $parser = new KParser( $html );
                    $html = $parser->get_HTML();
                }

                return $html;
            }
        }

        function block( $params, $node ){
            global $CTX;

            $block_name = trim( $params[0]['rhs'] );
            if( $block_name=='' ) die( "ERROR: Tag \"block\": requires a 'name' parameter" );

            // set self in blocks queue
            $obj = &$CTX->get_object( $block_name, 'extends', 1 );
            if( is_null($obj) ){
                $node->final = 1;
                $obj = array($node);
                $CTX->set_object( $block_name, $obj, '', 'extends' );
                $block = $node;
            }
            else{
                if( $obj[count($obj)-1]->ID!=$node->ID ){ // if not already in queue
                    if( $obj[count($obj)-1]->final ) die( "ERROR: block '$block_name' already defined." );

                    $node->final = 1;
                    $obj[count($obj)-1]->parent_block = $node;
                    $obj[] = $node;
                }
                $block = $obj[0];
            }
            unset( $obj );

            // show the first block in queue..
            // but before that push the block on stack for any cms:block_parent that might follow
            $level = count($CTX->ctx)-1;
            $CTX->ctx[$level]['_obj_'] = array( 'cur_block'=>$block );

            foreach( $block->children as $child ){
                $html .= $child->get_HTML();
            }
            return $html;
        }

        function block_parent( $params, $node ){
            global $CTX;

            // find the current block being executed
            $block = &$CTX->get_object( 'cur_block', 'block', 1 );
            if( !is_null($block) ){
                $parent_block = $block->parent_block;
                if( !is_null($parent_block) ){

                    // show the parent block..
                    // but before that push the block on stack for any cms:block_parent that might follow
                    $CTX->push( 'block' );
                    $level = count($CTX->ctx)-1;
                    $CTX->ctx[$level]['_obj_'] = array( 'cur_block'=>$parent_block );

                    foreach( $parent_block->children as $child ){
                        $html .= $child->get_HTML();
                    }

                    $CTX->pop();
                }
            }

            return $html;
        }

        // aux. tag to list options defined for dropdown, checkbox and radio editable regions
        function list_options( $params, $node ){
            global $FUNCS, $CTX;

            $field_type = $CTX->get( 'k_field_type' );
            if( !in_array($field_type, array('dropdown', 'radio', 'checkbox')) ) return;

            $opt_values = trim( $CTX->get('k_field_opt_values') );
            $separator = trim( $CTX->get('k_field_separator') );
            $val_separator = trim( $CTX->get('k_field_val_separator') );
            if( !strlen($opt_values) || !strlen($separator) || !strlen($val_separator) ) return;

            $selected = $CTX->get( 'k_field_selected_value' );

            if( $field_type=='checkbox' ){
                //$selected = ( $selected != '' ) ? array_map( "trim", explode( $separator, $selected ) ) : array();
                $selected = ( $selected != '' ) ? array_map( "trim", preg_split( "/(?<!\\\)\\".$separator."/", $selected ) ) : array();
                for( $x=0; $x<count($selected); $x++ ){
                    $selected[$x] = str_replace( '\\'.$separator, $separator, $selected[$x] ); //unescape
                    // not necessary to escape the selected values but no harm in doing so
                    $selected[$x] = str_replace( '\\'.$val_separator, $val_separator, $selected[$x] );
                }
            }

            // loop through the opt_values
            $count = $opt_count = 0;
            $arr_values = array_map( "trim", preg_split( "/(?<!\\\)\\".$separator."/", $opt_values ) );
            $arr_vars = array();

            foreach( $arr_values as $val ){
                $opt_is_selected = 0;
                $arr_vars['k_option_count'] = $opt_count;
                $arr_vars['k_count'] = $count;

                if( $val!='' ){
                    $val = str_replace( '\\'.$separator, $separator, $val ); //unescape
                    $arr_values_args = array_map( "trim", preg_split( "/(?<!\\\)\\".$val_separator."/", $val ) );
                    $opt = str_replace( '\\'.$val_separator, $val_separator, $arr_values_args[0] ); //unescape
                    if( isset($arr_values_args[1]) ){
                        $opt_val = str_replace( '\\'.$val_separator, $val_separator, $arr_values_args[1] ); //unescape
                    }
                    else{
                        $opt_val = $opt;
                    }

                    if( $field_type=='dropdown' ){
                        if( $opt_val== $selected ) $opt_is_selected = 1;
                    }
                    elseif( $field_type=='radio' ){
                        if( $selected=='' && $opt_count == 0  ){
                            $opt_is_selected = 1; // if no button selected select the first one (RFC1866)
                        }
                        else{
                            if( $opt_val == $selected ) $opt_is_selected = 1;
                        }

                    }
                    elseif( $field_type=='checkbox' ){
                        if( in_array($opt_val, $selected) ) $opt_is_selected = 1;

                        // checkbox can have multiple selections.. escape discrete values for output
                        $opt_val = str_replace( $separator, '\\'.$separator, $opt_val );
                    }

                    $opt_count++;
                    $opt_blank = 0;
                }
                else{
                    // empty option
                    if( $field_type=='dropdown' ){
                        continue;
                    }
                    else{
                        // for radio and checkbox, this will translate to a new line
                        $opt = $opt_val = '';
                        $opt_blank = 1;
                    }
                }

                $arr_vars['k_option'] = $opt;
                $arr_vars['k_option_value'] = $opt_val;
                $arr_vars['k_option_is_selected'] = $opt_is_selected;
                $arr_vars['k_option_is_blank'] = $opt_blank;

                $count++;

                // call the children
                $CTX->set_all( $arr_vars );
                foreach( $node->children as $child ){
                    $html .= $child->get_HTML();
                }
            }

            return $html;
        }

        function render( $params, $node ){
            global $FUNCS, $CTX;
            static $named_args = array();

            if( count($node->children) ) {die("ERROR: Tag \"".$node->name."\" is a self closing tag");}

            $name = trim( $params[0]['rhs'] );
            if( !$name || !is_array($FUNCS->renderables[$name]) || !count($FUNCS->renderables[$name]) ){
                return;
            }
            array_shift( $params ); // lop off the $name
            $args = array();

            if( !array_key_exists($name, $named_args) ){
                // the first entry defines the function signature
                $entry = $FUNCS->renderables[$name][0];
                $callback = ( $entry['template_path'] ) ? $entry['template_ctx_setter'] : $entry['renderable'];
                if( $FUNCS->is_callable($callback, true /* check syntax_only */) ){
                    if( $entry['include_file'] ){ require_once( $entry['include_file'] ); }

                    if( is_array($callback) ){
                        if( is_object($callback[0]) ) $callback[0] = get_class($callback[0]);
                        $reflection = new ReflectionMethod( $callback[0], $callback[1] );
                    }
                    elseif( is_string($callback) ){
                        $reflection = new ReflectionFunction( $callback );
                    }

                    if( $reflection ){
                        $parameters = $reflection->getParameters();
                        foreach( $parameters as $param ){
                            $val = ( $param->isDefaultValueAvailable() ) ? $param->getDefaultValue() : '';
                            $args[$param->name] = $val;
                        }
                    }
                }
                $named_args[$name] = $args;
            }
            else{
                $args = $named_args[$name];
            }

            if( count($args) ){
                $args = array_values( $FUNCS->get_named_vars($args, $params) );

                // resolve values that point to PHP objects (always begin with a '$')
                for( $x=0; $x<count($args); $x++ ){
                    if( $args[$x][0]=='$' ){
                        $obj = $CTX->get_object( substr($args[$x], 1) );
                        if( $obj ) $args[$x] = $obj;
                    }
                }
            }

            // delegate to $FUNCS->render()
            array_unshift( $args, $name );
            $html = call_user_func_array( array($FUNCS, 'render'), $args );

            return $html;
        }

        // Array related tags
        /*
            for non-associative arrays e.g.
            <cms:set rec='["de", "fr", "es"]' is_json='1' />
            <cms:arr_val_exists 'fr' in=rec />
        */
        function arr_val_exists( $params, $node ){
            global $FUNCS;
            if( count($node->children) ) {die("ERROR: Tag \"".$node->name."\" is a self closing tag");}

            extract( $FUNCS->get_named_vars(
                        array( 'val'=>'',
                               'in'=>'',
                              ),
                        $params)
                   );

            return ( is_array($in) && in_array($val, $in) ) ? '1' : '0';
        }

        // shorter alias for cms:arr_val_exists e.g. <cms:is 'fr' in=rec />
        function is( $params, $node ){
            return $this->arr_val_exists( $params, $node );
        }

        /*
            for associative arrays e.g.
            <cms:set rec='{"name":"John", "age":30, "cars":[ "Ford", "BMW", "Fiat" ]}' is_json='1' />
            <cms:arr_key_exists 'cars' in=rec />
        */
        function arr_key_exists( $params, $node ){
            global $FUNCS;
            if( count($node->children) ) {die("ERROR: Tag \"".$node->name."\" is a self closing tag");}

            extract( $FUNCS->get_named_vars(
                        array( 'key'=>'',
                               'in'=>'',
                              ),
                        $params)
                   );

            return ( is_array($in) && array_key_exists($key, $in) ) ? '1' : '0';
        }

        function arr_count( $params, $node ){
            global $FUNCS;
            if( count($node->children) ) {die("ERROR: Tag \"".$node->name."\" is a self closing tag");}

            extract( $FUNCS->get_named_vars(
                        array( 'var'=>'',
                              ),
                        $params)
                   );

            return ( is_array($var) ) ? count($var) : '';
        }

        function func( $params, $node ){
            global $FUNCS, $CTX;

            if( !count($params) || !is_null($params[0]['lhs']) ){ // anonymous function
                $anon = 1;
                $_into = $_scope = '';
                $tmp = array();
                for( $x=0; $x<count($params); $x++ ){
                    $attr = strtolower(trim($params[$x]['lhs']));
                    if( in_array($attr, array('_into', '_scope')) ){
                        $$attr = trim( $params[$x]['rhs'] );
                    }
                    else{
                        $tmp[] = $params[$x];
                    }
                }
                if( !$_into ){ return; }
                $_scope = strtolower( $_scope );
                if( $_scope != '' && ($_scope!='parent' && $_scope!='global' && $_scope!='local') ){
                    die("ERROR: Tag \"".$node->name."\" has unknown scope '" . $_scope. "'. Only 'global', 'local' or 'parent' are valid.");
                }

                $params = $tmp;
            }
            else{
                $name = trim( $params[0]['rhs'] );
                if( !$name ){ ob_end_clean(); die( "ERROR: tag &lt;cms:func /&gt;: Please provide a name for the function being defined" ); }
                if( array_key_exists($name, $FUNCS->funcs) ){ ob_end_clean(); die( "ERROR: tag &lt;cms:func /&gt;: '$name' already registered" ); }
            }

            $func = array();
            $func['code'] = ( count($node->children) ) ? $node->children : array();
            $func['params'] = array();
            for( $x=($anon)?0:1; $x<count($params); $x++ ){
                if( $params[$x]['op']=='=' && $params[$x]['lhs']){
                    $func['params'][$params[$x]['lhs']]=$params[$x]['rhs'];
                }
            }

            if( $anon ){
                $CTX->set( $_into, $func, $_scope );
            }
            else{
                // register function
                $FUNCS->funcs[$name] = $func;
            }
        }

        function call( $params, $node ){
            global $FUNCS, $CTX;

            $html = '';
            if( is_array($params[0]['rhs']) && is_array($params[0]['rhs']['code']) && is_array($params[0]['rhs']['params']) ){ // anonymous function
                $func = $params[0]['rhs'];
                $name = 'anonymous';
            }
            else{
                $name = trim( $params[0]['rhs'] );
                if( !$name ) return;

                if( !array_key_exists($name, $FUNCS->funcs) ){ // function not registered
                    return 'Error: &lt;cms:func /&gt;: "'.$name.'" not available';
                }
                $func = $FUNCS->funcs[$name];
            }

            // execute function ..
            $CTX->push( '__call__', 1 /*no_check*/ );

            array_shift( $params );
            $vars = $FUNCS->get_named_vars( $func['params'], $params );
            $CTX->set_all( $vars );

            $args = $named_args = array();
            for( $x=0; $x<count($params); $x++ ){
                if( $params[$x]['op']=='=' ){
                    if( $params[$x]['lhs'] ){
                        $named_args[$params[$x]['lhs']] = $params[$x]['rhs'];
                    }
                    $args[] = array( 'name'=>($params[$x]['lhs'])?$params[$x]['lhs']:'', 'val'=>$params[$x]['rhs'] );
                }
            }
            $CTX->set( 'k_func', $name );
            $CTX->set( 'k_args', $args );
            $CTX->set( 'k_named_args', $named_args ); // make available original arguments

            foreach( $func['code'] as $child ){
                $html .= $child->get_HTML();
            }

            $CTX->pop();

            return $html;
        }

        // same as call() above but trims the output.. useful while setting variables
        function call_ex( $params, $node ){
            $html = $this->call( $params, $node );

            return trim( $html );
        }

        function func_exists( $params, $node ){
            global $FUNCS;
            if( count($node->children) ) {die("ERROR: Tag \"".$node->name."\" is a self closing tag");}

            $name = trim( $params[0]['rhs'] );
            $res = ( array_key_exists($name, $FUNCS->funcs) ) ? '1' : '0';

            return $res;
        }

        function tag_exists( $params, $node ){
            global $FUNCS, $TAGS;
            if( count($node->children) ) {die("ERROR: Tag \"".$node->name."\" is a self closing tag");}

            $name = trim( $params[0]['rhs'] );
            if( $name=='if' || $name=='else' || $name=='while' || $name=='extends' || $name=='break' || $name=='continue' ) $name = 'k_'.$name;

            $res = ( $name[0]!=='_' && method_exists($TAGS, $name) || array_key_exists($name, $FUNCS->tags) ) ? '1' : '0';

            return $res;
        }

        function escape_json( $params, $node ){
            global $FUNCS;

            // call the children
            foreach( $node->children as $child ){
                $html .= $child->get_HTML();
            }

            $html = $FUNCS->json_encode( $html );
            return $html;
        }

        function conditional_js( $params, $node ){
            global $FUNCS;
            if( count($node->children) ) {die("ERROR: Tag \"".$node->name."\" is a self closing tag");}

            $js = $FUNCS->gen_js_for_conditional_fields( 1 );

            return $js;
        }

        function alt_js( $params, $node ){
            return;
        }
    } //end class KTags
