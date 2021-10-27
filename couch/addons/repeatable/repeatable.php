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
        var $search_data = '';
        var $validation_errors = 0;

        function __construct( $row, &$page, &$siblings ){
            // call parent
            parent::__construct( $row, $page, $siblings );

            // now for own logic
            $this->orig_data = $this->data = array();
            $this->_fill_cells_info();

        }

        static function tag_handler( $params, $node ){
            global $CTX, $FUNCS, $TAGS, $PAGE, $AUTH;
            if( $AUTH->user->access_level < K_ACCESS_LEVEL_SUPER_ADMIN ) return;
            if( defined('K_ADMIN') ) return; // nop within admin panel

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
                if( $child->type==K_NODE_TYPE_CODE ){
                    if( strtolower($child->name)=='editable' ){
                        $child_params = $FUNCS->resolve_parameters( $child->attributes );
                        $attr = $FUNCS->get_named_vars( array('type'=>'', 'name'=>''), $child_params );
                        $child_type = strtolower( trim($attr['type']) );
                        if( $FUNCS->is_core_type($child_type) ){
                            if( in_array($child_type, array('thumbnail', 'hidden', 'group')) ){ //unsupported types
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
                        if( !$FUNCS->is_title_clean($child_name) && !$FUNCS->is_variable_clean($child_name) ){ // retaining is_variable_clean() check for backward compatibility
                            die( "ERROR: Tag \"".$node->name."\": a child region's 'name' attribute ({$child_name}) contains invalid characters. (Only lowercase[a-z], numerals[0-9], hyphen and underscore permitted.)" );
                        }
                        if( substr($child_name, 0, 2)=='k_' ){
                            die("ERROR: Tag \"".$node->name."\": a child region's 'name' attribute begins with 'k_'. Reserved for system fields only.");
                        }

                        $tmp = $TAGS->editable( $child_params, $child, 1 ); // piggyback on the real 'editable' tag to handle constituent fields
                        $html = '';
                        foreach( $child->children as $grandchild ){
                            $html .= $grandchild->get_HTML();
                        }
                        $tmp['default_data'] = $html;
                        $schema[] = $tmp;
                    }
                    elseif( strtolower($child->name)=='func' ){
                        $child->get_HTML();
                    }
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

                // preserve  the deleted columns in schema (just mark them as deleted)
                if( count($arr_deleted) ) $schema = array_merge( $arr_deleted, $schema );
            }

            // create an editable region of type 'repeatable' with data of constituent fields as its custom_param
            $custom_params = $FUNCS->serialize( $schema );
            $params[] = array( 'lhs'=>'type', 'op'=>'=', 'rhs'=>'__repeatable' );
            $params[] = array( 'lhs'=>'hidden', 'op'=>'=', 'rhs'=>'1' );
            $params[] = array( 'lhs'=>'schema', 'op'=>'=', 'rhs'=>$custom_params );
            $_node = clone $node;
            $_node->children = array();
            $TAGS->editable( $params, $_node );
        }

        static function show_handler( $params, $node ){
            global $FUNCS, $CTX;

            extract( $FUNCS->get_named_vars(
                    array( 'var'=>'',
                           'startcount'=>'',
                           'limit'=>'',
                           'offset'=>'',
                           'order'=>'asc',
                           'extended_info'=>'0',
                           'as_json'=>'0',
                           'into'=>'',
                           'scope'=>'',
                           'token'=>'',
                    ),
                $params)
            );
            $var = trim( $var );
            $startcount = $FUNCS->is_int( $startcount ) ? intval( $startcount ) : 1;
            $limit = $FUNCS->is_non_zero_natural( $limit ) ? intval( $limit ) : 1000;
            $offset = $FUNCS->is_natural( $offset ) ? intval( $offset ) : 0;
            $order = strtolower( trim($order) );
            if( $order!='desc' && $order!='asc' && $order!='random') $order='asc';
            $extended_info = ( $extended_info==1 ) ? 1 : 0;
            $as_json = ( $as_json==1 ) ? 1 : 0;
            $into = trim( $into );
            $scope = strtolower( trim($scope) );
            $token = trim( $token );

            if( $var ){
                // get the data array from CTX
                $obj = &$CTX->get_object( $var );

                if( $obj ){
                    $cells = $obj['cells'];
                    $data = $obj['data'];

                    if( $as_json ){ return $FUNCS->json_encode($data); }
                    if( $into!='' ){
                        if( $scope!='parent' && $scope!='global' ){ //local scope makes no sense
                            die("ERROR: Tag \"".$node->name."\" has unknown scope " . $scope);
                        }
                        $CTX->set( $into, $data, $scope );
                        return;
                    }

                    if( $order=='desc' ){ $data = array_reverse($data); }
                    elseif( $order=='random' ){ shuffle($data); }

                    // loop through the rows..
                    $total_rows = count($data) - $offset;
                    if( $limit < $total_rows ) $total_rows = $limit;

                    for( $x=0; $x<$total_rows; $x++ ){

                        // .. set each cell's value in the row as a simple variable..
                        for( $y=0; $y<count($cells); $y++ ){
                            $c = &$cells[$y];
                            $c->store_data_from_saved( $data[$x+$offset][$c->name] );
                            $CTX->set( $c->name, $c->get_data( 1 ) );
                            unset( $c );
                        }
                        $CTX->set( 'k_count', $x + $startcount );
                        $CTX->set( 'k_total_rows', $total_rows );
                        $CTX->set( 'k_total_records', $total_rows ); // backward compatibility
                        $CTX->set( 'k_first_row', ($x==0) ? '1' : '0' );
                        $CTX->set( 'k_last_row', ($x==$total_rows-1) ? '1' : '0' );
                        if( $extended_info ){
                            $pg = new StdClass(); /* a dummy container for fields */
                            $pg->fields = $cells;
                            $CTX->set_object( 'k_bound_page', $pg );
                        }

                        // HOOK: rr_alter_ctx_xxx
                        if( $token ){
                            $FUNCS->dispatch_event( 'rr_alter_ctx_'.$token, array($params, $node) );
                        }

                        // and call the children providing each row's data
                        foreach( $node->children as $child ){
                            $html .= $child->get_HTML();
                        }
                    }

                }

                return $html;
            }
        }

        static function handle_params( $params ){
            global $FUNCS;
            $attr = $FUNCS->get_named_vars(
                array(  'schema'=>'',
                    'max_rows'=>'',
                    'button_text'=>'Add a Row',
                    'stacked_layout'=>'0',
                    'no_default_row'=>'0',
                ),
                $params
            );
            $attr['max_rows'] = $FUNCS->is_natural( $attr['max_rows'] ) ? intval( $attr['max_rows'] ) : 0; //unused for now
            $attr['button_text'] = trim( $attr['button_text'] ); //unused for now
            $attr['stacked_layout'] = ( $attr['stacked_layout']==1 ) ? 1 : 0;
            $attr['no_default_row'] = ( $attr['no_default_row']==1 ) ? 1 : 0;
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

        function _render( $input_name, $input_id, $extra='', $dynamic_insertion=0 ){
            global $FUNCS, $CTX, $AUTH;

            $FUNCS->render( 'repeatable_assets' ); // JS/CSS

            $arr_deleted_html = array();
            ob_start();
            ?>
            <div class="<?php if( $this->stacked_layout ): ?>mosaic <?php endif; ?>repeatable-region tableholder">
                <table class="rr" id="<?php echo $input_id; ?>">
                    <thead>
                        <tr>
                            <th class="dg-arrange-table-header">&nbsp;</th>
                            <?php if( $this->stacked_layout ) : ?>
                                <th class="col-contents"><span>&nbsp;</span></th>
                                <th class="col-up-down">&nbsp;</th>
                                <th class="col-actions"></th>
                            <?php else: ?>
                                <?php foreach( $this->cells as $c ) :  ?>
                                <th class="k_element_<?php echo $c->name; ?>" <?php if($c->col_width){ echo 'style="width:'.$c->col_width.'px;"'; } ?>>
                                    <span><?php echo $c->label; ?></span>
                                    <span class="carat"></span>
                                </th>
                                <?php endforeach; ?>
                                <th class="delete" style="padding:0; margin:0; width:28px;"></th>
                            <?php endif; ?>
                        </tr>
                    </thead>
                    <tbody>
                    <?php
                        $data = ( is_null($this->rendered_data) ) ? $this->orig_data : $this->rendered_data;

                        // dynamic params
                        if( is_null($this->rendered_data) ){
                            for( $y=0; $y<count($this->cells); $y++ ){
                                $c = &$this->cells[$y];
                                $c->resolve_dynamic_params();
                                $c->simple_mode = $this->simple_mode;
                                unset( $c );
                            }
                        }

                        for( $x=0; $x<count($data); $x++ ){ $row_id = $x; ?>
                            <tr id="<?php echo $input_id; ?>-<?php echo $row_id; ?>">
                                <td class="dg-arrange-table-rows-drag-icon">&nbsp;</td>
                                <?php
                                if( $this->stacked_layout ){ echo( '<td class="col-contents editable"><div class="mosaic-list">' ); };
                                if( is_null($this->rendered_data) ){ // not handling posted data
                                    // move data into cells
                                    for( $y=0; $y<count($this->cells); $y++ ){
                                        $c = &$this->cells[$y];
                                        $c->store_data_from_saved( $data[$row_id][$c->name] );
                                        $c->err_msg = '';

                                        if( $row_id==0 ){
                                            // generate JS for conditional fields
                                            $FUNCS->resolve_active( $c, $CTX->get('k_cur_form'), false, $this->name, $row_id, $y );
                                        }

                                        // display
                                        $c_input_name = 'f_'. $this->name .'['. $row_id .']['. $c->name .']';
                                        $c_input_id = 'f_'. $this->name .'-'. $row_id .'-'. $c->name;
                                        $field_html = $c->_render( $c_input_name, $c_input_id, '', false );
                                        if( $c->deleted && $AUTH->user->access_level >= K_ACCESS_LEVEL_SUPER_ADMIN && defined('K_ADMIN') ){
                                            $field_html .= '<div class="k_cell_deleted">&nbsp;</div>';
                                        }

                                        if( $this->stacked_layout ){
                                            $html = "
                                            <div class=\"row k_element_".$c->name."\">
                                                <div class=\"cell cell-label col-md-2\">
                                                    <label>".$c->label."</label>
                                                </div>
                                                <div class=\"cell cell-content col-md-10\">
                                                    <div class=\"field-content\">
                                                        ".$field_html."
                                                    </div>
                                                </div>
                                            </div>";
                                        }
                                        else{
                                            $html = '<td class="editable k_element_'.$c->name.'"><div style="position:relative;">';
                                            $html .= $field_html;
                                            $html .= '</div></td>';
                                        }

                                        echo $html;

                                        unset( $c );
                                    }
                                }
                                else{
                                    foreach( $this->cells as $c ){
                                        echo $data[$row_id][$c->name]; // pre-rendered with posted data
                                    }
                                }
                                if( $this->stacked_layout ){
                                    echo( '</div></td>' );
                                    echo( '<td class="col-up-down">' );
                                    echo( '    <a class="up icon" href="#" onclick="return false;" title="'.$FUNCS->t('up').'">'.$FUNCS->get_icon('chevron-top').'</a>' );
                                    echo( '    <a class="down icon" href="#" onclick="return false;" title="'.$FUNCS->t('down').'">'.$FUNCS->get_icon('chevron-bottom').'</a>' );
                                    echo( '</td>' );

                                    echo( '<td class="col-actions">' );
                                    echo( '    <input type="checkbox" name="delete[]" value="" style="display: none;">' );
                                    echo( '    <a class="icon add-row" data_mosaic_row="'.$input_id.'-'.$row_id.'" href="#" title="'.$FUNCS->t('add_above').'" onclick="return false;">'.$FUNCS->get_icon('plus').'</a>' );
                                    echo( '    <a class="icon delete-row" title="'.$FUNCS->t('delete').'" href="#">'.$FUNCS->get_icon('trash').'</a>' );
                                    echo( '</td>' );

                                }
                                else{
                                    echo( '<td class="delete">' );
                                    echo( '<input type="checkbox" name="delete[]" value="'.$row_id.'" id="delete'.$row_id.'" style="display: none;"/>' );
                                    echo( '<label for="delete'.$row_id.'">' );
                                    echo( '    <img src="'. REPEATABLE_URL .'tablegear/delete.gif" alt="Delete Row" />' );
                                    echo( '</label>' );
                                    echo( '</td>' );
                                }
                                ?>
                            </tr>
                    <?php } ?>
                    </tbody>
                </table>
                <div>
                    <?php if( $this->stacked_layout ): ?>
                        <div class="mosaic-buttons">
                            <div class="btn-group" id="addRow_<?php echo $input_id; ?>">
                                <a class="btn" onclick="this.blur();"><?php echo $FUNCS->get_icon('plus'); echo $FUNCS->t('add_row'); ?></a>
                            </div>
                        </div>
                    <?php else: ?>
                        <p class="addRow" id="addRow_<?php echo $input_id; ?>"><a><?php echo $FUNCS->t('add_row'); ?></a></p>
                    <?php endif; ?>
                </div>
                <input type="hidden" name="_<?php echo $input_id; ?>_sortorder" id="_<?php echo $input_id; ?>_sortorder"/>
                <div id="addNewRow_<?php echo $input_id; ?>" class="newRow" style="display:none;">
                    <table>
                        <tbody>
                            <tr id="newDataRow_<?php echo $input_id; ?>" class="newRow even">
                                <td class="dg-arrange-table-rows-drag-icon">&nbsp;</td>
                                <?php
                                if( $this->stacked_layout ){ echo( '<td class="col-contents editable"><div class="mosaic-list">' ); };
                                $y=0;
                                foreach( $this->cells as $c ){
                                    $c->data = is_array($c->data) ? array() : '';

                                    if( !count($data) ){// no saved rows
                                        // generate JS for conditional fields
                                        $FUNCS->resolve_active( $c, $CTX->get('k_cur_form'), false, $this->name, 0, $y );
                                    }

                                    $widget = $c->_render( 'data[xxx]['.$c->name.']', 'data-xxx-'.$c->name, '', 1 );
                                    // ID hack..innerHTML does not return 'id' so adding an 'idx' attribute with the same values.
                                    $widget = preg_replace('/(\sid)(\s*=\s*["\']data-xxx-[\w]+["\'])/is', '$1x$2$1$2', $widget);
                                    if( $c->deleted && $AUTH->user->access_level >= K_ACCESS_LEVEL_SUPER_ADMIN && defined('K_ADMIN') ){
                                        $widget .= '<div class="k_cell_deleted">&nbsp;</div>';
                                        $arr_deleted_html[] = $c->_html;
                                    }

                                    if( $this->stacked_layout ){
                                        $html = "
                                        <div class=\"row k_element_".$c->name."\">
                                            <div class=\"cell cell-label col-md-2\">
                                                <label>".$c->label."</label>
                                            </div>
                                            <div class=\"cell cell-content col-md-10\">
                                                <div class=\"field-content\">
                                                    ".$widget."
                                                </div>
                                            </div>
                                        </div>";
                                    }
                                    else{
                                        $html = '<td class="editable k_element_'.$c->name.'"><div style="position:relative;">';
                                        $html .= $widget;
                                        $html .= '</div></td>';
                                    }

                                    echo $html;
                                    $y++;
                                }
                                if( $this->stacked_layout ){
                                    echo( '</div>' );
                                    echo( '<img src="'.K_SYSTEM_THEME_URL.'assets/blank.gif" alt="" onload="
                                        var el=$(\'#data-xxx-dummyimg\');
                                        if(!el.attr(\'idx\')){
                                            var row = el.closest(\'tr\');
                                            var add_icon = row.find(\'.col-actions .add-row\');
                                            add_icon.attr( \'data_mosaic_row\', row.attr(\'id\') );
                                        }
                                    " idx="data-xxx-dummyimg" id="data-xxx-dummyimg" />' );
                                    echo( '</td>' );
                                    echo( '<td class="col-up-down">' );
                                    echo( '    <a class="up icon" href="#" onclick="return false;" title="'.$FUNCS->t('up').'">'. $FUNCS->get_icon('chevron-top') .'</a>' );
                                    echo( '    <a class="down icon" href="#" onclick="return false;" title="'.$FUNCS->t('down').'">'. $FUNCS->get_icon('chevron-bottom') .'</a>' );
                                    echo( '</td>' );

                                    echo( '<td class="col-actions">' );
                                    echo( '    <input type="checkbox" name="delete[]" value="" style="display: none;">' );
                                    echo( '    <a class="icon add-row" data_mosaic_row="" href="#" title="'.$FUNCS->t('add_above').'" onclick="return false;">'.$FUNCS->get_icon('plus').'</a>' );
                                    echo( '    <a class="icon delete-row" title="'.$FUNCS->t('delete').'" href="#">'.$FUNCS->get_icon('trash').'</a>' );
                                    echo( '</td>' );
                                }
                                else{
                                    echo( '<td class="delete">' );
                                    echo( '<input type="checkbox" name="delete[]" value="" id="deleteNULL_STRING" style="display: none;"/>' );
                                    echo( '<label for="deleteNULL_STRING">' );
                                    echo( '    <img src="'. REPEATABLE_URL .'tablegear/delete.gif" alt="Delete Row" />' );
                                    echo( '</label>' );
                                    echo( '</td>' );
                                }
                                ?>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
            <script type="text/javascript">
                $(function(){
                    <?php if( $this->stacked_layout ): ?>
                        COUCH.rrInit('<?php echo $input_id; ?>', <?php echo( intval($this->no_default_row) ); ?>);
                    <?php else: ?>
                        $('#<?php echo $input_id; ?>').tableGear({addDefaultRow:<?php echo( intval($this->no_default_row) ); ?>});
                    <?php endif; ?>
                });
            </script>
            <?php
            $html = ob_get_contents();
            ob_end_clean();

            if( count($arr_deleted_html) ){
                $html = $FUNCS->render( 'repeatable_column_deleted', $arr_deleted_html ) . $html;
            }

            return $html;
        }

        // Handle posted data
        function store_posted_changes( $post_val ){
            global $FUNCS, $Config, $AUTH, $CTX;
            if( $this->deleted || $this->k_inactive ) return; // no need to store

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
                else{
                    $set_explictly = 1; // as direct params of cms:db_persist
                }
            }

            // dynamic params
            for( $y=0; $y<count($this->cells); $y++ ){
                $c = &$this->cells[$y];
                $c->resolve_dynamic_params();
                $c->simple_mode = $this->simple_mode;
                unset( $c );
            }

            $this->validation_errors = 0;
            $this->data = array();
            $this->rendered_data = array();
            $this->search_data = '';
            $sep = '';
            $without_form = ( func_num_args()>1 ) ? 1 : 0;
            for( $row=0; $row<count($data); $row++ ){
                // recreate each row
                for( $y=0; $y<count($this->cells); $y++ ){

                    // hydrate cell with data from database
                    $c = &$this->cells[$y];
                    $c->orig_data = null;
                    $c->store_data_from_saved( $this->orig_data[$row][$c->name] );
                    $c->err_msg = '';
                    $c->k_inactive = 0;

                    // pass posted data to each cell
                    if( $set_explictly && $c->k_type== 'checkbox' ){
                        // supplied static checkbox values are supposed to be comma-separated -
                        // this needs to be changed to match the separator expected by page-field
                        $separator = ( $c->k_separator ) ? $c->k_separator : '|';
                        $sep2 = '';
                        $str_val = '';
                        $arr_tmp = explode(',', $data[$row][$c->name]);
                        foreach( $arr_tmp as $v ){
                            $str_val .= $sep2 . trim( $v );
                            $sep2 = $separator;
                        }
                        $data[$row][$c->name] = $str_val;
                    }

                    // field conditionally inactive? Control fields can be siblings less than $y
                    if( $without_form ){
                        $c->k_inactive = !$FUNCS->resolve_active_without_form( $c, $this->page, true, $y );
                    }
                    else{
                        $c->k_inactive = !$FUNCS->resolve_active( $c, $CTX->get('k_cur_form'), true, $this->name, $row, $y );
                    }

                    $c->store_posted_changes( $data[$row][$c->name] );
                    if( $c->modified ){
                        $this->modified = 1;
                    }

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
                        $this->modified = 1;
                    }

                    // get data to save (will be used if no validation errors occur)
                    if( $c->k_type=='image' || $c->k_type=='file' ){
                        $this->data[$row][$c->name] = $c->data; // backward compatibility.. raw data without domain info
                    }
                    else{
                        $this->data[$row][$c->name] = $c->get_data_to_save();
                    }

                    // get searchable data
                    if( $c->search_type=='text' ){
                        $search_data = '';

                        if( $c->udf && $FUNCS->udfs[$c->k_type]['searchable'] && $c->searchable ){
                            $search_data = $c->get_search_data();
                        }
                        else{ // core types
                            if( (($c->k_type=='textarea' && !$c->no_xss_check) || $c->k_type=='richtext' || $c->k_type=='text') && $c->searchable){
                                $search_data = $this->data[$row][$c->name];
                            }
                        }

                        if( strlen($search_data) ){
                            $this->search_data .= $FUNCS->strip_tags( $search_data ) . ' ';
                        }
                    }

                    // get rendered markup (will be used if validation errors occur)
                    $input_name = 'f_'. $this->name .'['. $row .']['. $c->name .']';
                    $input_id = 'f_'. $this->name .'-'. $row .'-'. $c->name;
                    $err_class = ( $c->err_msg ) ? ' highlite' : '';
                    $field_html = $c->_render( $input_name, $input_id, '', false );
                    if( $c->deleted && $AUTH->user->access_level >= K_ACCESS_LEVEL_SUPER_ADMIN && defined('K_ADMIN') ){
                        $field_html .= '<div class="k_cell_deleted">&nbsp;</div>';
                    }
                    if( $this->stacked_layout ){
                        $html = "
                        <div class=\"row k_element_".$c->name."\">
                            <div class=\"cell cell-label col-md-2".$err_class."\">
                                <label>".$c->label."</label>
                            </div>
                            <div class=\"cell cell-content col-md-10\">
                                <div class=\"field-content".$err_class."\">
                                    ".$field_html."
                                </div>
                            </div>
                        </div>";
                    }
                    else{
                        $html = '<td class="editable k_element_'.$c->name.$err_class.'"><div style="position:relative;">';
                        $html .= $field_html;
                        $html .= '</div></td>';
                    }

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
            if( $this->deleted || $this->k_inactive ) return true;

            return ( $this->validation_errors ) ? false : true;
        }

        // Output to front-end via $CTX
        function get_data( $for_ctx=0 ){
            global $CTX;

            if( $for_ctx ){
                // Data not a simple string hence
                // we'll store it into '_obj_' of CTX directly
                // to be used by the auxilally tag which knows how to display it
                $arr = array( 'cells'=>$this->cells, 'data'=>$this->data );
                $CTX->set_object( $this->name, $arr );
            }

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
            return $this->search_data;
        }

        // renderable theme functions
        static function register_renderables(){
            global $FUNCS;

            $FUNCS->register_render( 'repeatable_column_deleted', array('template_path'=>K_ADDONS_DIR.'repeatable/theme/', 'template_ctx_setter'=>array('Repeatable', '_render_repeatable_column_deleted')) );
            $FUNCS->register_render( 'repeatable_assets', array('renderable'=>array('Repeatable', '_render_repeatable_assets')) );
        }

        static function _render_repeatable_column_deleted( $arr_deleted_html ){
            global $FUNCS, $CTX;

            static $done=0;
            if( !$done ){
                $CTX->set( 'k_add_js_for_repeatable_column_deleted', '1' );
                $done=1;
            }
            else{
                $CTX->set( 'k_add_js_for_repeatable_column_deleted', '0' );
            }

            foreach( $arr_deleted_html as $deleted_html ){
                $str_deleted_html .= $deleted_html . "\r\n\r\n";
            }
            $CTX->set( 'k_deleted_html', $FUNCS->escape_HTML($str_deleted_html) );
        }

        static function _render_repeatable_assets(){
            global $FUNCS;

            if( !defined('REPEATABLE_URL') ){
                define( 'REPEATABLE_URL', K_ADMIN_URL . 'addons/repeatable/' );
                $FUNCS->load_js( REPEATABLE_URL . 'jquery-ui.min.js' );
                $FUNCS->load_js( REPEATABLE_URL . 'tablegear/tablegear.js' );
                $FUNCS->load_css( REPEATABLE_URL . 'tablegear/tablegear.css' );
                $FUNCS->load_css( K_ADMIN_URL . 'addons/bootstrap-grid/theme/grid12.css' );

                ob_start();
                ?>
                    if ( !window.COUCH ) var COUCH = {};
                    $(function(){
                        $('table.rr > tbody').sortable({
                            axis: "y",
                            handle: ".dg-arrange-table-rows-drag-icon",
                            helper: function (e, ui) { // https://paulund.co.uk/fixed-width-sortable-tables
                                ui.children().each(function() {
                                    $(this).width($(this).width());
                                });
                                return ui;
                            },
                            update: function( event, ui ){
                                var row = ui.item;
                                var tbody = $( row ).closest( 'tbody' );
                                tbody.trigger('_reorder');
                            },
                            start: function( event, ui ){
                                var row = ui.item;
                                row.trigger('_reorder_start');
                              },
                            stop: function( event, ui ){
                                var row = ui.item;
                                row.trigger('_reorder_stop');
                            },
                        });
                    });

                    COUCH.rrInit = function( field_id, default_row ){
                        var $field = $('#'+field_id);
                        $field.tableGear({addDefaultRow:default_row, stackLayout:1});
                        $field.on('click', '.col-actions .add-row', function(){
                            var $this = $(this);
                            var row_id = $this.attr('data_mosaic_row');
                            var add_btn = $('#addRow_'+field_id+' a');
                            add_btn.trigger("click", [row_id]);
                        });
                    }
                    COUCH.t_confirm_delete_row = "<?php echo $FUNCS->t('confirm_delete_row'); ?>";
                    COUCH.t_no_data_message = "<?php echo $FUNCS->t('no_data_message'); ?>";
                <?php
                $js = ob_get_contents();
                ob_end_clean();
                $FUNCS->add_js( $js );
            }
        }

    }// end class

    // Register
    $FUNCS->register_udf( '__repeatable', 'Repeatable' ); // The UDF
    $FUNCS->register_tag( 'repeatable', array('Repeatable', 'tag_handler') ); // The helper 'shim' tag that helps create the above UDF
    $FUNCS->register_tag( 'show_repeatable', array('Repeatable', 'show_handler'), 1, 1 ); // The helper tag that shows the variables via CTX
    $FUNCS->add_event_listener( 'register_renderables',  array('Repeatable', 'register_renderables') );
