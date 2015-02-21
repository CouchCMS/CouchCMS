/************************************************************************************************************
Arrange table rows
Copyright (C) November 2010  DTHMLGoodies.com, Alf Magne Kalleland

This library is free software; you can redistribute it and/or
modify it under the terms of the GNU Lesser General Public
License as published by the Free Software Foundation; either
version 2.1 of the License, or (at your option) any later version.

This library is distributed in the hope that it will be useful,
but WITHOUT ANY WARRANTY; without even the implied warranty of
MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the GNU
Lesser General Public License for more details.

You should have received a copy of the GNU Lesser General Public
License along with this library; if not, write to the Free Software
Foundation, Inc., 51 Franklin Street, Fifth Floor, Boston, MA  02110-1301  USA

Dhtmlgoodies.com., hereby disclaims all copyright interest in this script
written by Alf Magne Kalleland.

Alf Magne Kalleland, 2010
Owner of DHTMLgoodies.com

************************************************************************************************************/

/*if(!window.DG) {
	window.DG = {};
};*/


ArrangeTableRows = new Class( {
	Extends: Events,

	el: null,
	dragEl: null,
	listeners : null,
	dragInProgress : false,
	sourceSibling : null,
	source : null,
	destination : null,
	onDrag : {
		showCell : 'all'
	},
	clsFilter : null,
	currentRowState : [],
	offsets : {
		insertionMarker : {
			x : -5
		}
	},

	initialize : function(config) {
		this.el = config.el;
		if(config.listeners) {
			this.addEvents( config.listeners);
		}
		if(config.clsFilter) {
			this.clsFilter = config.clsFilter;
		}
		if(config.onDrag){
			if(!config.onDrag.showCell.length) {
				config.onDrag.showCell = [config.onDrag.showCell];
			}
			this.onDrag.showCell = config.onDrag.showCell
		}


		this._prepareTable();
		this._createDragContainer();
		this._createInsertionMarker();
		$(document.documentElement).addEvent('mouseup', this._drop.bind(this));
		$(document.documentElement).addEvent('mousemove', this._drag.bind(this));

		this.saveState();

	},

	_prepareTable : function() {

		var tbody = $(this.el).getElements('tbody');
		if(tbody) {
			var rows = tbody[0].getElements('tr');
			this._insertEmptyCellIntoThead();

		}else{
			var rows = $(this.el).getElements('tr');
		}


		for (var i = 0; i < rows.length; i++) {
			var cell = new Element('td');
			cell.set('html', '&nbsp;');
			if (!this.clsFilter || rows[i].hasClass(this.clsFilter)) {
				cell.addClass('dg-arrange-table-rows-drag-icon');
				cell.addEvent('mousedown', this._initDrag.bind(this));
				rows[i].addEvent('mousemove', this._overTableRow.bind(this));
			}
			cell.inject(rows[i], 'top');
		}

		var thead = $(this.el).getElements('thead');
		if(thead.length) {
			var rows = thead[0].getElements('tr');
			for (var i = 0; i < rows.length; i++) {
				rows[i].addEvent('mousemove', this._overTableRow.bind(this));
			}
		}
	},

	_insertEmptyCellIntoThead : function() {
		var thead = $(this.el).getElements('thead');
		if(thead.length) {
			var rows = thead[0].getElements('tr');
			if (rows.length) {
				var cell = new Element('td');
            cell.addClass('dg-arrange-table-header');
				cell.inject(rows[0],'top');
			}
		}
	},

	_createDragContainer : function() {
		this.dragEl = new Element('div');
		this.dragEl.addClass('dg-arrange-table-rows-container');
		this.dragEl.setStyles({
			position: 'absolute',
			display: 'none'
		});
		$(document.body).adopt(this.dragEl);
		var table = new Element('table');
		this.dragEl.adopt(table);

	},

	_createInsertionMarker : function(){
		var el = this.insertionMarker = new Element('div');
		el.addClass('dg-arrange-table-rows-insertion-marker');
		var pos = $(this.el).getPosition();
		el.setStyles({
			position:'absolute',
			display:'none',
			left : pos.x + this.offsets.insertionMarker.x
		});
		$(document.body).adopt(el);
	},

	_initDrag : function(e) {
		this.dragInProgress = true;
		this._positionDragContainer(e);
		this.dragEl.setStyle('display','');

		this._setSourceSibling(e);

		this.source = e.target.getParent('tr');

		if (this.onDrag.showCell != 'all') {
			this.dragEl.getElements('table')[0].set('html', '');
			var row = new Element('tr');
			for (i = 0; i < this.onDrag.showCell.length; i++) {
				var cell = this.source.getElements('td')[this.onDrag.showCell[i] + 1].clone();
				row.adopt(cell);
			}
			this.dragEl.getElements('table')[0].adopt(row);
		}
		else {
			this.dragEl.getElements('table')[0].adopt(this.source);
		}
		return false;
	},

	_setSourceSibling : function(e) {
		var tr = e.target.getParent('tr');

		if (tr.getNext('tr')) {
			this.sourceSibling = {
				el: tr.getNext('tr'),
				which: 'next'
			}
		}else{
			this.sourceSibling = {
				el: tr.getPrevious('tr'),
				which: 'previous'
			}
		}

	},

	_drop : function() {
		if (this.dragInProgress) {
			this.dragInProgress = false;
			this.dragEl.setStyle('display', 'none');
			this.insertionMarker.setStyle('display', 'none');


			if (this.destination) {
				this.source.inject(this.destination.el, this.destination.where);

				this.fireEvent('drop', this._getJsonDropObject());
				this.destination = null;
			}
			else {
				if (this.source) {
					this.source.inject(this.sourceSibling.el, this.sourceSibling.which == 'next' ? 'before' : 'after');
				}
			}

			this.source = null;

         this.tg._reordered();
		}
	},

	_getJsonDropObject : function() {
		var json = {
			source : this.source.id,
			destination : this.destination.el.id,
			where : this.destination.where,
			position: this._getPositionOfRow(this.source)
		};
		return json;
	},
	_getPositionOfRow : function(row) {
		var pos = 1;
		while(row = row.getPrevious('tr')) {
			pos++;
		}
		return pos;
	},
	getRows : function() {
		var parent = this._getParentOfRows();
		var rows = parent.getElements('tr');


		var ret = [];
		var rowLength = rows.length;

		for(var i=0;i< rowLength; i++) {
			ret[ret.length] = rows[i].id.substr(rows[i].id.lastIndexOf('-')+1);
		}

		return ret;

	},

	_drag : function(e) {
		if(this.dragInProgress) {
			this._positionDragContainer(e);
			return false;
		}
	},

	_positionDragContainer : function(e) {
		this.dragEl.setStyles({
			top : e.page.y,
			left : e.page.x
		});
	},

	_overTableRow : function(e){
		if (this.dragInProgress) {
			var el = e.target;
			if(el.tagName.toLowerCase()!='tr') {

				el = $(el).getParent('tr');
			}
			var pos = 'after';
			if(el.getParent('thead')) {
				el = el.getParent('table').getElements('tbody')[0].getElements('tr')[0];
				pos = 'before';
			};

			if (el.tagName.toLowerCase() == 'tr') {
				this.destination = {
					el: el,
					where: pos
				}
				this._positionInsertionMarker();
			}
         return false;
		}

		return true;
	},



	reset : function() {

		var parent = this._getParentOfRows();
		for(var i=0;i<this.currentRowState.length;i++) {
			parent.adopt($(this.currentRowState[i]));
		}

	},

	_getParentOfRows : function() {
		var tbody = $(this.el).getElements('tbody');
		if(tbody.length) {
			return tbody[0];
		}
		return $(this.el).getElements('table')[0];
	},

	saveState : function() {
		this.currentRowState = this.getRows();
	},

	_positionInsertionMarker : function(e) {
		var pos = this.destination.el.getPosition();
		this.insertionMarker.setStyles({
			top : this.destination.where == 'before' ? pos.y - 5: pos.y - 5 + this.destination.el.offsetHeight,
			display : ''
		});

	},

   _k :function(row){
      var cell = new Element('td');
      cell.set('html', '&nbsp;');
      if (!this.clsFilter || rows[i].hasClass(this.clsFilter)) {
         cell.addClass('dg-arrange-table-rows-drag-icon');
         cell.addEvent('mousedown', this._initDrag.bind(this));
         row.addEvent('mousemove', this._overTableRow.bind(this));
      }
      cell.inject(row, 'top');
   }



});
