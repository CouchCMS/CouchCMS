<?php

    if ( !defined('K_ADMIN') ) die(); // cannot be loaded directly

    class InlineEx{

        function InlineEx(){
            if( isset($_GET['act']{0}) ){
                $action = $_GET['act'];
                if( in_array($action, array('edit', 'create', 'delete')) ){
                    $this->$action();
                }
                else{
                    die( 'Unknown action' );
                }
            }
            else{
                die( 'No action was specified' );
            }
        }

        function edit(){
            global $FUNCS, $PAGE, $CTX;

            $tpl_id = ( isset($_GET['tpl']) && $FUNCS->is_non_zero_natural($_GET['tpl']) ) ? (int)$_GET['tpl'] : null;
            if( is_null($tpl_id) ) die( 'No template specified' );

            $page_id = ( isset($_GET['p']) && $FUNCS->is_non_zero_natural($_GET['p']) ) ? (int)$_GET['p'] : null;
            $obj_id = ( $page_id ) ? $page_id : $tpl_id;
            $FUNCS->validate_nonce( 'edit_page_' . $obj_id );

            $is_ajax = ( isset($_GET['ajax']) && $_GET['ajax']=='1' ) ? 1 : 0; // if called from 'cms:inline_link'

            $PAGE = new KWebpage( $tpl_id, $page_id );
            if( $PAGE->error ){
                ob_end_clean();
                die( 'ERROR: ' . $PAGE->err_msg );
            }

            // get fields to render
            $arr_fields = array_flip( array_filter(array_map("trim", explode('|', $_GET['flist']))) );
            if( !count($arr_fields) ) die( 'No Fields specified' );
            $requires_multipart = 0;
            for( $x=0; $x<count($PAGE->fields); $x++ ){
                $f = &$PAGE->fields[$x];
                if( $f->deleted || $f->k_type=='group' ){
                    unset( $f );
                    continue;
                }
                if( array_key_exists( $f->name, $arr_fields ) ){
                    if( $is_ajax ){
                        // can have only one field .. complete all processing here
                        $f->store_posted_changes( $_POST['data'] );
                        $errors = $PAGE->save();
                        if( !$errors ){
                            $FUNCS->invalidate_cache();
                            $html = $f->get_data( 1 );
                        }
                        else{
                            $html = '<font color="red"><i>('.$f->err_msg.')</i></font>';
                        }
                        ob_end_clean();
                        echo $html;
                        exit;
                    }
                    else{
                        $f->resolve_dynamic_params();
                        if( $f->requires_multipart ) $requires_multipart = 1;
                        if( $f->k_type=='richtext' ){
                            require_once( K_COUCH_DIR.'includes/ckeditor/ckeditor.php' );
                        }
                        $arr_fields[$f->name]=&$f;
                    }
                }
                unset( $f );
            }

            foreach( $arr_fields as $k=>$v ){
                if( !is_object($v) ){
                    die( 'Field not found: ' . $FUNCS->escape_HTML($k) );
                }
            }

            // form posted?
            $errors = '';
            if( isset($_POST['op']) && $_POST['op']=='save' ){
                // move posted data into fields
                $refresh_form = $refresh_errors = 0;
                foreach( $arr_fields as $k=>$v ){
                    $f = &$arr_fields[$k];
                    $f->store_posted_changes( $_POST['f_'.$f->name] );
                    if( $f->refresh_form ) $refresh_form = 1;
                    if( $f->err_msg_refresh ) $refresh_errors++;
                    unset( $f );
                }

                if( !$refresh_form ){
                    $errors = $PAGE->save();

                    if( !$errors ){
                        $FUNCS->invalidate_cache();

                        ob_end_clean();

                        // redirect
                        echo '<font color="green"><b>Saved.</b></font><br/>Reloading page..<script>parent.location.reload()</script>';
                        exit;
                    }
                }
                else{
                    $errors = $refresh_errors;
                }
            }

            // render fields
            ob_start();
            require_once( K_COUCH_DIR.'addons/inline/view/edit.php' );
            $html = ob_get_contents();
            ob_end_clean();

            // header needs to be called after all fields are rendered as it includes css/js set by fields
            ob_start();
            require_once( K_COUCH_DIR.'addons/inline/view/header.php' );
            $html = ob_get_contents().$html;
            ob_end_clean();

            echo $html;
            exit;
        }

        function create(){

            echo 'create: not yet implemented';
        }

        function delete(){

            echo 'delete: not yet implemented';
        }

    } // end class
