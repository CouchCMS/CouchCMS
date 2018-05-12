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

    class Nicedit extends KUserDefinedField{

        static function handle_params( $params ){
            global $FUNCS, $AUTH;
            if( $AUTH->user->access_level < K_ACCESS_LEVEL_SUPER_ADMIN ) return;

            $attr = $FUNCS->get_named_vars(
                array(
                    'buttons'=>'',
                    'maxheight'=>'0',
                  ),
                $params
            );
            $attr['maxheight'] = $FUNCS->is_non_zero_natural( $attr['maxheight'] ) ? intval( $attr['maxheight'] ) : 0;
            $attr['buttons'] = trim( $attr['buttons'] );
            $available_buttons = array(
                'bold'=>'bold',
                'italic'=>'italic',
                'underline'=>'underline',
                'left'=>'left',
                'center'=>'center',
                'right'=>'right',
                'justify'=>'justify',
                'ol'=>'ol',
                'ul'=>'ul',
                'subscript'=>'subscript',
                'superscript'=>'superscript',
                'strikethrough'=>'strikethrough',
                'removeformat'=>'removeformat',
                'indent'=>'indent',
                'outdent'=>'outdent',
                'hr'=>'hr',
                'fontsize'=>'fontSize',
                'fontfamily'=>'fontFamily',
                'fontformat'=>'fontFormat',
                'link'=>'link',
                'unlink'=>'unlink',
                'forecolor'=>'forecolor',
                'bgcolor'=>'bgcolor',
                'image'=>'image',
                'source'=>'xhtml',
            );
            $arr_buttons = array_map( "trim", explode( ',', $attr['buttons'] ) );
            $arr_tmp = array();
            foreach( $arr_buttons as $btn ){
                if( array_key_exists( strtolower($btn), $available_buttons ) ){
                    $arr_tmp[] = $available_buttons[strtolower($btn)];
                }
            }
            if( count($arr_tmp) ){
                $buttons=$sep='';
                foreach( $arr_tmp as $btn ){
                    $buttons .= $sep."'".$btn."'";
                    $sep=',';
                }
                $attr['buttons'] = "[".$buttons."]";
            }
            else{
                $attr['buttons']="['bold','italic','underline','ol','ul','link','unlink','image','removeformat','xhtml']"; // default set of buttons
            }

            return $attr;
        }

        // Posted data
        function store_posted_changes( $post_val ){
            global $FUNCS;
            if( $this->deleted || $this->k_inactive ) return; // no need to store

            if( is_null($this->orig_data) ) $this->orig_data = $this->data;
            if( $this->trust_mode==0 ){
                // if value submitted from front-end form, input is untrusted and so only a limited subset of HTML tags will be allowed
                $allowed_tags = '<a><br><strong><b><em><i><u><blockquote><pre><code><ul><ol><li><del>';
                $this->data = trim( $FUNCS->cleanXSS(strip_tags($post_val, $allowed_tags), 1) );
            }
            else{
                $this->data = $FUNCS->cleanXSS( $post_val );
            }
            $this->modified = ( strcmp( $this->orig_data, $this->data )==0 ) ? false : true; // values unchanged
        }

        function is_empty(){
            $data = trim( $this->get_data() );
            return ( strlen($data) ) ? false : true;
        }

        function _render( $input_name, $input_id, $extra1='', $dynamic_insertion=0 ){
            global $FUNCS, $CTX;

            /*
            // calc paths to assets. Current script assumed to be somewhere within or below site's root (i.e. Couch's parent folder).
            $path = str_replace( '\\', '/', dirname(realpath(__FILE__)).'/' );
            if( (strpos($path, K_SITE_DIR)===0) && ($path != K_SITE_DIR) ){
               $subdomain = substr( $path, strlen(K_SITE_DIR) );
            }
            if( !defined('NICEDIT_URL') ) define( 'NICEDIT_URL', K_SITE_URL . $subdomain );
            $FUNCS->load_js( NICEDIT_URL . 'nicedit.js?kver=' . time() );
            */
            if( !defined('NICEDIT_URL') ){
                define( 'NICEDIT_URL', K_ADMIN_URL . 'addons/nicedit/' );
                $FUNCS->load_js( NICEDIT_URL . 'nicedit.js' );
                $FUNCS->load_css( NICEDIT_URL . 'nicedit.css' );
            }
            $style = ( $this->height ) ? 'height:'.$this->height.'px; ' : '';
            $style .= ( $this->width ) ? 'width:'.$this->width.'px; ' : 'width:100%; ';
            $html .= '<div class="nicEdit-wrapper" style="'.( $this->width ? 'width:'.$this->width.'px;' : 'width:100%;').'"><textarea id="' . $input_id . '" name="'. $input_name .'" '.$rtl.' rows="12" cols="79" style="'.$style.'" '.$extra.'>'. htmlspecialchars( $this->get_data(), ENT_QUOTES, K_CHARSET ) .'</textarea></div>';

            if( $this->maxheight && $this->height && ($this->maxheight < $this->height) ){
                $this->maxheight = $this->height;
            }

            if( !$dynamic_insertion ){
                ob_start();
                ?>
                $(function(){
                    var ed = new nicEditor({iconsPath : '<?php echo K_SYSTEM_THEME_URL; ?>assets/', buttonList : <?php echo $this->buttons; ?><?php if($this->maxheight){echo ', maxHeight : '.$this->maxheight;}?>}).panelInstance('<?php echo $input_id; ?>');

                    $('#btn_submit').bind("my_submit", function(event){
                        var el = nicEditors.findEditor('<?php echo $input_id ?>');
                        if (el) el.saveContent();
                    });

                    <?php if( $dynamic_insertion===false ){ /* not dynamically inserted but is within repeatable regions */ ?>
                    try{
                        var parentRow = $('#<?php echo $input_id ?>').closest('tr');
                        if(parentRow.length){
                            parentRow.bind('row_delete', function(event){
                                ed.removeInstance('<?php echo $input_id ?>');
                            });
                        }
                    }
                    catch(e){}
                    <?php } ?>
                });
                <?php
                $js = ob_get_contents();
                ob_end_clean();
                $FUNCS->add_js( $js );
            }
            else{
                // Being dynamically inserted (e.g. through 'repeatable' tag).
                // Simply outputting script will not work.
                // Have to use a workaround (http://24ways.org/2005/have-your-dom-and-script-it-too).
                // Additionally, we are adding an id - the logic is that the id gets duplicated into 'idx' for the 'template' row code.
                // This 'idx' will not be present in the cloned rows. We use this property to avoid executing JavaScript in template row.
                ob_start();
                ?>
                <img src="<?php echo K_SYSTEM_THEME_URL; ?>assets/blank.gif" alt="" id="<?php echo $input_id ?>_dummyimg" onload="
                    el=$('#<?php echo $input_id ?>_dummyimg');
                    if(!el.attr('idx')){
                    var ed = new nicEditor({iconsPath : '<?php echo K_SYSTEM_THEME_URL; ?>assets/', buttonList : <?php echo $this->buttons; ?><?php if($this->maxheight){echo ', maxHeight : '.$this->maxheight;}?>}).panelInstance('<?php echo $input_id; ?>');

                    $('#btn_submit').bind('my_submit', function(event){
                        var el = nicEditors.findEditor('<?php echo $input_id ?>');
                        if (el) el.saveContent();
                    });

                    var parentRow = el.closest('tr');
                    if(parentRow.length){
                        parentRow.bind('row_delete', function(event){
                            ed.removeInstance('<?php echo $input_id ?>');
                        });
                    }
                    el.css('display', 'none');
                    }
                " />
                <?php
                $html .= ob_get_contents();
                ob_end_clean();
            }
            return $html;
        }
    }

    // Register
    $FUNCS->register_udf( 'nicedit', 'Nicedit', 1/*repeatable*/ );
