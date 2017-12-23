"use strict";

if ( !window.COUCH ) var COUCH = {};

/* Methods */

/**
 * Close callback for mosaic modal
 */
COUCH.mosaicModalClose = function() {
    window.KMosaic = null;
    $.magnificPopup.close();
};

/**
 * Open callback for mosaic modal
 */
COUCH.mosaicModalOpen = function() {
    var $this = $( this.st.el );  // this is $.magnificPopup.instance
    window.KMosaic = {
        callBack: function( content, pid, update_link ) {
            var content_div;

            var field_id = $this.attr('data_mosaic_field');

            if( $this.hasClass('edit-row') ){ // editing an existing row
                var row_id = $this.attr('data_mosaic_row');
                var row = $this.closest('tr#'+row_id);
                if(row.length == 0){
                    row = $('#'+field_id+' #'+row_id);
                }

                content_div = row.find('.col-contents.editable .mosaic_contents');
                var orig_content = content_div.html();
                content_div.html( content );

                var pid_input = row.find('#'+row_id+'-pid');
                pid_input.attr( 'value', pid );

                var edit_row = row.find('.edit-row');
                edit_row.attr( 'data-mfp-src', update_link );
            }
            else{ // adding a new row

                // find and set the targets
                var new_data_row = $('#newDataRow_'+field_id);

                content_div = new_data_row.find('.mosaic_contents');
                content_div.html( content );

                var pid_input = new_data_row.find('#data-xxx-pid');
                pid_input.attr( 'value', pid );

                var edit_row = new_data_row.find('.edit-row');
                edit_row.attr( 'data-mfp-src', update_link );

                var row_id = $this.attr('data_mosaic_row'); // will be set if called from row actions popover

                // add a new row
                var add_btn = $('#addRow_'+field_id+' a');
                add_btn.trigger("click", [row_id]);

                // reset
                content_div.html( '' );
                pid_input.val( '' );
                edit_row.attr( 'data-mfp-src', '' );
            }

            // close modal
            COUCH.mosaicModalClose();
        }
    };
};

COUCH.mosaicPopover = function( el, field_id ){
    el.popover({
        container: '#k_element_'+field_id.slice(2),
        html:      true,
        placement: "auto",
        trigger:   "focus",
        content:   function() {
            var $el = $( this );
            var row_id = $el.attr('data_mosaic_row');

            var source = $('#mosaic_selector_'+field_id).find( "a.popup-iframe" ).clone();
            $(source).each(function(index){
                var item = $(this);
                item.attr( 'data_mosaic_row', row_id );
                COUCH.bindPopupIframe( item, COUCH.mosaicModalOpen, COUCH.mosaicModalClose, "mosaic-iframe", true );
            });

            return $( '<div class="mosaic-popover"></div>' ).append( source );
        }
    });
}

COUCH.mosaicActions = function( el, field_id ){
    var row = el.closest('tr');
    var edit_icon = row.find('.col-actions .edit-row');
    edit_icon.attr( 'data_mosaic_row', row.attr('id') );
    COUCH.bindPopupIframe( edit_icon, COUCH.mosaicModalOpen, COUCH.mosaicModalClose, 'mosaic-iframe', true );

    var add_icon = row.find('.col-actions .add-row');
    add_icon.attr( 'data_mosaic_row', row.attr('id') );
    COUCH.mosaicPopover( add_icon, field_id );
}

COUCH.mosaicInit = function( field_id ){
    var field = $('#'+field_id);
    field.tableGear({addDefaultRow:1, stackLayout:1});

    COUCH.mosaicPopover( $('#mosaic_hidden_selector_'+field_id), field_id );
    COUCH.mosaicPopover( $('#'+field_id).find('.col-actions .add-row'), field_id );
}

$( function(){
    COUCH.bindPopupIframe( COUCH.el.$content.find( ".mosaic.tableholder .popup-iframe" ), COUCH.mosaicModalOpen, COUCH.mosaicModalClose, "mosaic-iframe", true );
});