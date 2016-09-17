<?php
    if ( !defined('K_COUCH_DIR') ) die(); // cannot be loaded directly
    require_once( K_COUCH_DIR.'route.php' );

    class KRoutes{

        var $routes = array();
        var $obj_routes = array();
        var $cache_key = 'k_cached_routes';

        function __construct(){
            global $FUNCS;

            $cache_value = @unserialize( base64_decode($FUNCS->get_setting($this->cache_key)) );
            if( is_array($cache_value) ){
                $this->routes = $cache_value;
            }
        }

        function &get_routes( $masterpage ){
            if( $masterpage=='' ) return;
            if( !is_array($this->routes[$masterpage]) ) return array();

            if( !is_array($this->obj_routes[$masterpage]) ){
                $this->obj_routes[$masterpage] = array();

                foreach( $this->routes[$masterpage] as $route ){
                    $this->obj_routes[$masterpage][$route['name']] = new Route(
                        $route['name'],
                        $masterpage,
                        $route['path'],
                        $route['params'],
                        $route['values'],
                        $route['method'],
                        $route['secure'],
                        ( is_null($route['routable']) ) ? true : $route['routable'],
                        $route['is_match'],
                        $route['generate'],
                        $route['filters'],
                        $route['validators']
                    );
                }
            }

            return $this->obj_routes[$masterpage];
        }

        //////// tag handlers //////////////////////////////////////////////////
        function process_route( $params, $node ){
            global $FUNCS, $CTX;

            $routes = &$CTX->get_object( '_tpl_routes', '__ROOT__' );

            if( is_array($routes) ){ // if template is routable

                // create route ..
                $route = array(
                    'name'        => null,
                    'path'        => null,
                    'params'      => null,
                    'values'      => null,
                    'method'      => null,
                    'secure'      => null,
                    'validators'  => null,
                    'generate'    => null,
                    'filters'     => null,
                    /*
                    'routable'    => true,
                    'is_match'    => null,
                    'name_prefix' => null,
                    'path_prefix' => null,
                    */
                );

                $attr = $FUNCS->get_named_vars(
                        array(
                               'name'=>'',
                               'path'=>'',
                               'secure'=>'',
                               'method'=>'',
                               'generate'=>'',
                               'filters'=>'',
                              ),
                        $params);
                extract( $attr );

                $name = trim( $name );
                if( !strlen($name) ){die("ERROR: Tag \"".$node->name."\" - 'name' is a required parameter");}

                $route['name'] = $name;
                $route['path'] = trim( $path );
                $secure = trim( $secure );
                if( strlen($secure) ){
                    $route['secure'] = ( $secure==1 ) ? 1 : 0;
                }
                $method = array_filter( array_map("trim", explode('|', $method)) );
                if( count($method) ) $route['method'] = array_map( "strtoupper", $method );
                $route['generate'] = trim( $generate );
                $route['filters'] = trim( $filters );

                $CTX->set_object( '_tpl_route', $route );
                foreach( $node->children as $child ){
                    $child->get_HTML();
                }

                // add route
                $routes[] = $route;
            }
        }

        function process_route_params( $params, $node ){
            global $FUNCS, $CTX;

            if( count($node->children) ) {die("ERROR: Tag \"".$node->name."\" is a self closing tag");}

            $route = &$CTX->get_object( '_tpl_route', 'route' );

            if( is_array($route) ){
                switch( $node->name ){
                    case 'route_constraints':
                        $name = 'params';
                        break;
                    case 'route_values':
                        $name = 'values';
                        break;
                    case 'route_validators':
                        $name = 'validators';
                        break;
                }

                $route[$name] = array();
                for( $x=0; $x<count($params); $x++ ){
                    $route[$name][trim($params[$x]['lhs'])] = trim( $params[$x]['rhs'] );
                }
            }
        }

        function process_dump_routes( $params, $node ){

            $routes = $this->routes;

            ob_start();
            print_r( $routes );
            $html = '<pre>' . ob_get_contents() . '</pre>';
            ob_end_clean();

            return $html;
        }

        function process_match_route( $params, $node ){
            global $FUNCS, $CTX, $PAGE, $TAGS;

            if( count($node->children) ) {die("ERROR: Tag \"".$node->name."\" is a self closing tag");}

            $attr = $FUNCS->get_named_vars(
                        array(
                               'path'=>'',
                               'masterpage'=>'',
                               'debug'=>'0',
                               'is_404'=>'0',
                              ),
                        $params);
            extract( $attr );

            $path = trim( $path );
            $masterpage = trim( $masterpage );
            $debug = ( $debug==1 ) ? 1 : 0;
            $is_404 = ( $is_404==1 ) ? 1 : 0;

            if( $path=='' ){ $path = $_GET['q']; }
            if( $masterpage=='' ){ $masterpage = $PAGE->tpl_name; }

            $routes = &$this->get_routes( $masterpage );
            if( !count($routes) ){
                if( $debug ){
                    $html = '<pre><b><font color="red">No routes defined.</font></b></pre>';
                }
                return $html;
            }

            $html='<pre>';
            if( $debug ) $html .= 'Path to match: <i>'.htmlspecialchars( $path, ENT_QUOTES, K_CHARSET ).'</i><br/><br/>';

            $found = null;
            foreach( $routes as $route ){
                if( $debug ){
                    $html .= 'Trying route <b>'.$route->name.':</b><br/>';
                    $html .= 'Pattern: <i>' . htmlspecialchars( $route->path, ENT_QUOTES, K_CHARSET ) .'</i><br/>';
                    $html .= 'Regex: <i>' . htmlspecialchars( $route->regex, ENT_QUOTES, K_CHARSET ) .'</i><br/>';
                }

                if( $route->isMatch($path, $_SERVER) ){
                    $found = $route;
                    if( $debug ){
                        $html .= '<b><font color="green">Matched!</font></b> <br/>Following variable(s) will be set:<br/>';
                        $html .= '   k_matched_route = ' . $route->name .'<br />';
                        foreach( $route->values as $k=>$v ){
                            if( $route->wildcard && $k==$route->wildcard ){
                                $wildcard_count = count($route->values[$route->wildcard]);
                                $html .= '   rt_wildcard_count = ' . $wildcard_count .'<br/>';
                                $html .= '   rt_'.$k.' = '.htmlspecialchars( implode('/', $route->values[$route->wildcard]), ENT_QUOTES, K_CHARSET ).'<br/>';
                                for( $x=0; $x<$wildcard_count; $x++ ){
                                    $html .= '   rt_'.$k.'_'.($x+1).' = '.htmlspecialchars( $route->values[$route->wildcard][$x], ENT_QUOTES, K_CHARSET ).'<br/>';
                                }
                            }
                            else{
                                $html .= '   rt_'.$k.' = '.htmlspecialchars( $v, ENT_QUOTES, K_CHARSET ).'<br/>';
                            }
                        }
                        $html .= '<br/>';
                    }
                    break;
                }
                else{
                    if( $debug ){
                        $html .= '<b><font color="red">Failed!</font></b><br/>Reason: ';
                        foreach( $route->debug as $msg ){
                            $html .= $msg.'<br/>';
                        }
                        $html .= '<br/>';
                    }
                }
            }

            if( $found ){
                $route = $found;
                $CTX->set( 'k_matched_route', $route->name, 'global' );

                $vars = array();
                foreach( $route->values as $k=>$v ){
                    if( $route->wildcard && $k==$route->wildcard ){
                        $wildcard_count = count($route->values[$route->wildcard]);

                        $vars['rt_wildcard_count'] = $wildcard_count;
                        $vars['rt_'.$k] = implode('/', $route->values[$route->wildcard]);
                        for( $x=0; $x<$wildcard_count; $x++ ){
                            $vars['rt_'.$k.'_'.($x+1)] = $route->values[$route->wildcard][$x];
                        }
                    }
                    else{
                        $vars['rt_'.$k] = $v;
                    }
                }
                $CTX->set_all( $vars, 'global' );

                // execute filters if any
                $str_filters = $route->filters; // e.g. 'test=1,abc,3 | test2 | test3=xyz'

                if( strlen($str_filters) ){

                    if( $debug ){
                        $html .= 'Following filter(s) will be called:<br/>';
                    }

                    $arr_filters = array_filter( array_map("trim", preg_split( "/(?<!\\\)\\|/", $str_filters )) ); // split on unescaped '|'

                    foreach( $arr_filters as $filter ){
                        $filter = str_replace( '\\|', '|', $filter );

                        // filter has arguments? e.g. 'test=1,2,3'
                        $arr_args = array_filter( array_map("trim", preg_split( "/(?<!\\\)\\=/", $filter )) ); // split on unescaped '='
                        $filter = $arr_args[0];
                        $args = '';
                        if( isset($arr_args[1]) ){
                            $str_args = str_replace( '\\=', '=', $arr_args[1] );

                            // multiple arguments?
                            $arr_args = array_filter( array_map("trim", preg_split( "/(?<!\\\)\\,/", $str_args )) ); // split on unescaped ','
                            for( $x=0; $x<count($arr_args); $x++ ){
                                $args .= " arg_" .($x+1). "='" . str_replace( array("\\,", "'"), array(",", "\'"), $arr_args[$x] ) ."'";
                            }
                            $args = " arg_count='".count($arr_args)."'" . $args;
                        }
                        else{
                            $args = " arg_count='0'";
                        }

                        if( $filter ){
                            $filter = 'filters/'.$filter.'.html';

                            if( !$debug ){
                                $code = "
                                    <cms:set rs= '' />

                                    <cms:capture into='rs' scope='parent'>
                                        <cms:embed '$filter' $args />
                                    </cms:capture>

                                    <cms:php>global \$CTX; \$CTX->set( 'rs', trim(\$CTX->get('rs')) ); </cms:php>

                                    <cms:if rs >
                                        <cms:abort rs is_404 />
                                    </cms:if>
                                ";

                                $parser = new KParser( $code, $node->line_num, 0, '', $node->ID );
                                $parser->get_HTML();
                            }
                            else{
                                $html .= '   ' . $filter . ' (' . htmlspecialchars( $args, ENT_QUOTES, K_CHARSET ) . ' ) <br/>';
                            }
                        }
                    }
                }

            }
            else{
                $CTX->set( 'k_matched_route', '', 'global' );
                if( $debug ){
                    $html .= '<h3><font color="red">No matching route found.</font></h3>';
                }
                elseif( $is_404 ){
                    $params = array( array('lhs'=>'msg', 'op'=>'=', 'rhs'=>''), array('lhs'=>'is_404', 'op'=>'=', 'rhs'=>'1') );
                    $TAGS->abort( $params, $node );
                }
            }

            $html.='</pre>';

            if( $debug ){
                return $html;
            }
        }


        function process_route_link( $params, $node ){
            global $FUNCS, $DB, $CTX;

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

            if( $masterpage=='' ){ $masterpage = $CTX->get('k_template_name'); }
            if( $masterpage=='' || $name=='' ){ return '#'; } // No masterpage or route name - no link

            // get route object
            $routes = &$this->get_routes( $masterpage );
            $route = $routes[$name];
            if( !$route ){ return '#'; }

            $values = array();
            for( $x=0; $x<count($params); $x++ ){
                $k = trim( $params[$x]['lhs'] );
                if( substr($k, 0, 3)=='rt_' ){
                    $values[substr($k, 3)] = trim( $params[$x]['rhs'] );
                }
            }

            $q = $route->generate( $values );

            if( K_PRETTY_URLS ){
                return K_SITE_URL . $FUNCS->get_pretty_template_link( $masterpage ) . $q;
            }
            else{
                $link = K_SITE_URL . $masterpage;
                if( strlen($q) ){ $link .= '?q=' . $q; }
                return $link;
            }

        }

        //////// event listeners ///////////////////////////////////////////////
        function add_template_param( &$attr_custom, $params, $node ){
            global $FUNCS, $CTX;

            $attr = $FUNCS->get_named_vars(
                array(
                    'routable'=>'0',
                ),
                $params
            );
            extract( $attr );

            $attr['routable'] = ( $routable==1 ) ? 1 : 0;

            // merge with existing custom params
            $attr_custom = array_merge( $attr_custom, $attr );

            // if template is routable, prepare ground for cms:route tags that may be present
            if( $routable ){
                $routes = array();
                $CTX->set_object( '_tpl_routes', $routes, 'global' );
            }

        }

        function persist_routes(){
            global $FUNCS, $PAGE, $CTX;

            // if template is routable, persist routes if modified
            if( $PAGE->tpl_routable ){
                $routes = (array)$CTX->get_object( '_tpl_routes', '__ROOT__' );

                // routes modified?
                $orig_routes = (array)$this->routes[$PAGE->tpl_name];
                if( $routes != $orig_routes ){
                    if( count($routes) ){
                        $this->routes[$PAGE->tpl_name]= $routes;
                    }
                    else{
                        unset( $this->routes[$PAGE->tpl_name] );
                    }
                    $cache_value = base64_encode( serialize($this->routes) );
                    $FUNCS->set_setting( $this->cache_key, $cache_value );
                }

            }

        }

        function delete_routes( $rec ){
            global $FUNCS;

            $tpl_name = $rec['name'];
            if( isset($this->routes[$tpl_name]) ){
                unset( $this->routes[$tpl_name] );
                $cache_value = base64_encode( serialize($this->routes) );
                $FUNCS->set_setting( $this->cache_key, $cache_value );
            }
        }

        function skip_qs_params( &$arr_skip_qs ){
            $arr_skip_qs[] = 'q';
        }

    }// end class

    $KROUTES = new KRoutes();

    // register custom tags
    $FUNCS->register_tag( 'route', array($KROUTES, 'process_route'), 1, 0 );
    $FUNCS->register_tag( 'route_constraints', array($KROUTES, 'process_route_params') );
    $FUNCS->register_tag( 'route_values', array($KROUTES, 'process_route_params') );
    $FUNCS->register_tag( 'route_validators', array($KROUTES, 'process_route_params') );
    $FUNCS->register_tag( 'dump_routes', array($KROUTES, 'process_dump_routes'), 1, 1 );
    $FUNCS->register_tag( 'match_route', array($KROUTES, 'process_match_route'), 1, 0 );
    $FUNCS->register_tag( 'route_link', array($KROUTES, 'process_route_link'), 1, 0 );

    // hook events
    $FUNCS->add_event_listener( 'add_template_params', array($KROUTES, 'add_template_param') );
    $FUNCS->add_event_listener( 'template_tag_end', array($KROUTES, 'persist_routes') );
    $FUNCS->add_event_listener( 'template_deleted', array($KROUTES, 'delete_routes') );
    $FUNCS->add_event_listener( 'skip_qs_params_in_paginator', array($KROUTES, 'skip_qs_params') );
