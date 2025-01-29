<?php
    if ( !defined('K_ADMIN') ) die(); // cannot be loaded directly

    require_once( K_ADDONS_DIR.'mosaic/edit-mosaic.php' );

    class KMosaicAdminEx{
        /////// 1. 'form' action  ////////////////////////////////////////////////////
        function form_action(){
            global $FUNCS, $CTX, $PAGE, $AUTH;

            $form = 'k_admin_frm';
            $CTX->set( 'k_cur_form', $form, 'global' );
            $PAGE->forms[$form] = array();
            $count = count( $PAGE->fields );

            $FUNCS->add_form_field( array('name'=>'_advanced_settings_') );
            $FUNCS->add_form_field( array('name'=>'_system_fields_', 'order'=>10) );
            $FUNCS->add_form_field( array('name'=>'_custom_fields_', 'order'=>20) );
            for( $x=0; $x<$count; $x++ ){
                $f = &$PAGE->fields[$x];
                $f->resolve_dynamic_params();

                $def = array(
                    'name'=>$f->name,
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
                    'content'=>'',
                    'is_compound'=>0,
                    'obj'=>&$f,
                );
                $def['group'] = ( $f->system ) ? '_system_fields_' : ( (trim($def['group'])=='') ? '_custom_fields_' : $def['group'] );

                if( $f->system ){
                    $def['hide'] = 1;
                }

                $FUNCS->add_form_field( $def );

                unset( $f );
            }

            if( $PAGE->id==-1 && isset($_POST['k_hid_'.$form]) ){
                $FUNCS->add_event_listener( 'alter_create_insert',  array('KMosaicAdmin', 'alter_create_insert') );
            }

            $FUNCS->reset_admin_actions( array('toolbar', 'filter', 'page', 'extended') );
            $FUNCS->add_page_action(
                array(
                    'name'=>'btn_submit',
                    'title'=>$FUNCS->t('ok'),
                    'onclick'=>array(
                        "$('#screen').css( 'display', 'block' );",
                        "$('#btn_submit').trigger('my_submit');",
                        "$('#".$CTX->get('k_cur_form')."').submit();",
                        "return false;",
                    ),
                    'class'=>'btn-primary',
                    'icon'=>'circle-check',
                    'weight'=>10,
                )
            );
            $FUNCS->add_page_action(
                array(
                    'name'=>'btn_cancel',
                    'title'=>$FUNCS->t('cancel'),
                    'onclick'=>array(
                        "var msg = window.onbeforeunload();",
                        "if( msg ){ if( !confirm(msg) ){ this.blur(); return false; } }",
                        "window.onbeforeunload = null;",
                        "window.parent.COUCH.mosaicModalClose();",
                        "return false;",
                    ),
                    'icon'=>'circle-x',
                    'weight'=>0,
                )
            );

            $html = self::_render_content_form_mosaic_ex();
            $html = $FUNCS->render( 'main', $html, 1 );
            $FUNCS->route_fully_rendered = 1;

            return $html;
        }

        static function _render_content_form_mosaic_ex(){
            global $FUNCS, $CTX, $PAGE, $DB, $AUTH;

            if( isset($_GET['close'][0]) ){
                $route = ( $PAGE->tpl_type=='tile' ) ? 'edit_view' : 'edit_view_ex';
                $link = $FUNCS->generate_route( $PAGE->tpl_name, $route, array('nonce'=>$FUNCS->create_nonce('edit_page_'.$PAGE->id), 'id'=>$PAGE->id) );

                $FUNCS->add_js("
                    $(function(){
                        if( window.parent && window.parent.KMosaic && window.parent.KMosaic.callBack ){
                            var content = $('#mosaic_content');
                            window.parent.KMosaic.callBack(content.html(), '".$PAGE->id."', '".$link."');
                        }
                    });
                ");

                $html .= '<div id="mosaic_content" style="display:none;">';
                $html .= $FUNCS->render( 'pb_show_tile', 0 /*no cache*/, 0 /*no overlay*/ );
                $html .= '</div>';

                return $html;
            }

            $form = $CTX->get('k_cur_form');
            $mode = ( $PAGE->id==-1 || is_null($PAGE->id) ) ? 'create' : 'edit';
            $PAGE->forms[$form] = array();
            $count = count( $PAGE->fields );

            $fields = array();
            $tree = &$FUNCS->get_admin_form_fields( 'weight', 'asc' );
            for( $x=0; $x<count($tree->children); $x++ ){
                if( $tree->children[$x]->name=='_custom_fields_' ){
                    $fields = $tree->children[$x]->children;
                    break;
                }
            }

            $submitted = isset($_POST['k_hid_'.$form]);
            if( $submitted && isset($_POST['k_orig_elements']) ){
                $arr_orig_elements = array_map( function($s){ return 'grp_'.$s; }, array_filter(array_map("trim", explode('|', $_POST['k_orig_elements']))) );
                foreach( array('icon_blocks','image_blocks','form','timeline','tabs','accordion','icon_list','keyval','menu') as $k ){
                    if( in_array('grp_'.$k, $arr_orig_elements) ){ $arr_orig_elements[]='grp_'.$k.'_settings'; }
                }
            }
            $arr_refresh = array( 'refresh_form'=>0, 'refresh_errors'=>array() );
            for( $y=0; $y<count($fields); $y++ ){
                $f = $fields[$y];
                self::_resolve_active( $f->obj, $form, $submitted, $arr_refresh );

                $force_inactive = $skip_post = 0;
                if( $f->obj->k_type=='group' ){
                    if( $f->obj->k_inactive ){ $force_inactive = 1; }

                    //if form being refreshed and group is active, check if it is being freshly added to the form..
                    if( $submitted && isset($_POST['k_orig_elements']) && $arr_refresh['refresh_form'] && !$force_inactive && is_array($f->obj->not_active) ){
                        if( !in_array($f->obj->name, $arr_orig_elements) ){
                            $skip_post = 1; // retain original contents
                        }
                    }
                }

                if( $f->obj->k_type=='group' || $f->obj->k_type=='row' ){
                    $count = count($f->children);
                    if( $count ){
                        for( $x=0; $x<$count; $x++ ){
                            self::_resolve_active( $f->children[$x]->obj, $form, $submitted, $arr_refresh, $force_inactive, $skip_post );
                            if( $f->children[$x]->obj->k_type=='row' ){
                                $count2 = count( $f->children[$x]->children );
                                if( $count2 ){
                                    for( $x2=0; $x2<$count2; $x2++ ){
                                        self::_resolve_active( $f->children[$x]->children[$x2]->obj, $form, $submitted, $arr_refresh, $force_inactive, $skip_post );
                                    }
                                }
                            }
                        }
                    }
                }
                unset( $f );
            }

            $arr_errors = array();
            if( $submitted ){
                if( !$arr_refresh['refresh_form'] ){

                    $f = &$PAGE->_fields['k_page_title'];
                    if( trim($f->get_data())=='' ){
                        $f->store_posted_changes( md5($AUTH->hasher->get_random_bytes(16)) );
                    }
                    unset( $f );

                    $f = &$PAGE->_fields['k_publish_date'];
                    if( !$f->get_data() ){
                        $f->store_posted_changes( $FUNCS->get_current_desktop_time() );
                    }
                    unset( $f );

                    if( $mode=='create' ){
                        for( $x=0; $x<count($PAGE->fields); $x++ ){
                            $f = &$PAGE->fields[$x];
                            $f->modified = 1;
                            unset( $f );
                        }
                    }

                    // save
                    $errors = $PAGE->save( 'pb' );

                    if( !$errors ){
                        $redirect_dest = self::_get_form_redirect_link( $PAGE, $mode ) . '&close=1';
                        $DB->commit( 1 ); // force commit, we are redirecting.
                        header( "Location: ".$redirect_dest );
                        die();
                    }
                    else{
                        for( $x=0; $x<count($PAGE->fields); $x++ ){
                            $f = &$PAGE->fields[$x];
                            if( $f->err_msg ){
                                $CTX->set( 'k_error_'.$f->name, $f->err_msg );
                                $arr_errors[] = '<b>' . (($f->label) ? $f->label : $f->name) . ':</b> ' . $f->err_msg;
                            }
                            unset( $f );
                        }
                    }
                }
                else{
                    $arr_errors = $arr_refresh['refresh_errors'];
                }
            }

            $buf = ''; $error='';
            for( $x=0; $x<count($fields); $x++ ){
                switch( $fields[$x]->obj->k_type ){
                    case 'group':
                        $buf .= static::_render_group( $fields[$x] );
                        break;
                    case 'row':
                        $buf .= static::_render_row( $fields[$x], $error );
                        break;
                    default:
                        $buf .= static::_render_field( $fields[$x], $error );
                }
            }
            $html = static::_render_modal( $buf, $form, $arr_errors );

            return $html;
        }

        static function _render_modal( $content, $form, $arr_errors ){
            global $PAGE, $CTX, $FUNCS;

            $html .= '<div id="k-modal-body">';
            $html .= '<form  enctype="multipart/form-data" method="post" id="'.$form.'" name="'.$form.'" action="'.$CTX->get('k_form_target').'" accept-charset="'.K_CHARSET.'">';
            $html .= '<div class="tab-pane fade active in" id="tab-pane-mosaic_ex">';
            if( count($arr_errors) ){
                $html .= $FUNCS->show_alert( '' /*$heading*/, implode('<br>', $arr_errors), 'error' );
            }
            $html .= $content;
            $html .= '<input type="hidden" name="k_hid_'.$form.'" id="k_hid_'.$form.'" value="'.$form.'" />';
            if( array_key_exists('elements', $PAGE->_fields) ){
                $html .= '<input type="hidden" id="k_orig_elements" name="k_orig_elements" value="'.$PAGE->_fields['elements']->data.'">';
            }
            $html .= '<input type="hidden" id="k_refresh_form" name="k_refresh_form" value="">';
            $html .= '</div>';
            $html .= '</form>';
            $html .= '</div>';
            $html .= '<div id="k-modal-footer">';
            $html .= $FUNCS->render( 'page_actions' );
            $html .= '</div>';
            $html .= '<div id="screen" style="display:none; position:absolute; width:100%; height:100%; top:0; left:0; background-color: black; opacity: 0.1;"></div>';

            return $html;
        }

        static function _get_form_redirect_link( &$pg, $_mode ){
            global $CTX, $FUNCS, $PAGE;

            if( $_mode=='edit' ){
                $redirect_dest = $CTX->get( 'k_qs_link' );
            }
            else{ // 'create' mode
                // redirect to 'edit' view of the newly created page
                $route = ( $PAGE->tpl_type=='tile' ) ? 'edit_view' : 'edit_view_ex';
                $link = $FUNCS->generate_route( $pg->tpl_name, $route, array('nonce'=>$FUNCS->create_nonce('edit_page_'.$pg->id), 'id'=>$pg->id) );
                $redirect_dest = $FUNCS->get_qs_link( $link ); // link with passed qs parameters
            }

            return $redirect_dest;
        }

        static function _resolve_active( $f, $form, $submitted, &$arr_refresh, $force_inactive=0, $skip_post=0 ){
            global $FUNCS, $PAGE, $CTX;

            if( $force_inactive && static::_is_kaleido($f) ){
                $f->k_inactive = 1;
                return;
            }

            $f->k_inactive = !$FUNCS->resolve_active( $f, $form, $submitted );
            if( $f->k_type=='group' ){
                if( $f->k_inactive ) return;
                if( $submitted && $f->name=='grp_elements' ){
                    if( isset($_POST['k_refresh_form'][0]) ){
                        $f->refresh_form = 1;
                        $f->collapsed = 0;
                    }
                }
            }

            $PAGE->forms[$form][$f->name] = &$f;

            if( $submitted ){
                if( !$skip_post ){
                    $f->store_posted_changes( $_POST['f_'.$f->name] );
                }

                if( $f->refresh_form ) $arr_refresh['refresh_form'] = 1;
                if( $f->err_msg_refresh ){
                    $CTX->set( 'k_error_'.$f->name, $f->err_msg_refresh );
                    $arr_refresh['refresh_errors'][] = '<b>' . (($f->label) ? $f->label : $f->name) . ':</b> ' .$f->err_msg_refresh;
                }
            }
        }

        static function _render_field( &$r, &$error ){
            global $FUNCS, $CTX, $PAGE, $AUTH;

            $f = $r->obj;
            if( $f->err_msg ){ $error=1; }

            if( $f->udf ){
                $CTX->push( '__my_render_field__', 1 /*no_check*/ );
                $r->set_in_context();
            }
            $name = $id = 'f_'.$f->name; // hack for admin-panel's unfortunate naming of fields
            $extra = '';
            $k_field_content = $f->_render( $name, $id, $extra );
            if( $f->udf ){
                $CTX->pop();
            }

            $k_field_name = $r->name;
            $k_field_label = $r->title;
            $k_field_desc = $FUNCS->escape_HTML( $r->menu_text );
            $k_field_class = $r->class;
            $k_field_hidden = $r->hide;
            $k_field_input_id = 'f_'.$r->name;
            $k_field_type = $f->k_type;
            $k_field_is_required = $f->required;
            $k_field_is_deleted = $f->deleted;
            $k_field_is_collapsed = $f->collapsed;
            $k_field_err_msg = $f->err_msg;
            $k_field_wrapper_id = ( $f->system ) ? $f->name : 'k_element_'.$f->name;

            if( $k_field_hidden ){
                $style='style="display:none;"';
            }
            if( $AUTH->user->access_level>=10 ){
                $k_field_label = '<span title="'.$k_field_name.'">'.$k_field_label.'</span>';
            }
            if( strlen($k_field_desc) ){
                $k_field_desc = '<span class="desc">('.$k_field_desc.')</span>';
            }
            if( $k_field_is_deleted && $AUTH->user->access_level>=10 ){
                $CTX->push( '__my_render_field_delete__', 1 /*no_check*/ );
                    $CTX->set( 'k_field_id', $f->id );
                    $CTX->set( 'k_field_name', $k_field_name );
                    $CTX->set( 'k_field_definition', $FUNCS->escape_HTML($f->_html) );
                    $k_field_is_deleted = $FUNCS->render( 'form_field_deleted' );
                $CTX->pop();
            }
            else{
                $k_field_is_deleted='';
            }
            $sub = '';
            if( $k_field_err_msg ){
                $sub = '<div class="labels"><span class="label label-error k_notice" id="k_notice_'.$k_field_input_id.'">'.$k_field_err_msg.'</span></div>';
            }
            elseif( $k_field_is_required ){
                $sub = '<div class="labels"><span class="label label-txt k_notice" id="k_notice_'.$k_field_input_id.'">('.$FUNCS->t( 'required' ).')</span></div>';
            }

            if( $f->k_type=='message' ){
                if( $f->custom_styles=='no_wrapper' ){
                    $html = $k_field_content;
                }
                else{
                $html=<<<EOS
                <div id="$k_field_wrapper_id" class="field k_element k_$k_field_type $k_field_class" $style>
                    $k_field_content
                </div>
EOS;
                }
            }
            else{
                $html=<<<EOS
                <div id="$k_field_wrapper_id" class="field k_element k_$k_field_type $k_field_class" $style>
                    <label id="k_label_$k_field_input_id" class="field-label" for="$k_field_input_id">
                        $k_field_label
                        $k_field_desc
                    </label><br/>

                    $k_field_is_deleted

                    $k_field_content

                    $sub
                </div>
EOS;
            }

            return $html;
        }

        static function _render_group( &$r ){
            global $FUNCS;
            static $done=0;

            $f = $r->obj;
            if( $f->k_inactive && static::_is_kaleido($f) ) return;

            // first get child contents
            $error=$child_error='';
            $buf = '';
            for( $x=0; $x<count($r->children); $x++ ){
                $child_error='';
                switch( $r->children[$x]->obj->k_type ){
                    case 'row':
                        $buf .= static::_render_row( $r->children[$x], $child_error );
                        break;
                    default:
                        $buf .= static::_render_field( $r->children[$x], $child_error );
                }
                if( $child_error ){ $error=1; }
            }

            // now wrap ..
            $k_field_class = $r->class;
            $k_field_hidden = $r->hidden;
            $k_field_label = $r->title;
            $k_field_desc = $FUNCS->escape_HTML( $r->menu_text );
            $k_field_wrapper_id = ( $f->system ) ? $f->name : 'k_element_'.$f->name;
            $k_field_is_collapsed = ( $error ) ? 0 : $f->collapsed;

            if( !$done ){
                $done=1;

                if( $k_field_is_collapsed=='-1' ){ $k_field_is_collapsed=0; } // first group is always shown expanded by default
                $FUNCS->add_js("
                    $( function() {
                        COUCH.el.\$content.on( \"click\", \".panel-toggle, .panel-body-toggle\", $.debounce(function( e ) {
                            var \$heading = $( this ).blur();

                            if ( \$heading.hasClass( \"panel-body-toggle\" ) ) \$heading = \$heading.parent().prev();

                            e.preventDefault();

                            \$heading.toggleClass( \"collapsed\" ).next().animate({
                                height: \"toggle\"
                            }, 500 );
                        }, 200, true ));
                    });
                ");
            }

            if( strlen($k_field_desc) ){
                $k_field_desc = '<span class="desc">('.$k_field_desc.')</span>';
            }
            if( $k_field_hidden ){
                $style='style="display:none;"';
            }
            $collapsed = ( $k_field_is_collapsed ) ? 'collapsed' : '';
            if( $k_field_is_collapsed ){
                $panel_style='style="display:none;"';
            }
            if( $error && !$k_field_hidden ){
                $k_field_class .= ' k_visible';
            }

            $html=<<<EOS
                <div id="$k_field_wrapper_id" class="group-wrapper k_group $k_field_class" $style>
                    <a class="panel-heading panel-toggle $collapsed" href="#">$k_field_label$k_field_desc
                    </a>
                    <div class="panel-body" $panel_style>
                        $buf
                        <div class="field placeholder"></div>
                        <a class="btn panel-body-toggle" href="#"></a>
                    </div>
                </div>
EOS;

            return $html;
        }

        static function _render_row( &$r, &$error ){
            global $FUNCS;
            static $done=0;

            // first get child contents
            $buf = '';
            for( $x=0; $x<count($r->children); $x++ ){
                $buf .= static::_render_field( $r->children[$x], $error );
            }

            // now wrap ..
            $f = $r->obj;
            $k_field_class = $r->class;
            $k_field_hidden = $r->hidden;
            $k_field_label = $r->title;
            $k_field_desc = $FUNCS->escape_HTML( $r->menu_text );
            $k_field_wrapper_id = ( $f->system ) ? $f->name : 'k_element_'.$f->name;
            $k_field_is_collapsible = ( $f->collapsed !='-1' ) ? 1 : 0;
            $k_field_is_collapsed = ( $error ) ? 0 : $f->collapsed;

            if( strlen($k_field_desc) ){
                $k_field_desc = '<span class="desc">('.$k_field_desc.')</span>';
            }
            if( $k_field_hidden ){
                $style='style="display:none;"';
            }
            $collapsed = ( $k_field_is_collapsed ) ? 'collapsed' : '';
            if( $k_field_is_collapsed ){
                $panel_style='style="display:none;"';
            }

            if( !$done ){
                $done=1;

                $FUNCS->load_css( K_ADMIN_URL.'addons/bootstrap-grid/theme/grid12.css' );
                $FUNCS->add_js("
                    $( function() {
                        COUCH.el.\$content.find('fieldset.row_fieldset > legend').on( \"click\", $.debounce(function( e ) {
                            var \$heading = $(this).blur().parent();

                            e.preventDefault();

                            \$heading.toggleClass( \"collapsed\" ).find('.row').animate({
                                height: \"toggle\"
                            }, 200 );
                        }, 200, true ));
                    });
                ");
            }

            if( $k_field_is_collapsible ){
                    $html=<<<EOS
                    <div id="$k_field_wrapper_id" class="k_row $k_field_class has_fieldset" $style>
                        <fieldset class="row_fieldset $collapsed">
                            <legend>$k_field_label$k_field_desc</legend>
                            <div class="row" $panel_style>
                                $buf
                            </div>
                        </fieldset>
                    </div>
EOS;
            }
            else{
                    $html=<<<EOS
                    <div id="$k_field_wrapper_id" class="row k_row $k_field_class" $style>
                        $buf
                    </div>
EOS;
            }

            return $html;
        }

        static function _render_text( $f, $input_name, $input_id, $extra, $dynamic_insertion ){
            global $CTX, $FUNCS;

            $rtl = ( $f->rtl ) ? 'dir="RTL"' : '';
            $style = ( $f->width ) ? 'width:'.$f->width.'px; ' : '';
            $deleted = ( $f->deleted ) ? 'disabled="1"' : '';

            $html = '<input type="'.$f->k_type.'" id="' . $input_id . '" name="'. $input_name .'" '.$rtl.' class="'.$f->k_type.'" value="'. $FUNCS->escape_HTML( $f->get_data(), ENT_QUOTES, K_CHARSET ) .'" '.$extra.' size="105" ';
            if( $f->maxlength ) $html .= 'maxlength="'.$f->maxlength.'" ';
            $html .= 'style="'.$style.'" '. $deleted .' />';

            return $html;
        }

        static function _render_textarea( $f, $input_name, $input_id, $extra, $dynamic_insertion ){
            global $CTX, $FUNCS;

            $rtl = ( $f->rtl ) ? 'dir="RTL"' : '';
            $style = ( $f->height ) ? 'height:'.$f->height.'px; ' : '';
            $style .= ( $f->width ) ? 'width:'.$f->width.'px; ' : '';
            $deleted = ( $f->deleted ) ? 'disabled="1"' : '';

            $html .= '<textarea id="' . $input_id . '" name="'. $input_name .'" '.$rtl.' rows="12" cols="79" class="textarea" '. $deleted .' style="'.$style.'" '.$extra.'>'.$FUNCS->escape_HTML( $f->get_data(), ENT_QUOTES, K_CHARSET ).'</textarea>';

            return $html;
        }

        static function _render_image( $f, $input_name, $input_id, $extra, $dynamic_insertion ){
            global $CTX, $FUNCS;

            return 'hello '.$input_name;
        }

        static function _render_thumbnail( $f, $input_name, $input_id, $extra, $dynamic_insertion ){
            global $CTX, $FUNCS;

            return 'hello '.$input_name;
        }

        static function _render_file( $f, $input_name, $input_id, $extra, $dynamic_insertion ){
            global $CTX, $FUNCS;

            return 'hello '.$input_name;
        }

        static function _render_options( $f, $input_name, $input_id, $extra, $dynamic_insertion ){
            global $CTX, $FUNCS;

            $value = trim( $f->get_data() );
            $selected = html_entity_decode( $value, ENT_QUOTES, K_CHARSET );
            if( $selected=='' && $_SERVER['REQUEST_METHOD']!='POST' ){ // the posted value can also be a blank, hence this check.
                $selected = trim( $f->opt_selected );
            }
            $separator = ( $f->k_separator ) ? $f->k_separator : '|';
            $val_separator = ( $f->val_separator ) ? $f->val_separator : '=';

            if( $f->k_type=='checkbox' ){
                //$selected = ( $selected != '' ) ? array_map( "trim", explode( $separator, $selected ) ) : array();
                $selected = ( $selected != '' ) ? array_map( "trim", preg_split( "/(?<!\\\)\\".$separator."/", $selected ) ) : array();
                for( $x=0; $x<count($selected); $x++ ){
                    $selected[$x] = str_replace( '\\'.$separator, $separator, $selected[$x] ); //unescape
                    // not necessary to escape the selected values but can be done
                    $selected[$x] = str_replace( '\\'.$val_separator, $val_separator, $selected[$x] );
                }
                $input_type = 'checkbox';
                $input_name = $input_name.'[]';
            }
            elseif( $f->k_type=='radio' ){
                $input_type = 'radio';
            }

            if( $f->k_type=='dropdown' ){
                $html .= '<div class="select"';
                if( $f->width ) $html .= ' style="width:'.$f->width.'px;"';
                $html .= $extra .'>';
                $html .= '<select name="'.$input_name.'" id="'.$input_id.'"';
                if( $f->deleted ) $html .= ' class="disabled" disabled="1"';
                $html .= '>';
            }
            else{
                $html .= '<div class="ctrls-'.$f->k_type;
                if( $f->deleted ) $html .= ' ctrls-disabled';
                $html .= '">';
            }

            if( strlen($f->opt_values) ){
                $arr_values = array_map( "trim", preg_split( "/(?<!\\\)\\".$separator."/", $f->opt_values ) );
                $count = 0;
                foreach( $arr_values as $val ){
                    if( $val=='' ){
                        if( $f->k_type!='dropdown' ) $html .= '<br>';
                        continue;
                    }
                    $val = str_replace( '\\'.$separator, $separator, $val ); //unescape
                    $arr_values_args = array_map( "trim", preg_split( "/(?<!\\\)\\".$val_separator."/", $val ) );
                    $opt = str_replace( '\\'.$val_separator, $val_separator, $arr_values_args[0] ); //unescape
                    if( isset($arr_values_args[1]) ){
                        $opt_val = str_replace( '\\'.$val_separator, $val_separator, $arr_values_args[1] ); //unescape
                    }
                    else{
                        $opt_val = $opt;
                    }

                    if( $f->k_type=='dropdown' ){
                        $html .= '<option value="'.$opt_val.'"';
                        if( $opt_val== $selected ) $html .= '  selected="selected"';
                        $html .= '>'.$opt.'</option>';
                    }
                    else{
                        $html .= '<label for="'.$input_id . $count.'">';
                        $html .= '<input type="'.$input_type.'" name="'.$input_name.'" id="'.$input_id . $count.'" value="';
                        if( $f->k_type=='radio' ){
                            $html .= $opt_val.'" '.$extra .' ';
                            if( $f->deleted ) $html .= 'disabled="1" ';
                            if( $selected=='' && $count == 0 ){
                                $html .= 'checked="checked"'; // if no button selected select the first one (RFC1866)
                            }
                            else{
                                if( $opt_val == $selected ) $html .= 'checked="checked"';
                            }
                        }
                        else{
                            // checkbox can have multiple selections.. escape discrete values
                            $html .= str_replace( $separator, '\\'.$separator, $opt_val ).'" '.$extra .' ';
                            if( $f->deleted ) $html .= 'disabled="1" ';
                            if( in_array($opt_val, $selected) ) $html .= 'checked="checked"';
                        }

                        $html .= '/><span class="ctrl-option"></span>' . $opt;
                        $html .= '</label>';
                    }
                    $count++;
                }
            }

            if( $f->k_type=='dropdown' ) $html .= '</select><span class="select-caret">'.$FUNCS->get_icon('caret-bottom').'</span>';
            $html .= '</div>';

            return $html;
        }

        static function _is_kaleido( $f ){
            return (array_key_exists('elements', $f->page->_fields) && $f->page->_fields['elements']->k_type=='checkbox' && $f->page->_fields['elements']->k_group=='grp_elements') ? true : false;
        }

        // route filters
        static function fix_link( $route ){
            global $FUNCS, $PAGE, $CTX;

            $PAGE->_fields['k_page_title']->store_posted_changes( $PAGE->page_title );
            $PAGE->_fields['k_page_name']->store_posted_changes( $PAGE->page_name );

            $link = $FUNCS->generate_route( $PAGE->tpl_name, 'clone_view', array('nonce'=>$route->resolved_values['nonce'], $route->resolved_values['id']) );
            $CTX->set( 'k_form_target', $link );
        }
    } // end class KMosaicAdminEx
