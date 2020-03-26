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

    class JCropThumb extends KUserDefinedField{

        static function handle_params( $params ){
            global $FUNCS, $AUTH;
            if( $AUTH->user->access_level < K_ACCESS_LEVEL_SUPER_ADMIN ) return;

            $attr = $FUNCS->get_named_vars(
                array(
                    'enforce_min'=>'1',
                    'auto_refresh'=>'0',
                    'hide_assoc_preview'=>'1',
                ),
                $params
            );

            $attr['enforce_min'] = ( $attr['enforce_min']==0 ) ? 0 : 1;
            $attr['auto_refresh'] = ( $attr['auto_refresh']==1 ) ? 1 : 0;
            $attr['hide_assoc_preview'] = ( $attr['hide_assoc_preview']==0 ) ? 0 : 1;

            return $attr;
        }

        function get_data_to_save(){
            return $this->data;
        }

        function get_search_data(){
            return;
        }

        function get_data( $for_ctx=0 ){
            global $Config;

        $data = trim( $this->data );
        if( !strlen($data) ) $data = trim( $this->default_data );

        if( $data[0]==':' ){ // if marker
        $data = substr( $data, 1 );
        $folder = ( $this->k_type=='jcropthumb' ) ? 'image' : $this->k_type;
        $domain_prefix = $Config['k_append_url'] . $Config['UserFilesPath'] . $folder . '/';
        $data = $domain_prefix . $data;
        }

            return $data;
        }

        function store_posted_changes( $post_val ){
            if( $this->auto_refresh ){
                global $FUNCS, $Config;

                $f = $this->page->_fields[$this->assoc_field];
                if( $this->deleted || $this->k_inactive || !$f || $f->deleted || $f->k_inactive ) return; // no need to store

                if( $_POST['f_'.$this->assoc_field.'_refresh'] ){
                    $f->refresh_form = 1;

                    if( $f->modified ){
                        // create thumbnail..
                        $data = '';
                        $src = $f->get_data();
                        $domain_prefix = $Config['k_append_url'] . $Config['UserFilesPath'] . 'image/';
                        if( strpos($src, $domain_prefix)===0 ){ // process image only if local
                            $src = substr( $src, strlen($domain_prefix) );
                            if( $src ){
                                $src = $Config['UserFilesAbsolutePath'] . 'image/' . $src;
                                $dest = null;
                                $w = $this->width;
                                $h = $this->height;
                                $enforce_max = $this->enforce_max;
                                $crop = ( $enforce_max ) ? 0 : 1;
                                $quality = $this->quality;

                                $thumbnail = k_resize_image( $src, $dest, $w, $h, $crop, $enforce_max, $quality );
                                if( !$FUNCS->is_error($thumbnail) ){
                                    $path_parts = $FUNCS->pathinfo( $f->get_data() );
                                    $img_path = $path_parts['dirname'] . '/';
                                    $img_path = substr( $img_path, strlen($domain_prefix) );
                                    if( $img_path ) $thumbnail = $img_path . $thumbnail;
                                    $data = ':' . $thumbnail; // add marker
                                }
                            }
                        }
                        $post_val = $data;
                    }
                }
            }

            parent::store_posted_changes( $post_val );
        }

        function validate(){

            $this->k_type = 'thumbnail';
            $this->enforce_max = 0; // always in 'crop' mode
            return true;
        }

        function render(){
            // defunct
        }

        function _render( $input_name, $input_id, $extra1='', $dynamic_insertion=0 ){
            global $FUNCS, $CTX, $Config;

            $f = $this->page->_fields[$this->assoc_field];
            if( $f ){
                $assoc_image = $this->page->_fields[$this->assoc_field]->get_data();
            }
            $value = $this->get_data();
            if( $value ) $value .= '?='.time();
            $tb_preview = $value ? $value : K_SYSTEM_THEME_URL . 'assets/upload-image.gif';
            $tb_preview_icon = $value ? $value : K_SYSTEM_THEME_URL . 'assets/upload-image.gif';

            define( 'JCROP_URL', K_ADMIN_URL . 'addons/jcropthumb/' );
            $FUNCS->load_js( JCROP_URL . 'assets/jquery.bpopup.min.js' );
            $FUNCS->load_js( JCROP_URL . 'assets/jquery.Jcrop.min.js' );
            $FUNCS->load_css( JCROP_URL . 'assets/jquery.Jcrop.min.css' );
            $FUNCS->load_css( JCROP_URL . 'assets/jcropthumb.css' );

            if( $this->show_preview ){
                $html .= '<div class="img-preview">';
                $html .= '<a id="'.$input_id.'_preview" href="'.$tb_preview.'" class="popup-img">';
                $html .= '<img id="'.$input_id.'_tb_preview" name="'.$input_name.'_tb_preview" src="'.$tb_preview_icon.'" ';
                $html .= ( $this->preview_width ) ? 'width="'.$this->preview_width.'" ': '';
                $html .= ( $this->preview_height ) ? 'height="'.$this->preview_height.'" ': '';
                $html .= 'class="k_thumbnail_preview" >';
                $html .= $FUNCS->get_icon( 'zoom-in' );
                $html .= '</a>';
                $html .= '</div>';
            }

            if( $assoc_image && !$this->deleted && $f && !$f->deleted ){

                // check if local
                $domain_prefix = $Config['k_append_url'] . $Config['UserFilesPath'] . 'image/';
                if( strpos($assoc_image, $domain_prefix)===0 ){
                    $path = substr( $assoc_image, strlen($domain_prefix) );
                    if( $path ){
                        $local_path = $Config['UserFilesAbsolutePath'] . 'image/' . $path;
                        if( file_exists($local_path) ){

                            $info = @getimagesize($local_path);
                            if( $info!==false && intval($info[0]) && intval($info[1]) ){

                                // calculate x or y coordinate and width or height of selection rectangle to show
                                // Adapted from TimThumb script created by Tim McDaniels and Darren Hoyt with tweaks by Ben Gillbanks
                                $orig_width = intval( $info[0] );
                                $orig_height = intval( $info[1] );

                                $new_width = $this->width;
                                $new_height = $this->height;

                                if( $new_width && $new_height ){
                                    $aspect_ratio = (float) $new_width / $new_height;
                                }
                                else{
                                    if( $new_width && !$new_height ) {
                                        $new_height = round( $orig_height * ($new_width / $orig_width) );
                                        $aspect_ratio = (float) $new_width / $new_height;

                                    }elseif($new_height && !$new_width){
                                        $new_width = round( $orig_width * ($new_height / $orig_height) );
                                        $aspect_ratio = (float) $new_width / $new_height;

                                    }elseif( !$new_width && !$new_height ){
                                        $aspect_ratio = 0; // allow freeform selection

                                        $new_width = $orig_width;
                                        $new_height = $orig_height;

                                    }
                                }

                                $src_x = $src_y = 0;
                                $src_w = $orig_width;
                                $src_h = $orig_height;

                                $cmp_x = $orig_width  / $new_width;
                                $cmp_y = $orig_height / $new_height;

                                if( $cmp_x > $cmp_y ){
                                    $src_w = round( ( $orig_width / $cmp_x * $cmp_y ) );
                                    $src_x = round( ( $orig_width - ( $orig_width / $cmp_x * $cmp_y ) ) / 2 );
                                }elseif ( $cmp_y > $cmp_x ){
                                    $src_h = round( ( $orig_height / $cmp_y * $cmp_x ) );
                                    $src_y = round( ( $orig_height - ( $orig_height / $cmp_y * $cmp_x ) ) / 2 );
                                }
                                $src_x2 = $src_x + $src_w;
                                $src_y2 = $src_y + $src_h;

                                $thumb_w = ( $aspect_ratio ) ? round( $new_width ) : '0';
                                $thumb_h = ( $aspect_ratio ) ? round( $new_height ) : '0';

                                $min_w = $min_h = 8;
                                if( $aspect_ratio  && $this->enforce_min ){
                                    if( $thumb_w < $src_w ){
                                        $min_w = $thumb_w;
                                        $min_h = $thumb_h;
                                    }
                                    else{
                                        $min_w = $src_w;
                                        $min_h = $src_h;
                                    }
                                }

                                // output markup
                                $html .= '<div class="crop-group"><a id="' .$input_id. '_pop_button" class="btn recreate_tb">'.$FUNCS->get_icon('reload').$FUNCS->t('recreate').'</a></div>';
                                $html .='<input type="hidden" id="' .$input_id. '_x" name="x" />';
                                $html .='<input type="hidden" id="' .$input_id. '_y" name="y" />';
                                $html .='<input type="hidden" id="' .$input_id. '_w" name="w" />';
                                $html .='<input type="hidden" id="' .$input_id. '_h" name="h" />';

                                $html .= '<div id="'.$input_id .'_pop" class="BPopup">';
                                $html .= '<img src="'. $assoc_image .'" id="'. $input_id .'_img" />';

                                $data = base64_encode( $path )  . '|' . $thumb_w . '|' . $thumb_h . '|' . $this->quality;
                                $nonce = $FUNCS->create_nonce( 'jcrop_image_'.$data );
                                $data = $data . '|' . $nonce;

                                $html .= '<div class="crop-group field"><a href="javascript:k_jcrop_thumb(\''.$input_id.'\', \''.$data.'\')" class="btn button">'.$FUNCS->t('crop').'</a>';
                                $html .= '<span id="' .$input_id. '_wh"></span> x ';
                                $html .= '</div>';
                                $html .='</div>';

                                $html .= '<input type="hidden" id="' . $input_id . '" name="'. $input_id .'" value="'. htmlspecialchars( $this->data, ENT_QUOTES, K_CHARSET ) .'" />';

                                ob_start();
                                ?>
                                $(function(){
                                    $('#<?php echo $input_id; ?>_img').Jcrop({
                                        onChange: function(c){
                                            $('#<?php echo $input_id;?>_x').val(c.x);
                                            $('#<?php echo $input_id;?>_y').val(c.y);
                                            $('#<?php echo $input_id;?>_w').val(c.w);
                                            $('#<?php echo $input_id;?>_h').val(c.h);
                                            $('#<?php echo $input_id;?>_wh').html('w: ' + parseInt(c.w) + '&nbsp;&nbsp;h: ' + parseInt(c.h));
                                        },
                                        aspectRatio: <?php echo $aspect_ratio; ?>,
                                        setSelect:   [ <?php echo $src_x; ?>, <?php echo $src_y; ?>, <?php echo $src_x2; ?>, <?php echo $src_y2; ?> ],
                                        allowSelect: false,
                                        addClass: 'jcrop-light',
                                        bgColor: 'white',
                                        bgOpacity: .5,
                                        minSize: [<?php echo $min_w; ?>, <?php echo $min_h; ?>],
                                        boxWidth: 700,
                                        boxHeight: 700,
                                        keySupport: false
                                    });

                                    $('#<?php echo $input_id; ?>_pop_button').bind('click', function(e){

                                        // Prevents the default action to be triggered.
                                        e.preventDefault();

                                        // Triggering bPopup when click event is fired
                                        $('#<?php echo $input_id; ?>_pop').bPopup();

                                    });
                                });
                                <?php
                                $js = ob_get_contents();
                                ob_end_clean();
                                $FUNCS->add_js( $js );
                            }
                        }
                    }
                }
            }

            if( $this->auto_refresh ){
                if( $f && !$f->_jcropthumb_done && !$this->deleted && !$f->deleted ){
                    $f->_jcropthumb_done = 1;
                    $html .= '<input type="hidden" id="f_' . $this->assoc_field . '_refresh" name="f_'. $this->assoc_field .'_refresh" value="0" />';
                    ob_start();
                    ?>
                    $(function(){
                        if( !$("div#k_overlay").length ){
                            $('<div/>', {
                                id: 'k_overlay',
                            })
                            .css({
                                'filter':'alpha(opacity=60)', 'zoom':'1',
                                'opacity':'0.6',
                                'height': '100%',
                                'width': '100%',
                                'background-color': '#0b0b0b',
                                'z-index': 10000,
                                'position': 'absolute',
                                'top': 0,
                                'left': 0,
                                'display': 'none'
                            })
                            .appendTo( 'body' );
                        }

                        $('#f_<?php echo $this->assoc_field; ?>').bind('k_change', function(e){
                            $('#k_overlay').css('display', 'block');
                            var form = $('#<?php echo $CTX->get('k_cur_form', 2); ?>');
                            form.find('#f_<?php echo $this->assoc_field; ?>_refresh').val('1');
                            form.submit();
                            return false;
                        });
                    });
                    <?php
                    $js = ob_get_contents();
                    ob_end_clean();
                    $FUNCS->add_js( $js );

                    if( $this->hide_assoc_preview ){
                        $css = "#k_element_".$this->assoc_field." div.img-preview, #k_element_".$this->assoc_field." a.btn.popup-img, #k_label_f_".$this->name."{ display:none; }\r\n";
                        $css .= "#k_element_".$this->assoc_field." div.input-group.upload-group, #k_element_".$this->name." { margin-top: 0; }";
                        $css .= "#k_element_".$this->name." div.img-preview{ margin-top: -8px; }";
                        $FUNCS->add_css( $css );
                    }
                }
            }

            static $count=0;
            $count++;
            if( $count==1 ){
                ob_start();
                ?>
                function k_jcrop_thumb( field_id, data ){

                    var el_preview = '#'+field_id+'_preview';
                    var x = Math.round( $('#'+field_id + '_x').val() );
                    var y = Math.round( $('#'+field_id + '_y').val() );
                    var w = Math.round( $('#'+field_id + '_w').val() );
                    var h = Math.round( $('#'+field_id + '_h').val() );

                    var qs = '<?php echo JCROP_URL; ?>ajax.php?';
                    qs += 'data='+encodeURIComponent( data );
                    qs += '&x='+encodeURIComponent( x );
                    qs += '&y='+encodeURIComponent( y );
                    qs += '&w='+encodeURIComponent( w );
                    qs += '&h='+encodeURIComponent( h );

                    $.ajax({
                        dataType: "text",
                        url:      qs
                    }).done(function( response ) {
                        if( response.substr(0, 7)=='OK:http' ){

                            var popup = $('#'+field_id+'_pop');
                            popup.bPopup().close();

                            var href = response.substr(3);
                            href = href + '?rand=' + Math.random();
                            $(el_preview).attr('href', href);
                            try{
                                $('#'+field_id+'_tb_preview').attr('src', href);
                            }
                            catch( e ){}

                            //alert('<?php echo $FUNCS->t('thumb_recreated'); ?>');

                        }
                        else{
                            alert(response);
                        }
                    });
                }
                <?php
                $js = ob_get_contents();
                ob_end_clean();
                $FUNCS->add_js( $js );
            }

            return $html;
        }
    }

    // Register
    $FUNCS->register_udf( 'jcropthumb', 'JCropThumb', 0/*repeatable*/ );
