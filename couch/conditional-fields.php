<?php

    if ( !defined('K_COUCH_DIR') ) die(); // cannot be loaded directly

    class KCondFields{
        static function gen_js(){
            global $PAGE;

            if( is_array($PAGE->form_dependencies) ){
                ob_start();
                ?>
/* conditional fields */
$(function(){
    function get_selected($field){
        var vals = [];
        if($field.is("select")){ $field = $field.find('option'); }
        $field.filter('input:checked, option:selected').each( function(){
            if($(this)[0].checked || $(this)[0].selected){ vals.push(this.value); }
        });
        if($field.is("option")||$field.is("radio")){ vals = (vals.length) ? vals[0] : ''; }

        return vals;
    }

    function setup($form, dependencies){
        var processor = function(e){
            if( !(e.target.type=='select-one' || e.target.type=='checkbox' || e.target.type=='radio') ){
                return;
            }

            var fname = e.target.name;
            var rr = e.data && e.data.rr; // called from within repeatable-region

            if(rr){ // called from repeatable region
                var matches = fname.match(/^(.*?)\[(\d+?)\]\[([^\]]+?)\](\[\])?$/); // e.g f_my_repeatable[0][show_review]
                if(matches===null) return;
                var row_id = matches[2];
                fname = matches[1]+':'+matches[3];
                if(matches[4]) fname += matches[4];
            }

            var field = dependencies.control_fields[fname];
            if( field && field.dependents ){
                $.each(field.dependents, function(name, wrapper){
                    var nested = (name.indexOf(":")!=-1) ? 1 : 0;

                    if(nested){
                        var stacked = (name.indexOf("::")!=-1) ? 1 : 0;
                        var sep = (stacked) ? '::' : ':';
                        var arr = name.split(sep);

                        var arr_row_ids = [];
                        if(rr){
                            arr_row_ids.push(row_id);
                        }
                        else{
                            arr_row_ids = $form.find('#_'+arr[0]+'_sortorder').val().split(',');
                        }

                        $.each(arr_row_ids, function(k, row_id){
                            if(stacked){
                                var $wrapper = $form.find('tr#'+arr[0]+'-'+row_id+' div.k_element_'+arr[1]);
                            }
                            else{
                                var $wrapper = $form.find('tr#'+arr[0]+'-'+row_id+' td.k_element_'+arr[1]+'>div');
                            }
                            var ret = dependencies.funcs[name](row_id);
                            if(ret){ $wrapper.hide(); }else{ $wrapper.show(); }
                        });
                    }
                    else{
                        if(wrapper==''){ wrapper='#k_element_'+name;}
                        var $wrapper = $form.find(wrapper);

                        var ret = dependencies.funcs[name]();
                        if(ret){ $wrapper.hide(); }else{ $wrapper.show(); }
                    }
                });
            }
        };

        var refresh = function(rr, f, arr_row_ids){
            var suffix='';
            if(f.slice(-2)=='[]'){
                f = f.slice(0,-2);
                suffix='[]';
            }
            $.each(arr_row_ids, function(k, row_id){
                var fname = rr+'['+row_id+']['+f+']'+suffix; // e.g. f_my_repeatable[0][review]
                var $obj = $form.find("select[name='"+fname+"'], input[name='"+fname+"']");
                $obj.trigger('change');
            });
        }

        // wire up each control field to respond to change
        var tblsx = [];
        $.each(dependencies.control_fields, function(key, val){
            if(val.obj){
                val.obj.on( "change", processor);
                if( val.dependents ){
                    var tbls = [];
                    $.each(val.dependents, function(name){
                        if(name.indexOf(":")!=-1){ //nested
                            var arr = name.split(':');
                            if($.inArray(arr[0], tbls)==-1){
                                tbls.push(arr[0]);
                                $( "table#"+arr[0]+" tbody" ).on('_insert', function(e, row){
                                    val.obj.trigger('change');
                                });
                            }
                        }
                    });
                }

                val.obj.trigger('change');
            }
            else{
                // repeatable fields
                key = key.split(':');
                if($.inArray(key[0], tblsx)==-1){
                    tblsx.push(key[0]);
                    $( "table#"+key[0] ).on( "change", {rr:1}, processor);
                }

                var arr_row_ids = $form.find('#_'+key[0]+'_sortorder').val().split(',');
                refresh( key[0], key[1], arr_row_ids);

                $( "table#"+key[0]+" tbody" ).on('_insert', function(e, row){
                    var prefix = key[0]+'-';
                    var row_id = $(row).attr('id').slice(prefix.length);
                    refresh( key[0], key[1], [row_id]);
                });
            }
        });
    }
                <?php
                        foreach( $PAGE->form_dependencies as $form_name=>$dependencies ){
            // open form
echo(
"// form
(function(){
    var \$form = \$('form[name=\"".$form_name."\"]');
"
);

            // dependencies..
            $x=0;
            foreach( $dependencies['control_fields'] as $f ){
                if( !$f->page instanceof KUserDefinedField ){
                    if($x==0){
echo(
"
    // controlling fields
"
);
                    }
                $key = $name = $f->name;
                if( $f->module ) $name = 'f_'.$name;
                if( $f->k_type=='checkbox' ) $name = $name.'[]';
                $type = ( $f->k_type=='dropdown' ) ? 'select' : 'input';
echo(
"    var \$".$key." = \$form.find(\"".$type."[name='".$name."']\");
"
);
                    $x++;
                }


            }


            //open control fields
echo(
"
    var dependencies = {
        control_fields:{
"
);

            $sep='';
            foreach( $dependencies['control_fields'] as $f ){
                $key = $f->name;
                if( !$f->page instanceof KUserDefinedField ){
                    if( $f->module ){ $key = 'f_'.$key; }
                    $obj = "                obj: \$".$f->name.",\r\n";
                }
                else{ // repeatable region
                    $key = 'f_'.$f->page->name.':'.$key;
                    $obj = '';
                }
                if( $f->k_type=='checkbox' ) $key = $key.'[]';
echo(
$sep."            \"".$key."\":{
"
);
echo( $obj );
echo(
"                dependents:{
"
);
                    $sep2='';
                    foreach( $f->_dependents as $f2 ){
                        $key2 = $f2->name;
                        if( $f2->page instanceof KUserDefinedField ){ // repeatable region
                            $colon = ( $f2->page->stacked_layout ) ? '::' : ':';
                            $key2 = 'f_'.$f2->page->name.$colon.$key2;
                        }
echo(
$sep2."                    \"".$key2."\": '".str_replace( "'", "\'", $f2->_dependent_target )."'"
);
                        $sep2=",\r\n";
                    }

echo( "\r\n                }
"
);
echo(
"            }"
);
                $sep=",\r\n";
            }// end foreach

            // close control fields, open funcs
echo(
"
        },
        funcs:{
"
);
            $sep='';
            foreach( $dependencies['dependent_fields'] as $f ){
                $key = $f->name;
                if( !$f->page instanceof KUserDefinedField ){
                    $row = '';
                }
                else{ // repeatable region
                    $colon = ( $f->page->stacked_layout ) ? '::' : ':';
                    $key = 'f_'.$f->page->name.$colon.$key;
                    $row = 'row_id';
                }
echo(
$sep."            \"".$key."\":function(".$row."){
"
);

                // resolve ambiguity in names
                $params = ( isset($f->not_active['val']) ) ? $f->not_active['val']['params'] : $f->not_active['params'];
                $tmp = array();
                foreach( $params as $k=>$v ){
                    if( array_key_exists('parent-'.$k, $params) ){
                        $k = 'rr-'.$k;
                    }
                    $tmp[] = $k;
                }
                $params = $tmp;

                foreach( $params as $k ){
                    $prefix = '';
                    $orig_k = $k;
                    if( strpos($k, 'parent-')===0 ){
                        $prefix = 'parent';
                        $k = substr( $k, 7 );
                    }
                    elseif( strpos($k, 'rr-')===0 ){
                        $prefix = 'rr';
                        $k = substr( $k, 3 );
                    }

                    $found = 0;
                    foreach( $f->_depends_on as $f2 ){
                        if( $f2->name==$k ){
                            if( ($prefix=='parent' && $f2->page instanceof KUserDefinedField) || ($prefix=='rr' && !$f2->page instanceof KUserDefinedField) ){
                                continue;
                            }
                            $found=1;

                            if( !$f2->page instanceof KUserDefinedField ){
echo(
"                var ".$orig_k." = get_selected(\$".$k.");
"
);
                            }
                            else{
                                $type = ( $f2->k_type=='dropdown' ) ? 'select' : 'input';
                                $postfix = ( $f2->k_type=='checkbox' ) ? '[]' : '';
echo(
"                var \$rr_".$k." = \$form.find(\"".$type."[name='f_".$f2->page->name."[\"+row_id+\"][".$k."]".$postfix."']\");
                var ".$k." = get_selected(\$rr_".$k.");
"
);
                            }
                            break;
                        }
                    }
                    if( !$found ){
echo(
"                var ".$k." = '';
"
);
                    }
                }

                // js code for function ..
                $code = ( isset($f->not_active['val']) ) ? $f->not_active['val']['code'] : $f->not_active['code'];
                $has_alt_js = 0;
                $js_code = '';
                if( is_array($code) ){
                    foreach( $code as $child ){
                        if( $child->type == K_NODE_TYPE_CODE && $child->name == 'alt_js' ){
                            // execute alt_js tag to get the js
                            foreach( $child->children as $grand_child ){
                                $js_code .= $grand_child->get_HTML();
                            }
                            $has_alt_js = 1;
                            break;
                        }
                    }
                }
                if( !$has_alt_js ){
                    $js_code = self::get_js( $code );
                }
echo(
"
".$js_code."
"
);

echo( "            }"
);
                $sep=",\r\n";
            }

            // close form
echo("
        }
    }

    setup(\$form, dependencies);
})(); //end form\r\n\r\n"
);

        }// end for each form
    echo(
    "
});
    "
    );
                $js = ob_get_contents();
                ob_end_clean();

                return $js;
            }
        }

        static function gen_css(){
            global $PAGE;

            if( is_array($PAGE->form_dependencies) ){
                $arr = array();
                foreach( $PAGE->form_dependencies as $form_name=>$dependencies ){
                    foreach( $dependencies['dependent_fields'] as $f ){
                        if( $f->page instanceof KUserDefinedField ){ //rr
                            if( $f->page->stacked_layout ){
                                $arr[] = 'table#f_'.$f->page->name.' .k_element_'.$f->name;
                            }
                            else{
                                $arr[] = 'table#f_'.$f->page->name.' .k_element_'.$f->name . ' >div';
                            }
                        }
                        else{
                            //$arr[] = ( $f->_dependent_target ) ? $f->_dependent_target : '#k_element_'.$f->name;
                            if( !$f->_dependent_target && $f->k_inactive ) $arr[] = '#k_element_'.$f->name;
                        }
                    }
                }

                if( count($arr) ){
                    $str = "/* conditional fields */\r\n" . implode( ', ', $arr ) . "\r\n{ display:none; }";
                    return $str;
                }
            }
        }

        static function get_js( $node ){
            $html = '';

            if( is_array($node) ){
                foreach( $node as $child ){
                    $html .= self::get_js( $child );
                }
            }
            else{
                switch( $node->type ){
                    case K_NODE_TYPE_ROOT:
                        foreach( $node->children as $child ){
                            $html .= self::get_js( $child );
                        }
                        break;
                    case K_NODE_TYPE_TEXT:
                        $html = trim( $node->text );
                        $ret = strtolower( $html );
                        if( $ret==='1' || $ret==='true' || $ret==='yes' || $ret==='hide' ){
                            $html='    return 1; //hide';
                        }
                        elseif( $ret==='0' || $ret==='false' || $ret==='no' || $ret==='show' ){
                            $html='    return 0; //show';
                        }
                        break;
                    case K_NODE_TYPE_CODE:
                        $func = $node->name;
                        if( !in_array($func, array('if', 'else', 'else_if', 'not', 'arr_val_exists', 'is')) ){
                            /*die( "ERROR: Tag 'cms:$func' not supported in generating JS for conditional fields. <br>
                                Only 'cms:if', 'cms:else', 'cms:else_if', 'cms:not', 'cms:arr_val_exists' and 'cms:is' tags can be used." );*/
                            return;
                        }

                        if( $func=='if' || $func=='else' ) $func = 'k_'.$func;
                        $html = self::$func( $node );
                        break;
                }
            }
            return $html;
        }

        static function resolve_parameters( $attributes ){
            $params = array();
            foreach( $attributes as $attr ){
                $param = array();
                $param['lhs'] = $attr['name'];
                $param['op'] = $attr['op'];
                switch( $attr['value_type'] ){
                    case K_VAL_TYPE_LITERAL:
                        $quote_type = $attr['quote_type'];
                        $param['rhs'] = $quote_type . str_replace( $quote_type, '\\'.$quote_type, $attr['value'] ) . $quote_type;
                        break;
                    case K_VAL_TYPE_VARIABLE:
                        $param['rhs'] = $attr['value'];
                        break;
                    case K_VAL_TYPE_SPECIAL:
                        $param['rhs'] = trim( self::get_js( $attr['value']) );
                        break;
                }
                $params[] = $param;
            }

            return $params;
        }

        static function resolve_condition( $attributes ){
            $equivalent_ops = array("lt"=>"<", "gt"=>">", "le"=>"<=", "ge"=>">=", "="=>"==", "eq"=>"==", "ne"=>"!=");
            $known_ops = array( '<', '>', '<=', '>=', '==', '!=', '(', ')', '&&', '||');

            $s="";
            foreach( $attributes as $attr ){
                if( isset($attr['name']) ){
                    $s .= $attr['name'];
                }
                if( isset($attr['op']) ){
                    $op = $attr['op'];

                    if( isset($equivalent_ops[$op]) ) $op = $equivalent_ops[$op];
                    if( !in_array($op, $known_ops) ) { die( "ERROR (generating JS for conditional fields): Unknown operator: " . $op); }
                    $s .= ' ' . $op . ' ';
                }
                if( isset($attr['value']) ){
                    switch( $attr['value_type'] ){
                        case K_VAL_TYPE_LITERAL:
                            $quote_type = $attr['quote_type'];
                            $s .= $quote_type . str_replace( $quote_type, '\\'.$quote_type, $attr['value'] ) . $quote_type;
                            break;
                        case K_VAL_TYPE_VARIABLE:
                            $s .= $attr['value'];
                            break;
                        case K_VAL_TYPE_SPECIAL:
                            $s .= self::get_js( $attr['value'] );
                            break;
                    }
                }
            }

            return $s;
        }

        static function k_if( $node ){
            static $level = -1;

            $level++;
            $children = $node->children;

            $attr = self::resolve_condition( $node->attributes  );

            $html = "if($attr){\r\n";
            foreach( $children as $child ){
                $html .= self::get_js( $child );
            }
            $html .= "\r\n}\r\n";

            // prettify
            $arr = explode( "\r\n", trim($html) );
            $html='';
            $prefix = '    ';
            if( $level==0 ) $prefix .= '            ';
            foreach( $arr as $s ){
                if( $s!=='' ){
                    $html .= $prefix.$s."\r\n";
                }
            }

            $level--;
            return $html;
        }

        static function k_else( $node ){
            if( count($node->children) ) {die("ERROR (generating JS for conditional fields): Tag \"".$node->name."\" is a self closing tag");}

            $html = "\r\n}\r\nelse{\r\n";
            return $html;
        }

        static function else_if( $node ){
            if( count($node->children) ) {die("ERROR (generating JS for conditional fields): Tag \"".$node->name."\" is a self closing tag");}

            $attr = self::resolve_condition( $node->attributes  );

            $html = "\r\n}\r\nelse if($attr){\r\n";
            return $html;
        }

        static function not( $node ){
            if( count($node->children) ) {die("ERROR (generating JS for conditional fields): Tag \"".$node->name."\" is a self closing tag");}

            $attr = self::resolve_condition( $node->attributes  );

            $html = "!($attr)";
            return $html;
        }

        static function arr_val_exists( $node ){
            global $FUNCS;
            if( count($node->children) ) {die("ERROR (generating JS for conditional fields): Tag \"".$node->name."\" is a self closing tag");}

            $params = self::resolve_parameters( $node->attributes  );
            extract( $FUNCS->get_named_vars(
                            array( 'val'=>'',
                                   'in'=>'',
                                  ),
                            $params)
                       );

            $html = "$.inArray($val, $in)!=-1";
            return $html;
        }

        static function is( $node ){
            return self::arr_val_exists( $node );
        }
    } // end class
