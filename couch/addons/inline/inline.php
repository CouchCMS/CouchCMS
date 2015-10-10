<?php

    if ( !defined('K_COUCH_DIR') ) die(); // cannot be loaded directly
    define( 'K_INLINE_BUILD', '20140106h' );

    class Inline{

        function load_edit_handler( $params, $node ){
            global $AUTH, $FUNCS, $CTX;
            if( ($AUTH->user->access_level < K_ACCESS_LEVEL_ADMIN) || $CTX->get('k_disable_edit') ) return;

            extract( $FUNCS->get_named_vars(
                array(
                    'skip_ckeditor'=>'0',
                    'no_border'=>'0',
                    'prompt_text'=>'',
                ),
                $params)
            );
            $skip_ckeditor = ( trim($skip_ckeditor)==1 ) ? 1 : 0;
            $no_border = ( trim($no_border)==1 ) ? 1 : 0; // border around contenteditable areas
            $prompt_text = trim( $prompt_text );
            $prompt_text = strlen( $prompt_text ) ? str_replace( "'", "\'", $prompt_text ) : 'Inline-edit has unsaved changes';

            $js_link = K_ADMIN_URL . 'addons/inline/tinybox2/tinybox.js?ver='.K_INLINE_BUILD;
            $css_link = K_ADMIN_URL . 'addons/inline/tinybox2/style.css?ver='.K_INLINE_BUILD;

            ob_start();
            ?>
            <script type="text/javascript" src="<?php echo $js_link; ?>"></script>
            <link rel="stylesheet" href="<?php echo $css_link; ?>" />
            <?php
            if( !$skip_ckeditor ){
                require_once( K_COUCH_DIR.'addons/inline/view/scripts.php' );
            }
            else{
                $CTX->set( 'k_disable_inline_edit', '1', 'global' );
            }
            $html = ob_get_contents();
            ob_end_clean();

            return $html;
        }

        function no_edit_handler( $params, $node ){
            global $CTX;

            $CTX->set( 'k_disable_edit', '1', 'global' );
        }

        function inline_handler( $params, $node ){
            global $CTX, $FUNCS, $AUTH;
            if( ($AUTH->user->access_level < K_ACCESS_LEVEL_ADMIN) || $CTX->get('k_disable_edit') ) return;

            extract( $FUNCS->get_named_vars(
                array(
                    'fields'=>'',
                    'link_text'=>'Edit',
                    'prompt_text'=>'',
                    'toolbar'=>'',
                    'custom_toolbar'=>'',
                    'custom_styles'=>'',
                    'class'=>'',
                    'url_only'=>'0', /* for cms:inline_link */
                ),
                $params)
            );

            // sanitize params
            $fields = trim( $fields );
            if( !$fields ){
                die( "ERROR: Tag \"".$node->name."\": no fields specified" );
            }
            $link_text = trim( $link_text ); // can be set to empty
            $prompt_text = trim( $prompt_text );
            $prompt_text = strlen( $prompt_text ) ? str_replace( "'", "\'", $prompt_text ) : 'Please save the inline-edit changes first';
            $toolbar = strtolower( trim($toolbar) );
            if( !in_array($toolbar, array('small', 'basic', 'medium', 'full', 'custom')) ) $toolbar='default';
            $custom_toolbar = trim( $custom_toolbar );
            $custom_styles = trim( $custom_styles );
            $class = trim( $class );
            if( strlen($class) ) $class = ' ' . $class;
            $url_only = ( trim($url_only)==1 ) ? 1 : 0;

            // get page_id (return if used in context of list_view)
            if( $CTX->get('k_is_page') ){
                $page_id = $CTX->get('k_page_id');
            }
            elseif( $CTX->get('k_is_list_page') ){ // non-clonable template
                $page_id = 0;
            }
            else return; // happens in list_view

            // template_id
            $tpl_id = $CTX->get('k_template_id');

            $obj_id = ( $page_id ) ? $page_id : $tpl_id;
            $nonce = $FUNCS->create_nonce( 'edit_page_' . $obj_id );

            // create link
            $url = K_ADMIN_URL."addons/inline/index.php?act=edit&tpl=".$tpl_id."&p=".$page_id."&nonce=".$nonce."&flist=".$fields;
            $onclick = "TINY.box.show({iframe:'".$url."',animate:false,width:795,height:535,boxid:'k_inline',modal:1});";
            if( !$CTX->get('k_disable_inline_edit') ){
                $onclick = "if(window.CKEDITOR && window.CKEDITOR.k_regions){for(var i=0;i<window.CKEDITOR.k_regions.length;i++){if(window.CKEDITOR.k_regions[i].checkDirty()){alert( '".$prompt_text."' );window.CKEDITOR.k_regions[i].focus();return false;}}}" . $onclick;
            }

            if( $node->name=='inline_link' ){
                $html = ( $url_only ) ? $url : $onclick;
            }
            elseif( $node->name=='inline_edit' ){
                if( $CTX->get('k_disable_inline_edit') ) return;

                $url = $url . '&ajax=1';
                $html = ' data-k-inline="'.$url.'" data-k-toolbar="'.$toolbar.'" contenteditable="true" ';
                if( $toolbar=='custom' ){
                    $custom_toolbar = Inline::_toolbar( $custom_toolbar );
                    $html .= "data-k-custom-toolbar='$custom_toolbar' ";
                }
                if( strlen($custom_styles) ){
                    list( $custom_style_name, $custom_style_file ) = array_map( "trim", explode( '=', $custom_styles ) );
                    if( strpos($custom_style_file, '://')===false ){
                        $custom_style_file = K_SITE_URL . (( $custom_style_file{0}=='/' ) ? substr($custom_style_file, 1) : $custom_style_file);
                    }
                    $custom_styles = $custom_style_name . ':' . $custom_style_file;
                    $html .= "data-k-custom-styles='$custom_styles' ";
                }
            }
            else{ //popup_edit
                $html = "<a href=\"#\" class=\"k_inline".$class."\" onclick=\"".$onclick."return false;\">".$link_text."</a>";
            }

            return $html;
        }

        function _toolbar( $str_toolbar ){

            if( $str_toolbar ){
                $available_buttons = array(
                    'bold'=>'Bold',
                    'italic'=>'Italic',
                    'underline'=>'Underline',
                    'strike'=>'Strike',
                    'subscript'=>'Subscript',
                    'superscript'=>'Superscript',
                    'justifyleft'=>'JustifyLeft',
                    'justifycenter'=>'JustifyCenter',
                    'justifyright'=>'JustifyRight',
                    'justifyblock'=>'JustifyBlock',
                    'numberedlist'=>'NumberedList',
                    'bulletedlist'=>'BulletedList',
                    'outdent'=>'Outdent',
                    'indent'=>'Indent',
                    'blockquote'=>'Blockquote',
                    'undo'=>'Undo',
                    'redo'=>'Redo',
                    'removeformat'=>'RemoveFormat',
                    'cut'=>'Cut',
                    'copy'=>'Copy',
                    'paste'=>'Paste',
                    'pastetext'=>'PasteText',
                    'pastefromword'=>'PasteFromWord',
                    'image'=>'Image',
                    'table'=>'Table',
                    'horizontalrule'=>'HorizontalRule',
                    'smiley'=>'Smiley',
                    'specialchar'=>'SpecialChar',
                    'pagebreak'=>'PageBreak',
                    'link'=>'Link',
                    'unlink'=>'Unlink',
                    'anchor'=>'Anchor',
                    'styles'=>'Styles',
                    'format'=>'Format',
                    'font'=>'Font',
                    'fontsize'=>'FontSize',
                    'textcolor'=>'TextColor',
                    'bgcolor'=>'BGColor',
                    'showblocks'=>'ShowBlocks',
                    'spellchecker'=>'SpellChecker',
                    'youtube'=>'Youtube',
                    '-'=>'-',
                    '_'=>'-'
                );

                $str_tb_buttons = '';
                $row_sep = '';
                $arr_toolbars = array_map( "trim", explode( '|', $str_toolbar ) );
                foreach( $arr_toolbars as $toolbar ){
                    $arr_buttons = array_map( "trim", explode( ',', $toolbar ) );
                    if( count($arr_buttons)==1 && $arr_buttons[0]=='' ){
                        $str_tb_buttons .= $row_sep . '"/"';
                        $row_sep = ',';
                    }
                    else{
                        $btn_sep = '';
                        $str_tmp = '';
                        foreach( $arr_buttons as $btn ){
                            if( array_key_exists( strtolower($btn), $available_buttons ) ){
                                $str_tmp .= $btn_sep . '"' . $available_buttons[strtolower($btn)] . '"';
                                $btn_sep = ',';
                            }
                            // Check if a custom button specified (starts with a #)
                            elseif( substr($btn, 0, 1)=='#' ){
                                $str_tmp .= $btn_sep . '"' . substr( $btn, 1 ) . '"';
                                $btn_sep = ',';
                            }
                        }
                        if( strlen($str_tmp) ){
                            $str_tb_buttons .= $row_sep . '['.$str_tmp.']';
                            $row_sep = ',';
                        }
                    }
                }
            }
            return '[' . $str_tb_buttons  . $row_sep . '["inlinesave"]]';
        }

    } // end class

    $FUNCS->register_tag( 'load_edit', array('Inline', 'load_edit_handler') );
    $FUNCS->register_tag( 'no_edit', array('Inline', 'no_edit_handler') );
    $FUNCS->register_tag( 'popup_edit', array('Inline', 'inline_handler') );
    $FUNCS->register_tag( 'inline_edit', array('Inline', 'inline_handler') );
    $FUNCS->register_tag( 'inline_link', array('Inline', 'inline_handler') );
