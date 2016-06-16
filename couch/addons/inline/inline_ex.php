<?php

    if ( !defined('K_ADMIN') ) die(); // cannot be loaded directly

    require_once( K_COUCH_DIR.'base.php' );

    class InlineEx extends KBaseAdmin{

        var $is_inline = 0;
        var $arr_fields = array();

        function edit_action(){
            global $FUNCS, $PAGE, $CTX;

            // validate parameters
            $tpl_id = ( isset($_GET['tpl']) && $FUNCS->is_non_zero_natural($_GET['tpl']) ) ? (int)$_GET['tpl'] : null;
            if( is_null($tpl_id) ) die( 'No template specified' );

            $page_id = ( isset($_GET['p']) && $FUNCS->is_non_zero_natural($_GET['p']) ) ? (int)$_GET['p'] : null;
            $obj_id = ( $page_id ) ? $page_id : $tpl_id;
            $FUNCS->validate_nonce( 'edit_page_' . $obj_id );

            // get fields to render
            $this->arr_fields = array_flip( array_filter(array_map("trim", explode('|', $_GET['flist']))) );
            if( !count($this->arr_fields) ) die( 'No Fields specified' );

            // inline or popup?
            $this->is_inline = ( isset($_GET['ajax']) && $_GET['ajax']=='1' ) ? 1 : 0; // if called from 'cms:inline_link'

            // resolve page object
            $PAGE = new KWebpage( $tpl_id, $page_id );
            if( $PAGE->error ){
                ob_end_clean();
                die( 'ERROR: ' . $PAGE->err_msg );
            }
            $PAGE->set_context();

            // render
            $html = $this->form_action();
            $html = $FUNCS->render( 'main', $html, 1 /* simple */ );

            // finish output
            $FUNCS->route_fully_rendered = 1;
            return $html;
        }

        function render_form(){
            global $FUNCS, $CTX;

            $CTX->set( 'k_cur_form', $this->form_name, 'global' );

            return $FUNCS->render( 'inline_content_form' );
        }

        function _set_default_fields( &$arr_fields ){
            global $FUNCS, $PAGE;

            for( $x=0; $x<count($PAGE->fields); $x++ ){
                $f = &$PAGE->fields[$x];
                if( $f->deleted || $f->k_type=='group' ){
                    unset( $f );
                    continue;
                }
                if( array_key_exists( $f->name, $this->arr_fields ) ){
                    if( $this->is_inline ){
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
                        $this->arr_fields[$f->name]=&$f;
                    }
                }
                unset( $f );
            }

            foreach( $this->arr_fields as $k=>$f ){
                if( !is_object($f) ){
                    die( 'Field not found: ' . $FUNCS->escape_HTML($k) );
                }
                else{
                    $f->resolve_dynamic_params();

                    $def = array(
                        'label'=>$f->label,
                        'desc'=>$f->k_desc,
                        'order'=>$f->k_order,
                        'group'=>$f->k_group,
                        'class'=>$f->class,
                        'icon'=>'',
                        'no_wrapper'=>0,
                        'skip'=>0,
                        'hide'=>( ($f->system && $f->hidden) || ($f->deleted && $AUTH->user->access_level < K_ACCESS_LEVEL_SUPER_ADMIN) || $f->no_render ) ? 1 : 0,
                        'required'=>$f->required,
                        'content'=>"<cms:render 'form_input' />",
                        'is_compound'=>0,
                        'obj'=>&$f,
                    );
                    $f->pre_render( $def ); // allow field to change settings for itself
                    $def['group'] = '_custom_fields_';

                    $arr_fields[$f->name] = $def;
                }
                unset( $f );
            }

            return;
        }

        function _setup_form_variables(){
            global $CTX, $PAGE;

            $CTX->set( 'k_selected_form_mode', 'edit', 'global' );
            $CTX->set( 'k_selected_masterpage', $PAGE->tpl_name, 'global' );
            $CTX->set( 'k_selected_page_id', $PAGE->id, 'global' );
        }
    } // end class
