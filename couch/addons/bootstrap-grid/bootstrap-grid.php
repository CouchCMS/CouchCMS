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
                for( $x=0; $x<count($node->children); $x++ ){
                    $child = &$node->children[$x];

                    if( $child->type==K_NODE_TYPE_CODE && ($child->name=='editable' || $child->name=='repeatable') ){
                        $arr_tmp = array();
                        foreach( $child->attributes as $child_attr ){
                            if( $child_attr['name']!='group' ){
                                $arr_tmp[] = $child_attr;
                            }
                        }
                        $arr_tmp[] = array( name=>'group', op=>'=', quote_type=>"'", value=>$attr['name'], value_type=>K_VAL_TYPE_LITERAL);
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

            $FUNCS->override_render( 'form_row', array('template_path'=>K_ADDONS_DIR.'bootstrap-grid/theme/') );
        }

    } // end class KRow

    // Register
    $FUNCS->register_udf( 'row', 'KRow', 0/*repeatable*/ );
    $FUNCS->add_event_listener( 'alter_editable', array('KRow', '_alter_editable') );
    $FUNCS->add_event_listener( 'override_renderables', array('KRow', '_override_renderables') );
    $FUNCS->add_event_listener( 'post_process_page_end', array('KRow', '_post_process_page') );
