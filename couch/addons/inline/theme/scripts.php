<?php
if ( !defined('K_COUCH_DIR') ) die(); // cannot be loaded directly
?>
<script src="<?php echo K_ADMIN_URL; ?>includes/ckeditor/ckeditor.js"></script>
<script>
    CKEDITOR.disableAutoInline = false;

    // Customize specific editor instances on the fly.
    // The "instanceCreated" event is fired for every editor instance created.
    CKEDITOR.on( 'instanceCreated', function( event ){

        var editor = event.editor;
        var inline = editor.element.$.getAttribute("data-k-inline");

        if( inline ){
            editor.on( 'configLoaded', function(){

                editor.config.extraPlugins = 'inlinesave';
                set_toolbar();

                // custom styles
                var custom_styles = editor.element.$.getAttribute("data-k-custom-styles");
                if( custom_styles ){
                    editor.config.stylesCombo_stylesSet = custom_styles;
                }

                <?php if( K_USE_KC_FINDER ): ?>
                    editor.config['filebrowserBrowseUrl'] = '<?php echo K_ADMIN_URL; ?>includes/kcfinder/browse.php?nonce=<?php echo $FUNCS->create_nonce( 'kc_finder' ); ?>&type=file';
                    editor.config['filebrowserImageBrowseUrl'] = '<?php echo K_ADMIN_URL; ?>includes/kcfinder/browse.php?nonce=<?php echo $FUNCS->create_nonce( 'kc_finder' ); ?>&type=image';
                    editor.config['filebrowserWindowWidth'] = '670';
                <?php else: ?>
                    editor.config['filebrowserBrowseUrl'] = '<?php echo K_ADMIN_URL; ?>includes/fileuploader/browser/browser.html';
                    editor.config['filebrowserImageBrowseUrl'] = '<?php echo K_ADMIN_URL; ?>includes/fileuploader/browser/browser.html?Type=Image';
                    editor.config['filebrowserWindowWidth'] = '600';
                <?php endif; ?>

                editor.on( 'mode', function(){
                    if( editor.readOnly ){
                        editor.setReadOnly( false );
                    }
                });

                function set_toolbar(){
                    var element = editor.element;
                    var toolbar = editor.element.$.getAttribute("data-k-toolbar");

                    var toolbar_types = ["small", "basic", "medium", "full", "custom", "default"];
                    if( toolbar_types.indexOf(toolbar) == -1 ){
                        toolbar = 'default';
                    }

                    if( toolbar=='custom' ){
                        var custom_toolbar = editor.element.$.getAttribute("data-k-custom-toolbar");
                        try{
                            var arr_toolbars = ( JSON && JSON.parse(custom_toolbar) ) || eval( custom_toolbar );
                            if( Array.isArray(arr_toolbars) ){
                                editor.config.toolbar = arr_toolbars;
                                return;
                            }
                            else{
                                throw "Invalid format for custom toolbars";
                            }
                        }
                        catch( e ){
                            console.error( "JSON Parsing error:", e );
                            toolbar = 'default';
                        }
                    }
                    if( toolbar=='default' ){
                        if ( element.is('h1', 'h2', 'h3', 'h4', 'h5', 'h6') ){
                            toolbar='small';
                        }
                        else{
                            toolbar='basic';
                        }
                    }

                    switch( toolbar ){
                        case "small":
                            editor.config.toolbar = [
                                [ 'Bold', 'Italic', 'Underline' ],
                                [ 'inlinesave' ]
                            ];
                            break;
                        case "basic":
                            editor.config.toolbar = [
                                [ 'Bold', 'Italic', 'Underline', 'Strike' ],
                                [ 'Format' ],
                                [ 'NumberedList', 'BulletedList', 'Blockquote', 'Link', 'Unlink' ],
                                [ 'Undo', 'Redo', 'RemoveFormat' ],
                                [ 'inlinesave' ]
                            ];
                            break;
                        case "medium":
                            editor.config.toolbar = [
                                [ 'Bold', 'Italic', 'Underline', 'Strike', '-', 'Subscript', 'Superscript' ],
                                [ 'Format' ],
                                [ 'JustifyLeft', 'JustifyCenter', 'JustifyRight', 'JustifyBlock' ],
                                [ 'NumberedList', 'BulletedList', '-', 'Outdent', 'Indent', 'Blockquote' ],
                                [ 'Undo', 'Redo', 'RemoveFormat' ],
                                '/',
                                [ 'Cut', 'Copy', 'Paste', 'PasteText', 'PasteFromWord' ],
                                [ 'Image', 'Table', 'HorizontalRule', 'Smiley', 'SpecialChar' ],
                                [ 'Link', 'Unlink', 'Anchor' ],
                                [ 'inlinesave' ]
                            ];
                            break;
                        case "full":
                            editor.config.toolbar = [
                                [ 'Bold', 'Italic', 'Underline', 'Strike', '-', 'Subscript', 'Superscript' ],
                                [ 'JustifyLeft', 'JustifyCenter', 'JustifyRight', 'JustifyBlock' ],
                                [ 'NumberedList', 'BulletedList', '-', 'Outdent', 'Indent', 'Blockquote' ],
                                [ 'Undo', 'Redo', 'RemoveFormat' ],
                                '/',
                                [ 'Cut', 'Copy', 'Paste', 'PasteText', 'PasteFromWord' ],
                                [ 'Image', 'Table', 'HorizontalRule', 'Smiley', 'SpecialChar' ],
                                [ 'Link', 'Unlink', 'Anchor' ],
                                '/',
                                [ 'Styles', 'Format', 'Font', 'FontSize' ],
                                [ 'TextColor', 'BGColor' ],
                                [ 'inlinesave' ]
                            ];
                            break;
                    }
                    return;
                }
            });

            if( !window.CKEDITOR.k_regions ) window.CKEDITOR.k_regions = [];
            window.CKEDITOR.k_regions.push( editor );

        }
    });

    window.onbeforeunload = function(e){
        if( window.CKEDITOR && window.CKEDITOR.k_regions ){
            for( var i=0;i<window.CKEDITOR.k_regions.length;i++ ){
                if( window.CKEDITOR.k_regions[i].checkDirty() ){
                    return '<?php echo $prompt_text; ?>';
                }
            }

        }
        //return null;
    };

</script>
<?php if( !$no_border ): ?>
<style>
    *[contenteditable="true"]{
        border: 1px solid #F1CA7F;
    }
</style>
<?php endif; ?>
