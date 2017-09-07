/*!
 * NicEdit - Micro Inline WYSIWYG
 * Copyright 2007-2008 Brian Kirchoff
 *
 * NicEdit is distributed under the terms of the MIT license
 * For more information visit http://nicedit.com/
 * Do not remove this copyright message
 */

var bkExtend = function () {
    var args = arguments;
    if (args.length == 1) args = [this, args[0]];
    for (var prop in args[1]) args[0][prop] = args[1][prop];
    return args[0];
};

function bkClass() {}

bkClass.prototype.construct = function () {};

bkClass.extend = function (def) {
    var classDef = function () {
        if (arguments[0] !== bkClass) {
            return this.construct.apply(this, arguments);
        }
    };
    var proto = new this(bkClass);
    bkExtend(proto, def);
    classDef.prototype = proto;
    classDef.extend = this.extend;
    return classDef;
};



var bkElement = bkClass.extend({
    construct: function (elm, d) {
        if (typeof (elm) == "string") {
            elm = (d || document).createElement(elm);
        }
        elm = $BK(elm);
        return elm;
    },

    appendTo: function (elm) {
        elm.appendChild(this);
        return this;
    },

    appendBefore: function (elm) {
        elm.parentNode.insertBefore(this, elm);
        return this;
    },

    addEvent: function (type, fn) {
        bkLib.addEvent(this, type, fn);
        return this;
    },

    setContent: function (c) {
        this.innerHTML = c;
        return this;
    },

    pos: function () {
        var curleft = curtop = 0;
        var o = obj = this;
        if (obj.offsetParent) {
            do {
                curleft += obj.offsetLeft;
                curtop += obj.offsetTop;
            } while (obj = obj.offsetParent);
        }
        return [curleft, curtop + this.offsetHeight];
    },

    noSelect: function () {
        bkLib.noSelect(this);
        return this;
    },

    parentTag: function (t) {
        var elm = this;
        do {
            if (elm && elm.nodeName && elm.nodeName.toUpperCase() == t) {
                return elm;
            }
            elm = elm.parentNode;
        } while (elm);
        return false;
    },

    hasClass: function (cls) {
        return this.className.match(new RegExp('(\\s|^)nicEdit-' + cls + '(\\s|$)'));
    },

    addClass: function (cls) {
        if (!this.hasClass(cls)) {
            this.className += " nicEdit-" + cls;
        }
        return this;
    },

    removeClass: function (cls) {
        if (this.hasClass(cls)) {
            this.className = this.className.replace(new RegExp('(\\s|^)nicEdit-' + cls + '(\\s|$)'), ' ');
        }
        return this;
    },

    setStyle: function (st) {
        var elmStyle = this.style;
        for (var itm in st) {
            switch (itm) {
            case 'float':
                elmStyle['cssFloat'] = elmStyle['styleFloat'] = st[itm];
                break;
            case 'opacity':
                elmStyle.opacity = st[itm];
                elmStyle.filter = "alpha(opacity=" + Math.round(st[itm] * 100) + ")";
                break;
            case 'className':
                this.className = st[itm];
                break;
            default:
                elmStyle[itm] = st[itm];
            }
        }
        return this;
    },

    getStyle: function (cssRule, d) {
        var doc = (!d) ? document.defaultView : d;
        if (this.nodeType == 1) {
            return (doc && doc.getComputedStyle) ? doc.getComputedStyle(this, null).getPropertyValue(cssRule) : this.currentStyle[bkLib.camelize(cssRule)];
        }
    },

    remove: function () {
        this.parentNode.removeChild(this);
        return this;
    },

    setAttributes: function (at) {
        for (var itm in at) {
            this[itm] = at[itm];
        }
        return this;
    }
});



var bkLib = {
    isMSIE: (navigator.appVersion.indexOf("MSIE") != -1),

    addEvent: function (obj, type, fn) {
        (obj.addEventListener) ? obj.addEventListener(type, fn, false) : obj.attachEvent("on" + type, fn);
    },

    toArray: function (iterable) {
        var length = iterable.length,
            results = new Array(length);
        while (length--) {
            results[length] = iterable[length];
        }
        return results;
    },

    noSelect: function (element) {
        if (element.setAttribute && element.nodeName.toLowerCase() != 'input' && element.nodeName.toLowerCase() != 'textarea') {
            element.setAttribute('unselectable', 'on');
        }
        for (var i = 0; i < element.childNodes.length; i++) {
            bkLib.noSelect(element.childNodes[i]);
        }
    },

    camelize: function (s) {
        return s.replace(/\-(.)/g, function (m, l) {
            return l.toUpperCase();
        });
    },

    inArray: function (arr, itm) {
        return (bkLib.search(arr, itm) !== null);
    },

    search: function (arr, itm) {
        for (var i = 0; i < arr.length; i++) {
            if (arr[i] == itm)
                return i;
        }
        return null;
    },

    cancelEvent: function (e) {
        e = e || window.event;
        if (e.preventDefault && e.stopPropagation) {
            e.preventDefault();
            e.stopPropagation();
        }
        return false;
    },

    domLoad: [],

    domLoaded: function () {
        if (arguments.callee.done) return;
        arguments.callee.done = true;
        for (i = 0; i < bkLib.domLoad.length; i++) bkLib.domLoad[i]();
    },

    onDomLoaded: function (fireThis) {
        this.domLoad.push(fireThis);
        if (document.addEventListener) {
            document.addEventListener("DOMContentLoaded", bkLib.domLoaded, null);
        } else if (bkLib.isMSIE) {
            document.write("<scr" + "ipt id=__ie_onload defer " + ((location.protocol == "https:") ? "src='javascript:void(0)'" : "src=//0") + "><\/scr" + "ipt>");
            $BK("__ie_onload").onreadystatechange = function () {
                if (this.readyState == "complete") {
                    bkLib.domLoaded();
                }
            };
        }
        window.onload = bkLib.domLoaded;
    }
};



function $BK(elm) {
    if (typeof (elm) == "string") {
        elm = document.getElementById(elm);
    }
    return (elm && !elm.appendTo) ? bkExtend(elm, bkElement.prototype) : elm;
}



var bkEvent = {
    addEvent: function (evType, evFunc) {
        if (evFunc) {
            this.eventList = this.eventList || {};
            this.eventList[evType] = this.eventList[evType] || [];
            this.eventList[evType].push(evFunc);
        }
        return this;
    },

    fireEvent: function () {
        var args = bkLib.toArray(arguments),
            evType = args.shift();
        if (this.eventList && this.eventList[evType]) {
            for (var i = 0; i < this.eventList[evType].length; i++) {
                this.eventList[evType][i].apply(this, args);
            }
        }
    }
};



Function.prototype.closure = function () {
    var __method = this,
        args = bkLib.toArray(arguments),
        obj = args.shift();
    return function () {
        if (typeof (bkLib) != 'undefined') {
            return __method.apply(obj, args.concat(bkLib.toArray(arguments)));
        }
    };
};

Function.prototype.closureListener = function () {
    var __method = this,
        args = bkLib.toArray(arguments),
        object = args.shift();
    return function (e) {
        var target;
        e = e || window.event;
        if (e.target) {
            target = e.target;
        } else {
            target = e.srcElement;
        }
        return __method.apply(object, [e, target].concat(args));
    };
};



/* START CONFIG */
var nicEditorConfig = bkClass.extend({
    buttons: {
        'bold': {
            name: 'Bold',
            command: 'Bold',
            tags: ['B', 'STRONG'],
            css: {
                'font-weight': 'bold'
            },
            key: 'b'
        },
        'italic': {
            name: 'Italicize',
            command: 'Italic',
            tags: ['EM', 'I'],
            css: {
                'font-style': 'italic'
            },
            key: 'i'
        },
        'underline': {
            name: 'Underline',
            command: 'Underline',
            tags: ['U'],
            css: {
                'text-decoration': 'underline'
            },
            key: 'u'
        },
        'left': {
            name: 'Left Align',
            command: 'justifyleft',
            noActive: true
        },
        'center': {
            name: 'Center Align',
            command: 'justifycenter',
            noActive: true
        },
        'right': {
            name: 'Right Align',
            command: 'justifyright',
            noActive: true
        },
        'justify': {
            name: 'Justify Align',
            command: 'justifyfull',
            noActive: true
        },
        'ol': {
            name: 'Insert Ordered List',
            command: 'insertorderedlist',
            tags: ['OL']
        },
        'ul': {
            name: 'Insert Unordered List',
            command: 'insertunorderedlist',
            tags: ['UL']
        },
        'subscript': {
            name: 'Subscript',
            command: 'subscript',
            tags: ['SUB']
        },
        'superscript': {
            name: 'Superscript',
            command: 'superscript',
            tags: ['SUP']
        },
        'strikethrough': {
            name: 'Strike Through',
            command: 'strikeThrough',
            css: {
                'text-decoration': 'line-through'
            }
        },
        'removeformat': {
            name: 'Remove Formatting',
            command: 'removeformat',
            noActive: true
        },
        'indent': {
            name: 'Indent',
            command: 'indent',
            noActive: true
        },
        'outdent': {
            name: 'Remove Indentation',
            command: 'outdent',
            noActive: true
        },
        'hr': {
            name: 'Insert Horizontal Rule',
            command: 'insertHorizontalRule',
            noActive: true
        }
    },

    buttonList: ['save', 'bold', 'italic', 'underline', 'left', 'center', 'right', 'justify', 'ol', 'ul', 'fontSize', 'fontFamily', 'fontFormat', 'indent', 'outdent', 'image', 'upload', 'link', 'unlink', 'forecolor', 'bgcolor'],

    iconList: {
        'xhtml': 'code',
        'bgcolor': 'brush',
        'forecolor': 'pencil',
        'bold': 'bold',
        'center': 'align-center',
        'hr': 'ellipses',
        'indent': 'arrow-thick-right',
        'italic': 'italic',
        'justify': 'justify-left',
        'left': 'align-left',
        'ol': 'list',
        'outdent': 'arrow-thick-left',
        'removeformat': 'delete',
        'right': 'align-right',
        'strikethrough': 'strikethrough',
        'subscript': 'subscript',
        'superscript': 'superscript',
        'ul': 'list-rich',
        'underline': 'underline',
        'image': 'image',
        'link': 'link-intact',
        'unlink': 'link-broken',
        'close': 'x'
    }
});
/* END CONFIG */



var nicEditors = {
    nicPlugins: [],
    editors: [],

    registerPlugin: function (plugin, options) {
        this.nicPlugins.push({
            p: plugin,
            o: options
        });
    },

    allTextAreas: function (nicOptions) {
        var textareas = document.getElementsByTagName("textarea");
        for (var i = 0; i < textareas.length; i++) {
            nicEditors.editors.push(new nicEditor(nicOptions).panelInstance(textareas[i]));
        }
        return nicEditors.editors;
    },

    findEditor: function (e) {
        var editors = nicEditors.editors;
        for (var i = 0; i < editors.length; i++) {
            if (editors[i].instanceById(e)) {
                return editors[i].instanceById(e);
            }
        }
    }
};



var nicEditor = bkClass.extend({
    construct: function (o) {
        this.options = new nicEditorConfig();
        bkExtend(this.options, o);
        this.nicInstances = [];
        this.loadedPlugins = [];
        this.isDisabled = false;

        var plugins = nicEditors.nicPlugins;
        for (var i = 0; i < plugins.length; i++) {
            this.loadedPlugins.push(new plugins[i].p(this, plugins[i].o));
        }
        nicEditors.editors.push(this);
        bkLib.addEvent((window.COUCH && !window.COUCH.simple && document.getElementById('scroll-content')) ? document.getElementById('scroll-content') : document.body, 'mousedown', this.selectCheck.closureListener(this));
    },

    panelInstance: function (e, o) {
        e = this.checkReplace($BK(e));
        var panelElm = new bkElement('DIV').appendBefore(e);
        this.setPanel(panelElm);
        return this.addInstance(e, o);
    },

    checkReplace: function (e) {
        var r = nicEditors.findEditor(e);
        if (r) {
            r.removeInstance(e);
            r.removePanel();
        }
        return e;
    },

    addInstance: function (e, o) {
        var newInstance;
        e = this.checkReplace($BK(e));
        if (e.contentEditable || !! window.opera) {
            newInstance = new nicEditorInstance(e, o, this);
        } else {
            newInstance = new nicEditorIFrameInstance(e, o, this);
        }
        this.nicInstances.push(newInstance);
        return this;
    },

    removeInstance: function (e) {
        e = $BK(e);
        var instances = this.nicInstances;
        for (var i = 0; i < instances.length; i++) {
            if (instances[i].e == e) {
                instances[i].remove();
                this.nicInstances.splice(i, 1);
            }
        }
    },

    removePanel: function (e) {
        if (this.nicPanel) {
            this.nicPanel.remove();
            this.nicPanel = null;
        }
    },

    instanceById: function (e) {
        e = $BK(e);
        var instances = this.nicInstances;
        for (var i = 0; i < instances.length; i++) {
            if (instances[i].e == e) {
                return instances[i];
            }
        }
    },

    setPanel: function (e) {
        this.nicPanel = new nicEditorPanel($BK(e), this.options, this);
        this.fireEvent('panel', this.nicPanel);
        return this;
    },

    nicCommand: function (cmd, args) {
        if (this.selectedInstance) {
            this.selectedInstance.nicCommand(cmd, args);
        }
    },

    getIcon: function (iconName, className) {
        return '<svg' + (className ? (' class="nicEdit-' + className + '"') : '') + '><use xlink:href="'+this.options.iconsPath+'open-iconic.svg#' + this.options.iconList[iconName] + '"></use></svg>';
    },

    selectCheck: function (e, t) {
        var found = false;
        do {
            if (t.className && t.className.indexOf('nicEdit') != -1) {
                return false;
            }
        } while (t = t.parentNode);
        this.fireEvent('blur', this.selectedInstance, t);
        this.lastSelectedInstance = this.selectedInstance;
        this.selectedInstance = null;
        return false;
    }
});

nicEditor = nicEditor.extend(bkEvent);



var nicEditorInstance = bkClass.extend({
    isSelected: false,

    construct: function (e, options, nicEditor) {
        this.ne = nicEditor;
        this.elm = this.e = e;
        this.options = options || {};

        var newX = e.clientWidth || parseInt(e.getStyle('width')),
            newY = e.clientHeight || parseInt(e.getStyle('height'));

        this.initialHeight = newY;

        var isTextarea = (e.nodeName.toLowerCase() == "textarea");
        if (isTextarea || this.options.hasPanel) {
            var ie7s = (bkLib.isMSIE && !((typeof document.body.style.maxHeight != "undefined") && document.compatMode == "CSS1Compat"))
            var s = {};
            s[(ie7s) ? 'height' : 'maxHeight'] = (this.ne.options.maxHeight) ? this.ne.options.maxHeight + 'px' : null;
            this.editorContain = new bkElement('DIV').setStyle(s).addClass('mainContain').appendBefore(e);
            var editorElm = new bkElement('DIV').setStyle({
                minHeight: newY + 18 + 'px'
            }).addClass('main').appendTo(this.editorContain);

            e.setStyle({
                display: 'none'
            });

            editorElm.innerHTML = e.innerHTML;
            if (isTextarea) {
                editorElm.setContent(e.value);
                this.copyElm = e;
                var f = e.parentTag('FORM');
                if (f) {
                    bkLib.addEvent(f, 'submit', this.saveContent.closure(this));
                }
            }
            editorElm.setStyle((ie7s) ? {
                height: newY + 'px'
            } : {
                overflow: 'hidden'
            });
            this.elm = editorElm;
        }
        this.ne.addEvent('blur', this.blur.closure(this));

        this.init();
        this.blur();
    },

    init: function () {
        this.elm.setAttribute('contentEditable', 'true');
        this.instanceDoc = document.defaultView;
        this.elm.addEvent('mousedown', this.selected.closureListener(this)).addEvent('keypress', this.keyDown.closureListener(this)).addEvent('focus', this.selected.closure(this)).addEvent('blur', this.blur.closure(this)).addEvent('keyup', this.selected.closure(this));
        this.ne.fireEvent('add', this);
    },

    remove: function () {
        this.saveContent();
        if (this.copyElm || this.options.hasPanel) {
            this.editorContain.remove();
            this.ne.removePanel();
        }
        this.disable();
        this.ne.fireEvent('remove', this);
    },

    disable: function () {
        this.elm.setAttribute('contentEditable', 'false');
    },

    getSel: function () {
        return (window.getSelection) ? window.getSelection() : document.selection;
    },

    getRng: function () {
        var s = this.getSel();
        if (!s || s.rangeCount === 0) {
            return;
        }
        return (s.rangeCount > 0) ? s.getRangeAt(0) : s.createRange && s.createRange() || document.createRange();
    },

    selRng: function (rng, s) {
        if (window.getSelection) {
            s.removeAllRanges();
            s.addRange(rng);
        } else {
            rng.select();
        }
    },

    selElm: function () {
        var r = this.getRng();
        if (!r) {
            return;
        }
        if (r.startContainer) {
            var contain = r.startContainer;
            if (r.cloneContents().childNodes.length == 1) {
                for (var i = 0; i < contain.childNodes.length; i++) {
                    var rng = contain.childNodes[i].ownerDocument.createRange();
                    rng.selectNode(contain.childNodes[i]);
                    if (r.compareBoundaryPoints(Range.START_TO_START, rng) != 1 &&
                        r.compareBoundaryPoints(Range.END_TO_END, rng) != -1) {
                        return $BK(contain.childNodes[i]);
                    }
                }
            }
            return $BK(contain);
        } else {
            return $BK((this.getSel().type == "Control") ? r.item(0) : r.parentElement());
        }
    },

    saveRng: function () {
        this.savedRange = this.getRng();
        this.savedSel = this.getSel();
    },

    restoreRng: function () {
        if (this.savedRange) {
            this.selRng(this.savedRange, this.savedSel);
        }
    },

    keyDown: function (e, t) {
        this.ne.fireEvent('keyDown', this, e);

        if (e.ctrlKey) {
            this.ne.fireEvent('key', this, e);
        }
    },

    selected: function (e, t) {
        if (!t && !(t = this.selElm)) {
            t = this.selElm();
        }
        if (!e.ctrlKey) {
            var selInstance = this.ne.selectedInstance;
            if (selInstance != this) {
                if (selInstance) {
                    this.ne.fireEvent('blur', selInstance, t);
                }
                this.ne.selectedInstance = this;
                this.ne.fireEvent('focus', selInstance, t);
            }
            this.ne.fireEvent('selected', selInstance, t);
            this.isFocused = true;
            this.editorContain.addClass('mainSelected');
            this.elm.addClass('selected');
        }
        return false;
    },

    blur: function () {
        this.isFocused = false;
        this.editorContain.removeClass('mainSelected');
        this.elm.removeClass('selected');
    },

    saveContent: function () {
        if (this.copyElm || this.options.hasPanel) {
            this.ne.fireEvent('save', this);
            (this.copyElm) ? this.copyElm.value = this.getContent() : this.e.innerHTML = this.getContent();
        }
    },

    getElm: function () {
        return this.elm;
    },

    getContent: function () {
        this.content = this.getElm().innerHTML;
        if (this.content.substr(-4, 4) === '<br>') {
            this.content = this.content.substr(0, this.content.length - 4);
        }
        this.ne.fireEvent('get', this);
        return this.content;
    },

    setContent: function (e) {
        this.content = e;
        this.ne.fireEvent('set', this);
        this.elm.innerHTML = this.content;
    },

    nicCommand: function (cmd, args) {
        document.execCommand(cmd, false, args);
    }
});



var nicEditorIFrameInstance = nicEditorInstance.extend({
    savedStyles: [],

    init: function () {
        var c = this.elm.innerHTML.replace(/^\s+|\s+$/g, '');
        this.elm.innerHTML = '';
        if (!c) c = "<br />";
        this.initialContent = c;

        this.elmFrame = new bkElement('iframe').setAttributes({
            'src': 'javascript:;',
            'frameBorder': 0,
            'allowTransparency': 'true',
            'scrolling': 'no'
        }).setStyle({
            height: '100px',
            width: '100%'
        }).addClass('frame').appendTo(this.elm);

        if (this.copyElm) {
            this.elmFrame.setStyle({
                width: (this.elm.offsetWidth - 4) + 'px'
            });
        }

        var styleList = ['font-size', 'font-family', 'font-weight', 'color'];
        for (var itm in styleList) {
            this.savedStyles[bkLib.camelize(itm)] = this.elm.getStyle(itm);
        }

        setTimeout(this.initFrame.closure(this), 50);
    },

    disable: function () {
        this.elm.innerHTML = this.getContent();
    },

    initFrame: function () {
        var fd = $BK(this.elmFrame.contentWindow.document);
        fd.designMode = "on";
        fd.open();
        var css = this.ne.options.externalCSS;
        fd.write('<html><head>' + ((css) ? '<link href="' + css + '" rel="stylesheet" type="text/css" />' : '') + '</head><body id="nicEditContent" style="margin:0 !important;background-color:transparent !important;">' + this.initialContent + '</body></html>');
        fd.close();
        this.frameDoc = fd;

        this.frameWin = $BK(this.elmFrame.contentWindow);
        this.frameContent = $BK(this.frameWin.document.body).setStyle(this.savedStyles);
        this.instanceDoc = this.frameWin.document.defaultView;

        this.heightUpdate();
        this.frameDoc.addEvent('mousedown', this.selected.closureListener(this)).addEvent('keyup', this.heightUpdate.closureListener(this)).addEvent('keydown', this.keyDown.closureListener(this)).addEvent('keyup', this.selected.closure(this));
        this.ne.fireEvent('add', this);
    },

    getElm: function () {
        return this.frameContent;
    },

    setContent: function (c) {
        this.content = c;
        this.ne.fireEvent('set', this);
        this.frameContent.innerHTML = this.content;
        this.heightUpdate();
    },

    getSel: function () {
        return (this.frameWin) ? this.frameWin.getSelection() : this.frameDoc.selection;
    },

    heightUpdate: function () {
        this.elmFrame.style.height = Math.max(this.frameContent.offsetHeight, this.initialHeight) + 'px';
    },

    nicCommand: function (cmd, args) {
        this.frameDoc.execCommand(cmd, false, args);
        setTimeout(this.heightUpdate.closure(this), 100);
    }
});



var nicEditorPanel = bkClass.extend({
    construct: function (e, options, nicEditor) {
        this.elm = e;
        this.options = options;
        this.ne = nicEditor;
        this.panelButtons = [];
        this.buttonList = bkExtend([], this.ne.options.buttonList);

        this.panelContain = new bkElement('DIV').addClass('panelContain');
        this.panelElm = new bkElement('DIV').addClass('panel').appendTo(this.panelContain);
        this.panelContain.appendTo(e);

        if (window.COUCH) {
            this.$tooltips = $(this.panelContain).tooltip({
                animation: false,
                container: 'body',
                selector: '.nicEdit-buttonsEnabled>.nicEdit-buttonContain'
            });
        }

        var opt = this.ne.options,
            buttons = opt.buttons;
        for (var button in buttons) {
            this.addButton(button, opt, true);
        }
        this.reorder();
        e.noSelect();
    },

    addButton: function (buttonName, options, noOrder) {
        var button = options.buttons[buttonName],
            type = null;

        if (button['type']) {
            type = typeof(window[button['type']]) === undefined ? null : window[button['type']];
        } else {
            type = nicEditorButton;
        }

        var hasButton = bkLib.inArray(this.buttonList, buttonName);
        if (type && (hasButton || this.ne.options.fullPanel)) {
            this.panelButtons.push(new type(this.panelElm, buttonName, options, this.ne));
            if (!hasButton) {
                this.buttonList.push(buttonName);
            }
        }
    },

    findButton: function (itm) {
        for (var i = 0; i < this.panelButtons.length; i++) {
            if (this.panelButtons[i].name == itm)
                return this.panelButtons[i];
        }
    },

    reorder: function () {
        var bl = this.buttonList;
        for (var i = 0; i < bl.length; i++) {
            var button = this.findButton(bl[i]);
            if (button) {
                this.panelElm.appendChild(button.contain);
            }
        }
    },

    remove: function () {
        if (window.COUCH) this.$tooltips.tooltip('destroy');
        this.elm.remove();
    }
});



var nicEditorButton = bkClass.extend({
    construct: function (e, buttonName, options, nicEditor) {
        this.options = options.buttons[buttonName];
        this.name = buttonName;
        this.ne = nicEditor;
        this.elm = e;

        this.contain = new bkElement('A').setAttributes({
            'title': this.options.name
        }).addClass('buttonContain');
        this.contain.innerHTML = this.ne.getIcon(buttonName, 'button');
        this.contain.appendTo(e);

        this.contain.addEvent('mousedown', this.mouseClick.closure(this)).noSelect();

        if (!window.opera) {
            this.contain.onmousedown = this.contain.onclick = bkLib.cancelEvent;
        }

        nicEditor.addEvent('selected', this.enable.closure(this)).addEvent('blur', this.disable.closure(this)).addEvent('key', this.key.closure(this));

        this.disable();
        this.init();
    },

    init: function () {},

    hide: function () {
        this.contain.setStyle({
            display: 'none'
        });
    },

    updateState: function () {
        if (this.isActive) {
            this.contain.addClass('buttonActive');
        } else {
            this.contain.removeClass('buttonActive');
        }
    },

    checkNodes: function (e) {
        var elm = e;
        do {
            if (this.options.tags && bkLib.inArray(this.options.tags, elm.nodeName)) {
                this.activate();
                return true;
            }
        } while ((elm = elm.parentNode) && elm.className != "nicEdit");
        elm = $BK(e);
        while (elm.nodeType == 3) {
            elm = $BK(elm.parentNode);
        }
        if (this.options.css) {
            for (var itm in this.options.css) {
                if (elm.getStyle(itm, this.ne.selectedInstance.instanceDoc) == this.options.css[itm]) {
                    this.activate();
                    return true;
                }
            }
        }
        this.deactivate();
        return false;
    },

    activate: function () {
        if (!this.isDisabled) {
            this.isActive = true;
            this.updateState();
            this.ne.fireEvent('buttonActivate', this);
        }
    },

    deactivate: function () {
        this.isActive = false;
        this.updateState();
        if (!this.isDisabled) {
            this.ne.fireEvent('buttonDeactivate', this);
        }
    },

    enable: function (ins, t) {
        if (this.ne.isDisabled) {
            this.ne.isDisabled = false;
            this.elm.addClass('buttonsEnabled');
        }

        this.isDisabled = false;
        this.updateState();
        if (t !== document) {
            this.checkNodes(t);
        }
    },

    disable: function (ins, t) {
        if (!this.ne.isDisabled) {
            this.ne.isDisabled = true;
            this.elm.removeClass('buttonsEnabled');
        }

        this.isDisabled = true;
        this.updateState();
    },

    toggleActive: function () {
        (this.isActive) ? this.deactivate() : this.activate();
    },

    mouseClick: function () {
        if (this.options.command) {
            this.ne.nicCommand(this.options.command, this.options.commandArgs);
            if (!this.options.noActive) {
                this.toggleActive();
            }
        }
        this.ne.fireEvent("buttonClick", this);
    },

    key: function (nicInstance, e) {
        if (this.options.key && e.ctrlKey && String.fromCharCode(e.keyCode || e.charCode).toLowerCase() == this.options.key) {
            this.mouseClick();
            if (e.preventDefault) {
                e.preventDefault();
            }
        }
    }
});



var nicPlugin = bkClass.extend({
    construct: function (nicEditor, options) {
        this.options = options;
        this.ne = nicEditor;
        this.ne.addEvent('panel', this.loadPanel.closure(this));

        this.init();
    },

    loadPanel: function (np) {
        var buttons = this.options.buttons;
        for (var button in buttons) {
            np.addButton(button, this.options);
        }
        np.reorder();
    },

    init: function () {}
});



/* START CONFIG */
var nicPaneOptions = {};
/* END CONFIG */

var nicEditorPane = bkClass.extend({
    construct: function (elm, nicEditor, options, openButton) {
        this.ne = nicEditor;
        this.elm = elm;
        this.pos = elm.pos();

        if (window.COUCH && !window.COUCH.simple) {
            var windowWidth = jQuery(window).width(),
                sidebarCollapsed = document.getElementById('sidebar').className.match(new RegExp('(\\s|^)collapsed(\\s|$)')),
                sidebarOffset = windowWidth <= 761 || sidebarCollapsed ? 0 : windowWidth >= 1921 ? 320 : 240;
        } else {
            var sidebarOffset = 0;
        }

        this.contain = new bkElement('DIV').setStyle({
            left: this.pos[0] - sidebarOffset + 'px',
            top: this.pos[1] + 'px'
        }).addClass('paneContain');
        this.pane = new bkElement('DIV').addClass('pane').setStyle(options).appendTo(this.contain);

        if (openButton && !openButton.options.noClose) {
            this.close = new bkElement('DIV').addClass('paneClose');
            this.close.innerHTML = this.ne.getIcon('close');
            this.close.addEvent('mousedown', openButton.removePane.closure(this)).appendTo(this.pane);
        }

        this.contain.noSelect().appendTo((window.COUCH && !window.COUCH.simple) ? document.getElementById('scroll-content') : document.body);

        this.position();
        this.init();
    },

    init: function () {},

    position: function () {
        if (this.ne.nicPanel) {
            var panelElm = this.ne.nicPanel.elm,
                panelPos = panelElm.pos(),
                newLeft = panelPos[0] + parseInt(panelElm.getStyle('width')) - (parseInt(this.pane.getStyle('width')) + 14) + (this.elm.hasClass('selectBox') ? 2 : 0);

            if (newLeft < this.pos[0]) {
                this.contain.setStyle({
                    left: 'auto',
                    right: panelElm.offsetLeft + 'px'
                });
            }
        }
    },

    toggle: function () {
        this.isVisible = !this.isVisible;
        this.contain.setStyle({
            display: ((this.isVisible) ? 'block' : 'none')
        });
    },

    remove: function () {
        if (this.contain) {
            this.contain.remove();
            this.contain = null;
        }
    },

    append: function (c) {
        c.appendTo(this.pane);
    },

    setContent: function (c) {
        var reposition = false;
        if (parseInt(this.pane.getStyle('width')) == 0) {
            reposition = true;
        }

        this.pane.setContent(c);

        this.position();
    }
});



var nicEditorAdvancedButton = nicEditorButton.extend({
    init: function () {
        this.ne.addEvent('selected', this.removePane.closure(this)).addEvent('blur', this.removePane.closure(this));
    },

    mouseClick: function () {
        if (!this.isDisabled) {
            if (this.pane && this.pane.pane) {
                this.removePane();
            } else {
                this.pane = new nicEditorPane(this.contain, this.ne, {
                    width: (this.width || '270px')
                }, this);
                this.addPane();
                this.ne.selectedInstance.saveRng();
            }
        }
    },

    addForm: function (f, elm) {
        this.form = new bkElement('form').addClass('form').addEvent('submit', this.submit.closureListener(this));
        this.pane.append(this.form);
        this.inputs = {};
        var leftAligned = false;

        for (var itm in f) {
            var field = f[itm],
                val = '';
            if (elm) {
                val = elm.getAttribute(itm);
            }
            if (!val) {
                val = field['value'] || '';
            }
            var type = f[itm].type;

            if (type == 'title') {
                new bkElement('label').addClass('paneTitle').setContent(field.txt).appendTo(this.form);
            } else if (type == 'title-left') {
                new bkElement('label').addClass('paneTitle-left').setContent(field.txt).appendTo(this.form);
                leftAligned = true;
            } else {
                var contain = new bkElement('DIV').addClass('paneRow').appendTo(this.form);
                if (field.txt) {
                    new bkElement('label').addClass('paneLabel').setContent(field.txt).appendTo(contain);
                }

                switch (type) {
                case 'text':
                    this.inputs[itm] = new bkElement('input').addClass('paneInput').setAttributes({
                        id: itm,
                        'value': val,
                        'type': 'text'
                    }).setStyle(field.style).appendTo(contain);
                    break;
                case 'select':
                    this.inputs[itm] = new bkElement('select').addClass('paneSelect').setAttributes({
                        id: itm
                    }).appendTo(contain);
                    for (var opt in field.options) {
                        var o = new bkElement('option').setAttributes({
                            value: opt,
                            selected: (opt == val) ? 'selected' : ''
                        }).setContent(field.options[opt]).appendTo(this.inputs[itm]);
                    }
                    break;
                case 'content':
                    this.inputs[itm] = new bkElement('textarea').addClass('paneTextarea').setAttributes({
                        id: itm
                    }).setStyle(field.style).appendTo(contain);
                    this.inputs[itm].value = val;
                }
            }
        }
        var submitContain = new bkElement('DIV').addClass('paneRow');
        if (leftAligned) {
            submitContain.appendTo(this.form);
        } else {
            submitContain.addClass('paneRowSubmit').appendTo(this.form);
        }
        new bkElement('input').setAttributes({
            'type': 'submit'
        }).appendTo(submitContain).className = "btn";
        this.form.onsubmit = bkLib.cancelEvent;
    },

    submit: function () {},

    findElm: function (tag, attr, val) {
        var list = this.ne.selectedInstance.getElm().getElementsByTagName(tag);
        for (var i = 0; i < list.length; i++) {
            if (list[i].getAttribute(attr) == val) {
                return $BK(list[i]);
            }
        }
    },

    removePane: function () {
        if (this.pane) {
            this.pane.remove();
            this.pane = null;
            this.ne.selectedInstance.restoreRng();
        }
    }
});



/* START CONFIG */
var nicSelectOptions = {
    buttons: {
        'fontSize': {
            name: 'Select Font Size',
            type: 'nicEditorFontSizeSelect',
            command: 'fontsize'
        },
        'fontFamily': {
            name: 'Select Font Family',
            type: 'nicEditorFontFamilySelect',
            command: 'fontname'
        },
        'fontFormat': {
            name: 'Select Font Format',
            type: 'nicEditorFontFormatSelect',
            command: 'formatBlock'
        }
    }
};
/* END CONFIG */

var nicEditorSelect = bkClass.extend({
    construct: function (e, buttonName, options, nicEditor) {
        this.options = options.buttons[buttonName];
        this.elm = e;
        this.ne = nicEditor;
        this.name = buttonName;
        this.selOptions = [];

        this.contain = new bkElement('DIV').addClass('selectContain').addEvent('click', this.toggle.closure(this)).appendTo(this.elm);
        this.items = new bkElement('DIV').addClass('selectBox').appendTo(this.contain);
        this.control = new bkElement('DIV').addClass('selectControl').appendTo(this.items);
        this.txt = new bkElement('DIV').addClass('selectTxt').appendTo(this.items);

        if (!window.opera) {
            this.contain.onmousedown = this.control.onmousedown = this.txt.onmousedown = bkLib.cancelEvent;
        }

        this.elm.noSelect();

        this.ne.addEvent('selected', this.enable.closure(this)).addEvent('blur', this.disable.closure(this));

        this.disable();
        this.init();
    },

    disable: function () {
        if (!this.ne.isDisabled) {
            this.ne.isDisabled = true;
            this.elm.removeClass('buttonsEnabled');
        }

        this.isDisabled = true;
        this.close();
    },

    enable: function (t) {
        if (this.ne.isDisabled) {
            this.ne.isDisabled = false;
            this.elm.addClass('buttonsEnabled');
        }

        this.isDisabled = false;
        this.close();
    },

    setDisplay: function (txt) {
        this.txt.setContent(txt);
    },

    toggle: function () {
        if (!this.isDisabled) {
            (this.pane) ? this.close() : this.open();
        }
    },

    open: function () {
        this.pane = new nicEditorPane(this.items, this.ne, {
            width: '110px',
            padding: '0'
        });

        for (var i = 0; i < this.selOptions.length; i++) {
            var opt = this.selOptions[i],
                itmContain = new bkElement('DIV').addClass('selectList'),
                itm = new bkElement('DIV').addClass('selectListTxt').setContent(opt[1]).appendTo(itmContain).noSelect();
            itm.addEvent('click', this.update.closure(this, opt[0])).setAttributes('id', opt[0]);
            this.pane.append(itmContain);
            if (!window.opera) {
                itm.onmousedown = bkLib.cancelEvent;
            }
        }
    },

    close: function () {
        if (this.pane) {
            this.pane = this.pane.remove();
        }
    },

    add: function (k, v) {
        this.selOptions.push(new Array(k, v));
    },

    update: function (elm) {
        this.ne.nicCommand(this.options.command, elm);
        this.close();
    }
});

var nicEditorFontSizeSelect = nicEditorSelect.extend({
    sel: {
        1: '8pt',
        2: '10pt',
        3: '12pt',
        4: '14pt',
        5: '18pt',
        6: '24pt'
    },

    init: function () {
        this.setDisplay('Font&nbsp;Size');
        for (var itm in this.sel) {
            this.add(itm, '<font size="' + itm + '">' + this.sel[itm] + '</font>');
        }
    }
});

var nicEditorFontFamilySelect = nicEditorSelect.extend({
    sel: {
        'arial': 'Arial',
        'comic sans ms': 'Comic Sans',
        'courier new': 'Courier New',
        'georgia': 'Georgia',
        'helvetica': 'Helvetica',
        'impact': 'Impact',
        'times new roman': 'Times New Roman',
        'trebuchet ms': 'Trebuchet',
        'verdana': 'Verdana'
    },

    init: function () {
        this.setDisplay('Font&nbsp;Family');
        for (var itm in this.sel) {
            this.add(itm, '<font face="' + itm + '">' + this.sel[itm] + '</font>');
        }
    }
});

var nicEditorFontFormatSelect = nicEditorSelect.extend({
    sel: {
        'p': 'Paragraph',
        'pre': 'Pre',
        'h6': 'Heading&nbsp;6',
        'h5': 'Heading&nbsp;5',
        'h4': 'Heading&nbsp;4',
        'h3': 'Heading&nbsp;3',
        'h2': 'Heading&nbsp;2',
        'h1': 'Heading&nbsp;1'
    },

    init: function () {
        this.setDisplay('Font&nbsp;Format');
        for (var itm in this.sel) {
            var tag = itm.toUpperCase();
            this.add('<' + tag + '>', '<' + itm + '>' + this.sel[itm] + '</' + tag + '>');
        }
    }
});

nicEditors.registerPlugin(nicPlugin, nicSelectOptions);



/* START CONFIG */
var nicLinkOptions = {
    buttons: {
        'link': {
            name: 'Insert Link',
            type: 'nicLinkButton',
            tags: ['A']
        },
        'unlink': {
            name: 'Remove Link',
            command: 'unlink',
            noActive: true
        }
    }
};
/* END CONFIG */

var nicLinkButton = nicEditorAdvancedButton.extend({
    addPane: function () {
        this.ln = this.ne.selectedInstance.selElm().parentTag('A');
        this.addForm({
            '': {
                type: 'title',
                txt: 'Add/Edit Link'
            },
            'href': {
                type: 'text',
                txt: 'URL',
                value: 'http://'
            },
            'title': {
                type: 'text',
                txt: 'Title'
            },
            'target': {
                type: 'select',
                txt: 'Open In',
                options: {
                    '': 'Current Window',
                    '_blank': 'New Window'
                }
            }
        }, this.ln);
    },

    submit: function (e) {
        var url = this.inputs['href'].value;
        if (url === "http://" || url === "") {
            alert("You must enter a URL to Create a Link");
            return false;
        }
        this.removePane();

        if (!this.ln) {
            var tmp = 'javascript:nicTemp();';
            this.ne.nicCommand("createlink", tmp);
            this.ln = this.findElm('A', 'href', tmp);
            // set the link text to the title or the url if there is no text selected
            if (this.ln && this.ln.innerHTML == tmp) {
                this.ln.innerHTML = this.inputs['title'].value || url;
            }
        }
        if (this.ln) {
            var oldTitle = this.ln.title;
            this.ln.setAttributes({
                href: this.inputs['href'].value,
                title: this.inputs['title'].value,
                target: this.inputs['target'].options[this.inputs['target'].selectedIndex].value
            });
            // set the link text to the title or the url if the old text was the old title
            if (this.ln.innerHTML == oldTitle) {
                this.ln.innerHTML = this.inputs['title'].value || this.inputs['href'].value;
            }
        }
    }
});

nicEditors.registerPlugin(nicPlugin, nicLinkOptions);



/* START CONFIG */
var nicColorOptions = {
    buttons: {
        'forecolor': {
            name: 'Change Text Color',
            type: 'nicEditorColorButton',
            noClose: true
        },
        'bgcolor': {
            name: 'Change Background Color',
            type: 'nicEditorBgColorButton',
            noClose: true
        }
    }
};
/* END CONFIG */

var nicEditorColorButton = nicEditorAdvancedButton.extend({
    addPane: function () {
        var colorList = {
            0: '00',
            1: '33',
            2: '66',
            3: '99',
            4: 'CC',
            5: 'FF'
        };
        var colorItems = new bkElement('DIV').setStyle({
            width: '270px'
        });

        for (var r in colorList) {
            for (var b in colorList) {
                for (var g in colorList) {
                    var colorCode = '#' + colorList[r] + colorList[g] + colorList[b];

                    var colorSquare = new bkElement('DIV').setStyle({
                        'cursor': 'pointer',
                        'height': '15px',
                        'float': 'left'
                    }).appendTo(colorItems);
                    var colorBorder = new bkElement('DIV').setStyle({
                        border: '2px solid ' + colorCode
                    }).appendTo(colorSquare);
                    var colorInner = new bkElement('DIV').setStyle({
                        backgroundColor: colorCode,
                        overflow: 'hidden',
                        width: '11px',
                        height: '11px'
                    }).addEvent('click', this.colorSelect.closure(this, colorCode)).addEvent('mouseover', this.on.closure(this, colorBorder)).addEvent('mouseout', this.off.closure(this, colorBorder, colorCode)).appendTo(colorBorder);

                    if (!window.opera) {
                        colorSquare.onmousedown = colorInner.onmousedown = bkLib.cancelEvent;
                    }

                }
            }
        }
        this.pane.append(colorItems.noSelect());
    },

    colorSelect: function (c) {
        this.ne.nicCommand('foreColor', c);
        this.removePane();
    },

    on: function (colorBorder) {
        colorBorder.setStyle({
            border: '2px solid #000'
        });
    },

    off: function (colorBorder, colorCode) {
        colorBorder.setStyle({
            border: '2px solid ' + colorCode
        });
    }
});

var nicEditorBgColorButton = nicEditorColorButton.extend({
    colorSelect: function (c) {
        this.ne.nicCommand('hiliteColor', c);
        this.removePane();
    }
});

nicEditors.registerPlugin(nicPlugin, nicColorOptions);



/* START CONFIG */
var nicImageOptions = {
    buttons: {
        'image': {
            name: 'Insert Image',
            type: 'nicImageButton',
            tags: ['IMG']
        }
    }
};
/* END CONFIG */

var nicImageButton = nicEditorAdvancedButton.extend({
    addPane: function () {
        this.im = this.ne.selectedInstance.selElm().parentTag('IMG');
        this.addForm({
            '': {
                type: 'title',
                txt: 'Add/Edit Image'
            },
            'src': {
                type: 'text',
                txt: 'URL',
                'value': 'http://'
            },
            'alt': {
                type: 'text',
                txt: 'Alt Text'
            },
            'align': {
                type: 'select',
                txt: 'Align',
                options: {
                    none: 'Default',
                    'left': 'Left',
                    'right': 'Right'
                }
            }
        }, this.im);
    },

    submit: function (e) {
        var src = this.inputs['src'].value;
        if (src === "" || src === "http://") {
            alert("You must enter a Image URL to insert");
            return false;
        }
        this.removePane();

        if (!this.im) {
            var tmp = 'javascript:nicImTemp();';
            this.ne.nicCommand("insertImage", tmp);
            this.im = this.findElm('IMG', 'src', tmp);
        }
        if (this.im) {
            this.im.setAttributes({
                src: this.inputs['src'].value,
                alt: this.inputs['alt'].value,
                align: this.inputs['align'].value
            });
        }
    }
});

nicEditors.registerPlugin(nicPlugin, nicImageOptions);



var nicXHTML = bkClass.extend({
    stripAttributes: ['_moz_dirty', '_moz_resizing', '_extended'],
    noShort: ['style', 'title', 'script', 'textarea', 'a'],
    cssReplace: {
        'font-weight:bold;': 'strong',
        'font-style:italic;': 'em',
        'text-decoration:underline;': 'u',
        'margin-left:': 'blockquote'
    },
    sizes: {
        1: 'xx-small',
        2: 'x-small',
        3: 'small',
        4: 'medium',
        5: 'large',
        6: 'x-large'
    },

    construct: function (nicEditor) {
        this.ne = nicEditor;
        if (this.ne.options.xhtml) {
            nicEditor.addEvent('get', this.cleanup.closure(this));
        }
    },

    cleanup: function (ni) {
        var node = ni.getElm(),
            xhtml = this.toXHTML(node);
        ni.content = xhtml;
    },

    toXHTML: function (n, r, d) {
        var txt = '',
            attrTxt = '',
            cssTxt = '',
            nType = n.nodeType,
            nName = n.nodeName.toLowerCase(),
            nChild = n.hasChildNodes && n.hasChildNodes(),
            extraNodes = [];

        switch (nType) {
        case 1:
            var nAttributes = n.attributes;

            switch (nName) {
            case 'b':
                nName = 'strong';
                break;
            case 'i':
                nName = 'em';
                break;
            case 'font':
                nName = 'span';
                break;
            }

            if (r) {
                for (var i = 0; i < nAttributes.length; i++) {
                    var attr = nAttributes[i];

                    var attributeName = attr.nodeName.toLowerCase();
                    var attributeValue = attr.nodeValue;

                    if (!attr.specified || !attributeValue || bkLib.inArray(this.stripAttributes, attributeName) || typeof (attributeValue) == "function") {
                        continue;
                    }

                    switch (attributeName) {
                    case 'style':
                        var css = attributeValue.replace(/ /g, "");
                        for (var itm in this.cssReplace) {
                            if (css.indexOf(itm) != -1) {
                                extraNodes.push(this.cssReplace[itm]);
                                css = css.replace(itm, "");
                            }
                        }
                        cssTxt += css;
                        attributeValue = "";
                        break;
                    case 'class':
                        attributeValue = attributeValue.replace("Apple-style-span", "");
                        break;
                    case 'size':
                        cssTxt += "font-size:" + this.sizes[attributeValue] + ';';
                        attributeValue = "";
                        break;
                    }

                    if (attributeValue) {
                        attrTxt += ' ' + attributeName + '="' + attributeValue + '"';
                    }
                }

                if (cssTxt) {
                    attrTxt += ' style="' + cssTxt + '"';
                }

                for (var i = 0; i < extraNodes.length; i++) {
                    txt += '<' + extraNodes[i] + '>';
                }

                if (attrTxt == "" && nName == "span") {
                    r = false;
                }
                if (r) {
                    txt += '<' + nName;
                    if (nName != 'br') {
                        txt += attrTxt;
                    }
                }
            }

            if (!nChild && !bkLib.inArray(this.noShort, attributeName)) {
                if (r) {
                    txt += ' />';
                }
            } else {
                if (r) {
                    txt += '>';
                }

                for (var i = 0; i < n.childNodes.length; i++) {
                    var results = this.toXHTML(n.childNodes[i], true, true);
                    if (results) {
                        txt += results;
                    }
                }
            }

            if (r && nChild) {
                txt += '</' + nName + '>';
            }

            for (var i = 0; i < extraNodes.length; i++) {
                txt += '</' + extraNodes[i] + '>';
            }

            break;
        case 3:
            txt += String(n.nodeValue).replace(/&/g, '&amp;').replace(/"/g, '&quot;').replace(/'/g, '&#39;').replace(/</g, '&lt;').replace(/>/g, '&gt;');
            break;
        }

        return txt;
    }
});

nicEditors.registerPlugin(nicXHTML);



/* START CONFIG */
var nicCodeOptions = {
    buttons: {
        'xhtml': {
            name: 'Edit HTML',
            type: 'nicCodeButton'
        }
    }
};
/* END CONFIG */

var nicCodeButton = nicEditorAdvancedButton.extend({
    addPane: function () {
        this.addForm({
            '': {
                txt: 'Edit HTML',
                type: 'title-left'
            },
            'code': {
                type: 'content',
                'value': this.ne.selectedInstance.getContent()
            }
        });
    },

    submit: function (e) {
        this.ne.selectedInstance.setContent(this.inputs['code'].value);
        this.removePane();
    }
});

nicEditors.registerPlugin(nicPlugin, nicCodeOptions);
