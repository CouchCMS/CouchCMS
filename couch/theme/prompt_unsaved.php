<?php if ( !defined('K_COUCH_DIR') ) die(); // cannot be loaded directly ?>
window.addEvent('domready', function(){
    var form = $( '<?php echo $k_form_id; ?>' );
    var counter = 0;
    
    if( form ){
        var serialize = function( frm ){ 
            /* function adapted from http://mootools.net/forge/p/element_serialize. Copyright (c) 2011 Arieh Glazer */
            var results = {}, inputs = frm.getElements('input, select, textarea');

            inputs.each(function(el){
                var type = el.type, names =[], name = el.name;
                if (!el.name || el.disabled || type == 'submit' || type == 'reset' || type == 'hidden' || type == 'image') return;

                var value = (el.get('tag') == 'select') ? el.getSelected().map(function(opt){
                    return $(opt).get('value');
                }) : ((type == 'radio' || type == 'checkbox') && !el.checked) ? null : el.get('value');

                if( !el.id ){ el.set( 'id', 'tmp_'+(counter++) ); }
                results[el.id] = value;
            });

            return JSON.encode(results);
            
        }
        
        var update_richText_content = function(){
            if( window.CKEDITOR ){
                var key, obj;

                for( key in CKEDITOR.instances ){
                    obj = CKEDITOR.instances[ key ];
                    if( CKEDITOR.instances.hasOwnProperty( key ) ) obj.updateElement();
                }
            }

            if( window.nicEditors ){
                var i = nicEditors.editors.length - 1;

                do{
                    try{
                        nicEditors.editors[ i ].nicInstances[ 0 ].saveContent();
                    }    
                    catch(err){}    
                    i--;
                    
                } while( i > -1 );
            }
        }
        
        var orig_content = serialize( form );
        
        window.onbeforeunload = function(){
            update_richText_content();
            var cur_content = serialize( form );
            
            if( cur_content!=orig_content ) return 'Unsaved changes!';
        };
    }
}); 