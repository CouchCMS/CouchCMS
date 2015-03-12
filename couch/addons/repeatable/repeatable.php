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

    class Repeatable extends KUserDefinedField{
        var $cells = array(); // array of fields representing each cell in a row
        var $rendered_data = null;
        var $rendered_deleted_html = null;
        var $validation_errors = 0;

        function Repeatable( $row, &$page, &$siblings ){
            // call parent
            parent::KUserDefinedField( $row, $page, $siblings );

            // now for own logic
            $this->orig_data = array();
            $this->_fill_cells_info();

        }

        function tag_handler( $params, $node ){
            global $CTX, $FUNCS, $TAGS, $PAGE, $AUTH;
            if( $AUTH->user->access_level < K_ACCESS_LEVEL_SUPER_ADMIN ) return;

            $attr = $FUNCS->get_named_vars(
                    array(  'name'=>'',
                ),
                $params
            );
            $name = trim( $attr['name'] );
            if( !$name ) {die("ERROR: Tag \"".$node->name."\" needs a 'name' attribute");}

            $children = $node->children;
            $schema = array();
            foreach( $children as $child ){
                if( $child->type==K_NODE_TYPE_CODE && strtolower($child->name)=='editable' ){
                    $child_params = $FUNCS->resolve_parameters( $child->attributes );
                    $attr = $FUNCS->get_named_vars( array('type'=>'', 'name'=>''), $child_params );
                    $child_type = strtolower( trim($attr['type']) );
                    if( $FUNCS->is_core_type($child_type) ){
                        if( in_array($child_type, array('richtext', 'thumbnail', 'hidden', 'message', 'group')) ){ //unsupported types
                            continue;
                        }
                    }
                    else{
                        if( !$FUNCS->udfs[$child_type]['repeatable'] ){
                            continue;
                        }
                    }

                    $child_name = trim($attr['name']);
                    if( !$child_name ) {die("ERROR: Tag \"".$node->name."\" a child region's 'name' attribute is missing");}
                    if( !$FUNCS->is_variable_clean($child_name) ){
                        die( "ERROR: Tag \"".$node->name."\": a child region's 'name' attribute contains invalid characters. (Only lowercase[a-z], numerals[0-9] and underscore permitted. The first character cannot be a numeral)" );
                    }
                    if( substr($child_name, 0, 2)=='k_' ){
                        die("ERROR: Tag \"".$node->name."\": a child region's 'name' attribute begins with 'k_'. Reserved for system fields only.");
                    }

                    $schema[] = $TAGS->editable( $child_params, $child, 1 ); // piggyback on the real 'editable' tag to handle constituent fields
                }
            }

            // Check for deleted columns
            $found = 0;
            for( $x=0; $x<count($PAGE->fields); $x++ ){
                $field = $PAGE->fields[$x];
                if( (strtolower($field->name)==strtolower($name)) && ($field->k_type=='__repeatable') ){
                    $found = 1;
                    break;
                }
            }
            if( $found ){ // field found..test the columns within it
                $arr_deleted = array();
                if( $field->schema ){
                    $cells = @$FUNCS->unserialize( $field->schema );
                }
                if( !is_array($cells) ) $cells=array();

                for( $x=0; $x<count($cells); $x++ ){
                    $found=0;
                    for( $y=0; $y<count($schema); $y++ ){
                        if( strtolower($cells[$x]['name'])==strtolower($schema[$y]['name']) ){
                            $found=1;
                            break;
                        }
                    }
                    if( !$found ){
                        $cells[$x]['deleted']='1';
                        $arr_deleted[] = $cells[$x];
                    }
                }
            }
            // preserve  the deleted columns in schema (just mark them as deleted)
            if( count($arr_deleted) ) $schema = array_merge( $arr_deleted, $schema );

            // create an editable region of type 'repeatable' with data of constituent fields as its custom_param
            $custom_params = $FUNCS->serialize( $schema );
            $params[] = array( 'lhs'=>'type', 'op'=>'=', 'rhs'=>'__repeatable' );
            $params[] = array( 'lhs'=>'hidden', 'op'=>'=', 'rhs'=>'1' );
            $params[] = array( 'lhs'=>'schema', 'op'=>'=', 'rhs'=>$custom_params );
            $node->children = array();
            $TAGS->editable( $params, $node );
        }

        function show_handler( $params, $node ){
            global $FUNCS, $CTX;

            extract( $FUNCS->get_named_vars(
                    array( 'var'=>'',
                           'startcount'=>'',
                    ),
                $params)
            );
            $var = trim( $var );
            $startcount = $FUNCS->is_int( $startcount ) ? intval( $startcount ) : 1;

            if( $var ){
                // get the data array from CTX
                $obj = &$CTX->get_object( $var );

                if( $obj ){
                    $cells = $obj['cells'];
                    $data = $obj['data'];

                    // loop through the rows..
                    $total_rows = count($data);
                    for( $x=0; $x<$total_rows; $x++ ){

                        // .. set each cell's value in the row as a simple variable..
                        for( $y=0; $y<count($cells); $y++ ){
                            $c = &$cells[$y];
                            $c->store_data_from_saved( $data[$x][$c->name] );
                            $CTX->set( $c->name, $c->get_data( 1 ) );
                            unset( $c );
                        }
                        $CTX->set( 'k_count', $x + $startcount );
                        $CTX->set( 'k_total_records', $total_rows );

                        // and call the children providing each row's data
                        foreach( $node->children as $child ){
                            $html .= $child->get_HTML();
                        }
                    }

                }

                return $html;
            }
        }

        function handle_params( $params ){
            global $FUNCS;
            $attr = $FUNCS->get_named_vars(
                array(  'schema'=>'',
                    'max_rows'=>'',
                    'button_text'=>'Add a Row'
                ),
                $params
            );
            $attr['max_rows'] = $FUNCS->is_natural( $attr['max_rows'] ) ? intval( $attr['max_rows'] ) : 0; //unused for now
            $attr['button_text'] = trim( $attr['button_text'] ); //unused for now
            return $attr;
        }

        function _fill_cells_info(){
            global $FUNCS;

            if( $this->schema ){
                $rs2 = $FUNCS->unserialize( $this->schema );
            }
            if( !is_array($rs2) ) $rs2=array();

            for( $x=0; $x<count($rs2); $x++ ){
                $fieldtype = $rs2[$x]['k_type'];
                if( $FUNCS->is_core_type($fieldtype) ){
                    $this->cells[] = new KField( $rs2[$x], $this, $this->cells );
                }
                else{
                    // is it a udf?
                    if( array_key_exists($fieldtype, $FUNCS->udfs) ){
                        $classname = $FUNCS->udfs[$fieldtype]['handler'];
                        $this->cells[] = new $classname( $rs2[$x], $this, $this->cells );
                    }
                    else{
                        $this->cells[] = new KField( $rs2[$x], $this, $this->cells );
                    }
                }
            }
        }//fill cells

        // Load from database
        function store_data_from_saved( $data ){
            global $FUNCS;
            $this->data = $FUNCS->unserialize( $data );
            if( !is_array($this->data) ) $this->data=array();
            $this->orig_data = $this->data;
        }

        function _render( $input_name, $input_id, $extra='' ){
            global $FUNCS, $CTX, $AUTH;

            /*
            // calc paths to assets. Current script assumed to be somewhere within or below site's root (i.e. Couch's parent folder).
            $path = str_replace( '\\', '/', dirname(realpath(__FILE__)).'/' );
            if( (strpos($path, K_SITE_DIR)===0) && ($path != K_SITE_DIR) ){
                $subdomain = substr( $path, strlen(K_SITE_DIR) );
            }
            if( !defined('REPEATABLE_URL') ) define( 'REPEATABLE_URL', K_SITE_URL . $subdomain );

            $FUNCS->load_js( K_SITE_URL . $subdomain . 'tablegear/tablegear.js?kver=' . time() );
            $FUNCS->load_js( K_SITE_URL . $subdomain . 'dg-arrange-table-rows/dg-arrange-table-rows.js?kver=' . time() );
            $FUNCS->load_css( K_SITE_URL . $subdomain . 'tablegear/tablegear.css' );
            $FUNCS->load_css( K_SITE_URL . $subdomain . 'dg-arrange-table-rows/dg-arrange-table-rows.css' );
            */

            define( 'REPEATABLE_URL', K_ADMIN_URL . 'addons/repeatable/' );
            $FUNCS->load_js( REPEATABLE_URL . 'tablegear/tablegear.js' );
            $FUNCS->load_js( REPEATABLE_URL . 'dg-arrange-table-rows/dg-arrange-table-rows.js' );
            $FUNCS->load_css( REPEATABLE_URL . 'tablegear/tablegear.css' );
            $FUNCS->load_css( REPEATABLE_URL . 'dg-arrange-table-rows/dg-arrange-table-rows.css' );

            ob_start();
            ?>
            <div class="tableholder">
                <table class="rr" id="<?php echo $input_id; ?>">
                    <thead>
                        <tr>
                            <th class="edit" style="display:none;">&nbsp;</th>
                            <?php foreach( $this->cells as $c ) :  ?>
                            <th <?php if($c->col_width){ echo 'style="width:'.$c->col_width.'px;"'; } ?>>
                                <span><?php echo $c->label; ?></span>
                                <span class="carat"></span>
                            </th>
                            <?php endforeach; ?>
                            <th class="delete" style="padding:0; margin:0; width:28px;"></th>
                        </tr>
                    </thead>
                    <tbody>
                    <?php
                        $data = ( is_null($this->rendered_data) ) ? $this->orig_data : $this->rendered_data;
                        $arr_deleted_html = ( is_null($this->rendered_deleted_html) ) ? array() : $this->rendered_deleted_html;

                        // dynamic params
                        if( is_null($this->rendered_data) ){
                            for( $y=0; $y<count($this->cells); $y++ ){
                                $c = &$this->cells[$y];
                                $c->resolve_dynamic_params();
                                unset( $c );
                            }
                        }

                        for( $x=0; $x<count($data); $x++ ){ $row_id = $x; ?>
                            <tr id="<?php echo $input_id; ?>-<?php echo $row_id; ?>">
                                <td class="edit" style="display:none;">
                                    <input type="checkbox" name="edit[]" value="<?php echo $row_id; ?>" />
                                </td>

                                <?php
                                if( is_null($this->rendered_data) ){ // not handling posted data
                                    // move data into cells
                                    for( $y=0; $y<count($this->cells); $y++ ){
                                        $c = &$this->cells[$y];
                                        $c->store_data_from_saved( $data[$row_id][$c->name] );
                                        $c->err_msg = '';

                                        // display
                                        $c_input_name = 'f_'. $this->name .'['. $row_id .']['. $c->name .']';
                                        $c_input_id = 'f_'. $this->name .'-'. $row_id .'-'. $c->name;
                                        $html = '<td class="editable"><div style="position:relative;">';
                                        $html .= $c->_render( $c_input_name, $c_input_id );
                                        if( $c->deleted && $AUTH->user->access_level >= K_ACCESS_LEVEL_SUPER_ADMIN && defined('K_ADMIN') ){
                                            $html .= '<div class="k_cell_deleted">&nbsp;</div>';
                                            if( $x==0 ) $arr_deleted_html[] = $c->_html;
                                        }
                                        $html .= '</div></td>';
                                        echo $html;

                                        unset( $c );
                                    }
                                }
                                else{
                                    foreach( $this->cells as $c ){
                                        echo $data[$row_id][$c->name]; // pre-rendered with posted data
                                    }
                                }
                            ?>

                            <td class="delete">
                                <input type="checkbox" name="delete[]" value="<?php echo $row_id; ?>" id="delete<?php echo $row_id; ?>" />
                                <label for="delete<?php echo $row_id; ?>">
                                    <img src="<?php echo REPEATABLE_URL; ?>tablegear/delete.gif" alt="Delete Row" />
                                </label>
                            </td>
                        </tr>
                    <?php } ?>
                    </tbody>
                </table>
                <div><p class="addRow" id="addRow_<?php echo $input_id; ?>"><a><?php echo $FUNCS->t('add_row'); ?></a></p>
                <?php
                    if( count($arr_deleted_html) ){
                        $html = '<ul class="k_element_deleted_nav">';
                        $html .= '<li>'.$FUNCS->t('columns_missing').' </li>';
                        $html .= '<li><a href="javascript:k_delete_column('.$this->id.', \''.$FUNCS->create_nonce( 'delete_column_'.$this->id ).'\')"><b>'.$FUNCS->t('delete_permanently').'</b></a></li>';
                        $html .= '<li><a href="'.K_SITE_URL .'#TB_inline?height=220&width=400&inlineId=k_element_deleted_'.$input_id.'&modal=true" class="smoothbox">('.$FUNCS->t('view_code').')</a></li>';
                        $html .= '</ul>';

                        foreach( $arr_deleted_html as $deleted_html ){
                            $str_deleted_html .= $deleted_html . "\r\n\r\n";
                        }
                        $html .= '<div style="display:none;" id="k_element_deleted_'.$input_id.'">';
                        $html .= '<pre>' . htmlspecialchars( $str_deleted_html, ENT_QUOTES, K_CHARSET ) . '</pre>';
                        $html .= '</div>';
                        echo $html;
                    }
                ?>
                </div>
                <input type="hidden" name="_<?php echo $input_id; ?>_sortorder" id="_<?php echo $input_id; ?>_sortorder"/>
                <div id="addNewRow_<?php echo $input_id; ?>" class="newRow">
                    <table>
                        <tbody>
                            <tr id="newDataRow_<?php echo $input_id; ?>" class="newRow even">
                                <td class="edit" style="display:none;">
                                    <input type="checkbox" name="edit[]" value="" />
                                </td>
                                <?php foreach( $this->cells as $c ) {
                                    $c->data='';
                                    $html = '<td class="editable"><div style="position:relative;">';
                                    $widget = $c->_render( 'data[xxx]['.$c->name.']', 'data-xxx-'.$c->name, '', '', 1 );
                                    // ID hack..innerHTML does not return 'id' so adding an 'idx' attribute with the same values.
                                    $widget = preg_replace('/(\sid)(\s*=\s*["\']data-xxx-[\w]+["\'])/is', '$1x$2$1$2', $widget);
                                    $html .= $widget;
                                    if( $c->deleted && $AUTH->user->access_level >= K_ACCESS_LEVEL_SUPER_ADMIN ) $html .= '<div class="k_cell_deleted">&nbsp;</div>';
                                    $html .= '</div></td>';
                                    echo $html;
                                } ?>
                                <td class="delete">
                                    <input type="checkbox" name="delete[]" value="" id="deleteNULL_STRING" />
                                    <label for="deleteNULL_STRING">
                                        <img src="<?php echo REPEATABLE_URL; ?>tablegear/delete.gif" alt="Delete Row" />
                                    </label>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
            <script type="text/javascript">
                window.addEvent('domready', function(){
                    var tmp = new TableGear("<?php echo $input_id; ?>");
                });
            </script>
            <?php
            $html = ob_get_contents();
            ob_end_clean();
            return $html;
        }

        // Handle posted data
        function store_posted_changes( $post_val ){
            global $FUNCS, $Config, $AUTH;
            if( $this->deleted ) return; // no need to store

            // rearrange posted rows
            //$data = is_array( $post_val ) ? $FUNCS->sanitize_deep( $post_val ) : array();
            $data = is_array( $post_val ) ? $post_val : array(); // was messing up no_xss_check. Individual fields will do this anyway.
            if( count($data) ){
                $sort_field = '_f_'.$this->name.'_sortorder';
                if( strlen(trim($_POST[$sort_field])) ){
                    $arr_sort = array_map( "trim", explode( ',', $_POST[$sort_field] ) );
                    $tmp = array(); $x = 0;
                    foreach( $arr_sort as $pos ){
                        if( is_numeric($pos) && isset($data[$pos]) ){
                            $tmp[$x++] = $data[$pos];
                        }
                    }
                    $data = $tmp;
                }
            }

            // dynamic params
            for( $y=0; $y<count($this->cells); $y++ ){
                $c = &$this->cells[$y];
                $c->resolve_dynamic_params();
                unset( $c );
            }

            $this->validation_errors = 0;
            $this->data = array();
            $this->rendered_data = array();
            $this->rendered_deleted_html = array();
            $sep = '';
            for( $row=0; $row<count($data); $row++ ){
                // recreate each row
                for( $y=0; $y<count($this->cells); $y++ ){

                    // hydrate cell with data from database
                    $c = &$this->cells[$y];
                    $c->store_data_from_saved( $this->orig_data[$row][$c->name] );
                    $c->err_msg = '';

                    // pass posted data to each cell
                    $c->store_posted_changes( $data[$row][$c->name] );
                    if( $c->modified ){ $this->modified = 1; }

                    unset( $c );
                }

                // At this point we have a complete row of hydrated cells for further processing
                for( $y=0; $y<count($this->cells); $y++ ){
                    $c = &$this->cells[$y];

                    // Validate
                    if( !$c->validate() ){
                        $this->validation_errors++;
                        $err_row = $row + 1;
                        $this->err_msg .= $sep . 'Row ' . $err_row . ' - '.$c->label.': ' . $c->err_msg;
                        $sep = '<br>';
                    }

                    // Process
                    if( $c->modified ){
                        // good time to process image data
                        if( $c->k_type == 'image' ){
                            // Resize
                            $resized = 0;
                            $domain_prefix = $Config['k_append_url'] . $Config['UserFilesPath'] . 'image/';

                            if( extension_loaded('gd') && function_exists('gd_info') ){
                                $src = $c->get_data();
                                if( strpos($src, $domain_prefix)===0 ){ // process image only if local
                                    $src = substr( $src, strlen($domain_prefix) );
                                    if( $src ){
                                        $src = $Config['UserFilesAbsolutePath'] . 'image/' . $src;

                                        // OK to resize now
                                        $dest = $src;
                                        $w = $c->width;
                                        $h = $c->height;
                                        $crop = $c->crop;
                                        $enforce_max = ( $crop ) ? 0 : $c->enforce_max; // make crop and enforce_max mutually exclusive
                                        $quality = $c->quality;

                                        $res = k_resize_image( $src, $dest, $w, $h, $crop, $enforce_max, $quality );
                                        if( $FUNCS->is_error($res) ){
                                            $c->err_msg = $res->err_msg;
                                            //$this->validation_errors++;
                                            // TODO: Non critical error. Will continue but have to report.
                                        }
                                    }
                                }
                            }
                        }
                    }

                    // get data to save (will be used if no validation errors occur)
                    if( $c->k_type=='image' || $c->k_type=='file' ){
                        $this->data[$row][$c->name] = $c->data; // backward compatibility.. raw data without domain info
                    }
                    else{
                        $this->data[$row][$c->name] = $c->get_data_to_save();
                    }

                    // get rendered markup (will be used if validation errors occur)
                    $input_name = 'f_'. $this->name .'['. $row .']['. $c->name .']';
                    $input_id = 'f_'. $this->name .'-'. $row .'-'. $c->name;
                    $err_class = ( $c->err_msg ) ? ' highlite' : '';
                    $html = '<td class="editable'.$err_class.'"><div style="position:relative;">';
                    $html .= $c->_render( $input_name, $input_id );
                    if( $c->deleted && $AUTH->user->access_level >= K_ACCESS_LEVEL_SUPER_ADMIN ){
                        $html .= '<div class="k_cell_deleted">&nbsp;</div>';
                        if( $x==0 ) $this->rendered_deleted_html[] = $c->_html;
                    }
                    $html .= '</div></td>';
                    $this->rendered_data[$row][$c->name] = $html;

                    unset( $c );
                }

            }// for each row

            if( count($this->orig_data) != count($this->data) ){
                $this->modified = 1;
            }
        }

        // before save
        function validate(){
            return ( $this->validation_errors ) ? false : true;
        }

        // Output to front-end via $CTX
        function get_data(){
            global $CTX;

            // Data not a simple string hence
            // we'll store it into '_obj_' of CTX directly
            // to be used by the auxilally tag which knows how to display it
            $arr = array( 'cells'=>$this->cells, 'data'=>$this->data );
            $CTX->set_object( $this->name, $arr );

            // and return nothing for the normal context
            return;
        }

        // Save to database.
        function get_data_to_save(){
            global $FUNCS;

            return $FUNCS->serialize( $this->data );
        }

        // Search value
        function get_search_data(){
            return '';
        }
    }// end class

    // Register
    $FUNCS->register_udf( '__repeatable', 'Repeatable' ); // The UDF
    $FUNCS->register_tag( 'repeatable', array('Repeatable', 'tag_handler') ); // The helper 'shim' tag that helps create the above UDF
    $FUNCS->register_tag( 'show_repeatable', array('Repeatable', 'show_handler'), 1, 1 ); // The helper tag that shows the variables via CTX
