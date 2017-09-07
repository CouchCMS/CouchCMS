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
    if( !defined('K_USE_KC_FINDER') ) define( 'K_USE_KC_FINDER', 0 );

    class KField{
        var $id;
        var $template_id;
        var $name;
        var $label;
        var $k_desc;
        var $k_type;
        var $hidden;
        var $search_type;
        var $k_order;
        var $data;
        var $default_data;
        var $required;
        var $validator;
        var $validator_msg;
        var $k_separator;
        var $val_separator;
        var $opt_values;
        var $opt_selected;
        var $toolbar;
        var $custom_toolbar;
        var $css;
        var $custom_styles;
        var $maxlength;
        var $height;
        var $width;
        var $k_group;
        var $collapsed;
        var $assoc_field;
        var $crop;
        var $enforce_max;
        var $quality;
        var $show_preview;
        var $preview_width;
        var $preview_height;
        var $no_xss_check;
        var $rtl;
        var $body_id;
        var $body_class;
        var $disable_uploader;
        var $dynamic;
        var $custom_params;
        var $searchable;
        var $class;


        var $page;
        var $siblings;
        var $processed;
        var $system = 0;
        var $err_msg = '';
        var $modified = false;
        var $udf = 0;
        var $cached = null;
        var $refresh_form = 0;
        var $err_msg_refresh = '';
        var $requires_multipart = 0;
        var $trust_mode = 1;
        var $simple_mode = 0;
        var $no_js = 0;
        var $orig_data = null;
        var $module = null;

        var $no_render = 0;

        var $available_validators = array(
            'min_len' => 'KFuncs::validate_min_len',
            'max_len' => 'KFuncs::validate_max_len',
            'exact_len' => 'KFuncs::validate_exact_len',
            'alpha' => 'KFuncs::validate_alpha',
            'alpha_num' => 'KFuncs::validate_alpha_num',
            'integer' => 'KFuncs::validate_int',
            'non_negative_integer' => 'KFuncs::validate_natural',
            'non_zero_integer' => 'KFuncs::validate_non_zero_natural',
            'decimal' => 'KFuncs::validate_numeric',
            'non_negative_decimal' => 'KFuncs::validate_non_negative_numeric',
            'non_zero_decimal' => 'KFuncs::validate_non_zero_numeric',
            'email' => 'KFuncs::validate_email',
            'url' => 'KFuncs::validate_url',
            'matches_field' => 'KFuncs::validate_matches',
            'regex' => 'KFuncs::validate_regex',
            /* used only internally */
            'title_ready' => 'KFuncs::validate_title',
            'unique_page' => 'KFuncs::validate_unique_page'
        );

        var $available_buttons = array(
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
            'flash'=>'Flash',
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
            'preview'=>'Preview',
            'maximize'=>'Maximize',
            'source'=>'Source',
            'spellchecker'=>'SpellChecker',
            '-'=>'-',
            '_'=>'-'
        );

        function __construct( $row, &$page, &$siblings ){
            global $FUNCS;

            foreach( $row as $k=>$v ){
               $this->$k = $v;
            }

            $this->page = &$page;
            $this->siblings = &$siblings;
        }

        // Invoked only while editing a page where all parameters of a field (instead of just the usual data) are needed.
        function resolve_dynamic_params(){
            if( !$this->system && $this->dynamic ){
                $arr_dynamic = array_map( "trim", explode( '|', $this->dynamic ) );
                foreach( $arr_dynamic as $dyn_param ){
                    if( in_array($dyn_param, array( 'desc', 'type', 'order', 'group', 'separator' )) ){
                        $dyn_param = 'k_'.$dyn_param;
                    }

                    if( array_key_exists($dyn_param, $this) && $this->$dyn_param ){
                        if( defined('K_SNIPPETS_DIR') ){ // always defined relative to the site
                            $base_snippets_dir = K_SITE_DIR . K_SNIPPETS_DIR . '/';
                        }
                        else{
                            $base_snippets_dir = K_COUCH_DIR . 'snippets/';
                        }
                        $filepath = $base_snippets_dir . ltrim( trim($this->$dyn_param), '/\\' );

                        if( file_exists($filepath) ){
                            $html = @file_get_contents($filepath);
                            if( strlen($html) ){
                                $parser = new KParser( $html );
                                $this->$dyn_param = $parser->get_HTML();
                            }
                        }
                    }
                }
            }
        }

        function store_posted_changes( $post_val ){
            global $FUNCS, $Config;
            if( $this->deleted ) return; // no need to store
            if( in_array($this->k_type, array('thumbnail', 'hidden', 'message', 'group')) ) return;

            if( $this->k_type== 'checkbox' && is_array($post_val) ){
                $separator = ( $this->k_separator ) ? $this->k_separator : '|';
                $sep = '';
                $str_val = '';
                foreach( $post_val as $v ){
                    $str_val .= $sep . $v;
                    $sep = $separator;
                }
                $post_val = $str_val;
            }

            // v1.4 - added sanity check for checkbox, radio and dropdown types
            // to allow only submitted values that are a subset of the available options
            if( $this->k_type=='dropdown' || $this->k_type=='radio' || $this->k_type=='checkbox' ){
                $post_val = trim( $post_val );
                if( $post_val!='' ){
                    $separator = ( $this->k_separator ) ? $this->k_separator : '|';
                    $val_separator = ( $this->val_separator ) ? $this->val_separator : '=';

                    // get the selected options
                    $selected = html_entity_decode( $post_val, ENT_QUOTES, K_CHARSET );
                    if( $this->k_type=='checkbox' ){
                        $selected = ( $selected != '' ) ? array_map( "trim", preg_split( "/(?<!\\\)\\".$separator."/", $selected ) ) : array();
                        for( $x=0; $x<count($selected); $x++ ){
                            $selected[$x] = str_replace( '\\'.$separator, $separator, $selected[$x] );
                            $selected[$x] = str_replace( '\\'.$val_separator, $val_separator, $selected[$x] );
                        }
                    }

                    // get the valid options
                    $valid_values = array();
                    $arr_values = array_map( "trim", preg_split( "/(?<!\\\)\\".$separator."/", $this->opt_values ) );
                    foreach( $arr_values as $val ){
                        if( $val!='' ){
                            $val = str_replace( '\\'.$separator, $separator, $val );
                            $arr_values_args = array_map( "trim", preg_split( "/(?<!\\\)\\".$val_separator."/", $val ) );
                            if( isset($arr_values_args[1]) ){
                                $opt_val = str_replace( '\\'.$val_separator, $val_separator, $arr_values_args[1] );
                            }
                            else{
                                $opt_val = str_replace( '\\'.$val_separator, $val_separator, $arr_values_args[0] );
                            }
                            $valid_values[] = $opt_val;
                        }
                    }

                    // remove selected options that are not valid
                    if( !count($valid_values) ){
                        $post_val = '';
                    }
                    else{
                        if( $this->k_type=='dropdown' || $this->k_type=='radio' ){
                            if( !in_array($selected, $valid_values) ){
                                $post_val = '';
                            }
                        }
                        else{
                            $sep = '';
                            $str_val = '';
                            foreach( $selected as $v ){
                                if( in_array($v, $valid_values) ){
                                    $str_val .= $sep . str_replace( $separator, '\\'.$separator, $v );
                                    $sep = $separator;
                                }
                            }
                            $post_val = $str_val;
                        }
                    }
                }
            }

            // strip off domain info from uploads
            if( $this->k_type=='image' || $this->k_type=='file' ){
                $domain_prefix = $Config['k_append_url'] . $Config['UserFilesPath'] . $this->k_type . '/';
                $post_val = trim( $post_val );
                if( strpos($post_val, $domain_prefix)===0 ){
                    $post_val = substr( $post_val, strlen($domain_prefix) );
                    $post_val = ( $post_val ) ? ':' . $post_val : ':'; // add marker
                    if( is_null($this->orig_data) ) $this->orig_data = $this->data;
                }
                else{
                    if( is_null($this->orig_data) ) $this->orig_data = $this->get_data();
                }
            }
            else{
                if( is_null($this->orig_data) ) $this->orig_data = $this->get_data();
            }

            if( $this->trust_mode==0 && in_array($this->k_type, array('text', 'textarea', 'richtext')) ){
                // if value submitted from front-end form, input is untrusted and so only a limited subset of HTML tags will be allowed
                if( $this->k_type=='richtext' ){
                    $allowed_tags = '<a><br><strong><b><em><i><u><blockquote><pre><code><ul><ol><li><del><strike><div><p><h1><h2><h3><h4><h5><h6>';
                    $this->data = trim( $FUNCS->cleanXSS(strip_tags($post_val, $allowed_tags), 2) );
                }
                else{
                    $this->data = trim( $FUNCS->cleanXSS(strip_tags($post_val)) );
                }
            }
            else{
                $this->data = ($this->k_type=='textarea' && $this->no_xss_check) ? $post_val : $FUNCS->cleanXSS( $post_val );
            }
            $this->modified = ( strcmp( $this->orig_data, $this->data )==0 ) ? false : true; // values unchanged
        }

        // Meant to be overridden by custom fields to store raw data picked from database into the field object.
        function store_data_from_saved( $data ){
            $this->data = $data;
        }

        // Meant to be overridden by custom fields to give data to be saved in database
        // If a custum field overrides this function, it should also override store_data_from_saved() as both deal directly with $this->data
        // From KUserDefinedField onwards, this function will return data straight from $f->data.
        // For now using $f->get_data for backward functionality with our in-built fields.
        function get_data_to_save(){
            return $this->get_data();
        }

        // Used primarily for display on front-end (by feeding the content of this field into $CTX ) but is
        // also used by _render() of built-in fields to render the field in admin panel.
        function get_data(){
            global $Config;

            if( !$this->data ){
                // make sure it is not numeric 0
                $data = ( is_numeric($this->data) ) ? (string)$this->data : $this->default_data;
            }
            else{
                $data = $this->data;
            }

            if( $this->search_type!='text' ){
                $pos = strpos( $data, ".00");
                if( $pos!==false ){
                    $data = substr( $data, 0, $pos );
                }
            }
            else{
                // add domain info to uploaded items
                if( $this->k_type=='image' || $this->k_type=='thumbnail' || $this->k_type=='file' ){
                    if( $data{0}==':' ){ // if marker
                        $data = substr( $data, 1 );
                        $folder = ( $this->k_type=='thumbnail' ) ? 'image' : $this->k_type;
                        $domain_prefix = $Config['k_append_url'] . $Config['UserFilesPath'] . $folder . '/';
                        $data = $domain_prefix . $data;
                    }
                }
            }

            return $data;
        }

        function validate(){
            global $FUNCS;

            if( $this->deleted ) return true; // skip deleted fields
            if( $this->page->tpl_nested_pages && !$this->system && $this->page->_fields['k_is_pointer']->get_data() ) return true; // skip custom fields if this nested page is a pointer_page

            $this->err_msg = '';
            $separator = ( $this->k_separator ) ? $this->k_separator : '|';
            $val_separator = ( $this->val_separator ) ? $this->val_separator : '=';


            // validation failure messages
            $msgs = array();
            if( $this->validator_msg ){
                $arr_msgs = explode( $separator, $this->validator_msg );
                $arr_msgs = array_map( "trim", $arr_msgs );
                foreach( $arr_msgs as $msg ){
                    $arr_msgs_parts = explode( $val_separator, $msg );
                    $arr_msgs_parts = array_map( "trim", $arr_msgs_parts );
                    $msgs[$arr_msgs_parts[0]] = $arr_msgs_parts[1];
                }
            }

            // check if a required field
            if( $FUNCS->is_core_type($this->k_type) ){
                $data = trim( $this->get_data() );
                if( $this->required ){
                    switch( $this->k_type ){
                        case 'text':
                        case 'password':
                        case 'textarea':
                        case 'richtext':
                        case 'image':
                        case 'file':
                        case 'radio':
                        case 'checkbox':
                            if( $data=='' ){
                                $check_failed = true;
                            }
                            break;
                        case 'thumbnail':
                        case 'hidden':
                        case 'message':
                        case 'group':
                            break; // no validation required
                        case 'dropdown':
                            if( $data=='' || $data=='-' || $data=='_' ){
                                $check_failed = true;
                            }

                    }

                }
                else{
                    // if not a required field and no data submitted, no further checks needed.
                    if( !strlen($data) ) return true;
                }
            }
            else{ // UDFs
                if( $this->is_empty() ){
                    if( $this->required ){
                        $check_failed = true;
                    }
                    else{
                        return true;
                    }
                }
            }

            if( $check_failed ){
                if( array_key_exists('required', $msgs) ){
                    $this->err_msg = $msgs['required'];
                }
                else{
                    $this->err_msg = $FUNCS->t('required_msg');
                }
                return false;
            }

            // custom validators
            if( $this->validator || ($this->search_type!='text') ){
                $int_validators = array( 'integer', 'natural', 'non_zero_natural' );
                $decimal_validators = array( 'numeric', 'non_negative_numeric', 'non_zero_numeric' );
                if( $this->search_type!='text' ) $validation_required = 1;

                $arr_validators = array();
                if( $this->validator ){
                    $arr_validator_elems = array_map( "trim", explode( $separator, $this->validator ) );
                    foreach( $arr_validator_elems as $validator_elem ){
                        $args = array_map( "trim", explode( $val_separator, $validator_elem ) );
                        $arr_validators[$args[0]] = $args[1];

                        // lookout if user has already defined a proper validator for 'integer' and 'decimal' search types.
                        if( $this->search_type=='integer' ){
                            if( in_array($args[0], $int_validators) ) $validation_required = 0;
                        }
                        elseif( $this->search_type=='decimal' ){
                            if( in_array($args[0], $decimal_validators) ) $validation_required = 0;
                        }
                    }
                }
                if( $validation_required ){
                    // add appropriate validator if not added by user
                    if( $this->search_type=='integer' ){
                        $arr_validators['integer']='';
                    }
                    elseif( $this->search_type=='decimal' ){
                        $arr_validators['decimal']='';
                    }
                }

                foreach( $arr_validators as $validator=>$validator_args ){
                    if( array_key_exists($validator, $this->available_validators) ){
                        $validator_func = $this->available_validators[$validator];
                    }
                    elseif( array_key_exists($validator, $FUNCS->validators) ){
                        $validator_func = $FUNCS->validators[$validator];
                    }
                    else{
                        $validator_func = trim( $validator ); // allow user defined validator
                    }

                    $validator_func = $FUNCS->is_callable( $validator_func );
                    if( !$validator_func ){
                        $this->err_msg = "Validator '".$validator."' not found";
                        return false;
                    }
                    $err = call_user_func_array( $validator_func, array(&$this, $validator_args) );

                    if( $FUNCS->is_error($err) ){
                        if( array_key_exists($validator, $msgs) ){
                            $this->err_msg = $msgs[$validator];
                        }
                        else{
                            $this->err_msg = $err->err_msg;
                        }
                        return false;
                    }
                }
            }
            return true;
        }

        // UDFs should override this method if using parent KField's validate method which calls this.
        // If field is a required one, being empty will return 'required field' validation error.
        // If field is non-required, being empty will not process any other validation rules attached to it.
        function is_empty(){
            // Default will always return false thus causing 'required' parameter not to take effect;
            return false;
        }

        function _prep_cached(){

        }

        // tweak how the field gets rendered in the admin-panel
        function pre_render( &$def ){
            global $FUNCS;

            if( $this->k_type=='group' ){ $def['content']=''; }
            elseif( $this->k_type=='thumbnail' ){
                $def['desc'] = ( $def['desc']!='') ? $def['desc'].'&nbsp;&nbsp;'.$FUNCS->t('thumb_created_auto') : $FUNCS->t('thumb_created_auto');
            }
        }

        function render(){ // defunct from v2.0

        }

        function _render( $input_name, $input_id, $extra='', $dynamic_insertion=0 ){
            global $FUNCS;

            $html = $FUNCS->render( 'field_'.$this->k_type, $this, $input_name, $input_id, $extra, $dynamic_insertion );

            return $html;
        }

        //// overridable render functions for core fields ////

        // textarea
        static function _render_textarea( $f, $input_name, $input_id, $extra, $dynamic_insertion ){
            global $CTX;

            KField::_set_common_vars( $f->k_type, $input_name, $input_id, $extra, $dynamic_insertion, $f->simple_mode );
            $arr_vars = array();
            $arr_vars['k_field_value'] = htmlspecialchars( $f->get_data(), ENT_QUOTES, K_CHARSET );
            $arr_vars['k_field_rtl'] = ( $f->rtl ) ? '1' : '0';
            $arr_vars['k_field_height'] = $f->height;
            $arr_vars['k_field_width'] = $f->width;

            $CTX->set_all( $arr_vars );
        }

        // richtext
        static function _render_richtext( $f, $input_name, $input_id, $extra, $dynamic_insertion ){
            global $FUNCS;

            if( $f->deleted || $f->no_js ){
                return $FUNCS->render( 'field_textarea', $f, $input_name, $input_id, $extra, $dynamic_insertion );
            }

            $html = '';
            $value = $f->get_data();
            $separator = ( $f->k_separator ) ? $f->k_separator : '|';
            $val_separator = ( $f->val_separator ) ? $f->val_separator : '=';
            $rtl = ( $f->rtl ) ? 'dir="RTL"' : '';

            if( !$f->page->CKEditor ){
                require_once( K_COUCH_DIR.'includes/ckeditor/ckeditor.php' );

                $f->page->CKEditor = new CKEditor();
                $f->page->CKEditor->returnOutput = true;
                $f->page->CKEditor->basePath = K_ADMIN_URL . 'includes/ckeditor/';

                if( $f->trust_mode ){
                    if( K_USE_KC_FINDER ){
                        $f->page->CKEditor->config['filebrowserBrowseUrl'] = K_ADMIN_URL . 'includes/kcfinder/browse.php?nonce='.$FUNCS->create_nonce( 'kc_finder' ).'&type=file';
                        $f->page->CKEditor->config['filebrowserImageBrowseUrl'] = K_ADMIN_URL . 'includes/kcfinder/browse.php?nonce='.$FUNCS->create_nonce( 'kc_finder' ).'&type=image';
                        $f->page->CKEditor->config['filebrowserFlashBrowseUrl'] = K_ADMIN_URL . 'includes/kcfinder/browse.php?nonce='.$FUNCS->create_nonce( 'kc_finder' ).'&type=flash';
                        $f->page->CKEditor->config['filebrowserWindowWidth'] = '670';
                    }
                    else{
                        $f->page->CKEditor->config['filebrowserBrowseUrl'] = K_ADMIN_URL . 'includes/fileuploader/browser/browser.html';
                        $f->page->CKEditor->config['filebrowserImageBrowseUrl'] = K_ADMIN_URL . 'includes/fileuploader/browser/browser.html?Type=Image';
                        $f->page->CKEditor->config['filebrowserFlashBrowseUrl'] = K_ADMIN_URL . 'includes/fileuploader/browser/browser.html?Type=Flash';
                        $f->page->CKEditor->config['filebrowserWindowWidth'] = '600';
                    }
                }
                $f->page->CKEditor->config['width'] = '100%';
                $f->page->CKEditor->config['height'] = 240;

            }
            $f->page->CKEditor->textareaAttributes = array("style" => "visibility:hidden", "id" => $input_id, "cols" => 80, "rows" => 15);

            //$config['baseHref'] = K_SITE_URL;
            // RTL
            if( $rtl ) $config['contentsLangDirection'] = 'rtl';

            // body class and body id
            if( $f->body_class ) $config['bodyClass'] = $f->body_class;
            if( $f->body_id ) $config['bodyId'] = $f->body_id;

            // height
            if( $f->height && $f->height > 0 ){
                $config['height'] = $f->height .'px';
            }

            // width
            if( $f->width && $f->width > 0 ){
                $config['width'] = $f->width .'px';
            }

            // content css
            // Multiple css files can be loaded and non-local files are supported too.
            $arr_css[] = $f->page->CKEditor->basePath . 'contents.css';
            if( $f->css ){
                $arr_custom_css = array_map( "trim", explode( $separator, $f->css ) );
                foreach( $arr_custom_css as $css ){
                    if( strpos($css, '://')===false ){
                        $css = K_SITE_URL . (( $css{0}=='/' ) ? substr($css, 1) : $css);
                    }
                    $arr_css[] = $css;
                }
            }
            $config['contentsCss'] = $arr_css;

            // custom styles dropdown
            // Only a single file can be added. Non local file supported.
            if( $f->custom_styles ){
                list( $custom_style_name, $custom_style_file ) = array_map( "trim", explode( $val_separator, $f->custom_styles ) );
                if( strpos($custom_style_file, '://')===false ){
                    $custom_style_file = K_SITE_URL . (( $custom_style_file{0}=='/' ) ? substr($custom_style_file, 1) : $custom_style_file);
                }
                $config['stylesCombo_stylesSet'] = $custom_style_name . ':' . $custom_style_file;
            }

            // rendered within repeatable region?
            $repeatable = 0;
            if( $dynamic_insertion || $dynamic_insertion===false ){
                // NOTE: With v2.0, for regions *within* repeatable-regions, when the need arose to differentiate between regions that already exist (from previous saves)
                // and those being added on-the-fly by clicking 'new', to preserve backward compatibility the $dynamic_insertion is set to boolean 'false' for
                // existing regions (and '1', as previously, for dynamically added).

                $repeatable = 1;
            }

            // toolbars
            $toolbar = $f->toolbar; // basic, medium, full.
            if( $repeatable && $toolbar=='' ) $toolbar = 'basic';

            if( $toolbar == 'full' ){
                $config['toolbar'] = array(
                    array( 'Bold', 'Italic', 'Underline', 'Strike', '-', 'Subscript', 'Superscript'),
                    array( 'JustifyLeft', 'JustifyCenter', 'JustifyRight', 'JustifyBlock' ),
                    array( 'NumberedList', 'BulletedList', '-', 'Outdent', 'Indent', 'Blockquote' ),
                    array( 'Undo', 'Redo', 'RemoveFormat' ),
                    '/',
                    array( 'Cut', 'Copy', 'Paste', 'PasteText', 'PasteFromWord' ),
                    array( 'Image', 'Flash', 'Table', 'HorizontalRule', 'Smiley', 'SpecialChar', 'PageBreak' ),
                    array( 'Link', 'Unlink', 'Anchor' ),
                    '/',
                    array( 'Styles', 'Format', 'Font', 'FontSize' ),
                    array( 'TextColor', 'BGColor' ),
                    array( 'ShowBlocks', 'Preview', 'Maximize', '-', 'Source'  )
                );
            }
            elseif( $toolbar == 'basic' ){
                $config['toolbar'] = array(
                    array( 'Bold', 'Italic', 'Underline', 'Strike' ),
                    array( 'Format' ),
                    array( 'NumberedList', 'BulletedList', 'Blockquote', 'Link', 'Unlink' ),
                    array( 'Undo', 'Redo', 'RemoveFormat' ),
                    array( 'Preview', 'Maximize', '-', 'Source'  )
                );
            }
            elseif( $toolbar == 'custom' ){
                $str_toolbar = $f->custom_toolbar;
                if( $str_toolbar ){
                    $arr_toolbars = array_map( "trim", explode( $separator, $str_toolbar ) );
                    foreach( $arr_toolbars as $toolbar ){
                        $arr_buttons = array_map( "trim", explode( ',', $toolbar ) );
                        if( count($arr_buttons)==1 && $arr_buttons[0]=='' ){
                            $arr_tb_buttons[] = '/';
                        }
                        else{
                            $arr_tmp = array();
                            foreach( $arr_buttons as $btn ){
                                if( array_key_exists( strtolower($btn), $f->available_buttons ) ){
                                    $arr_tmp[] = $f->available_buttons[strtolower($btn)];
                                }
                                // Check if a custom button specified (starts with a #)
                                elseif( substr($btn, 0, 1)=='#' ){
                                    $arr_tmp[] = substr( $btn, 1 );
                                }
                            }
                            if( count($arr_tmp) ) $arr_tb_buttons[] = $arr_tmp;
                        }
                    }
                    if( count($arr_tb_buttons) ) $config['toolbar'] = $arr_tb_buttons;
                }
            }
            else{ //medium (defult)
                $config['toolbar'] = array(
                    array( 'Bold', 'Italic', 'Underline', 'Strike', '-', 'Subscript', 'Superscript'),
                    array( 'Format' ),
                    array( 'JustifyLeft', 'JustifyCenter', 'JustifyRight', 'JustifyBlock' ),
                    array( 'NumberedList', 'BulletedList', '-', 'Outdent', 'Indent', 'Blockquote' ),
                    array( 'Undo', 'Redo', 'RemoveFormat' ),
                    '/',
                    array( 'Cut', 'Copy', 'Paste', 'PasteText', 'PasteFromWord' ),
                    array( 'Image', 'Flash', 'Table', 'HorizontalRule', 'Smiley', 'SpecialChar', 'PageBreak' ),
                    array( 'Link', 'Unlink', 'Anchor' ),
                    array( 'ShowBlocks', 'Preview', 'Maximize', '-', 'Source'  )
                );
            }

            if( $repeatable ){
                ob_start();
                ?>
                var parentRow = $("#"+eid).closest("tr");
                if(parentRow.length){
                    parentRow.bind("row_delete", function(event){
                        var ed = CKEDITOR.instances[ eid ];
                        if( typeof ed != "undefined" ){
                            ed.destroy();
                            CKEDITOR.remove( ed );
                        }
                    });
                    parentRow.bind("_reorder_start", function(event){
                        var ed = CKEDITOR.instances[ eid ];
                        if( typeof ed != "undefined" ){
                            if( typeof CKEDITOR.k_config_cks == "undefined" ) CKEDITOR.k_config_cks = [];
                            CKEDITOR.k_config_cks[eid] = ed.config;
                            var txtarea = $("#"+eid);
                            var dummy = txtarea.next(".cke").clone().addClass("dummy");
                            ed.destroy();
                            CKEDITOR.remove( ed );
                            txtarea.hide().after(dummy);
                        }
                    });
                    parentRow.bind("_reorder_stop", function(event){
                        CKEDITOR.replace( eid, CKEDITOR.k_config_cks[eid] );
                        var txtarea = $("#"+eid);
                        txtarea.next(".dummy").remove();
                    });
                }
                <?php
                $dyn_js = ob_get_contents();
                ob_end_clean();

                if( $dynamic_insertion ){ // being added on-the-fly.. output template markup that will be duplicated for each dynamically added new row
                    $tmp = $f->page->CKEditor->editor( $input_name, $value, $config );

                    if( preg_match('/<textarea.*?<\/textarea>/i', $tmp, $matches) ){
                        $html .= $matches[0];
                    }

                    if( strpos($tmp, 'window.CKEDITOR_BASEPATH')!==false ){
                        if( preg_match_all('/<script[^>]*>.*?<\/script>/s', $tmp, $matches, PREG_PATTERN_ORDER) ){
                            $matches = $matches[0]; // scripts
                            $tmp = array_pop( $matches );

                            $html0 = '';
                            foreach ($matches as $match) {
                                $html0 .= $match;
                            }
                            if( strlen($html0) ){ $FUNCS->add_html( $html0 ); };
                        }
                    }

                    if( preg_match('/CKEDITOR\\.replace\\(.*\\);/sim', $tmp, $matches) ){
                        $html1 = str_replace( '\'', '"', $matches[0] );
                    }

                    // Being dynamically inserted (e.g. through 'repeatable' tag).
                    // Simply outputting script will not work.
                    // Have to use a workaround (http://24ways.org/2005/have-your-dom-and-script-it-too).
                    // Additionally, we are adding an id - the logic is that the id gets duplicated into 'idx' for the 'template' row code.
                    // This 'idx' will not be present in the cloned rows. We use this property to avoid executing JavaScript in template row.
                    ob_start();
                    ?>
                    <img src="<?php echo K_SYSTEM_THEME_URL; ?>assets/blank.gif" alt="" id="<?php echo $input_id ?>_dummyimg" onload='
                        el=$("#<?php echo $input_id ?>_dummyimg");
                        if(!el.attr("idx")){
                            <?php echo $html1 ?>

                            var eid = "<?php echo $input_id ?>";
                            <?php echo $dyn_js ?>

                            el.css("display", "none");
                        }
                    ' />
                    <?php
                    $html .= ob_get_contents();
                    ob_end_clean();

                    return $html;

                }
                else{ /* not dynamically inserted but is being rendered within repeatable regions */
                    $config['removePlugins']='resize';

                    static $done=0;
                    if( !$done ){
                        $done=1;
                        ob_start();
                        ?>
                        $(function(){
                            if( CKEDITOR && CKEDITOR.k_repeatable_cks ){
                                $.each(CKEDITOR.k_repeatable_cks, function(index, value){
                                    try{
                                        var eid = value;
                                        <?php echo $dyn_js ?>
                                    }
                                    catch(e){}
                                });
                            }
                        });
                        <?php
                        $js = ob_get_contents();
                        ob_end_clean();
                        $FUNCS->add_js( $js );
                    }

                    $html .= $f->page->CKEditor->editor( $input_name, $value, $config );
                    $html .= "<script type=\"text/javascript\">//<![CDATA[\n";
                    $html .= "if( !CKEDITOR.k_repeatable_cks ) CKEDITOR.k_repeatable_cks = []; CKEDITOR.k_repeatable_cks.push( '". $input_id ."' );";
                    $html .= "\n//]]></script>\n";

                    return $html;
                }
            }

            $html .= $f->page->CKEditor->editor( $input_name, $value, $config );

            return $html;
        }

        // image
        static function _render_image( $f, $input_name, $input_id, $extra, $dynamic_insertion ){
            global $CTX;

            KField::_set_common_vars( $f->k_type, $input_name, $input_id, $extra, $dynamic_insertion, $f->simple_mode );
            $arr_vars = array();
            $arr_vars['k_field_value'] = $f->get_data();
            $arr_vars['k_field_show_preview'] = ( $f->show_preview ) ? '1' : '0';
            $arr_vars['k_field_preview_width'] = $f->preview_width;
            $arr_vars['k_field_preview_height'] = $f->preview_height;
            $arr_vars['k_field_input_width'] = $f->input_width; // Set by repeatable tag
            if( K_USE_KC_FINDER ){
                $arr_vars['k_field_use_kcfinder'] = '1';
            }
            else{
                $arr_vars['k_field_use_kcfinder'] = '0';
            }
            KField::add_browse_js();

            $CTX->set_all( $arr_vars );
        }

        // thumbnail
        static function _render_thumbnail( $f, $input_name, $input_id, $extra, $dynamic_insertion ){
            global $CTX;

            KField::_set_common_vars( $f->k_type, $input_name, $input_id, $extra, $dynamic_insertion, $f->simple_mode );
            $arr_vars = array();
            $arr_vars['k_field_value'] = $f->get_data();
            $arr_vars['k_field_show_preview'] = ( $f->show_preview ) ? '1' : '0';
            $arr_vars['k_field_preview_width'] = $f->preview_width;
            $arr_vars['k_field_preview_height'] = $f->preview_height;
            $arr_vars['k_field_tpl_id'] = $f->page->tpl_id;
            $arr_vars['k_field_page_id'] = $f->page->id;

            static $done=0;
            if( !$done ){
                $arr_vars['k_add_crop_js'] = '1';
                $done=1;
            }
            else{
                $arr_vars['k_add_crop_js'] = '0';
            }

            $CTX->set_all( $arr_vars );
        }

        // file
        static function _render_file( $f, $input_name, $input_id, $extra, $dynamic_insertion ){
            global $CTX;

            KField::_set_common_vars( $f->k_type, $input_name, $input_id, $extra, $dynamic_insertion, $f->simple_mode );
            $arr_vars = array();
            $arr_vars['k_field_value'] = $f->get_data();
            $arr_vars['k_field_input_width'] = $f->input_width; // Set by repeatable tag
            if( K_USE_KC_FINDER ){
                $arr_vars['k_field_use_kcfinder'] = '1';
            }
            else{
                $arr_vars['k_field_use_kcfinder'] = '0';
            }
            KField::add_browse_js();

            $CTX->set_all( $arr_vars );
        }

        // js for type 'image' and 'file'
        static function add_browse_js(){
            global $CTX;

            static $done=0;
            if( !$done ){
                $CTX->set( 'k_add_browse_js', '1' );
                $done=1;
            }
            else{
                $CTX->set( 'k_add_browse_js', '0' );
            }
        }

        // text
        static function _render_text( $f, $input_name, $input_id, $extra, $dynamic_insertion ){
            global $CTX;

            KField::_set_common_vars( $f->k_type, $input_name, $input_id, $extra, $dynamic_insertion, $f->simple_mode );
            $arr_vars = array();
            $arr_vars['k_field_value'] = htmlspecialchars( $f->get_data(), ENT_QUOTES, K_CHARSET );
            $arr_vars['k_field_rtl'] = ( $f->rtl ) ? '1' : '0';
            $arr_vars['k_field_width'] = $f->width;
            $arr_vars['k_field_maxlength'] = $f->maxlength;

            $CTX->set_all( $arr_vars );
        }

        // dropdown, radio, checkbox
        static function _render_options( $f, $input_name, $input_id, $extra, $dynamic_insertion ){
            global $FUNCS, $CTX;

            $value = trim( $f->get_data() );
            $selected = html_entity_decode( $value, ENT_QUOTES, K_CHARSET );
            if( $selected=='' && $_SERVER['REQUEST_METHOD']!='POST' ){ // the posted value can also be a blank, hence this check.
                $selected = trim( $f->opt_selected );
            }
            $separator = ( $f->k_separator ) ? $f->k_separator : '|';
            $val_separator = ( $f->val_separator ) ? $f->val_separator : '=';

            KField::_set_common_vars( $f->k_type, $input_name, $input_id, $extra, $dynamic_insertion, $f->simple_mode );
            $arr_vars = array();
            $arr_vars['k_field_opt_values'] = $f->opt_values;
            $arr_vars['k_field_selected_value'] = $selected;
            $arr_vars['k_field_separator'] = $separator;
            $arr_vars['k_field_val_separator'] = $val_separator;
            $arr_vars['k_field_width'] = $f->width;

            $CTX->set_all( $arr_vars );
        }

        // message
        static function _render_message( $f, $input_name, $input_id, $extra, $dynamic_insertion ){
            $html = $f->get_data();
            return $html;
        }

        // hidden
        static function _render_hidden( $f, $input_name, $input_id, $extra, $dynamic_insertion ){
            $value = $f->get_data();
            $html = '<input type="hidden" id="' . $input_id . '" name="'. $input_id .'" value="'. htmlspecialchars( $value, ENT_QUOTES, K_CHARSET ) .'" />';
            return $html;
        }

        static function _set_common_vars( $type, $input_name, $input_id, $extra, $dynamic_insertion, $simple_mode ){
            global $CTX;

            $arr_vars = array();
            $arr_vars['k_field_type'] = $type;
            $arr_vars['k_field_input_name'] = $input_name;
            $arr_vars['k_field_input_id'] = $input_id;
            $arr_vars['k_field_extra'] = $extra;
            $arr_vars['k_field_dynamic_insertion'] = $dynamic_insertion;
            $arr_vars['k_field_simple_mode'] = $simple_mode;

            $CTX->set_all( $arr_vars );
        }

    }// end class KField

    class KFieldUser extends KField{
        function __construct( $fields, &$siblings ){
            foreach( $fields as $k=>$v ){
               $this->$k = $v;
            }
            $this->page = new stdClass();
            $this->siblings = &$siblings;
        }

        function get_data(){
            return (string)$this->data;
        }
    }

    class KFieldForm extends KFieldUser{

        function store_posted_changes( $post_val ){
            global $FUNCS;
            if( $this->k_type=='hidden' ) return;

            if( $this->k_type== 'checkbox' && is_array($post_val) ){
                $separator = ( $this->k_separator ) ? $this->k_separator : '|';
                $sep = '';
                $str_val = '';
                foreach( $post_val as $v ){
                    $str_val .= $sep . $v;
                    $sep = $separator;
                }
                $post_val = $str_val;
            }

            if( is_null($this->orig_data) ) $this->orig_data = $this->get_data();
            if( $this->trust_mode==0 && ($this->k_type=='text' || $this->k_type=='textarea') ){
                $this->data = trim( $FUNCS->cleanXSS(strip_tags($post_val)) );
            }
            else{
                $this->data = ($this->k_type=='textarea' && $this->no_xss_check) ? $post_val : $FUNCS->cleanXSS( $post_val, 0, $this->allowed_html_tags );
            }
            $this->modified = ( strcmp( $this->orig_data, $this->data )==0 ) ? false : true; // values unchanged
        }

        function _render( $input_name, $input_id, $extra='', $dynamic_insertion=0 ){
            global $FUNCS, $CTX;

            $value = $this->get_data();
            if( $this->k_type=='text' || $this->k_type=='password' || $this->k_type=='submit' || $this->k_type=='hidden' ){
                $html = '<input type="'.$this->k_type.'" name="'.$input_name.'"  id="'.$input_id.'" value="'.htmlspecialchars( $value, ENT_QUOTES, K_CHARSET ).'" '.$extra.'/>';
            }
            elseif( $this->k_type=='textarea' ){
                $html = '<textarea  name="'.$input_name.'"  id="'.$input_id.'" '.$extra.'>'.htmlspecialchars( $value, ENT_QUOTES, K_CHARSET ).'</textarea>';
            }
            elseif( $this->k_type=='radio' || $this->k_type=='checkbox' || $this->k_type=='dropdown' ){
                $html = parent::_render( $input_name, $input_id, $extra, $dynamic_insertion );
            }
            elseif( $this->k_type=='captcha' ){
                $fmt = $this->captcha_format;
                for( $x=0; $x<7; $x++ ){
                    switch( @$fmt{$x} ){
                        case '-':
                            $html .= '<br>';
                            break;
                        case 't':
                            $html .= '<input type="text" name="'.$input_name.'"  id="'.$input_id.'"  maxlength="4" value="" '.$extra.'/>';
                            break;
                        case 'i':
                            $html .= '<img id="'.$input_name.'_img" src="'.K_ADMIN_URL . 'captcha.php?c='.$this->captcha_num.'&sid='.md5(uniqid(time())).'" alt="CAPTCHA Image" />';
                            break;
                        case 'r':
                            $html .= ' <a href="#" onclick="document.getElementById(\''.$input_name.'_img\').src = \''.K_ADMIN_URL.'captcha.php?c='.$this->captcha_num.'&sid=\' + Math.random(); return false">'.$this->captcha_reload_text.'</a>';
                    }
                }
            }

            return $this->wrap_fieldset( $html );
        }

        function wrap_fieldset( $html ){
            global $CTX;

            $wrap = $CTX->get( 'k_wrapper' );
            if( $wrap ){
                $label = $this->label ? $this->label : $this->name;
                $pre = '<dt><label ';
                if( $this->err_msg ) $pre .= 'class="k_fielderror" ';
                $pre .= 'for="'.$this->name.'">'.$label.' ';
                if( $this->required ) $pre .= '<span class="k_fielderror">*</span> ';
                $pre .= '</label></dt>';
                $pre .= "\n<dd>";
                $html = $pre . $html;

                if( $this->k_desc ){
                    $post = '<p class="k_instructions">'.$this->k_desc.'</p>';
                    $post .= "\n";
                }
                $post .= "</dd>\n";
                $html .= $post;
            }

            return $html;
        }

        function validate(){
            global $FUNCS;

            if( $this->k_type=='captcha' ){
                if ( session_id() == '' ) { // session needed for validation
                    session_start();
                }
                $var = 'securimage_code_value'.$this->captcha_num;
                if( isset($_SESSION[$var]) && !empty($_SESSION[$var]) ){
                    if( $_SESSION[$var] == strtolower(trim($this->get_data())) ){
                        $correct_code = true;
                        $_SESSION[$var] = '';
                    }
                    else{
                        $correct_code = false;
                    }
                }
                else{
                    $correct_code = false;
                }

                if( !$correct_code ) $this->err_msg = 'Incorrect';
                return $correct_code;
            }
            else{
                return parent::validate();
            }
        }

    } // end class KFieldForm

    // Custom fields used for rendering system fields in admin-panel
    class KPageFolderIDField extends KField{
        function pre_render( &$def ){
            if( !$this->page->is_master && count($this->page->folders->children) && !$this->page->tpl_nested_pages /*&& !$this->page->parent_id*/ ){
                $visibile = 1;
            }
            $def['hide']= $visibile ? 0 : 1;
        }

        function render(){} // defunct

        function _render( $input_name, $input_id, $extra='', $dynamic_insertion=0 ){
            global $FUNCS, $CTX;

            $CTX->push( '__ROOT__' );
            $dropdown_html = '';
            $hilited = $this->get_data();
            $this->page->folders->visit( array('KFolder', '_k_visitor'), $dropdown_html, $hilited, 0/*$depth*/, 0/*$extended_info*/, array()/*$exclude*/ );
            $CTX->pop();

            $html = '<select id="'.$input_id.'" name="'.$input_name.'"><option value="-1" >-- '.$FUNCS->t('select_folder').' --</option>' .$dropdown_html. '</select>';

            if( !$this->simple_mode ){
                $html = '<div class="select dropdown">' . $html . '<span class="select-caret">'.$FUNCS->get_icon('caret-bottom').'</span></div>';
            }

            return $html;
        }
    }

    class KFolderIDField extends KField{
        function render(){} // defunct

        function _render( $input_name, $input_id, $extra='', $dynamic_insertion=0 ){
            global $FUNCS, $CTX, $PAGE, $OBJ;

            $CTX->push( '__ROOT__' );
            $dropdown_html = '';
            $hilited = $this->get_data();
            $PAGE->folders->visit( array('KFolder', '_k_visitor'), $dropdown_html, $hilited, 0/*$depth*/, 0/*$extended_info*/, array($OBJ->name)/*$exclude*/ );
            $CTX->pop();

            $html = '<select id="'.$input_id.'" name="'.$input_name.'"><option value="-1" >-- '.$FUNCS->t('none').' --</option>' .$dropdown_html. '</select>';

            if( !$this->simple_mode ){
                $html = '<div class="select dropdown">' . $html . '<span class="select-caret">'.$FUNCS->get_icon('caret-bottom').'</span></div>';
            }

            return $html;
        }
    }

    class KPublishDateField extends KField{
        function store_posted_changes( $post_val ){
            global $FUNCS;

            if( is_null($post_val) ){
                $post_val = ( $_POST['f_publish_status'] ) ? $FUNCS->sanitize_posted_date() : '0000-00-00 00:00:00';
            }
            else{
                $post_val = trim( $post_val );
                if( $post_val == '' ){ // shortcut for unpublishing page
                    $post_val = '0000-00-00 00:00:00';
                }
            }
            parent::store_posted_changes( $post_val );
        }

        function validate(){
            global $FUNCS;

            if( !strlen($this->data) ){
                if( $this->required ){
                    return parent::validate(); // parent will handle custom error message, if any
                }
                else{
                    return true;
                }
            }

            if( $this->data == '0000-00-00 00:00:00' ){
                return true;
            }

            // Validate date
            if( KDateTime::_checkdate($this->data, 1 /* with time */) ){
                // date is ok. Let parent handle custom validators, if any
                return parent::validate();
            }

            $this->err_msg = 'Invalid Date'; //TODO: localize string
            return false;
        }

        function render(){} // defunct

        function _render( $input_name, $input_id, $extra='', $dynamic_insertion=0 ){
            global $FUNCS;

            $publish_date = $this->get_data();

            ob_start();
            if( !$this->page->tpl_nested_pages ){
            ?>
                <div class="ctrls-radio">
                    <label for="f_publish_status_1" onClick="$('#publish-date').show()"><input type="radio" <?php if( $publish_date != '0000-00-00 00:00:00' ){?>checked="checked"<?php } ?> value="1" id="f_publish_status_1" name="f_publish_status"/><span class="ctrl-option"></span><?php echo $FUNCS->t('published'); ?></label>
                    <label for="f_publish_status_0" onClick="$('#publish-date').hide()"><input type="radio" <?php if( $publish_date == '0000-00-00 00:00:00' ){?>checked="checked"<?php } ?> value="0" id="f_publish_status_0" name="f_publish_status"/><span class="ctrl-option"></span><?php echo $FUNCS->t('unpublished'); ?></label>
                </div>
                <div id="publish-date" style="display:<?php if( $publish_date == '0000-00-00 00:00:00' ){ echo 'none'; } else{ echo 'block'; }?>;">
                    <?php echo $FUNCS->date_dropdowns( $publish_date, $this->simple_mode ); ?>
                </div>
            <?php
            }else{
            ?>
                <div class="ctrls-radio">
                    <label for="f_publish_status_1"><input type="radio" <?php if( $publish_date != '0000-00-00 00:00:00' ){?>checked="checked"<?php } ?> value="1" id="f_publish_status_1" name="f_publish_status" /><span class="ctrl-option"></span><?php echo $FUNCS->t('active'); ?></label>
                    <label for="f_publish_status_0"><input type="radio" <?php if( $publish_date == '0000-00-00 00:00:00' ){?>checked="checked"<?php } ?> value="0" id="f_publish_status_0" name="f_publish_status" /><span class="ctrl-option"></span><?php echo $FUNCS->t('inactive'); ?></label>
                </div>
                <div id="publish-date" style="display:none;">
                    <?php echo $FUNCS->date_dropdowns( $publish_date, $this->simple_mode ); ?>
                </div>
            <?php
            }
            $html .= ob_get_contents();
            ob_end_clean();
            return $html;
        }
    }

    class KCommentDateField extends KPublishDateField{
        function store_posted_changes( $post_val ){
            global $FUNCS;

            if( is_null($post_val) ){
                $post_val = $FUNCS->sanitize_posted_date();
            }
            else{
                $post_val = trim( $post_val );
            }
            parent::store_posted_changes( $post_val );
        }

        function _render( $input_name, $input_id, $extra='', $dynamic_insertion=0 ){
            global $FUNCS;

            $publish_date = $this->get_data();
            $html = $FUNCS->date_dropdowns( $publish_date, $this->simple_mode );

            return $html;
        }
    }

    class KSingleCheckField extends KField{
        var $field_label;
        var $inverse;

        function __construct( $row, &$page, &$siblings, $field_label='', $inverse=0 ){
            parent::__construct( $row, $page, $siblings );

            $field_label = trim( $field_label );
            $inverse = trim( $inverse );
            $this->field_label = strlen( $field_label ) ? $field_label : $this->label;
            $this->inverse = ( $inverse==='1' ) ? 1 : 0;
        }

        function store_posted_changes( $post_val ){
            $post_val = trim( $post_val );
            if( $post_val !== '1' ){ $post_val = '0'; }

            if( $this->inverse ){ $post_val = ($post_val==='1') ? '0' : '1'; }

            parent::store_posted_changes( $post_val );
        }

        function render(){} // defunct

        function _render( $input_name, $input_id, $extra='', $dynamic_insertion=0 ){
            global $FUNCS;

            if( !$this->inverse ){
                $checked = ( $this->get_data() ) ? 'checked="checked"' : '';
            }
            else{
                $checked = ( !$this->get_data() ) ? 'checked="checked"' : '';
            }

            $html = '<input type="checkbox" value="1" '.$checked.' name="'.$input_name.'" id="'.$input_id.'"/>';

            if( $this->simple_mode ){
                $html = '<label for="'.$input_id.'">' . $html . ' ' . $this->field_label . '</label>';
            }
            else{
                $html = '<div class="ctrls-checkbox"><label for="'.$input_id.'">' . $html . '<span class="ctrl-option"></span>' . $this->field_label . '</label></div>';
            }

            return $html;
        }
    }

    class KCommentsOpenField extends KSingleCheckField{
        function pre_render( &$def ){
            $def['hide']= $this->page->tpl_is_commentable ? 0 : 1;
        }
    }

    class KAccessLevel extends KField{
        function store_posted_changes( $post_val ){
            if( is_null($post_val) ){
                $post_val = ( isset($_POST['f_k_levels_list']) ) ? $_POST['f_k_levels_list'] : '0';
            }
            else{
                $post_val = trim( $post_val );
            }
            parent::store_posted_changes( intval($post_val) );
        }

        function render(){} // defunct

        function _render( $input_name, $input_id, $extra='', $dynamic_insertion=0 ){
            global $FUNCS, $AUTH;

            $inherited = 0;
            $level = $this->page->get_access_level( $inherited ); //template level and folder level override page level
            if( $this->page->access_level > $AUTH->user->access_level ){
                $inherited = 1;
                $html = $FUNCS->access_levels_dropdown( $level, 10, 0, $inherited, $this->simple_mode);
            }
            else{
                if( !$inherited ){
                    $level = $this->get_data();
                }
                $html = $FUNCS->access_levels_dropdown( $level /*selected*/, $AUTH->user->access_level/*max*/, 0/*min*/, $inherited/*disabled*/, $this->simple_mode);
            }

            return $html;
        }
    }

    class KUserAccessLevel extends KAccessLevel{
        function _render( $input_name, $input_id, $extra='', $dynamic_insertion=0 ){
            global $FUNCS, $AUTH;

            $html = $FUNCS->access_levels_dropdown( $this->page->access_level, $AUTH->user->access_level - 1, 1, $this->simple_mode );

            return $html;
        }
    }

    class KNestedPagesField extends KField{
        function _render( $input_name, $input_id, $extra='', $dynamic_insertion=0 ){
            global $FUNCS, $CTX;

            if( $this->page->tpl_nested_pages ){
                $tree = $FUNCS->get_nested_pages( $this->page->tpl_id, $this->page->tpl_name, $this->page->tpl_access_level, 'weightx', 'asc' );
                $CTX->push( '__ROOT__' );
                $dropdown_html = '';
                $hilited = $this->get_data();
                $tree->visit( array('KNestedPage', '_k_visitor_pages'), $dropdown_html, $hilited, 0/*$depth*/, 0/*$extended_info*/, array($this->page->page_name)/*$exclude*/ );
                $CTX->pop();

                $html = '<select id="'.$input_id.'" name="'.$input_name.'"><option value="-1" >-- '.$FUNCS->t('none').' --</option>' .$dropdown_html. '</select>';

                if( !$this->simple_mode ){
                    $html = '<div class="select dropdown">' . $html . '<span class="select-caret">'.$FUNCS->get_icon('caret-bottom').'</span></div>';
                }
            }
            return $html;
        }

    }

    class KLinkUrlField extends KField{
        function store_posted_changes( $post_val ){
            global $FUNCS;
            if( $this->deleted ) return; // no need to store

            if( is_null($this->orig_data) ) $this->orig_data = $this->data;

            // strip off domain info from posted value, if it is an internal link
            $domain_prefix = K_SITE_URL;
            $post_val = trim( $post_val );
            if( strpos($post_val, $domain_prefix)===0 ){
                $post_val = substr( $post_val, strlen($domain_prefix) );
                $post_val = ( $post_val ) ? ':' . $post_val : ':'; // add marker
            }

            $this->data = $FUNCS->cleanXSS( $post_val );
            $this->modified = ( strcmp( $this->orig_data, $this->data )==0 ) ? false : true; // values unchanged
        }

        function get_data_to_save(){
            return $this->data;
        }

        function get_data(){

            $data = $this->data;

            // add domain info to internal links
            if( $data{0}==':' ){ // if marker
                $data = substr( $data, 1 );
                $data = K_SITE_URL . $data;
            }

            return $data;
        }

        function render(){
            // defunct
        }
    }

    // All UDFs (User Defined Fields) should extend this class.
    //
    // We are trying to simplify the (probably) unnecessary existing relation between -
    // store_data_from_saved(), store_posted_changes(), render(), get_data() and get_data_to_save().
    // Under revised scenario $f->data is the key element -
    // Upon loading a field, $f->data gets filled through store_data_from_saved() that gets data straight from database. It can modify the data if format differs.
    // $f->_render uses $f->data to output the field onto the write panel (making changes to the way the data is displayed, if required)
    // store_posted_changes() gets the returned data from the admin panel and stores it back to $f->data (reversing the display logic of $f->_render, if required)
    // get_data() is now solely used to output the data from $f->data onto the front-end (via $CTX) (it can also make the same display changes as $f->_render, or maybe someother changes)
    //  in fact, $f->_render could possibly call get_data() if their display logics match.
    // get_data_to_save() returns back the $f->data to be saved into the database. It can modify the data to be stored if format differes.
    //
    // So now, $f->data at all times contains the same data as is stored in the database.
    class KUserDefinedField extends KField{

        function __construct( $row, &$page, &$siblings ){
            global $FUNCS;

            // udf params
            $arr_params = array();
            $custom_params = $row['custom_params'];
            if( is_string($custom_params) && strlen($custom_params) ){
                $arr_params = $FUNCS->unserialize($custom_params);
            }
            elseif( is_array($custom_params) ){
                $arr_params = $custom_params;
            }

            if( is_array($arr_params) && count($arr_params) ){
                foreach( $arr_params as $k=>$v ){
                    $this->$k = $v;
                }
            }

            // call parent
            parent::__construct( $row, $page, $siblings );

            if( !$FUNCS->is_core_type($this->k_type) ){
                $this->udf = 1;
            }
        }

        // called statically from 'cms:editable' tag to handle the parameters passed to it
        // Should parse out the parameters specific to this field and also sanitize the values.
        static function handle_params( $params ){
            /*
            global $FUNCS;
            $attr = $FUNCS->get_named_vars(
                        array( 'foo'=>'hello',
                               'baz'=>'0',
                              ),
                        $params);
            $attr['foo'] = strtolower( trim($attr['foo']) );
            $attr['baz'] = ( $attr['baz']==1 ) ? 1 : 0;
            return $attr;
            */
            return array();
        }

        // Load from database
        function store_data_from_saved( $data ){ // just duplicating the default logic of KField.
            $this->data = $data;
        }

        // Output to admin panel
        function _render( $input_name, $input_id, $extra1='', $dynamic_insertion=0 ){
            global $FUNCS, $CTX;

            return 'Extend _render() to create your own markup for this field';
        }

        // Posted data
        function store_posted_changes( $post_val ){
            global $FUNCS;
            if( $this->deleted ) return; // no need to store

            if( is_null($this->orig_data) ) $this->orig_data = $this->data;
            $this->data = $FUNCS->cleanXSS( $post_val );
            $this->modified = ( strcmp( $this->orig_data, $this->data )==0 ) ? false : true; // values unchanged
        }

        // Output to front-end.
        // Not always via $CTX as, for example, output of cms:editable tag used outside cms:template.
        // _render function can also use this function if the rendered field on back-end uses the same data.
        // To make a distinction, when the returned value is set into $CTX by the calling routine,
        // (as when called from cms:pages, cms:form on success, cms:show_repeatable), the '$for_ctx' will be 1.
        // This could be useful if the UDF wishes to set additional variables into $CTX (i.e. in addition to the one named after itself) .
        // The value of the variable set in $CTX named after the field, however, should always be returned to be set by the caller.
        function get_data( $for_ctx=0 ){

            if( !$this->data ){
                // make sure it is not numeric 0
                $data = ( is_numeric($this->data) ) ? (string)$this->data : $this->default_data;
            }
            else{
                $data = $this->data;
            }

            if( $this->search_type!='text' ){
                $pos = strpos( $data, ".00");
                if( $pos!==false ){
                    $data = substr( $data, 0, $pos );
                }
            }

            return $data;
        }

        // Save to database.
        function get_data_to_save(){
            return $this->data;
        }

        // Search value
        function get_search_data(){
            return $this->data;
        }
        // TODO // guess not.. validate() will do
        // pre_save()


        // The following are invoked during the CRUD events of a field's life.
        // Come in handy for fields that store data in separate tables.

        // Called from page's save() routine while saving a new page.
        // Also called from 'cms:editable' for all existing pages when this type
        // of field gets added to a template for the first time
        // (in which case the '$first_time' param is '1'
        // UDFs may want to INSERT records here if using custom tables.
        function _create( $page_id, $first_time=0 ){
            return;
        }

        // Called by the page this field belongs to when the page gets cloned
        // UDFs may want to INSERT records here if using custom tables.
        function _clone( $cloned_page_id, $cloned_page_title ){
            return;
        }

        // Called from 'cms:editable' when this type of field gets modified in a template (i.e. its parameters)
        function _update_schema( $orig_values ){
            return;
        }

        // Called from page's save() routine
        // UDFs may want to UPDATE records here if using custom tables.
        function _update( $page_id ){
            return;
        }

        // Called either from a page being deleted
        // or when this field's definition gets removed from a template (in which case the $page_id param would be '-1' )
        // IMP: when $page_id is -1, this routine is called from ajax.php (admin-panel) which creates the field object using dummy params for $PAGE and $siblings -
        // so cannot use these here.
        // UDFs may want to DELETE records here if using custom tables.
        // If $page_id is -1, could delete all records
        function _delete( $page_id ){
            return;
        }

        // Called when the page this field belongs to is being recreated from a cloned page.
        // Should prepare for the impending get_data_to_save()/_update() via $PAGE->save()
        function _unclone( &$cloned_field ){
            return;
        }

        function is_empty(){
            $data = trim( $this->get_data() );
            return ( strlen($data) ) ? false : true;
        }

        // called when a cached field object is reused in creating new page object.
        // Any private data saved with the cached field object can be deleted here
        // to allow the field's reuse.
        function _prep_cached(){

        }
    }

    // All User Defined Form Fields (cms:input) should extend this class.
    class KUserDefinedFormField extends KFieldForm{
        // called statically from 'cms:input' tag to handle the parameters passed to it
        // Should parse out the parameters specific to this field and also sanitize the values.
        // The $node parameter can be used to set the 'value' parameter by looping through child nodes (as in textarea)
        static function handle_params( $params, $node ){
            /*
            global $FUNCS;
            $attr = $FUNCS->get_named_vars(
                        array( 'foo'=>'hello',
                               'baz'=>'0',
                              ),
                        $params);
            $attr['foo'] = strtolower( trim($attr['foo']) );
            $attr['baz'] = ( $attr['baz']==1 ) ? 1 : 0;
            return $attr;
            */
            return array();
        }

        // Handle Posted data
        function store_posted_changes( $post_val ){
            global $FUNCS;

            if( is_null($this->orig_data) ) $this->orig_data = $this->data;
            $this->data = $FUNCS->cleanXSS( $post_val );
            $this->modified = ( strcmp( $this->orig_data, $this->data )==0 ) ? false : true; // values unchanged
        }

        // Render input field
        function _render( $input_name, $input_id, $extra='', $dynamic_insertion=0 ){
            $html = 'Extend _render() to create your own markup for this form field';
            return $this->wrap_fieldset( $html );
        }
    }

    class KExif extends KUserDefinedField{
        function store_posted_changes( $post_val ){
            return; // calculated and stored by save routine directly to database
        }

        // Called to give value to be set in CTX
        function get_data( $for_ctx=0 ){
            global $CTX;

            if( count($CTX->ctx) ){
                // Data not a simple string hence
                // we'll store it into '_obj_' of CTX directly
                // to be used by the auxilally tag which knows how to display it
                $CTX->set_object( 'k_file_meta', $this->data );

                // and return nothing for the normal context
                return;
            }
        }
    }

    // Form Input field for SingleCheck (a wrapper around KSingleCheckField above)
    class KSingleCheckFieldForm extends KUserDefinedFormField{
        var $obj;

        static function handle_params( $params, $node ){
            global $FUNCS;

            $attr = $FUNCS->get_named_vars(
                        array(
                               'field_label'=>'',
                               'inverse'=>'',
                              ),
                        $params);

            return $attr;

        }

        function __construct( $fields, &$siblings ){
            global $PAGE;

            $this->obj = new KSingleCheckField( $fields, $PAGE /*dummy*/, $siblings, $fields['field_label'], $fields['inverse'] );
            parent::__construct( $fields, $siblings );
        }

        function _render( $input_name, $input_id, $extra='', $dynamic_insertion=0 ){
            return call_user_func( array(&$this->obj, '_render'), $input_name, $input_id, $extra, $dynamic_insertion );
        }

        function get_data(){
            return call_user_func( array(&$this->obj, 'get_data') );
        }

        function store_posted_changes( $post_val ){
            return call_user_func( array(&$this->obj, 'store_posted_changes'), $post_val );
        }

        function validate(){
            $res = call_user_func( array(&$this->obj, 'validate') );
            if( $res==false ) $this->err_msg = $this->obj->err_msg;

            return $res;
        }
    }
    $FUNCS->register_udform_field( 'singlecheck', 'KSingleCheckFieldForm' );
