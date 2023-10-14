<?php
    if ( !defined('K_COUCH_DIR') ) die(); // cannot be loaded directly

    function k_override_renderables(){
        global $FUNCS;

        // add more candidate template names for renderables implemented as templates
        $FUNCS->add_event_listener( K_THEME_NAME.'_alter_render_vars_content_list_inner', 'MyTheme::_alter_render_vars' );
        $FUNCS->add_event_listener( K_THEME_NAME.'_alter_render_vars_content_form', 'MyTheme::_alter_render_vars' );
    }


    // class containing the theme functions
    class MyTheme{

        static function _alter_render_vars( &$candidate_templates, $name, $args ){
            global $FUNCS, $CTX;

            // for every candidate template for the renderable, add a template that has the current masterpage suffixed
            $cur_route = $FUNCS->current_route;
            $cur_masterpage = $FUNCS->get_clean_url( $cur_route->masterpage ); // e.g., this will turn 'en/blog.php' into 'en-blog-php'.

            $tmp_array = array();
            foreach( $candidate_templates as $tpl ){
                $tmp_array[] = $tpl;

                if( $cur_route->module=='folders' ){
                    $tmp_array[] = $tpl . '__folder';
                    $tmp_array[] = $tpl . '__folder_' . $cur_masterpage;
                }
                else{
                    $tmp_array[] = $tpl . '_' . $cur_masterpage;
                }
            }

            $candidate_templates = $tmp_array;
        }

    }// end class
