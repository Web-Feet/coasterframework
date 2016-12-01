String.prototype.capitalize = function() {
    return this.replace(/(?:^|\s)\S/g, function(a) { return a.toUpperCase(); });
};

$.expr[':'].between = function(a, b, c) {
    var args = c[3].split(',');
    var val = parseInt(jQuery(a).val());
    return val >= parseInt(args[0]) && val <= parseInt(args[1]);
};

function getURLParameter(name, url) {
    return decodeURIComponent((new RegExp('[?|&]' + name + '=' + '([^&;]+?)(&|#|;|$)').exec(url)||[,""])[1].replace(/\+/g, '%20'))||null;
}

function nth(d) {
    if(d>3 && d<21) return 'th';
    switch (d % 10) {
        case 1:  return "st";
        case 2:  return "nd";
        case 3:  return "rd";
        default: return "th";
    }
}

function jq(myid) {
    return "#" + myid.replace( /(:|\.|\[|\]|,)/g, "\\$1" );
}

function get_public_url() {
    return adminPublicUrl;
}

var alertTitle = {danger:'Error',info:'Notice',success:'Success',warning:'Warning'};
function cms_alert(alertClass, alertContent) {
    var newNotification = $('#cmsDefaultNotification').clone();
    newNotification.append('<b>'+alertTitle[alertClass].capitalize()+':</b> '+alertContent).addClass('alert-'+alertClass).show();
    $('#cmsNotifications').append(newNotification);
    setTimeout(function() {
        newNotification.fadeOut(2500, function () {$(this).remove();});
    }, 7500);
    $('html, body').animate({scrollTop: 0}, 500);
}

function selected_tab(form, indexOrId) {
    var hash = window.location.hash;
    if (hash.substring(0, 4) == '#tab') {
        indexOrId = hash.substring(4);
    }
    var tabs = $('#contentTabs > .nav-tabs');
    var tabEl = $('#navtab'+indexOrId);
    if (!tabEl.length) {
        tabEl = tabs.children().eq(parseInt(indexOrId));
        if (!tabEl.length) {
            tabEl = tabs.children().first();
        }
    }
    tabEl.children('a').tab('show');
    var url = $(form).attr('action').split('#')[0];
    $(form).attr('action', url+tabs.find('li.active a').attr('href'));
    tabs.find('a').click(function () { $(form).attr('action', url+$(this).attr('href')); });
}

function initialize_sort(sort_type, success_callback, fail_callback) {
    $('.sortable').sortable({
        update: function() {
            var sortable_el = $(this);
            $.ajax({
                url: window.location.href+'/sort',
                type: 'POST',
                data: sortable_el[sort_type]("serialize"),
                success: function() {
                    if (success_callback) {
                        success_callback(sortable_el.attr('id'));
                    }
                },
                error: function() {
                    if (fail_callback) {
                        fail_callback(sortable_el.attr('id'));
                    }
                }
            });
        }
    });
}

var deletedItems = {};
function watch_for_delete(selector, item, callback_find_id, custom_url) {
    var delete_modal_el = $('#deleteModal');
    var deletedItem;
    $(selector).click(function() {
        deletedItem = {id: callback_find_id ? callback_find_id($(this)) : $(this).data('id'), name: $(this).data('name'), item: item};
        delete_modal_el.find('.itemName').html(deletedItem.name);
        delete_modal_el.find('.itemType').html(deletedItem.item);
        delete_modal_el.find('.itemTypeC').html(deletedItem.item.capitalize());
        delete_modal_el.modal('show');
    });
    if (!custom_url) {
        custom_url = window.location.href.split('#')[0]+'/delete';
    }
    delete_modal_el.find('.yes').click(function() {
        $.ajax({
            dataType: 'json',
            url: custom_url+'/'+deletedItem.id.replace(/\D/g,''),
            type: 'POST',
            success: function(r) {
                var logIds = r.join(',');
                deletedItems[logIds] = deletedItem;
                $('#' + deletedItem.id).hide();
                cms_alert('warning', 'The ' + deletedItem.item + ' \'' + deletedItem.name + '\' has been deleted. <a href="#" onclick="undo_log(\''+logIds+'\')">Undo</a>');
            },
            error: function() {
                cms_alert('danger', 'The ' + deletedItem.item + ' was not deleted (try refreshing the page, you may no longer be logged in)');
            }
        });
    });
}

function undo_log(logIds) {
    if ($.type(logIds) === 'string') {
        logIds = logIds.split(',');
    }
    var deletedItem;
    if (!(deletedItem = deletedItems[logIds.toString()])) {
        deletedItem = {item:'log', name: 'ID #'+logIds.toString()};
    }
    $.ajax({
        url: route('coaster.admin.backups.undo'),
        data: {'log_ids': logIds},
        type: 'POST',
        success: function () {
            $('#' + deletedItem.id).show();
            cms_alert('info', 'The ' + deletedItem.item + ' \'' + deletedItem.name + '\' has been restored.');
        },
        error: function () {
            cms_alert('danger', 'The ' + deletedItem.item + ' was not restored (try refreshing the page, you may no longer be logged in)');
        }
    });
}

function headerNote() {
    var notediv = null;
    $('.header_note').hover(function (e) {
        var thEl = $(this);
        var theEloffset = thEl.offset();
        var x = theEloffset.left + thEl.width() + 10;
        var y = theEloffset.top;
        notediv = $('<div class="well well-sm fade in">' + $(this).data('note') + '</div>');
        notediv.css({position: 'absolute', top: y, left: x, 'max-width': '25%'});
        notediv.appendTo($('body'));
    }, function (e) {
        notediv.remove();
    });
}