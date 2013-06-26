/**
 *  Main library
 *  @author Meshin Dmitry <0x7ffec at gmail.com>
 */
'use strict';

var alljs = alljs || {};

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

//-----------------------------------------------------------------------------
// decoding array values from string
Array.prototype.fromString = function ( str ) {
    var values = str.split(","),
        value = null;

    while (value = values.shift()) {
        this.push(value);
    }
};

Date.prototype.getMonthName = function(lang) {
    lang = lang && (lang in Date.locale) ? lang : 'en';
    return Date.locale[lang].month_names[this.getMonth()];
};

Date.prototype.getMonthNameShort = function(lang) {
    lang = lang && (lang in Date.locale) ? lang : 'en';
    return Date.locale[lang].month_names_short[this.getMonth()];
};

Date.locale = {
    en: {
        month_names: ['January', 'February', 'March', 'April', 'May', 'June', 'July', 'August', 'September', 'October', 'November', 'December'],
        month_names_short: ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun', 'Jul', 'Aug', 'Sep', 'Oct', 'Nov', 'Dec']
    }
};

alljs.cookie = alljs.cookie || {};
alljs.cookie.pluses = /\+/g;
alljs.cookie.defaults = {};

alljs.cookie.raw = function(s) {
    return s;
};

alljs.cookie.decoded = function(s) {
    return decodeURIComponent(s.replace(alljs.cookie.pluses, ' '));
};

/**
 * Setting cookie
 * @param key
 * @param value
 * @param options
 * @return {*}
 */
alljs.cookie.cookie = function(key, value, options) {
    // key and at least value given, set cookie...
    if (arguments.length > 1 && (!/Object/.test(Object.prototype.toString.call(value)) || value == null)) {
        options = jQuery.extend({}, alljs.cookie.defaults, options);

        if (value == null) {
            options.expires = -1;
        }

        if (typeof options.expires === 'number') {
            var days = options.expires, t = options.expires = new Date();
            t.setDate(t.getDate() + days);
        }

        value = String(value);

        return (document.cookie = [
            encodeURIComponent(key), '=', options.raw ? value : encodeURIComponent(value),
            options.expires ? '; expires=' + options.expires.toUTCString() : '', // use expires attribute, max-age is not supported by IE
            options.path    ? '; path=' + options.path : '',
            options.domain  ? '; domain=' + options.domain : '',
            options.secure  ? '; secure' : ''
        ].join(''));
    }

    // key and possibly options given, get cookie...
    options = value || alljs.cookie.defaults || {};
    var decode = options.raw ? alljs.cookie.raw : alljs.cookie.decoded;
    var cookies = document.cookie.split('; ');
    for (var i = 0, parts; (parts = cookies[i] && cookies[i].split('=')); i++) {
        if (decode(parts.shift()) === key) {
            return decode(parts.join('='));
        }
    }
    return null;
};

//-----------------------------------------------------------------------------

alljs.socialer = alljs.socialer || {};

alljs.socialer.get_scheduled_tweet = function(callback) {

    // if tweet scheduled - then we do not need to get schedule information
    if ( alljs.options.scheduled ) {
        if ( typeof callback === 'function') {
            callback();
        }
        return;
    }

    var post = alljs.socialer.post();

    jQuery.ajax({
        url: post.base_request_url + 'get_scheduled_tweet',
        dataType: 'json',
        type: 'post',
        data: {
            post_id: post.id
        },
        success: function(response) {
            alljs.socialer.show_scheduled_tweet_data(response);
            if ( typeof callback === 'function') {
                callback();
            }
        }
    });
};

alljs.socialer.get_draft_tweet = function(callback) {
    var post = alljs.socialer.post();

    jQuery.ajax({
        url: post.base_request_url + 'get_draft_tweet',
        dataType: 'json',
        type: 'post',
        data: {
            post: post.id
        },
        success: function(response) {
            if ( response && response.success ) {
                jQuery('#socialer-tweet-body').val(response.entry.tweet);
            }
            if ( typeof callback === 'function') {
                callback();
            }
        }
    });
};

alljs.socialer.show_scheduled_tweet_data = function(response) {
    if ( response.success && response.result && response.result.id ) {

        if ( jQuery('#alljs-dispatcher-socialer').attr('data-scheduled-tweet') ) {
            // let it be from session! there is correct for scheduling value
        } else {
            //console.log(jQuery('#socialer-tweet-body').val);
            //console.log(response.result.tweet);
            jQuery('#socialer-tweet-body').val(response.result.tweet);
        }

        var post = alljs.socialer.post();
        var date_time_parts = post.date.split(' ');

        var date_parts = date_time_parts[0].split('-');
        var time_parts = date_time_parts[1].split(':');
        var year        = date_parts[0],
            month       = date_parts[1],
            day         = date_parts[2];

        var hours       = time_parts[0],
            minutes     = time_parts[1],
            seconds     = time_parts[2];

        var s_hours     = response.result.hours;
        var schedule_date = new Date();

        schedule_date.setYear(year);
        schedule_date.setMonth(month);
        schedule_date.setDate(day);
        schedule_date.setHours(hours);
        schedule_date.setMinutes(minutes);
        schedule_date.setSeconds(seconds);
        schedule_date.setHours(schedule_date.getHours() + hours);

        var schedule_msg = 'Tweet scheduled for: <b>';
        schedule_msg += schedule_date.getMonthNameShort() + ' ' + schedule_date.getDate();
        schedule_msg += ', ' + schedule_date.getFullYear() + ' @ ' + schedule_date.toLocaleTimeString() + '</b>';

        alljs.socialer.show_custom_message(schedule_msg);

        jQuery('#socialer-tweet-schedule').attr('checked', true);

        if ( response.result.hours ) {
            jQuery('#socialer-tweet-delay').val(response.result.hours);
        }
    }
};

alljs.socialer.get_statistics_for_story = function( callback ) {
    // TODO: get stats for story
    if ( typeof callback === 'function') {
        callback();
    }
};

alljs.socialer.show_custom_message = function(msg) {
    jQuery('#socialer-custom-messages').html(msg);
    jQuery('#socialer-custom-messages').show();
};

alljs.socialer.hide_custom_message = function() {
    jQuery('#socialer-custom-messages').hide();
};

/**
 * Getting register button or tweet box
 */
alljs.socialer.get_correct_box = function() {
    jQuery('#socialer-container-wait').show();
    jQuery('#socialer-container').hide();
    var post = alljs.socialer.post();

    jQuery.ajax({
        url: post.base_request_url + 'is_user_registered',
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

/**
 * Getting register button
 */
alljs.socialer.get_register_button = function() {
    var post = alljs.socialer.post();
    jQuery.ajax({
        url: post.base_request_url + 'get_register_button',
        dataType: 'html'
    })
    .done(function(response) {
        jQuery('#socialer-container').html(response);
        jQuery('#socialer-container-wait').hide();
        jQuery('#socialer-container').show();
        alljs.socialer.bind_register_modal();
    });
};

/**
 * Getting tweet textarea
 */
alljs.socialer.get_tweet_box = function() {
    var post = alljs.socialer.post();
    var post_id = post.id;
    jQuery.ajax({
        url: post.base_request_url + 'get_tweet_box' + (post_id ? ('&post=' + post_id) : ''),
        dataType: 'html'
    })
    .done(function(response) {
        jQuery('#socialer-container').html(response);

        var function_after_get_schedule_or_draft = function(){

            jQuery('#socialer-container-wait').hide();
            jQuery('#socialer-container').show();

            // and then get all statistics into widget
            alljs.socialer.get_statistics_for_story(function(){
                // then bind checkbox memory
                alljs.socialer.bind_tweeting_onoff();
                // bind tweeting functionality
                alljs.socialer.bind_push_tweet();
                // bind scheduling functionality
                alljs.socialer.bind_schedule_tweet();
                // ind counting of characters in tweet
                alljs.socialer.bind_count_tweet_characters();
            });
        };

        // automatically tweet or schedule if no story was before
        alljs.socialer.auto_tweet_or_schedule(function() {
            // then get scheduled tweet or draft tweet body
            if ( post.status == 'draft' || post.status == 'auto-draft' ) {
                alljs.socialer.get_draft_tweet(function_after_get_schedule_or_draft);
            } else if ( !post.status ) {
                function_after_get_schedule_or_draft();
            } else {
                alljs.socialer.get_scheduled_tweet(function_after_get_schedule_or_draft);
            }
        });
    });
};

/**
 * Automatically send tweet or schedule it on document load
 * @param callback
 */
alljs.socialer.auto_tweet_or_schedule = function(callback) {
    var called = false;

    if ( jQuery('#alljs-dispatcher-socialer').data('autosend-tweet') ) {
        jQuery(document).bind('alljs.socialer.auto_tweet_or_schedule', function(){
            if ( typeof callback === 'function') {
                called = true;
                callback();
            }
        });
        jQuery('#alljs-dispatcher-socialer').removeAttr('data-autosend-tweet');
        alljs.socialer.bind_push_tweet();
        jQuery('#socialer-ajax-push-tweet').click();
    }

    if ( jQuery('#alljs-dispatcher-socialer').data('schedule-tweet') ) {
        jQuery(document).bind('alljs.socialer.auto_tweet_or_schedule', function(){
            if ( typeof callback === 'function' && !called) {
                called = true;
                callback();
            }
        });
        jQuery('#alljs-dispatcher-socialer').removeAttr('schedule-tweet');
        alljs.socialer.bind_schedule_tweet();
        jQuery('#socialer-ajax-schedule-tweet').click();
    }

    if ( typeof callback === 'function' && !called) {
         callback();
    }
};

/**
 *  Scheduling tweet functionality
 */
alljs.socialer.bind_schedule_tweet = function () {
    jQuery('#socialer-ajax-schedule-tweet').unbind();
    jQuery('#socialer-ajax-schedule-tweet').bind('click', function(){

        var post = alljs.socialer.post();

        if ( !jQuery('#alljs-dispatcher-socialer').attr('data-schedule-tweet') ) {
            // update data-tag with changed value
            var newval = jQuery('#socialer-tweet-delay').val();
            jQuery('#alljs-dispatcher-socialer').attr('data-schedule-delay', newval);
        }

        jQuery('#socialer-ajax-schedule-tweet-wait').show();

        var post_data = {
            text:               jQuery('#socialer-tweet-body').val(),
            url:                post.permalink,
            title:              post.title,
            post_id:            post.id,
            schedule_date:      post.schedule_date,
            schedule_delay:     post.schedule_delay
        };

        jQuery('#socialer-message').hide();
        jQuery('#socialer-ajax-schedule-tweet').attr('disabled', 'disabled');
        jQuery('#socialer-ajax-schedule-tweet').unbind();

        jQuery.ajax({
            url: post.base_request_url + 'schedule_tweet',
            dataType: 'json',
            type: 'post',
            data: post_data,
            success: function(response) {
                alljs.options.scheduled = true;
                //console.log(response);
                alljs.socialer.show_scheduled_tweet_data(response);
                jQuery('#socialer-message').show();
                jQuery('#socialer-message').html(response.message);
                jQuery('#socialer-ajax-schedule-tweet-wait').hide();
                jQuery('#socialer-ajax-schedule-tweet').removeAttr('disabled');
                jQuery('#alljs-dispatcher-socialer').removeAttr('data-schedule-tweet');
                alljs.socialer.bind_schedule_tweet();
                jQuery(document).trigger('alljs.socialer.auto_tweet_or_schedule');
            }
        });
    });
};

/**
 * Show/hide hours number
 */
/*alljs.socialer.bind_schedule_option_change = function() {
    jQuery('#socialer-tweet-type').unbind();
    jQuery('#socialer-tweet-type').bind('change', function(){
        if ( jQuery('#socialer-tweet-type').is(':checked') ) {
            jQuery('#socialer-tweet-delay-label').show();
        } else {
            jQuery('#socialer-tweet-delay-label').hide();
        }
    });

    jQuery('#socialer-tweet-type').trigger('change');
};*/

/**
 * Remembering tweet checkbox state
 */
alljs.socialer.bind_tweeting_onoff = function() {
    if ( !alljs.cookie.cookie('socialer-tweeting-enabled') ) {
        jQuery('#socialer-tweeting-enabled').removeAttr('checked');
    } else {
        jQuery('#socialer-tweeting-enabled').attr('checked', 'checked');
    }

    jQuery('#socialer-tweeting-enabled').unbind();
    jQuery('#socialer-tweeting-enabled').bind('change', function(){
        alljs.cookie.cookie(
            'socialer-tweeting-enabled',
            jQuery('#socialer-tweeting-enabled').attr('checked')
        );
    });
};

/**
 * Pushing tweet with ajax
 */
alljs.socialer.bind_push_tweet = function() {
    jQuery('#socialer-ajax-push-tweet').unbind();
    jQuery('#socialer-ajax-push-tweet').bind('click', function(){
        jQuery('#socialer-ajax-push-tweet-wait').show();
        jQuery('#socialer-ajax-push-tweet').unbind();
        jQuery('#socialer-ajax-push-tweet').attr('disabled', 'disabled');
        jQuery('#socialer-message').hide();
        var post = alljs.socialer.post();
        jQuery.ajax({
            url: post.base_request_url + 'push_tweet' + (post.id ? ('&post=' + post.id) : ''),
            dataType: 'json',
            type: 'post',
            data: {
                text:   jQuery('#socialer-tweet-body').val(),
                url:    post.permalink,
                title:  post.title
            },
            success: function(response) {
                jQuery('#socialer-message').show();
                jQuery('#socialer-message').html(response.message);
                jQuery('#socialer-ajax-push-tweet-wait').hide();
                jQuery('#socialer-ajax-push-tweet').removeAttr('disabled');
                alljs.socialer.bind_push_tweet();
                jQuery(document).trigger('alljs.socialer.auto_tweet_or_schedule');
            }
        });
    });
};

/**
 * Counting characters
 */
alljs.socialer.bind_count_tweet_characters = function() {
    var limit = jQuery('#socialer-tweet-body').attr('maxlength');
    jQuery('#socialer-tweet-body').unbind();
    jQuery('#socialer-tweet-body').bind('keyup', function(){
        var available = limit - jQuery('#socialer-tweet-body').val().length;
        jQuery('#socialer-tweet-chars-left').html(available);
    });
    jQuery('#socialer-tweet-body').trigger('keyup');
};

/**
 * Showing popup
 */
alljs.socialer.bind_register_modal = function() {
    jQuery('#socialer-register-button').unbind();
    jQuery('#socialer-register-button').bind('click', function(){

        var url = jQuery('#socialer-register-button').data('url');
        var popup = window.open(url, '', 'width=750,height=600');
        var timer = setInterval(checkChild, 200);
        function checkChild() {
            if (popup.closed) {
                alljs.socialer.get_correct_box();
                clearInterval(timer);
            }
        }
    });
};

alljs.socialer.post = function() {
    var dispatcher = jQuery('#alljs-dispatcher-socialer');

    var post = {
    base_request_url    :dispatcher.attr('data-base-url'),
    id                  :dispatcher.attr('data-post_id'),
    permalink           :dispatcher.attr('data-post_permalink'),
    title               :(
        dispatcher.attr('data-post_title')
            ? dispatcher.attr('data-post_title')
            : jQuery('#title').val()
        ),
    date                :dispatcher.attr('data-post_date'),
    schedule_date       :(
        dispatcher.attr('data-schedule-tweet')
            ? dispatcher.attr('data-schedule-date')
            : dispatcher.attr('data-post_date')
        ),
    schedule_delay      :(
        dispatcher.attr('data-schedule-tweet')
            ? dispatcher.attr('data-schedule-delay')
            : jQuery('#socialer-tweet-delay').val()
        ),
    status: dispatcher.attr('data-post_status')
    };

    return post;
};

//-----------------------------------------------------------------------------

// Dispatching all system
jQuery(document).ready(function () {
    alljs.system.dispatch();
});