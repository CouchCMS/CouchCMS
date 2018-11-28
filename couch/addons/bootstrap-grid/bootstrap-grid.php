<?php
    if ( !defined('K_COUCH_DIR') ) die(); // cannot be loaded directly

    class KRow extends KUserDefinedField{

        // Output to admin panel - do nothing
        function _render( $input_name, $input_id, $extra='', $dynamic_insertion=0 ){
            return;
        }

        // Output to front-end via $CTX - do nothing
        function get_data( $for_ctx=0 ){
            return;
        }

        // Handle posted data - do nothing
        function store_posted_changes( $post_val ){
            return;
        }

        function validate(){
            return true;
        }

        // alter <cms:editable>
        static function _alter_editable( &$attr, &$attr_udf, $params, $node ){
            if( $attr['type']!='row' ) return;

            // find all immediate child editable regions and set this 'row' as their parent
            if( count($node->children) ){
                $attr_not_active = null;
                foreach( $node->attributes as $node_attr ){
                    if( $node_attr['name']=='not_active' ){
                        $attr_not_active = $node_attr;
                        break;
                    }
                }

                for( $x=0; $x<count($node->children); $x++ ){
                    $child = &$node->children[$x];

                    if( $child->type==K_NODE_TYPE_CODE && ($child->name=='editable' || $child->name=='repeatable') ){
                        $arr_tmp = array();
                        $child_attr_not_active = null;

                        foreach( $child->attributes as $child_attr ){
                            if( $child_attr['name']!='group' ){
                                if( $child_attr['name']=='not_active' ){
                                    $child_attr_not_active = 1;
                                }
                                elseif( $child_attr['name']=='type' && ($child_attr['value']=='row' || $child_attr['value']=='group') ){
                                    die( "ERROR: Type 'row' editable cannot have  '".$child_attr['value']."' nested within it." );
                                }
                                $arr_tmp[] = $child_attr;
                            }
                        }
                        $arr_tmp[] = array( 'name'=>'group', 'op'=>'=', 'quote_type'=>"'", 'value'=>$attr['name'], 'value_type'=>K_VAL_TYPE_LITERAL);
                        if( $attr_not_active && !$child_attr_not_active ){
                            $arr_tmp[] = $attr_not_active;
                        }
                        $child->attributes = $arr_tmp;
                    }
                    unset( $child );
                }
            }
        }

        // remove deleted row
        static function _post_process_page(){
            global $DB, $PAGE;

            foreach( $PAGE->fields as $field ){
                if( $field->k_type!='row' || $field->system ) continue;

                // if a template is clonable, an unprocessed field will only be deleted in 'page view'
                if( !$field->processed && !$field->deleted && ((!$PAGE->tpl_is_clonable) || ($PAGE->tpl_is_clonable && !$PAGE->is_master)) ){

                    // This type do not contain data hence can be deleted immediately ..
                    // remove all instances of this text field
                    $rs = $DB->delete( K_TBL_DATA_TEXT, "field_id='" . $DB->sanitize( $field->id ). "'" );
                    if( $rs==-1 ) die( "ERROR: Unable to delete field data from K_TBL_DATA_TEXT" );

                    // finally remove this field
                    $rs = $DB->delete( K_TBL_FIELDS, "id='" . $DB->sanitize( $field->id ). "'" );
                    if( $rs==-1 ) die( "ERROR: Unable to delete field K_TBL_FIELDS" );

                    continue;
                }
            }
        }

        // renderable theme functions
        static function _override_renderables(){
            global $FUNCS;

            $FUNCS->override_render( 'form_row', array('template_path'=>K_ADDONS_DIR.'bootstrap-grid/theme/', 'template_ctx_setter'=>array('KRow', '_render_form_row')) );
        }

        static function _render_form_row(){
            global $FUNCS, $CTX;

            if( $CTX->get('k_field_type')!='row' ) return;

            $f = $CTX->get('k_field_obj');
            if( $f->collapsed !='-1' ){
                $CTX->set( 'k_field_is_collapsible', '1' );

                if( $CTX->get('k_error') ){ // if form error, expand containing row
                    $tree = &$FUNCS->get_admin_form_fields( 'weight', 'asc' );
                    $f = &$tree->find( $f->name );
                    if( $f ){
                        $count = count($f->children);
                        for( $x=0; $x<$count; $x++ ){
                            if( $f->children[$x]->obj->err_msg ){
                                $CTX->set( 'k_field_is_collapsed', '0' );
                                break;
                            }
                        }
                        unset( $f );
                    }
                    unset( $tree );
                }
            }
            else{
                $CTX->set( 'k_field_is_collapsible', '0' );
            }
        }

    } // end class KRow

    // Register
    $FUNCS->register_udf( 'row', 'KRow', 0/*repeatable*/ );
    $FUNCS->add_event_listener( 'alter_editable', array('KRow', '_alter_editable') );
    $FUNCS->add_event_listener( 'override_renderables', array('KRow', '_override_renderables') );
    $FUNCS->add_event_listener( 'post_process_page_end', array('KRow', '_post_process_page') );
