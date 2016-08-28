"use strict";

if ( !window.COUCH ) var COUCH = {};

/* Data */

COUCH.data = {};

/* Elements */

COUCH.el = {};

/* Methods */

/**
 * Add leave listener
 */
COUCH.addLeaveListener = function() {
    $( function(){
        var form = COUCH.el.$content.find( "#k_admin_frm" );
        if( form.length ){
            COUCH.updateRichTextContent();
            COUCH.data.formOriginal = form.serialize();

            form.on( "submit", function() {
                window.onbeforeunload = null;
            });

            window.onbeforeunload = function() {
                COUCH.updateRichTextContent();
                var cur_data = form.serialize();
                if ( COUCH.data.formOriginal !== cur_data ) return COUCH.lang.leave;
            };
        }
    });
};

/**
 * Add media query listeners
 */
COUCH.addMediaQueryListeners = function() {
    this.mediaQuery = {
        currentView:  "?"
    };

    if ( window.matchMedia ) {
        this.mediaQuery.medium = window.matchMedia( "(max-width: 950px)" );
        this.mediaQuery.small  = window.matchMedia( "(max-width: 761px)" );

        this.configViewInitial( this.mediaQuery.medium, this.mediaQuery.small );

        this.mediaQuery.medium.addListener( this.configViewMedium );
        this.mediaQuery.small.addListener( this.configViewSmall );
    } else {
        this.configViewInitial( { matches: false }, { matches: false } );
    }
};

/**
 * Bind navigation menu toggle action
 */
COUCH.bindNavMenuToggle = function() {
    this.el.$menuBtn.on( "click", function( e ) {
        e.preventDefault();

        COUCH.toggleNavMenu();
    });
};

/**
 * Bind Magnific Popup AJAX
 * @param {jQuery Object} $elements
 * @param {Boolean}       [code]
 */
COUCH.bindPopupAJAX = function( $elements, code ) {
    $elements.magnificPopup({
        callbacks: {
            parseAjax: code ? function( response ) {
                response.data = '<div class="popup-blank popup-code"><div class="popup-code-content">' + response.data.replace( /</g, "&lt;" ).replace( />/g, "&gt;" ).replace( /(?:\r\n|\r|\n)/g, "<br/>" ) + '</div></div>';
            } : false
        },
        closeOnBgClick: false,
        preloader:      false,
        type:           "ajax"
    });
};

/**
 * Bind Magnific Popup gallery
 */
COUCH.bindPopupGallery = function() {
    this.el.$content.find( "#gallery-listing" ).magnificPopup({
        delegate: ".popup-gallery",
        gallery: {
            enabled: true
        },
        type: "image"
    });
};

/**
 * Bind Magnific Popup iframe
 * @param {jQuery Object} $elements
 * @param {Function}      [callbackOpen]
 * @param {Function}      [callbackClosed]
 * @param {String}        [mainClass]
 */
COUCH.bindPopupIframe = function( $elements, callbackOpen, callbackClosed, mainClass ) {
    $elements.magnificPopup({
        callbacks: {
            afterClose: callbackClosed,
            beforeOpen: callbackOpen
        },
        mainClass: mainClass ? mainClass : "",
        closeOnBgClick: false,
        preloader:      false,
        type:           "iframe"
    });
};

/**
 * Bind Magnific Popup image preview
 * @param {jQuery Object} $elements
 */
COUCH.bindPopupImage = function( $elements ) {
    $elements.magnificPopup({
        type: "image"
    });
};

/**
 * Bind Magnific Popup inline
 * @param {jQuery Object} $elements
 */
COUCH.bindPopupInline = function( $elements ) {
    $elements.magnificPopup({
        preloader: false,
        type:      "inline"
    });
};


/**
 * Browse choose file action
 * @param {jQuery Object} $button
 * @param {String}        file
 */
COUCH.browseChooseFile = function( $button, file ) {
    var id = $button.attr( "data-kc-finder" );

    $( "#" + id ).val( file ).trigger( "change" );
    $( "#" + id + "_preview" ).attr( "href", file );
    $( "#" + id + "_img_preview" ).attr( "src", file );

    $.magnificPopup.close();
};

/**
 * Close callback for KCFinder file manager modal
 */
COUCH.browseKCFinderClose = function() {
    window.KCFinder = null;
};

/**
 * Open callback for KCFinder file manager modal
 */
COUCH.browseKCFinderOpen = function() {
    var $this = $( this.st.el );  // this is $.magnificPopup.instance
    window.KCFinder = {
        callBack: function( file ) {
            COUCH.browseChooseFile( $this, file );
        }
    };
};

/**
 * Plupload bulk file upload finish
 * @param {jQuery Object} $button
 * @param {String}        result
 */
COUCH.bulkPluploadFinish = function( $button, result ) {
    var msg = $.trim( result );

    if ( msg.length ) {
        $.magnificPopup.dialog({
            icon:     "x",
            iconType: "error",
            text:     msg,
            closedCallback: function() {
                $button.focus();
            }
        });
    } else {
        window.location.reload();
    }
};

/**
 * Bind relation click select action
 */
COUCH.bindRelationSelect = function() {
    this.el.$content.find( ".checklist" ).not( ".checklist-disabled" ).on( "change", "input", function() {
        $( this ).parent().toggleClass( "selected" );
    });
};

/**
 * Bind sidebar click toggle actions
 */
COUCH.bindSidebarToggles = function() {
    $( "#sidebar-toggle" ).on( "click", function( e ) {
        e.preventDefault();

        $( this ).blur();

        COUCH.toggleSidebar();
    });

    this.el.$menuContent.on( "click", ".nav-heading-toggle", function() {
        var $this = $( this ),
            target = $this.data( "id" ),
            position = COUCH.state.collapsedGroups.indexOf( target );

        $this.parent().toggleClass( "collapsed" ).next().slideToggle( 150 );

        if ( position === -1 ) {
            COUCH.state.collapsedGroups.push( target );
        } else {
            COUCH.state.collapsedGroups.splice( position, 1 );

            if ( !COUCH.state.collapsedGroups.length ) {
                $.removeCookie( "collapsed_groups" );

                return;
            }
        }

        $.setCookie( "collapsed_groups", COUCH.state.collapsedGroups.join( "," ), 86400, null, null, document.location.protocol === "https:" );
    });
};

/**
 * Bind table toggle all checkbox and row click select action
 */
COUCH.bindTableSelect = function() {
    this.el.$table.on( "change", ".checkbox-item, .checkbox-all", function() {
        var $checkboxes = COUCH.el.$table.find( ".checkbox-item" ).not( ":disabled" ),
            $this = $( this );

        if ( $this.hasClass( "checkbox-item" ) ) {
            var $checkboxAll = COUCH.el.$table.find( ".checkbox-all" );

            if ( $checkboxes.length === $checkboxes.filter( ":checked" ).length ) {
                $checkboxAll.prop( "checked", true );
            } else {
                $checkboxAll.prop( "checked", false );
            }

            if ( $this.prop( "checked" ) ) {
                $this.closest( "tr" ).addClass( "selected" );
            } else {
                $this.closest( "tr" ).removeClass( "selected" );
            }
        } else {
            $checkboxes.prop( "checked", $this.prop( "checked" ) ).trigger( "change" );
        }
    }).on( "click", "td", function( e ) {
        if ( e.target === this || /INPUT|LI|SPAN|STRONG/.test( e.target.nodeName ) ) {
            $( this ).closest( "tr" ).find( ".checkbox-item" ).not( ":disabled" ).prop( "checked", function( i, val ) {
                return !val;
            }).trigger( "change" );
        }
    });
};

/**
 * Bind comment item click select action
 */
COUCH.bindCommentsSelect = function() {
    this.el.$content.find( "#comments-listing" ).on( "click", ".comment-heading", function( e ) {
        if ( e.target === this ) {
            var $checkboxAll = COUCH.el.$content.find( ".checkbox-all" ),
                $checkboxes = COUCH.el.$content.find( ".checkbox-item" ).not( ":disabled" ),
                $this = $( this );

            $this.find( ".checkbox-item" ).not( ":disabled" ).prop( "checked", function( i, val ) {
                return !val;
            }).trigger( "change" );

            if ( $checkboxes.length === $checkboxes.filter( ":checked" ).length ) {
                $checkboxAll.prop( "checked", true );
            } else {
                $checkboxAll.prop( "checked", false );
            }
        }
    });

    this.el.$content.find( "#comments-listing" ).on( "change", ".checkbox-all", function( e ) {
        var $this = $( this ),
            $checkboxes = COUCH.el.$content.find( ".checkbox-item" ).not( ":disabled" ),
            checked = $this.prop( "checked" );

        $checkboxes.prop( "checked", checked );
    });
};

/**
 * Bind gallery item click select action
 */
COUCH.bindGallerySelect = function() {
    this.el.$content.find( "#gallery-listing" ).on( "click", ".gallery-item:not(.gallery-folder)", function( e ) {
        var $checkboxAll = COUCH.el.$content.find( ".checkbox-all" ),
            $checkboxes = COUCH.el.$content.find( ".checkbox-item" ).not( ":disabled" ),
            $this = $( this );

        if ( e.target === this || /DIV|STRONG/.test( e.target.nodeName ) ) {
            $this.find( ".checkbox-item" ).not( ":disabled" ).prop( "checked", function( i, val ) {
                $this.toggleClass( "selected" );

                return !val;
            }).trigger( "change" );
        } else if ( e.target.nodeName === "INPUT" && !$( e.target ).is( ":disabled" ) ) {
            $this.toggleClass( "selected" );
        }

        if ( $checkboxes.length === $checkboxes.filter( ":checked" ).length ) {
            $checkboxAll.prop( "checked", true );
        } else {
            $checkboxAll.prop( "checked", false );
        }
    });

    this.el.$content.find( "#gallery-listing" ).on( "change", ".checkbox-all", function( e ) {
        var $this = $( this ),
            $checkboxes = COUCH.el.$content.find( ".checkbox-item" ).not( ":disabled" ),
            checked = $this.prop( "checked" );

        $checkboxes.prop( "checked", checked ).trigger( "change" ).closest( ".gallery-item" ).toggleClass( "selected", checked );
    });
};

/**
 * Bind scroll to top click scroll action
 */
COUCH.bindTopScroll = function() {
    $( "#top" ).on( "click", function( e ) {
        e.preventDefault();

        $( this ).blur();

        $( "html, body, #scroll-content" ).animate({
            scrollTop: 0
        }, 400 );
    });
};

/**
 * Configure initial view
 * @param {MediaQueryList Object} medium
 * @param {MediaQueryList Object} small
 */
COUCH.configViewInitial = function( medium, small ) {
    if ( small.matches ) {
        this.mediaQuery.currentView = "S";

        this.createActionPopovers();
    } else if ( medium.matches ) {
        this.mediaQuery.currentView = "M";

        if ( !this.data.overflowScrolling ) this.createSidebarScrollbar();

        this.createTooltips( $( "body" ), ".tt" );

        this.createTooltips( this.el.$navCount );

        this.createTooltips( this.el.$collapseTooltips );
    } else {
        this.mediaQuery.currentView = "L";

        if ( !this.data.overflowScrolling ) this.createSidebarScrollbar();

        this.createTooltips( $( "body" ), ".tt" );

        this.createTooltips( this.el.$navCount );

        this.createTooltips( this.el.$tabErrors );
    }
};

/**
 * Configure medium view
 * @param {MediaQueryList Object} mediaQuery
 */
COUCH.configViewMedium = function( mediaQuery ) {
    if ( mediaQuery.matches ) {
        COUCH.mediaQuery.currentView = "M";

        COUCH.destroyTooltips( COUCH.el.$tabErrors );

        COUCH.createTooltips( COUCH.el.$collapseTooltips );
    } else {
        COUCH.mediaQuery.currentView = "L";

        COUCH.createTooltips( COUCH.el.$tabErrors );

        COUCH.destroyTooltips( COUCH.el.$collapseTooltips );
    }
};

/**
 * Configure small view
 * @param {MediaQueryList Object} mediaQuery
 */
COUCH.configViewSmall = function( mediaQuery ) {
    if ( mediaQuery.matches ) {
        COUCH.mediaQuery.currentView = "S";

        if ( !COUCH.data.overflowScrolling ) COUCH.destroySidebarScrollbar();

        COUCH.createActionPopovers();

        COUCH.destroyTooltips( $( "body" ) );

        COUCH.destroyTooltips( COUCH.el.$navCount );

        COUCH.destroyTooltips( COUCH.el.$collapseTooltips );
    } else {
        COUCH.mediaQuery.currentView = "M";

        if ( !COUCH.data.overflowScrolling ) COUCH.createSidebarScrollbar();

        COUCH.destroyActionPopovers();

        COUCH.createTooltips( $( "body" ), ".tt" );

        COUCH.createTooltips( COUCH.el.$navCount );

        COUCH.createTooltips( COUCH.el.$collapseTooltips );
    }
};

/**
 * Create list action popovers
 */
COUCH.createActionPopovers = function() {
    this.el.$content.popover({
        container: "body",
        html:      true,
        placement: "top",
        selector:  ".btn-actions",
        trigger:   "focus",
        content:   function() {
            var $this = $( this ),
                $content = $this.siblings( "a" ),
                $actions = $content.filter( ".approve-comment, .disapprove-comment, .up, .down" ).add( $this.parent().siblings( ".col-up-down" ).children( ".up, .down" ) );

            if ( $actions.length ) {
                return $( '<div class="popover-actions"></div>' ).append( $actions.clone() ).append( '<span class="popover-actions-sep"></span>' ).append( $content.not( $actions ).clone() );
            } else {
                return $( '<div class="popover-actions"></div>' ).append( $content.clone() );
            }
        }
    });
};

/**
 * Create edit help popovers
 */
COUCH.createHelpPopovers = function() {
    this.el.$content.parent().popover({
        container: "body",
        placement: "top",
        selector:  ".field-help",
        trigger:   "hover focus"
    });
};

/**
 * Create relation scrollbars
 */
COUCH.createRelationScrollbars = function() {
    var relation_fields = this.el.$content.find( ".scroll-relation" );

    if( relation_fields.length ){
        relation_fields.mCustomScrollbar({
            advanced: {
                autoScrollOnFocus:      "input",
                updateOnImageLoad:      false,
                updateOnSelectorChange: false
            },
            keyboard: {
                enable: false
            },
            mouseWheel: {
                scrollAmount: 64
            },
            scrollInertia: 300,
            snapAmount:    32,
            theme:         "dark-thick"
        });

        COUCH.bindRelationSelect();
    }
};

/**
 * Create sidebar scrollbar
 */
COUCH.createSidebarScrollbar = function() {
    var scrollbar = $( "#scroll-sidebar" );
    if( !scrollbar.length ){ return; }

    scrollbar.mCustomScrollbar({
        advanced: {
            autoScrollOnFocus:      "",
            updateOnImageLoad:      false,
            updateOnSelectorChange: false
        },
        autoHideScrollbar: true,
        keyboard: {
            enable: false
        },
        mouseWheel: {
            scrollAmount: 64
        },
        scrollInertia: 350,
        theme:         "light-thick"
    });
};

/**
 * Create tooltips
 * @param {jQuery Object} $element
 * @param {String}        [selector]
 */
COUCH.createTooltips = function( $element, selector ) {
    $element.doOnce(function() {
        $( this ).tooltip({
            animation: false,
            container: "body",
            selector: selector ? selector : false
        });
    });
};

/**
 * Destroy list action popovers
 */
COUCH.destroyActionPopovers = function() {
    this.el.$content.popover( "destroy" );
};

/**
 * Destroy sidebar scrollbar
 */
COUCH.destroySidebarScrollbar = function() {
    $( "#scroll-sidebar" ).mCustomScrollbar( "destroy" );
};

/**
 * Destroy tooltips
 * @param {jQuery Object} $element
 */
COUCH.destroyTooltips = function( $element ) {
    $element.doOnce(function() {
        $( this ).tooltip( "destroy" );
    });
};

/*!
 * Check for overflow-scrolling CSS property support
 * @author Hay Kranen <https://github.com/hay/>
 * @return {Boolean}
 */
COUCH.hasOverflowScrolling = function() {
    if ( !window.getComputedStyle ) return false;

    var computedStyle, i,
        div      = document.createElement( "div" ),
        hasIt    = false,
        prefixes = [ "moz", "ms", "o", "webkit" ];

    document.body.appendChild( div );

    for ( i = 0; i < prefixes.length; i++ ) div.style[ prefixes[ i ] + "OverflowScrolling" ] = "touch";

    div.style.overflowScrolling = "touch";

    computedStyle = window.getComputedStyle( div );

    hasIt = !!computedStyle.overflowScrolling;

    for ( i = 0; i < prefixes.length; i++ ) {
        if ( !!computedStyle[ prefixes[ i ] + "OverflowScrolling" ] ) {
            hasIt = true;
            break;
        }
    }

    div.parentNode.removeChild( div );

    return hasIt;
};

/**
 * Set Magnific Popup default settings
 */
COUCH.setMagnificPopupDefaults = function() {
    if ( !$.magnificPopup ) return;

    $.extend( true, $.magnificPopup.defaults, {
        ajax: {
            tError: COUCH.lang.popup.ajaxError
        },
        gallery: {
            tCounter: COUCH.lang.popup.counter,
            tNext:    COUCH.lang.popup.next,
            tPrev:    COUCH.lang.popup.previous
        },
        image: {
            titleSrc: "data-popup-title",
            tError:   COUCH.lang.popup.imgError
        },
        mainClass:    "mfp-fade",
        removalDelay: 210,
        tClose:       COUCH.lang.popup.close,
        tLoading:     COUCH.lang.popup.loading
    });
};

/**
 * Slide up, fade out, and hide the element, then optionally call the callback function
 * @param {jQuery Object} $element
 * @param {Number|String} speed
 * @param {Function}      [callback]
 */
COUCH.slideFadeHide = function( $element, speed, callback ) {
    $element.removeClass( "in" );

    setTimeout(function() {
        $element.slideUp( speed, function() {
            if ( $.isFunction( callback ) ) callback.call( this );
        });
    }, speed );
};

/**
 * Slide down, fade in, and show the element, then optionally call the callback function
 * @param {jQuery Object} $element
 * @param {Number|String} speed
 * @param {Function}      [callback]
 */
COUCH.slideFadeShow = function( $element, speed, callback ) {
    $element.slideDown( speed, function() {
        $( this ).addClass( "in" );

        if ( $.isFunction( callback ) ) {
            setTimeout(function() {
                callback.call( this );
            }, speed );
        }
    });
};

/**
 * Toggle sidebar navigation menu
 */
COUCH.toggleNavMenu = $.debounce(function() {
    this.el.$menuBtn.toggleClass( "toggled" );

    this.el.$menuContent.animate({
        height: "toggle"
    }, 400, function() {
        var $this = $( this );

        if ( !$this.is( ":visible" ) ) $this.removeAttr( "style" );
    });
}, 200, true );

/**
 * Toggle sidebar
 */
COUCH.toggleSidebar = $.debounce(function() {
    if ( this.el.$sidebar.hasClass( "collapsed" ) ) {
        this.el.$sidebar.removeClass( "collapsed" );

        $.removeCookie( "collapsed_sidebar" );
    } else {
        this.el.$sidebar.addClass( "collapsed" );

        $.setCookie( "collapsed_sidebar", "1", 86400, null, null, document.location.protocol === "https:" );
    }
}, 200, true );

/**
 * Update rich text form content
 */
COUCH.updateRichTextContent = function() {
    if ( window.CKEDITOR ) {
        var key, obj;

        for ( key in CKEDITOR.instances ) {
            obj = CKEDITOR.instances[ key ];

            if ( CKEDITOR.instances.hasOwnProperty( key ) ) obj.updateElement();
        }
    }

    if ( window.nicEditors ) {
        var i = nicEditors.editors.length - 1;

        do {
            try {
                nicEditors.editors[ i ].nicInstances[ 0 ].saveContent();
            } catch ( e ) {}

            i--;
        } while ( i > -1 );
    }
};


/**
 * Initialize Couch application
 */
COUCH.init = function() {
    $(function() {
        COUCH.data.overflowScrolling = COUCH.hasOverflowScrolling();

        COUCH.el.$collapseTooltips = $( ".tt-collapse" );
        COUCH.el.$content          = $( "#content" );
        COUCH.el.$menuContent      = $( "#menu-content" );
        COUCH.el.$sidebar          = $( "#sidebar" );
        COUCH.el.$tabs             = $( "#tabs" );
        COUCH.el.$table            = COUCH.el.$content.find( ".table-list" );
        COUCH.el.$menuBtn          = COUCH.el.$sidebar.find( ".btn-primary.btn-menu" );
        COUCH.el.$navCount         = COUCH.el.$sidebar.find( ".nav-count" );
        COUCH.el.$tabErrors        = COUCH.el.$tabs.find( ".tab-error" );

        COUCH.state.collapsedGroups = $.hasCookie( "collapsed_groups" ) ? $.getCookie( "collapsed_groups" ).split( "," ) : [];

        COUCH.setMagnificPopupDefaults();
        COUCH.addMediaQueryListeners();
        COUCH.bindSidebarToggles();
        COUCH.bindNavMenuToggle();
        COUCH.createHelpPopovers();
        COUCH.bindTopScroll();
        COUCH.bindTableSelect();
        COUCH.addLeaveListener();
        COUCH.bindPopupAJAX( COUCH.el.$sidebar.find( ".popup-ajax" ), true );
        if ( !COUCH.data.overflowScrolling ) COUCH.createRelationScrollbars();
    });
};

COUCH.lang = {
    leave:             "Any unsaved changes will be lost.",

    popup: {
        ajaxError: "<a href='%url%' target='_blank'>The content</a> could not be loaded.",
        close:     "Close (Esc)",
        counter:   "%curr% of %total%",
        imgError:  "<a href='%url%' target='_blank'>The image</a> could not be loaded.",
        loading:   "Loading\u2026",
        next:      "Next",
        previous:  "Previous"
    }
};

COUCH.state = {};

COUCH.init();
