(function () {
    CKEDITOR.plugins.add('inlinesave', {
        init: function (editor) {
            var inline = editor.element.$.getAttribute("data-k-inline");
            if( inline ){
                editor.addCommand('inlinesave', {
                    exec: function (editor) {
                        if( editor.checkDirty() ){
                            editor.resetDirty();
                            setTimeout( function(){ button.setState( CKEDITOR.TRISTATE_DISABLED ); }, 0);

                            var data = editor.getData();
                            TINY.box.show({url:inline,post:'data='+encodeURIComponent(data),opacity:10,top:4,modal:1,returnjs:function(response){
                                TINY.box.hide();
                                editor.setData( response );
                                editor.resetDirty();
                                setTimeout( function(){ button.setState( CKEDITOR.TRISTATE_DISABLED ); }, 0);
                            }});
                        }
                    }
                });

                editor.ui.addButton && editor.ui.addButton('inlinesave', {
                    label: 'Inline Save',
                    command: 'inlinesave',
                    icon: this.path + 'save.png'
                });

                var button = editor.getCommand( 'inlinesave' );
                setTimeout( function(){ button.setState( CKEDITOR.TRISTATE_DISABLED ); }, 0);

                var timer;
                editor.on('change', function (){
                    if ( timer ) return;

                    timer = setTimeout( function() {
                        var state = ( editor.checkDirty() ) ? CKEDITOR.TRISTATE_OFF: CKEDITOR.TRISTATE_DISABLED;
                        button.setState( state );
                        timer = 0;
                    }, 100);
                });
                editor.on( 'destroy', function(){ if( timer ) clearTimeout( timer ); timer=null; } );

            }
        }
    });
})();
