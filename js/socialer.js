/**
 *  Main library
 *  @author Meshin Dmitry <0x7ffec at gmail.com>
 */
'use strict';

var alljs = alljs || [];

//-----------------------------------------------------------------------------

/**
 * 	Options Object
 */
if (!alljs.options) {
    alljs.options = {
        DEBUG_MODE: false,
        socialer_callback_base: 'http://socialer.local/callback/'
    };
};
//-----------------------------------------------------------------------------

/**
 * 	SYSTEM library
 */
// first initialization of system object
alljs.system = alljs.system || {};

// check if we in debug mode
alljs.system.is_debug_mode = function () {
    return (alljs.options.DEBUG_MODE && alljs.options.DEBUG_MODE === true);
};

alljs.system.log_array = alljs.system.log_array || {};

// logging some message
alljs.system.log = function (message) {
    alljs.system.is_debug_mode() && window.console && console.log(message);
};

// logging warning
alljs.system.warn = function (message) {
    alljs.system.is_debug_mode() && window.console && console.warn(message);
};

// logging error
alljs.system.error = function (message) {
    alljs.system.is_debug_mode() && window.console && console.error(message);
};

// checking that it is string and not empty
alljs.system.not_empty_string = function (str) {
    if (str && typeof str === "string") {
        return true;
    }

    alljs.system.warn('Empty string for not_empty_string function');

    return false;
};

/**
 * 	Dispatching one element
 */
alljs.system.dispatch_element = function (element) {
    var dispatch_object	= jQuery(element);
    var func_name = dispatch_object.data('function');

    dispatch_object.removeClass('alljs-dispatcher').addClass('alljs-dispatcher-executed');

    alljs.system.log("Executing: " + func_name);

    // Allow fn to be a function object or the name of a global function
    var fn = top.window;
    var parts = func_name.split('.');

    for ( var i = 0; i < parts.length; i++ ) {
        if (fn[parts[i]]) {
            fn = fn[parts[i]];
        }
    }

    if ( fn ) {
        return fn.apply(alljs, arguments || []);
    }

    alljs.system.error('Function does not exists!');

    return false;
};

// dispatching all
alljs.system.dispatch = function ( container ) {

    var elements = null;

    if ( typeof (container) === 'undefined' ) {
        elements = jQuery("div.alljs-dispatcher");
    } else {
        elements = jQuery("div.alljs-dispatcher", jQuery("#" + container));
    }

    jQuery.each(elements, function(index, element){
        alljs.system.dispatch_element(element);
    });
};

alljs.system.showElement = function(obj, mode) {
    var elem = typeof obj === "string" ? jQuery(obj) : obj;

    if (elem) {
        if (!mode) {
            if (!elem.is(':visible')) {
                elem.show();
            } else {
                elem.hide();
            }
        } else {
            if (mode == 1) {
                elem.show();
            } else {
                elem.hide();
            }
        }
    }
};

//-----------------------------------------------------------------------------
// decoding array values from string
Array.prototype.fromString = function ( str ) {
    var values = str.split(","),
        value = null;

    while (value = values.shift()) {
        this.push(value);
    }
};

//-----------------------------------------------------------------------------

alljs.socialer = alljs.socialer || {};

alljs.socialer.get_correct_box = function() {
    var base_request_url = jQuery('#alljs-dispatcher-socialer').data('base-url');
    jQuery.ajax({
        url: base_request_url + 'is_user_registered',
        dataType: 'json',
        success: function(response) {
            if ( !response.success ) {
                alljs.socialer.get_register_button();
            } else {
                alljs.socialer.get_tweet_box();
            }
        }
    });
};

alljs.socialer.get_register_button = function() {
    var base_request_url = jQuery('#alljs-dispatcher-socialer').data('base-url');
    jQuery.ajax({
        url: base_request_url + 'get_register_button',
        dataType: 'html'
    })
    .done(function(response) {
        jQuery('#socialer-container').html(response);
    });
};

alljs.socialer.get_tweet_box = function() {
    var base_request_url = jQuery('#alljs-dispatcher-socialer').data('base-url');
    var post_id = jQuery('#alljs-dispatcher-socialer').data('post-id');
    jQuery.ajax({
        url: base_request_url + 'get_tweet_box' + (post_id ? ('&post=' + post_id) : ''),
        dataType: 'html'
    })
    .done(function(response) {
        jQuery('#socialer-container').html(response);
    });
};

//-----------------------------------------------------------------------------

// Dispatching all system
jQuery(document).ready(function () {
    alljs.system.dispatch();
});