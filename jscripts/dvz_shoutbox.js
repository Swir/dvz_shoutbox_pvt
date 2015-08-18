/* DVZ Shoutbox by Tomasz 'Devilshakerz' Mlynski [devilshakerz.com]; Copyright (C) 2014 */
/* Customised by Arne Van Daele */

var dvz_shoutbox = {

    // defaults
    interval:   5,
    antiflood:  0,
    maxShouts:  20,
    awayTime:   600000,
    lazyMode:   false,
    lang:       [],
    status:     true,
    reversed:   false,

    // runtime
    timeout:    false,
    frozen:     false,
    updating:   false,
    started:    false,
    lastSent:   0,
    lastId:     0,
    activity:   0,

    loop: function (forced) {

        if (forced == true) {
            clearTimeout(dvz_shoutbox.timeout);
        } else {
            if (dvz_shoutbox.isAway()) {
                dvz_shoutbox.toggle(false, false);
                dvz_shoutbox.frozen = true;
                return false;
            }
            if (!dvz_shoutbox.lazyLoad()) {
                dvz_shoutbox.frozen = true;
                return false;
            }

            if (dvz_shoutbox.status == false) {
                dvz_shoutbox.frozen = true;
                return false;
            }
        }

        dvz_shoutbox.update(function(){

            dvz_shoutbox.started = true;

            // next request
            if (dvz_shoutbox.interval) {
                dvz_shoutbox.timeout = setTimeout('dvz_shoutbox.loop(true)', dvz_shoutbox.interval * 1000);
            }

        });


    },

    // actions
    update: function (callback) {

        if (dvz_shoutbox.updating) {
            return false;
        } else {
            dvz_shoutbox.updating = true;
        }

        jQuery.get(
            'xmlhttp.php',
            { action: 'dvz_sb_get_shouts', from: dvz_shoutbox.lastId },
            function(data) {

                if (dvz_shoutbox.handleErrors(data)) {
                    return false;
                }

                if (data) {

                    var data = jQuery.parseJSON(data);

                    // insert new shouts
                    if (dvz_shoutbox.reversed) {

                        var scrollMax = jQuery('#shoutbox .data').innerHeight() - jQuery('#shoutbox .window').innerHeight();
                        var scroll    = jQuery('#shoutbox .window').scrollTop();

                        jQuery('#shoutbox .data').append ( jQuery(data.html).hide().show() );

                        // scroll to bottom again
                        if (!dvz_shoutbox.started || scroll >= scrollMax) {
                            jQuery('#shoutbox .window').scrollTop( jQuery('#shoutbox .window')[0].scrollHeight );
                        }
                    } else {
                        jQuery('#shoutbox .data').prepend( jQuery(data.html).hide().show() );
                    }

                    // remove old shouts to fit the limit
                    var old = jQuery('#shoutbox .entry').length - dvz_shoutbox.maxShouts;
                    if (old > 0) {
                        jQuery('#shoutbox .entry:nth'+(dvz_shoutbox.reversed ? '' : '-last')+'-child(-n+'+old+')').remove();
                    }

                    // mark new shouts
                    if (dvz_shoutbox.started) {

                        jQuery('#shoutbox .entry').filter(function(){
                            return jQuery(this).attr('data-id') > dvz_shoutbox.lastId && jQuery(this).not('[data-own]').length
                        }).addClass('new');

                        setTimeout("jQuery('#shoutbox .entry.new').removeClass('new')", 1000);
                    }

                    dvz_shoutbox.lastId = data.last;

                    dvz_shoutbox.appendControls();

                }

                dvz_shoutbox.updating = false;

                if (typeof(callback) == 'function') {
                    callback();
                }

            }
        );

    },
    shout: function() {

        var message = jQuery('#shoutbox input.text').val();
        if (jQuery.trim(message) == '') {
            return false;
        }

        if (!dvz_shoutbox.antifloodPass()) {
            dvz_shoutbox.handleErrors('A');
        }

        dvz_shoutbox.toggleForm(false);

        jQuery.post(
            'xmlhttp.php',
            { action: 'dvz_sb_shout', text: message, key: my_post_key },
            function(data) {

                if (!dvz_shoutbox.handleErrors(data)) {
                    dvz_shoutbox.lastSent = Math.floor((new Date).getTime() / 1000);
                    dvz_shoutbox.clearForm();
                    dvz_shoutbox.loop(true);
                }

                dvz_shoutbox.toggleForm(true);

            }
        );

    },
    edit: function (id) {

        // text request
        jQuery.get(
            'xmlhttp.php',
            { action: 'dvz_sb_get', id: id, key: my_post_key },
            function(data){

                if (dvz_shoutbox.handleErrors(data)) {
                    return false;
                }

                var data = jQuery.parseJSON(data);
                var newText = prompt('Shout #'+id+':', data.text);

                if (newText && newText != data.text) {

                    // update request
                    jQuery.post(
                        'xmlhttp.php',
                        { action: 'dvz_sb_update', text: newText, id: id, key: my_post_key },
                        function(data) {

                            if (!dvz_shoutbox.handleErrors(data)) {
                                jQuery('#shoutbox .entry[data-id="'+id+'"] .text').html(data);
                            }

                        }
                    );

                }

            }
        );
    },
    delete: function (id) {

        if (confirm(dvz_shoutbox.lang[0])) {

            jQuery.post(
                'xmlhttp.php',
                { action: 'dvz_sb_delete', id: id, key: my_post_key },
                function(data) {

                    if (!dvz_shoutbox.handleErrors(data)) {
                        jQuery('#shoutbox .entry[data-id="'+id+'"]').fadeOut(function(){ jQuery(this).remove() });
                    }

                }
            );

        }

    },

    report: function(id) {
        var reason = prompt('Reden');
        if(reason.trim()) {
            jQuery.post(
                'xmlhttp.php',
                {action: 'dvz_sb_report', id: id, key: my_post_key, reason: reason }, function(data){
                    if(data == true) {
                        alert('We hebben je aangifte ontvangen, bedankt!');
                    } else {
                        alert('Er is iets misgegaan, probeer het opnieuw');
                    }
                }
            );
            return;
        }

        alert('Geen geldige reden ingevuld');
        return;
    },

    // functionality
    toggle: function (status, remember) {
        if (status == true) {
            dvz_shoutbox.status = true;
            jQuery('#shoutbox').removeClass('collapsed');
            jQuery('#shoutbox .body').fadeIn();
            if (remember !== false) document.cookie = cookiePrefix+'dvz_sb_status=1; path='+cookiePath+'; max-age=31536000';

            if (dvz_shoutbox.frozen) {
                dvz_shoutbox.frozen = false;
                dvz_shoutbox.loop();
            }
        } else {
            dvz_shoutbox.status = false;
            jQuery('#shoutbox .body').stop(1).fadeOut(function(){
                if (dvz_shoutbox.status == false) jQuery('#shoutbox').stop(1).addClass('collapsed');
            });
            if (remember !== false) document.cookie = cookiePrefix+'dvz_sb_status=0; path='+cookiePath+'; max-age=31536000';
        }
    },

    // core
    antifloodPass: function() {
        var time = Math.floor((new Date).getTime() / 1000);
        return (time - dvz_shoutbox.lastSent) >= dvz_shoutbox.antiflood;
    },
    updateActivity: function () {
        dvz_shoutbox.activity = (new Date).getTime();
    },
    isAway: function () {
        if (!dvz_shoutbox.awayTime) return false;
        return (new Date).getTime() - dvz_shoutbox.activity > dvz_shoutbox.awayTime;
    },
    onDisplay: function () {
        var threshold = 0;

        var viewTop       = jQuery(document).scrollTop(),
            viewBottom    = viewTop + jQuery(window).height(),
            elementTop    = jQuery('#shoutbox').offset().top,
            elementBottom = elementTop + jQuery('#shoutbox').height();

        return elementBottom >= (viewTop - threshold) && elementTop <= (viewBottom + threshold);
    },
    checkVisibility: function () {
        if (dvz_shoutbox.frozen && dvz_shoutbox.onDisplay()) {
            dvz_shoutbox.frozen = false;
            dvz_shoutbox.loop();
        }
    },
    lazyLoad: function () {
        if (dvz_shoutbox.lazyMode && !dvz_shoutbox.onDisplay()) {
            if (
                dvz_shoutbox.lazyMode == 'start' && !dvz_shoutbox.started ||
                dvz_shoutbox.lazyMode == 'always'
            ) {
                return false;
            }
        }
        return true;
    },
    handleErrors: function (response) {
        if (response == 'A') {
            alert(dvz_shoutbox.lang[1]);
            return true;
        } else
        if (response == 'P') {
            alert(dvz_shoutbox.lang[2]);
            return true;
        }
        if (response == 'S') {
            dvz_shoutbox.toggle(false);
            return true;
        }
        return false;
    },

    // visual
    call: function (username) {
        jQuery('#shoutbox input.text').focus();
        jQuery('#shoutbox input.text').val(jQuery('#shoutbox input.text').val() + '[b]'+username+'[/b], ');
        jQuery('#shoutbox input.text').focus();
    },
    toggleForm: function (status) {
        if (status == false) {
            jQuery("#shoutbox input.text").attr('disabled', 'disabled');
        } else {
            jQuery("#shoutbox input.text").removeAttr('disabled');
            jQuery("#shoutbox input.text").focus();
        }
    },
    clearForm: function () {
        jQuery('#shoutbox input.text').val('');
    },
    appendControls: function () {
        jQuery('#shoutbox .entry:not(:has(.call))').each(function(){

            jQuery(this).children('.user').prepend('<span class="call">&raquo;</span> ');

            if (typeof jQuery(this).attr('data-mod') !== 'undefined') {
                jQuery(this).children('.info').append('<a href="" class="mod edit">E</a><a href="" class="mod del">X</a>');
            }

        });
    },

    doPrivate: function(uid) {
        jQuery('#shoutbox form .text').val('/pvt ' + uid + ' ').focus();
        return;
    }

};

jQuery(document).on('click', '#shoutbox .thead', function() {
    dvz_shoutbox.toggle(!dvz_shoutbox.status);
});
jQuery(document).on('click', '#shoutbox .thead a', function(e){
    e.stopPropagation();
});
jQuery(document).on('click', '#shoutbox .entry .call', function() {
    dvz_shoutbox.call( jQuery(this).parents('.entry').attr('data-username') );
    return false;
});
jQuery(document).on('click', '#shoutbox .entry .mod.edit', function() {
    dvz_shoutbox.edit( jQuery(this).parents('.entry').attr('data-id') );
    return false;
});
jQuery(document).on('click', '#shoutbox .entry .mod.del', function() {
    dvz_shoutbox.delete( jQuery(this).parents('.entry').attr('data-id') );
    return false;
});
jQuery(document).on('click', '#shoutbox .entry .mod.report', function() {
    dvz_shoutbox.report(jQuery(this).parents('.entry').attr('data-id'));
    return false;
});
jQuery(document).on('click', '#shoutbox .panel-away button', function() {
    dvz_shoutbox.setAway(false);
    return false;
});
jQuery(document).on('submit', '#shoutbox .panel form', function() {
    dvz_shoutbox.shout();
    return false;
});
jQuery(document).on('click', '#shoutbox .user .username', function() {
    dvz_shoutbox.doPrivate(jQuery(this).attr('data-id'));
    return false;
});