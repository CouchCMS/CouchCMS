<?php
    if ( !defined('K_COUCH_DIR') ) die(); // cannot be loaded directly

    class KGC{
        private $uid;
        private $use_ipv4 = 0;
        private $method = '';
        private $jobs = null;
        private $end_time;
        private $func_exists;
        var $debug = 1;
        var $config = array();

        function __construct( $uid ){
            global $FUNCS;

            $this->uid = $uid;
            $this->populate_config();

            if( $_SERVER['REQUEST_METHOD']!='POST' ){
                $FUNCS->add_event_listener( 'admin_route_found', array($this, 'set_listeners') ); // wait for route being determined
            }
        }

        function populate_config(){
            $cfg = array();
            if( file_exists(K_COUCH_DIR.'addons/mosaic/gc/config.php') ){
                require_once( K_COUCH_DIR.'addons/mosaic/gc/config.php' );
            }

            $this->config = array_map( "trim", $cfg );
            unset( $cfg );

            // sanatize values
            $this->config['method'] = strtolower( $this->config['method'] );
            if( !in_array($this->config['method'], array('curl', 'stream', 'ajax', 'manual')) ){
                $this->config['method'] = ''; // auto detection
            }

            $this->config['use_ipv4'] = ( $this->config['use_ipv4'] ) ? 1 : 0;

            $memory_limit = intval( $this->config['memory_limit'] );
            if( !$memory_limit ) $memory_limit = 32; // 32M
            $this->config['memory_limit'] = ($memory_limit * 0.9) * 1024 * 1024;
            $this->func_exists = function_exists( 'memory_get_usage' );

            $time_limit = intval( $this->config['time_limit'] );
            if( !$time_limit ) $time_limit = 20; // 20 seconds
            $this->config['time_limit'] = $time_limit;

            $this->config['debug'] = ( $this->config['debug'] ) ? 1 : 0;
        }

        function set_listeners( $route ){
            global $FUNCS;

            if( $route->masterpage != 'gc' ){
                $this->method = $this->get_method();

                if( $this->method != 'manual' ){
                    if( $this->method=='ajax' ){
                        $FUNCS->add_event_listener( 'add_admin_js', array($this, 'check_and_fork') );
                    }
                    else{
                        $FUNCS->add_event_listener( 'alter_final_admin_page_output', array($this, 'check_and_fork') );
                    }
                }
            }
        }

        function get_method(){
            global $FUNCS;

            $available_methods = $FUNCS->get_setting_ex( 'gc_methods_'.$this->uid );

            if( !is_array($available_methods) ){ // first run?
                $available_methods = array();

                if( !$FUNCS->is_error($this->test_method('curl')) ){
                    $available_methods[] = 'curl';
                }
                if( !$FUNCS->is_error($this->test_method('stream')) ){
                    $available_methods[] = 'stream';
                }
                $available_methods[]='ajax';
                $available_methods[]='manual';

                $FUNCS->set_setting_ex( 'gc_methods_'.$this->uid, $available_methods );
            }

            if( $this->config['method'] && in_array($this->config['method'], $available_methods) ){
                $method = $this->config['method'];
            }
            else{
                $method = $available_methods[0];
            }

            return $method;
        }

        function check_and_fork(){
            global $FUNCS, $DB;

            if( !$DB->is_free_lock('lock_gc_'.$this->uid) ) return; // gc process is already running

            // get pending jobs and ask modules if they have other jobs to run asynchronously
            $this->jobs = $this->get_jobs();
            $FUNCS->dispatch_event( 'register_gc_jobs' );

            if( count($this->jobs) ){
                $link = $FUNCS->generate_route( 'gc', 'process', array('nonce'=>$FUNCS->create_nonce('process_gc_'.$this->uid)) );

                // .. fork process asynchronously
                $this->fork( $link, $this->method );
            }
        }

        private function get_jobs(){
            global $DB;

            $jobs = array();
            $rows = $DB->select( K_TBL_SETTINGS, array('*'), 'k_key like "gc_jobs_'.$this->uid.'_%" ORDER BY k_key ASC' );
            if( count($rows) ){
                foreach( $rows as $row ){
                    $jobs[$row['k_key']] = @unserialize( base64_decode($row['k_value']) );
                }
            }

            return $jobs;
        }

        function add_job( $callback, $args=array() ){
            global $FUNCS, $DB;

            if( ($callback = $FUNCS->is_callable($callback)) ){
                $job = array( 'cb'=>$callback, 'args'=>$args );

                // check if job does not already exist
                if( array_search($job, $this->jobs, true)===false ){
                    $key = 'gc_jobs_'.$this->uid.'_'.$FUNCS->real_to_str( microtime(true) ).rand();
                    $FUNCS->set_setting_ex( $key, $job );

                    if( $DB->is_free_lock('lock_gc_'.$this->uid) ){ // if not being called from within the gc process
                        $this->jobs[$key] = $job;
                    }
                }
            }
        }

        function fork( $url, $method, $test=0 ){
            global $FUNCS;

            session_write_close();
            $timeout = 5; // sec
            $ret = "";

            $headers = array();
            $headers[] = 'Accept-Encoding:';
            foreach($_SERVER as $name => $value){
                if( substr($name, 0, 5) == 'HTTP_' ){
                    $headername = str_replace(' ', '-', ucwords(strtolower(str_replace('_', ' ', substr($name, 5)))));
                    if( $headername=='Host' || $headername=='Connection' || $headername=='Accept-Encoding' ) continue;
                    if( defined('K_IS_MY_TEST_MACHINE') ){
                        if( $headername=='Cookie' ) $value = str_replace( 'XDEBUG_SESSION=kksidd', '' , $value );
                    }
                    $headers[] = $headername . ': ' . $value;
                }
            }
            $headers[] = 'Connection: Close';

            if( $method=='curl' ){
                if( extension_loaded('curl') ){
                    if( !$test ) $timeout = 1; // sec

                    $ch = curl_init();
                    curl_setopt( $ch, CURLOPT_URL, $url );
                    curl_setopt( $ch, CURLOPT_HTTPHEADER, $headers );
                    curl_setopt( $ch, CURLOPT_RETURNTRANSFER, 1 );
                    curl_setopt( $ch, CURLOPT_BINARYTRANSFER, 1 );
                    curl_setopt( $ch, CURLOPT_FORBID_REUSE, 1 );
                    curl_setopt( $ch, CURLOPT_FRESH_CONNECT, 1 );
                    curl_setopt( $ch, CURLOPT_TIMEOUT, $timeout );
                    curl_setopt( $ch, CURLOPT_SSL_VERIFYPEER, 0 );
                    curl_setopt( $ch, CURLOPT_SSL_VERIFYHOST, 0 );
                    if( $this->config['use_ipv4'] ){
                        if( defined('CURLOPT_IPRESOLVE') && defined('CURL_IPRESOLVE_V4') ){
                           curl_setopt( $ch, CURLOPT_IPRESOLVE, CURL_IPRESOLVE_V4 );
                        }
                    }
                    $ret = curl_exec( $ch );

                    if( $error = curl_error( $ch ) ){
                        $ret = $FUNCS->raise_error( $error );
                    }

                    curl_close( $ch );
                }
                else{
                    $ret = $FUNCS->raise_error( 'curl unavailable' );
                }
            }
            elseif( $method=='stream' ){
                if( function_exists('stream_socket_client') ){
                    $urlparts = @parse_url( $url );
                    $proto = ( $urlparts['scheme']=='https' ) ? 'ssl://' : 'tcp://'; // note: ssl will fail if !extension_loaded('openssl')
                    $port = ( isset($urlparts['port'] ) && !empty($urlparts['port']) ) ? $urlparts['port'] : ( ($urlparts['scheme']=='https') ? 443 : 80 );
                    $path = ( isset($urlparts['path']) && !empty($urlparts['path']) ) ? $urlparts['path'] : '/';
                    $query = ( isset($urlparts['query']) && !empty($urlparts['query']) ) ? '?'.$urlparts['query'] : '';
                    $host = $urlparts['host'];
                    if( $this->config['use_ipv4'] ){
                        $ipp = @gethostbyname( $host );
                        $ip = ( $ipp!=$host ) ? long2ip( ip2long($ipp) ) : $host;
                    }
                    else{
                        $ip = $host;
                    }

                    $opts = array();
                    if( $urlparts['scheme']=='https' ){
                        $opts['ssl'] = array(
                            'verify_peer' => false,
                            'verify_host' => false,
                            'capture_peer_cert' => false,
                        );
                    }
                    $context = stream_context_create( $opts );

                    if( $fp = @stream_socket_client( $proto.$ip.':'.$port, $errno, $errstr, $timeout, STREAM_CLIENT_CONNECT, $context )){
                        $msg  = 'GET '.$path.$query.' HTTP/1.1' . "\r\n";
                        $msg .= 'Host: ' . $host;
                        if( $port != '80' && $port != '443' ) $msg .= ":" . $port;
                        $msg .= "\r\n";
                        foreach( $headers as $header ){
                            $msg .= $header . "\r\n";
                        }
                        $msg .= "\r\n";

                        if( fwrite($fp, $msg) ){
                            if( $test ){
                                stream_set_timeout( $fp, $timeout );
                                while( !feof($fp) ){
                                    $ret .= fgets( $fp, 1024 );
                                }
                            }
                        }
                        fclose($fp);

                        if( $test ){
                            $ret = (string)substr( $ret, strpos($ret, "\r\n\r\n") + 4 ); // strip headers
                        }
                    }
                    else{
                        $ret = false;
                    }

                    if( $ret === false ){
                        $ret = $FUNCS->raise_error( $errno.': '.$errstr );
                    }
                }
                else{
                    $ret = $FUNCS->raise_error( 'stream_socket_client unavailable' );
                }
            }
            elseif( $method=='ajax' ){
                $ret = $FUNCS->render( 'gc_ajax', $url );
                if( $test ){
                    $ret = 'OK';
                }
            }

            return $ret;
        }

        function process( $nonce ){
            global $FUNCS, $DB;

            ignore_user_abort();
            set_time_limit(0);

            // say goodbye to calling script ..
            // (the calling function times out in 1 sec but this could help shave off a few ms)
            while( ob_get_level() ) ob_end_clean();
            header( "Connection: close", true );
            header( "Content-Encoding: none\r\n" );
            header( "Content-Length: 0", true );
            flush();
            ob_flush();

            session_write_close();

            // backgound processing starts
            $cur_time = microtime( true );
            $this->end_time = $cur_time + $this->config['time_limit'];
            $this->method = $this->get_method();

            $this->jobs = $this->get_jobs();
            $this->process_jobs(); // max run of default 20 seconds

            // backgound processing ends
            $DB->release_lock( 'lock_gc_'.$this->uid );

            // if more jobs to do, fork again ..
            if( $this->method=='curl' || $this->method=='stream' ){
                $this->check_and_fork();
            }

            die();
        }

        function process_jobs(){
            global $FUNCS;

            while( $job = current($this->jobs) ){
                $callable = $FUNCS->is_callable( $job['cb'] );
                if( $callable ){
                    $requeue = call_user_func_array( $callable, array_merge((array)key($this->jobs), $job['args']) ); // prefix process_id to args
                }

                // if job not asking to be kept in queue, remove it
                if( !$callable || !$requeue ){
                    $FUNCS->delete_setting( key($this->jobs) );
                    $this->log( 'Removing from queue: '.key($this->jobs) );
                }

                // before processing next job make sure it is not time to quit
                if( !$this->can_continue() ) break;

                next( $this->jobs );
            }
        }

        function can_continue(){
            // time ok?
            $cur_time = microtime( true );
            if( $cur_time >= $this->end_time ){ return 0; }

            // memory ok?
            $current_memory = memory_get_usage( true );
            if( $this->func_exists && ($current_memory >= $this->config['memory_limit']) ){ return 0; }

            return 1;
        }

        function log( $msg, $file='' ){
            global $FUNCS;

            if( $this->config['debug'] ){
                if( !$file ){
                    $file = 'gc.txt';
                    $FUNCS->log( $msg, $file );
                }
            }
        }

        function test(){
            die( 'OK' );
        }

        function test_method( $name ){
            global $FUNCS;

            $link = $FUNCS->generate_route( 'gc', 'test' ); // test link

            $rs = $this->fork( $link, $name, 1 );
            if( !$FUNCS->is_error($rs) ){
                // check if 'OK' returned
                $rs = trim( $rs );
                if( $rs!='OK' && !preg_match("/\r\nOK\r\n0/", $rs) ){// keeping 'Transfer-Encoding: chunked' in consideration
                    $rs = $FUNCS->raise_error( $rs );
                }
                else{
                    $rs = 'OK';
                }
            }

            return $rs;
        }

        function index(){
            global $FUNCS;

            $FUNCS->set_admin_title( 'GC' );
            $FUNCS->set_admin_subtitle( 'Info', 'cog' );

            // begin test
            $available_methods_orig = $FUNCS->get_setting_ex( 'gc_methods_'.$this->uid );
            $available_methods = array();
            $html = '<h3>Testing all methods ..</h3>';

            // test method 'curl'
            $ret = $this->test_method( 'curl' );
            if( $FUNCS->is_error($ret) ){ $ret = 'ERROR - ' . $ret->err_msg; }
            else{
                $available_methods[] = 'curl';
            }
            $html .= '<b>curl</b>: '.$ret.'<br>';

            // test method 'stream'
            $ret = $this->test_method( 'stream' );
            if( $FUNCS->is_error($ret) ){ $ret = 'ERROR - ' . $ret->err_msg; }
            else{
                $available_methods[] = 'stream';
            }
            $html .= '<b>stream</b>: '.$ret.'<br>';

            // test method 'ajax'
            $ret = $this->test_method( 'ajax' );
            $available_methods[]='ajax';
            $available_methods[]='manual';
            $html .= '<b>ajax</b>: See console for "GC OK"<br>';

            // persist
            if( $available_methods !== $available_methods_orig ){
                $FUNCS->set_setting_ex( 'gc_methods_'.$this->uid, $available_methods );
            }

            $html .= '<br><b>Method being used: </b>: ' . $this->get_method() . ' (can be changed from config)<br><br>';

            return $html;
        }

        // route filters
        function validate_gc( $route ){
            global $FUNCS, $DB;

            // if process already running ..
            if( !$DB->get_lock('lock_gc_'.$this->uid) ) die;

            $nonce = $route->resolved_values['nonce'];
            if( !$FUNCS->check_nonce('process_gc_'.$this->uid, $nonce) ) die;
        }

        // renderable theme functions
        function register_renderables(){
            global $FUNCS;

            $FUNCS->register_render( 'gc_ajax', array('template_path'=>K_ADDONS_DIR.'mosaic/gc/theme/', 'template_ctx_setter'=>array($this, '_render_gc_ajax')) );
        }

        function _render_gc_ajax( $url ){
            global $CTX;

            $CTX->set( 'url', $url );
        }

        // routes
        function register_routes(){
            global $FUNCS;

            $FUNCS->register_route( 'gc', array(
                'name'=>'index',
                'action'=>array($this, 'index'),
            ) );

            $FUNCS->register_route( 'gc', array(
                'name'=>'process',
                'path'=>'process/{:nonce}',
                'constraints'=>array(
                    'nonce'=>'([a-fA-F0-9]{32})',
                ),
                'filters'=>'validate_gc',
                'action'=>array($this, 'process'),
            ) );
            $FUNCS->register_filter( 'validate_gc', array($this, 'validate_gc') );

            $FUNCS->register_route( 'gc', array(
                'name'=>'test',
                'path'=>'test',
                'action'=>array($this, 'test'),
            ) );
        }
    } //end class KGC

    $GC = new KGC( 'core' );

    if( defined('K_ADMIN') ){
        $FUNCS->add_event_listener( 'register_renderables',  array($GC, 'register_renderables') );
        $FUNCS->add_event_listener( 'register_admin_routes', array($GC, 'register_routes') );
    }
