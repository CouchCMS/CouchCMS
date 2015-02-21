/*
 *
 *  TableGear (Dynamic table data in HTML)
 *
 *  Version: 1.6
 *  Documentation: AndrewPlummer.com (http://www.andrewplummer.com/code/tablegear/)
 *  Inspired by: TableKit for Prototype (http://www.millstream.com.au/view/code/tablekit/)
 *  Written for: Mootools 1.2
 *  License: MIT-style License
 *
 *  Copyright (c) 2010 Andrew Plummer
 *
 *
 */


var TableGear = new Class({

  Implements: Options,

  options: {
    hideInputs: true,
    rowStriping: true,
    autoSelect: true,
    addNewRows: true,
    deletePrompt: "Delete this row?",
    noDataMessage: "- No Data -",
    addRowLabel: "Add a Row",
    limitAddedRows: 0
  },

  initialize: function(id, options){

    this.tableID = id;
    this.setOptions(options);
    var el = $(id);

    if(!el){
      this.throwError("Element '"+id+"' does not exist.");
    }
    if(el.get("tag") != "table"){
      this.throwError("Element '"+id+"' must be a <table>.");
    }
    this.table = el;
    this.form = el.getParent("form");

    var thead = this.requireElement("thead", this.table, "Element <thead> is required inside <table>.");
    this.headerRow = this.requireElement("tr", thead, "A <tr> element is required inside <thead>.", "title");
    this.headers = this.headerRow.getChildren("th");

    this.editableCells = new Array();

    this.tbody = this.requireElement("tbody", this.table, "Element <tbody> is required inside <table>.");

    this.rows = this.tbody.getChildren("tr");
    //if(!this.rows.length) this.throwError("Element <tbody> requires at least one row.");
    this.nextid = this.rows.length;

    this.rows.each(function(row, rowIndex){
      if(row.hasClass("noDataRow")) return;
      this.initializeRow(row, rowIndex);
    }, this);

    if(this.options.addNewRows && this.form){
      var newRowForm = $('addNewRow_' + id);
      newRowForm.setStyle("display", "none");
      this.emptyDataRow = $('newDataRow_' + id);
      /*this.addRow = new Element("p", {"class": "addRow"});
      this.addRow.adopt(new Element("a", {html: this.options.addRowLabel, events: {click: function(event){
        this.addNewRow();
      }.bind(this)}}));
      this.addRow.inject(this.table, "after");*/
      this.addRow = $('addRow_' + id);
      this.addRow.getElement('a').addEvent("click", function(event){
         this.addNewRow();
      }.bind(this));

      if(!this.options.editableCellsPerRow){
        this.options.editableCellsPerRow = this.emptyDataRow.getElements("td.editable").length;
      }
    }

    // row drag and drop
    this.arrangeObj = new ArrangeTableRows({
      el: this.tableID,
      onDrag : {
         showCell : 0
      }
    })
    this.arrangeObj.tg = this;
    this._sortorder = $('_' + this.tableID + '_sortorder');
    this._record_order();

    if(!this.rows.length) this.addNewRow();
  },

  queue: new Array(),

  requireElement: function(css, parent, error, exclude){

    //var elements = parent.getChildren(css);

    /* This is a workaround for lack of "," support in getChildren... change to getChildren when fixed */
    var split = css.split(",");
    var elements = [];
    for(i=0;i<split.length;i++){
      elements.combine(parent.getChildren(split[i]));
    }
    /* End workaround */

    if(!elements) this.throwError(error);
    var match;
    elements.each(function(element){
      if(element.hasClass(exclude)) return;
      else match = element;
    });
    return match;
  },

  throwError: function(error){

    var exception = "TableGear Error: " + error;
    alert(exception);
    throw new Error(exception);
  },

  addJob: function(row, cell, input, span){

    if(input.get("value") == input.retrieve("currentValue")) return;
    span.set("html", input.get("value"));

  },

  update: function(){

    this.rows = this.tbody.getChildren("tr");

  },

  stripify: function(){
    this.rows.each(function(row, rowIndex){
      this.addStripe(row, rowIndex+1);
    }, this);
  },

  addStripe: function(row, index){

    if(!this.options.rowStriping) return;
    var css = ((index + 1) % 2) ? "even" : "odd";
    row.erase("class");
    row.addClass(css);
  },

  setValue: function(input, value){
    var tag = input.get("tag");
    if(tag == "select") input.selectedIndex = input.getElement("option[value="+value+"]").index;
    else input.set("value", value);
  },

  initializeRow: function(row, rowIndex){

    this.addStripe(row, rowIndex+1);
    var cells = row.getChildren("td");

    if(!this.options.editableCellsPerRow) this.options.editableCellsPerRow = row.getElements("td.editable").length;

    var keyInput = row.getElement("input[name^=edit]");
    if(keyInput){
      this.hasKeyInput = true;
      row.store('keyInput', keyInput);
      if(this.options.hideInputs){
        var parentCell = keyInput.getParent("td");
        if(parentCell){
          var editColumn = cells.indexOf(parentCell);
          if(this.headers) this.headers[editColumn].setStyle("display", "none");
          if(this.footers) this.footers[editColumn].setStyle("display", "none");
          parentCell.setStyle("display", "none");
        }
        else keyInput.setStyle("display", "none");
      }
    }

    cells.each(function(cell, colIndex){

      var cellID = rowIndex + ":" + colIndex;
      var column = this.headers[colIndex];
      var colType = column.retrieve("colType");
      if(column.hasClass("sortable")){
        if(colType == "numeric" && !column.hasClass("numeric")){
          var text = cell.get("text");
          if(text && !text.match(/[-+]?\d*\.?\d+/)) column.store("colType", "string");
        }
      }
      if(cell.hasClass("inline")){

        this.editableCells.push(cell);

        if (!this.form) this.throwError("Cells require a <form> element to be editable.");

        var span  = this.requireElement("span", cell, "A <span> element is required in editable cell " + cellID + ".");
        var input = this.requireElement("input,select,textarea", cell, "An <input>, <select>, or <textarea> element is required in editable cell " + cellID + ".");
          span.setStyle("display", "inline");
          input.setStyle("display", "none");
          input.set("autoComplete", "off");
          var tag = input.get("tag");
          input.store("currentValue", input.get("value"));
          input.store("column", colIndex);

          /* IE Selects fire on every key press, so make them act like Firefox */
          var tridentSelect = (Browser.Engine.trident && tag == "select") ? true : false;
          input.store("tridentSelect", tridentSelect);

        if(this.options.hideInputs){
          cell.addEvent("click", function(event){

            span.setStyle("display", "none");
            input.setStyle("display", "inline");

            input.focus();
            if(input.select && this.options.autoSelect) input.select();

          }.bindWithEvent(this));
        }


        input.addEvent("blur", function(event){

          if(tridentSelect) this.addJob(row, cell, input, span);
          if(!this.options.hideInputs) return;

          span.setStyle("display", "inline");
          input.setStyle("display", "none");

        }.bindWithEvent(this));

        input.addEvent("change", function(event){
          if(tridentSelect) return;
          this.addJob(row, cell, input, span);
        }.bindWithEvent(this));

        input.addEvent("click", function(event){
          event.stopPropagation();
        }.bindWithEvent(this));

        input.addEvent("esckey", function(event){
          this.setValue(input, input.retrieve("currentValue"));
          if(input.select && this.options.autoSelect) input.select();
          else input.focus();
        }.bindWithEvent(this));

      } else if(cell.getElement("input[name^=delete]")){

        var input = this.requireElement("input[type=checkbox]", cell, "An <input> checkbox element is required for deletable rows in cell " + cellID + '.\n(Name property should be "delete[]".)');
        if(this.options.hideInputs) input.setStyle("display", "none");

        var label = cell.getElement("label");
        if(label) label.setStyle("display", "block");
        cell.addEvent("click", function(event){

          event.preventDefault();
          this.deleteDataRow(row, input);

        }.bindWithEvent(this));

      }

      // deleted columns?
      var deleted_div = cell.getElement("div.k_cell_deleted");
      if(deleted_div){
         deleted_div.setStyle('height', cell.offsetHeight);
      }

    }, this);
  },

  addNewRow: function(event){
    var noDataRow = this.tbody.getElement("tr.noDataRow");
    if(noDataRow){
      noDataRow.dispose();
      this.rows.erase(noDataRow);
    }

    var newDataRow = this.emptyDataRow.clone();
    newDataRow.erase("style");

    newDataRow.erase("id");
    newDataRow.set('id', this.tableID + '-' + this.nextid );
    var cells = newDataRow.getChildren("td");
    cells.each(function(cell, colIndex){
      if(cell.hasClass("editable")){
         /*var input = this.requireElement("input,select,textarea", cell, "An <input>, <select>, or <textarea> element is required in editable cell.");
         var name = input.get('name');
         input.set('name', name.replace('data[xxx]', this.tableID + '[' + this.nextid + ']'));
         */

         var td_content = cell.get("html");
         td_content = td_content.replace(/data\[xxx\]/g, this.tableID + '[' + this.nextid + ']')
         td_content = td_content.replace(/data-xxx-/g, this.tableID + '-' + this.nextid + '-')
         cell.set("html", td_content);

         cell.getElements('*').each(
            function(el){
               if(el.get('tag')=='a'){
                  if(el.hasClass("smoothbox")){ //smoothbox
                     el.onclick = TB_bind;
                  }
                  if(el.rel && el.rel.test(/^lightbox/i)){ //lightbox
                    el.slimbox();
                  }
               }
               // id hack
               var idx = el.get('idx');
               if( idx ){
                  el.erase("idx");
                  el.set('id', idx.replace('data-xxx-', this.tableID + '-' + this.nextid + '-'));
               }
            }, this
         );
         //
      }
    }, this);
    this.nextid++;

    newDataRow.inject(this.tbody);
    this.rows.push(newDataRow);
    this.initializeRow(newDataRow, this.rows.length-1);

    this.arrangeObj._k(newDataRow);
    this._record_order();
  },

  deleteDataRow: function(row, input){
    if(this.options.deletePrompt && !confirm(this.options.deletePrompt)) return;
    this.removeRow(row);
    this.stripify();
    this._record_order();
  },

  removeRow: function(row){
    this.rows.erase(row);
    row.fireEvent('row_delete');
    row.destroy();
    if(this.rows.length < 1){
      var colspan = this.headers.length + 1; // drag handle
      this.headers.each(function(header){
        if(header.getStyle("display") == "none") colspan--;
      });
      var noData = new Element("td", {"text": this.options.noDataMessage,"colspan": colspan,"align": "center"});
      var noDataRow = new Element("tr", {"class": "noDataRow odd"});
      noDataRow.adopt(noData);
      this.tbody.adopt(noDataRow);
      this.addRow.setStyle("display", "block");
    }
    this.editableCells.empty();
    this.tbody.getElements('td.editable').each(function(cell){
      this.editableCells.push(cell);
    }, this);
  },

  _reordered: function(){
      this.update();
      this.stripify();
      this._record_order();
  },

  _record_order: function(){
      this._sortorder.set("value", this.arrangeObj.getRows().join(','));
  }

});


if(!Element.Events.tabkey){
  Element.Events.tabkey = {
    base: "keydown",
    condition: function(event){
      return (event.key == "tab");
    }
  }
}
if(!Element.Events.enterkey){
  Element.Events.enterkey = {
    base: "keydown",
    condition: function(event){
      return (event.key == "enter");
    }
  }
}

if(!Element.Events.esckey){
  Element.Events.esckey = {
    base: "keydown",
    condition: function(event){
      return (event.key == "esc");
    }
  }
}

if(!Element.Events.arrowkeys){
  Element.Events.arrowkeys = {
    base: "keydown",
    condition: function(event){
      var arrows = ["up", "down"];
      return (arrows.contains(event.key));
    }
  }
}

if(!Element.Events.pageUpKey){
  Element.Events.pageUpKey = {
    base: "keydown",
    condition: function(event){
      return (event.code == 33);
    }
  }
}

if(!Element.Events.pageDownKey){
  Element.Events.pageDownKey = {
    base: "keydown",
    condition: function(event){
      return (event.code == 34);
    }
  }
}
