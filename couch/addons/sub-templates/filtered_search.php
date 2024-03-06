<?php
    if ( !defined('K_COUCH_DIR') ) die(); // cannot be loaded directly

    // Custom field-type 'single_check'
    class KSingle_CheckField extends KUserDefinedField{
        static function handle_params( $params ){
            global $FUNCS, $AUTH;
            if( $AUTH->user->access_level < K_ACCESS_LEVEL_SUPER_ADMIN ) return;

            $attr = $FUNCS->get_named_vars(
                array(
                    'inverse'=>'0',
                    ),
                $params);

            $attr['inverse'] = ( $attr['inverse']==1 ) ? 1 : 0;

            return $attr;
        }

        function store_posted_changes( $post_val ){
            if( $this->deleted || $this->k_inactive ) return; // no need to store

            $post_val = trim( $post_val );
            if( $post_val !== '1' ){ $post_val = '0'; }

            if( $this->inverse ){ $post_val = ($post_val==='1') ? '0' : '1'; }

            parent::store_posted_changes( $post_val );
        }

        function _render( $input_name, $input_id, $extra='', $dynamic_insertion=0 ){
            global $FUNCS;

            if( !$this->inverse ){
                $checked = ( $this->get_data() ) ? 'checked="checked"' : '';
            }
            else{
                $checked = ( !$this->get_data() ) ? 'checked="checked"' : '';
            }

            $input = '<input type="checkbox" value="1" '.$checked.' name="'.$input_name.'" id="'.$input_id.'" '.$extra .' ';
            if( $this->deleted ) $input .= 'disabled="1" ';
            $input .= '/>';

            if( $this->simple_mode ){
                $html = '<label for="'.$input_id.'">' . $input . ' ' . $this->opt_values . '</label>';
            }
            else{
                $html = '<div class="ctrls-checkbox';
                if( $this->deleted ) $html .= ' ctrls-disabled';
                $html .= '"><label for="'.$input_id.'">' . $input . '<span class="ctrl-option"></span>' . $this->opt_values . '</label></div>';
            }

            return $html;
        }

        // Search value
        function get_search_data(){
            return;
        }
    }
    $FUNCS->register_udf( 'single_check', 'KSingle_CheckField', 0/*repeatable*/, 0/*searchable*/ );

    // Form Input field for SingleCheck (a wrapper around KSingle_CheckField above)
    class KSingle_CheckFieldForm extends KUserDefinedFormField{
        var $obj;

        static function handle_params( $params, $node ){
            global $FUNCS;

            $attr = $FUNCS->get_named_vars(
                        array(
                            'inverse'=>'0',
                            ),
                        $params);

            $attr['inverse'] = ( $attr['inverse']==1 ) ? 1 : 0;

            return $attr;

        }

        function __construct( $fields, &$siblings ){
            global $PAGE;

            $this->obj = new KSingle_CheckField( $fields, $PAGE /*dummy*/, $siblings );
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
    $FUNCS->register_udform_field( 'single_check', 'KSingle_CheckFieldForm' );

    // Tag <cms:process_filters>
    $FUNCS->register_tag( 'process_filters', function($params, $node){
        global $FUNCS, $CTX, $DB, $PAGE;
        if( count($node->children) ){ die("ERROR: Tag \"".$node->name."\" is a self closing tag"); }

        $attr = $FUNCS->get_named_vars(
            array(
                'masterpage'=>'',
                'folder'=>'',
                'include_subfolders'=>'1',
                'with_count'=>'1',
                ),
            $params);
        extract( $attr );

        // sanitize params
        $masterpage = trim( $masterpage );
        if( $masterpage=='' ){
            $masterpage = $PAGE->tpl_name;
            $tpl_id = $PAGE->tpl_id;
        }
        else{
            $rs = $DB->select( K_TBL_TEMPLATES, array('*'), "name='".$DB->sanitize( $masterpage )."'" );
            if( !count($rs) ){ die( "ERROR: Tag \"".$node->name."\": masterpage '".$FUNCS->cleanXSS($masterpage)."' not found" ); }
            $tpl_id = $rs[0]['id'];
        }
        $folder = trim( $folder );
        $include_subfolders = ( $include_subfolders==0 ) ? 0 : 1;
        $with_count = ( $with_count==0 ) ? 0 : 1;

        $where = "WHERE p.template_id='".$DB->sanitize( $tpl_id )."'";

        if( $folder!='' ){
            $arr_folders = array();

            // get all the folders of the masterpage
            if( $masterpage==$PAGE->tpl_name ){
                $folders = &$PAGE->folders;
            }
            else{
                $folders = &$FUNCS->get_folders_tree( $tpl_id, $masterpage );
            }

            // locate the folder
            $f = &$folders->find( $folder );
            if( $f ){
                if( $include_subfolders ){
                    // get all the child folders of it
                    $sub_folders = $f->get_children(); //includes the parent folder too
                    foreach( $sub_folders as $sf ){
                        $arr_folders[$sf->name] = $sf->id;
                    }
                }
                else{
                    $arr_folders[$f->name] = $f->id;
                }
            }
            else{
                $folder='';
            }

            if( count($arr_folders) ){
                $where .= " AND ";
                $where .= "(";
                $sep = "";
                foreach( $arr_folders as $k=>$v ){
                    $where .= $sep . "p.page_folder_id='" . $DB->sanitize( $v )."'";
                    $sep = " OR ";
                }
                $where .= ")";
            }
        }
        else{
            if( !$include_subfolders ){
                $where .= " AND p.page_folder_id='-1'";
            }
        }
        $where .= " AND NOT p.publish_date = '0000-00-00 00:00:00' AND p.parent_id=0";

        $selector = KSubTemplates::subtpl_selector;
        $aux_tpl_name = KSubTemplates::_get_aux_tpl_name( $masterpage );
        $id_selector = null;
        if( $masterpage==$PAGE->tpl_name ){
            if( $PAGE->_fields[$selector] ){
                $id_selector = $PAGE->_fields[$selector]->id;
            }
        }
        else{
            $rs = $DB->select( K_TBL_FIELDS, array('id'), "template_id='".$DB->sanitize( $tpl_id )."' AND name='".$DB->sanitize( $selector )."'" );
            if( count($rs) ){ $id_selector = $rs[0]['id']; }
        }
        if( !$id_selector ){ die("ERROR: Tag \"".$node->name."\" couldn't find subtemplate selector in the given masterpage"); }

        // find the filter fields
        $sql = "SELECT st.field_id, st.is_stub, st.filter_type, f.name, f.label, f.opt_values, f.custom_params FROM ".K_TBL_SUB_TEMPLATES." st
        INNER JOIN ".K_TBL_FIELDS." f on f.id = st.field_id
        WHERE st.template_id='".$DB->sanitize( $tpl_id )."'
        AND st.sub_template_id IN
        (
            SELECT rel1.cid FROM ".K_TBL_PAGES." p
            inner join ".K_TBL_RELATIONS." rel1 on rel1.pid = p.id
            ".$where."
            AND rel1.fid='".$DB->sanitize( $id_selector )."'
            GROUP BY rel1.cid

            UNION

            SELECT p.id from ".K_TBL_PAGES." p
            inner join ".K_TBL_TEMPLATES." t on p.template_id=t.id
            WHERE t.name='".$DB->sanitize( $aux_tpl_name )."'
            AND p.page_name='@common'
        )
        AND st.is_stub IS NOT NULL
        AND st.filter_type IS NOT NULL
        GROUP BY st.field_id, st.is_stub, st.filter_type
        ORDER BY st.is_stub asc";

        $fields = array();
        $rs = $DB->raw_select( $sql );
        foreach( $rs as $rec ){
            if( !array_key_exists($rec['name'], $fields) ){
                $fields[$rec['name']]=$rec;
            }
        }

        // resolve querystring filters using the fields
        $filters = array();
        foreach( $fields as $f ){
            $k = $f['name'];
            $filter_type = $f['filter_type'];

            if( $filter_type==0 ){ // dropdown
                if( isset($_GET[$k]) ){
                    $val = $_GET[$k];

                    // validate before accepting
                    $custom_params = $f['custom_params'];
                    if( is_string($custom_params) && strlen($custom_params) ){
                        $arr_params = @$FUNCS->unserialize($custom_params);
                        if( is_array($arr_params) ){
                            $rs = $DB->raw_select( "SELECT p.id, p.page_name  FROM ".K_TBL_TEMPLATES." t INNER JOIN ".K_TBL_PAGES." p on t.id = p.template_id WHERE t.name='".$DB->sanitize( $arr_params['masterpage'] )."'AND p.page_name='".$DB->sanitize( $val )."'" );
                            if( count($rs) ){
                                $filters[$k] = array( 'id'=>$rs[0]['id'], 'name'=>$rs[0]['page_name'], 'type'=>$filter_type );
                            }
                        }
                    }
                }
            }
            else if( $filter_type==1 ){ // checkbox
                if( isset($_GET[$k]) ){
                    $filters[$k] = array( 'id'=>$f['field_id'], 'name'=>$f['name'], 'type'=>$filter_type );
                }
            }
            else if( $filter_type==2 ){ // slider
                if( isset($_GET[$k]) ){
                    list( $val0, $val1 ) = explode( ',', $_GET[$k] );
                    if( is_numeric($val0) && is_numeric($val1) ){
                        $val0 = floatval( $val0 );
                        $val1 = floatval( $val1 );

                        $filters[$k] = array( 'id'=>$f['field_id'], 'name'=>$f['name'], 'type'=>$filter_type, 'val0'=>$val0, 'val1'=>$val1 );
                    }
                }
            }
        }

        $filter_fields = $arr_str_filters = array();
        foreach( $fields as $fname=>$f ){
            $filter_fields[$fname] = array('name'=>$fname, 'label'=>$f['label'], 'order'=>$f['is_stub'], 'selected'=>'-');
            $filter_type = $f['filter_type'];

            if( $filter_type==0 ){ // dropdown

                // query for available values (taking querystring filters into consideration)
                $sql = "#".$fname."\r\n";
                $sql .= "SELECT p2.page_title as label, p2.page_name as name, count(p2.id) as count FROM ".K_TBL_PAGES." p\r\n";
                $sql .= "INNER JOIN ".K_TBL_RELATIONS." rel1 on rel1.pid = p.id\r\n";
                $x=2;
                foreach( $filters as $k=>$v ){
                    if( $k!=$fname ){
                        if( $v['type']==0 ){
                            $sql .= "INNER JOIN ".K_TBL_RELATIONS." rel".$x." on rel".$x.".pid = p.id\r\n";
                        }
                        elseif( $v['type']==1 || $v['type']==2 ){
                            $sql .= "INNER JOIN ".K_TBL_DATA_NUMERIC." t".$x." on t".$x.".page_id = p.id\r\n";
                        }
                        $x++;
                    }
                }
                $sql .= "INNER JOIN ".K_TBL_PAGES." p2 on p2.id = rel1.cid\r\n";
                $sql .= $where."\r\n";
                $sql .= "AND rel1.fid='".$DB->sanitize( $f['field_id'] )."'";
                if( array_key_exists($fname, $filters) ){
                    $sql .= " AND rel1.cid='".$DB->sanitize( $filters[$fname]['id'] )."'";
                }
                $sql .= "\r\n";
                $x=2;
                foreach( $filters as $k=>$v ){
                    if( $k!=$fname ){
                        if( $v['type']==0 ){
                            $sql .= "AND rel".$x.".fid='".$DB->sanitize( $fields[$k]['field_id'] )."' AND rel".$x.".cid='".$DB->sanitize( $v['id'] )."'\r\n";
                        }
                        elseif( $v['type']==1 ){
                            $sql .= "AND t".$x.".field_id='".$DB->sanitize( $fields[$k]['field_id'] )."' AND t".$x.".value='1'\r\n";
                        }
                        elseif( $v['type']==2 ){
                            $sql .= "AND t".$x.".field_id='".$DB->sanitize( $fields[$k]['field_id'] )."' AND t".$x.".value>='".$DB->sanitize( $v['val0'] )."' AND t".$x.".value<='".$DB->sanitize( $v['val1'] )."'\r\n";
                        }
                        $x++;
                    }
                    else{
                        $filter_fields[$fname]['is_active'] = 1;
                        $filter_fields[$fname]['selected'] = $v['name'];
                        $arr_str_filters[] = $fname.'='.$v['name'];
                    }
                }
                $sql .= "GROUP BY rel1.cid\r\n";
                $sql .= "ORDER BY p2.page_name asc\r\n";

                $options = $DB->raw_select( $sql );

                $filter_fields[$fname]['options'] = $options;
                $str_options = '';
                $sep = '';
                foreach( $options as $opt ){
                    $str_options .= $sep . $opt['label'];
                    if( $with_count ){ $str_options .= ' ('.$opt['count'].')'; }
                    $str_options .= '='.$opt['name'];
                    $sep = '|';
                }
                $filter_fields[$fname]['options_str'] = $str_options;
                $filter_fields[$fname]['is_disabled'] = ( !count($options) ) ? 'disabled="1"' : '';
                $filter_fields[$fname]['filter_type'] = 'dropdown';
            }
            else if( $filter_type==1 ){ // checkbox
                $sql = "#".$fname."\r\n";
                $sql .= "SELECT count(*) as count FROM ".K_TBL_PAGES." p\r\n";
                $sql .= "INNER JOIN ".K_TBL_DATA_NUMERIC." t1 on t1.page_id = p.id\r\n";
                $x=2;
                foreach( $filters as $k=>$v ){
                    if( $k!=$fname ){
                        if( $v['type']==0 ){
                            $sql .= "INNER JOIN ".K_TBL_RELATIONS." rel".$x." on rel".$x.".pid = p.id\r\n";
                        }
                        elseif( $v['type']==1 || $v['type']==2 ){
                            $sql .= "INNER JOIN ".K_TBL_DATA_NUMERIC." t".$x." on t".$x.".page_id = p.id\r\n";
                        }
                        $x++;
                    }
                }
                $sql .= $where."\r\n";
                $sql .= "AND t1.field_id='".$DB->sanitize( $f['field_id'] )."' AND t1.value='1'\r\n";
                $x=2;
                foreach( $filters as $k=>$v ){
                    if( $k!=$fname ){
                        if( $v['type']==0 ){
                            $sql .= "AND rel".$x.".fid='".$DB->sanitize( $fields[$k]['field_id'] )."' AND rel".$x.".cid='".$DB->sanitize( $v['id'] )."'\r\n";
                        }
                        elseif( $v['type']==1 ){
                            $sql .= "AND t".$x.".field_id='".$DB->sanitize( $fields[$k]['field_id'] )."' AND t".$x.".value='1'\r\n";
                        }
                        elseif( $v['type']==2 ){
                            $sql .= "AND t".$x.".field_id='".$DB->sanitize( $fields[$k]['field_id'] )."' AND t".$x.".value>='".$DB->sanitize( $v['val0'] )."' AND t".$x.".value<='".$DB->sanitize( $v['val1'] )."'\r\n";
                        }
                        $x++;
                    }
                    else{
                        $filter_fields[$fname]['is_active'] = 1;
                        $arr_str_filters[] = $fname.'=1';
                    }
                }

                $rs = $DB->raw_select( $sql );
                $count = $rs[0]['count'];

                $filter_fields[$fname]['count'] = $count;
                $filter_fields[$fname]['opt_values'] = $f['opt_values'];
                $filter_fields[$fname]['options_str'] = ( $with_count ) ? $f['label'].' ('.$count.')' : $f['label'];
                $filter_fields[$fname]['is_disabled'] = ( !$count ) ? 'disabled="1"' : '';
                $filter_fields[$fname]['filter_type'] = 'checkbox';
            }
            else if( $filter_type==2 ){ // slider
                $opt_values = array();

                $sql = "#".$fname."\r\n";
                $sql .= "SELECT t.value FROM ".K_TBL_PAGES." p\r\n";
                $sql .= "INNER JOIN ".K_TBL_DATA_NUMERIC." t on t.page_id = p.id\r\n";
                $sql .= $where."\r\n";
                $sql .= "AND t.field_id='".$DB->sanitize( $f['field_id'] )."'\r\n";
                $sql .= "GROUP BY t.value\r\n";
                $sql .= "ORDER BY t.value asc\r\n";

                $rs = @mysql_query( $sql, $DB->conn );
                if( $rs ){
                    while( $rec = mysql_fetch_row($rs) ) {
                        $opt_values[] = floatval( $rec[0] );
                    }
                    mysql_free_result( $rs );
                }
                $count = count( $opt_values );

                if( $count ){
                    $filter_fields[$fname]['opt_values'] = '['.implode( ',', $opt_values ).']';
                    $val0 = 0;
                    $val1 = count($opt_values)-1;

                    if( count($filters) && array_key_exists($fname, $filters) ){
                        $val0 = (($key = array_search($filters[$fname]['val0'], $opt_values))!==false) ? $key : $val0;
                        $val1 = (($key = array_search($filters[$fname]['val1'], $opt_values))!==false) ? $key : $val1;
                        $arr_str_filters[] = $fname.'>='.$opt_values[$val0];
                        $arr_str_filters[] = $fname.'<='.$opt_values[$val1];
                        $filter_fields[$fname]['is_active'] = 1;
                    }

                    $filter_fields[$fname]['val0'] = $val0;
                    $filter_fields[$fname]['val1'] = $val1;
                }
                $filter_fields[$fname]['count'] = $count;
                $filter_fields[$fname]['filter_type'] = 'slider';
            }
        }
        $CTX->set( 'st_filter_fields', $filter_fields, 'global' );
        $CTX->set( 'st_filters_str', implode( '|', $arr_str_filters ), 'global' );
        $CTX->set( 'st_filters_folder', $folder, 'global' );
        $CTX->set( 'st_include_subfolders', $include_subfolders, 'global' );
    });