/*
 *
 *  TableGear (Dynamic table data in HTML)
 *
 *  Version: 1.6 for jQuery
 *  Documentation: AndrewPlummer.com (https://github.com/andrewplummer/TableGear)
 *  Inspired by: TableKit for Prototype (http://www.millstream.com.au/view/code/tablekit/)
 *  Written for: jQuery 1.4
 *  License: MIT-style License
 *
 *  Copyright (c) 2010 Andrew Plummer
 *
 *
 */


(function($){

  function setDefaults(name, value, hash){
    if(hash[name] === undefined) hash[name] = value;
  }

  jQuery.fn.tableGear = function(options){

    options = options || {};

    setDefaults('hideInputs',   true,                options);
    setDefaults('rowStriping',  true,                options);
    setDefaults('addNewRows',   true,                options);
    setDefaults('deletePrompt', (typeof COUCH=='object' && COUCH.t_confirm_delete_row) ? COUCH.t_confirm_delete_row : 'Delete this row?',  options);
    setDefaults('noDataMessage', (typeof COUCH=='object' && COUCH.t_no_data_message) ? COUCH.t_no_data_message : '- No Data -',  options);
    setDefaults('addRowLabel',  'Add a Row',         options);
    setDefaults('limitAddedRows', 0,                 options);
    setDefaults('addDefaultRow', 0,                  options);
    setDefaults('stackLayout', 0,                    options);

    initialize(this);

    var table;
    var tbody;

    var id;  // Id of the table
    var nextid;

    var rows;
    var headers;
    var emptyRow;

    var addRow;
    var _sortorder;

    function initialize(el){

      rows = [];
      id = el.attr('id');

      if(el.is('table')){
        table = el;
      } else {
        throwError("Element '"+id+"' must be a <table>.");
      }

      requireElement('thead', table, '<thead> is required inside <table>');

      headers = $('thead th', table)

      tbody = requireElement('> tbody', table, '<tbody> is required inside <table>');
      tbody.bind( '_reorder', _reordered );

      nextid = 0;
      table.find("> tbody > tr").each(function(rowIndex){
        var el = $(this);
        if(el.hasClass('noDataRow')) return;
        initializeRow(el, rowIndex);
        nextid++;
      });


      if(options.addNewRows){
        $('#addNewRow_' + id).hide();
        emptyRow = $('#newDataRow_' + id);
        addRow = $('#addRow_' + id);
        $('a', addRow).on("click", function(event, arg1){
             addNewRow(arg1);
        });
      }

      _sortorder = $('#_' + id + '_sortorder');

      _record_order();
      if(!rows.length){ (options.addDefaultRow) ? addNoDataRow() : addNewRow(); }
      if(options.stackLayout){
        table.on('click', '.col-up-down .up', function(){
            var row = $(this).closest('tr');
            var tbody = row.closest( 'tbody' );
            row.prev().before(row);
            tbody.trigger('_reorder');
        });
        table.on('click', '.col-up-down .down', function(){
            var row = $(this).closest('tr');
            var tbody = row.closest( 'tbody' );
            row.next().after(row);
            tbody.trigger('_reorder');
        });
      }
    }

    function requireElement(selector, el, error){
      var found = el.find(selector);
      if(found.length == 0) throwError(error);
      return found;
    }

    function throwError(error){
      var exception = "TableGear Error: " + error;
      alert(exception);
      throw new Error(exception);
    }

    function update(){
      rows = [];
      table.find("> tbody > tr").each(function(rowIndex){
        var el = $(this);
        rows.push(el);
      });
    }

    function stripify(){
        $.each(rows, function(rowIndex){
          var row = $(this);
          addStripe(row, rowIndex);
        });
    }

    function addStripe(row, index){
      if(index % 2 == 0){
        row.addClass('odd');
        row.removeClass('even');
      } else {
        row.addClass('even');
        row.removeClass('odd');
      }
    }

    function initializeRow(row, rowIndex){
      rows.push(row);
      addStripe(row, rowIndex);
      prepNewRow(row, rowIndex);
    }

    function prepNewRow(row, rowIndex){
        $('td', row).each(function(columnIndex){

            var cell = $(this);
            var cellID = rowIndex + ":" + columnIndex;

            var deleteCheckbox = $('input[name^=delete]', cell);
            if(deleteCheckbox.length > 0){
                var input = requireElement("input[type=checkbox]", cell, "An <input> checkbox element is required for deletable rows in cell " + cellID + '.\n(Name property should be "delete[]".)');
                if(options.hideInputs) input.css("display", "none");

                var label = $("label", cell);
                if(label) label.css("display", "block");
                var target = ( !options.stackLayout ) ? cell : $('.delete-row', cell);
                target.on("click", function(event){
                  event.preventDefault();
                  removeRow(row);
                });
            }
      });
    }

    function addNewRow( above ){
      $('.noDataRow', tbody).remove();
      var newRow = emptyRow.clone();
      newRow.removeAttr('style');
      newRow.removeAttr('id');
      newRow.attr('id', id + '-' + nextid );

      $('td', newRow).each(function(columnIndex){
          var cell = $(this);

          if(cell.hasClass('editable')){
             var td_content = cell.html();
             td_content = td_content.replace(/data\[xxx\]/g, id + '[' + nextid + ']')
             td_content = td_content.replace(/data-xxx-/g, id + '-' + nextid + '-')
             cell.html(td_content);

             cell.find('*').each(function(){
               // id hack
               var el = $(this);
               var idx = el.attr('idx');
               if( idx ){
                  el.removeAttr("idx");
                  el.attr('id', idx); // not necessary with jQuery as, unlike mootools, it does return the id
               }
             });
          }

      });

      nextid++;

      if( above ){
        $(tbody.find('#'+above)).before(newRow);
        prepNewRow(newRow, rows.length);
        update();
        stripify();
      }
      else{
        tbody.append(newRow);
        initializeRow(newRow, rows.length);
      }

      _record_order();
       tbody.trigger("_insert", newRow);
    }

    function removeRow(row){
      if(options.deletePrompt && !confirm(options.deletePrompt)) return;

      rows = $.grep(rows, function(r){ return r.attr('id') !== row.attr('id'); });
      row.trigger('row_delete');
      row.remove();
      if(rows.length < 1){
        addNoDataRow();
      }
      stripify();
      _record_order();
    }

    function addNoDataRow(){
        var message = options.noDataMessage;
        var colspan = $('thead th:visible', table).length;
        var noDataRow = $('<tr class="noDataRow odd"><td align="center" colspan="'+colspan+'">'+message+'</td></tr>');
        tbody.append(noDataRow);
        addRow.show();
    }

    function _reordered(){
      update();
      stripify();
      _record_order();
    }

    function _record_order(){
        var ret = [];

        $.each(rows, function(rowIndex){
          var row = $(this);
          var row_id = row.attr('id');
          ret.push(row_id.substr(row_id.lastIndexOf('-')+1));
        });

        _sortorder.val(ret.join(','));
    }

  };

})(jQuery);
