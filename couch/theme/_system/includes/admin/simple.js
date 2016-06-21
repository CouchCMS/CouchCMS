"use strict";

if ( !window.COUCH ) var COUCH = {};

/* Methods */

/**
 * Create tooltips
 */
COUCH.createTooltips = function() {
    $( "body" ).tooltip({
        animation: false,
        container: "body",
        selector:  ".tt"
    });
};

/**
 * Initialize Couch application
 */
COUCH.init = function() {
    $(function() {
        COUCH.createTooltips();
    });
};

COUCH.init();
