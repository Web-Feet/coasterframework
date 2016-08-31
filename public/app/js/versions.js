var page_id, version_id, latest_version;
var version_table = {'page': 1};

function version_pagination(e) {
    e.preventDefault();
    version_table['page'] = getURLParameter('page', $(this).attr('href'));
    update_version_table();
}

function update_version_table() {
    $.ajax({
        url: route('coaster.admin.pages.versions', {pageId: page_id}),
        data: {
            page: version_table['page']
        },
        type: 'POST',
        success: function(r) {
            $('.itemTooltip').tooltip('hide');
            $('#version_table').html(r);
            $('#version_pagination').find('a').click(version_pagination);
            $('.version_publish').click(version_publish);
            $('.version_rename').click(version_rename_modal);
            $('.version_publish_schedule').click(version_publish_schedule_modal);
            $('.version_publish_schedule_remove').click(version_schedule_remove);
        }
    });
}

function version_rename_modal() {
    version_id = $(this).data('version');
    $('#version_name').val($('#v_' + version_id + ' td:nth-of-type(2)').html());
    $('#renameModal').modal('show');
}

function version_rename() {
    $.ajax({
        url: route('coaster.admin.pages.version-rename', {pageId: page_id}),
        type: 'POST',
        data: {
            version_name: $('#version_name').val(),
            version_id: version_id
        },
        success: function (r) {
            if (r == 1) {
                update_version_table();
            }
        }
    });
}

function version_publish() {
    version_id = $(this).data('version');
    $.ajax({
        url: route('coaster.admin.pages.version-publish', {pageId: page_id}),
        type: 'POST',
        data: {
            version_id: version_id
        },
        success: function() {
            $('.live_page_name').html('');
            update_version();
        }
    });
}

function version_publish_schedule_modal() {
    var modal = $('#versionPublishScheduleModal');
    version_id = $(this).data('version');
    var version_name = $(this).parent().parent().find('td:eq(1)').html();
    modal.find('.version').html(version_name +' (ID #'+version_id+')');
    modal.modal('show');
}

function version_publish_schedule() {
    var schedule_from = $('#version_schedule_from').val();
    var schedule_to = $('#version_schedule_to').val();
    var schedule_repeat = $('#version_schedule_repeat').val();
    $.ajax({
        url: route('coaster.admin.pages.version-schedule', {pageId: page_id}),
        type: 'POST',
        data: {
            version_id: version_id,
            schedule_from: schedule_from,
            schedule_to: schedule_to,
            schedule_to_version: $('.live_version_id').html(),
            schedule_repeat: schedule_repeat
        },
        success: function (r) {
            if (r == 1) {
                update_version_table();
            } else {
                cms_alert('danger', 'Error scheduling version for publishing');
            }
        },
        error: function () {
            cms_alert('danger', 'Error scheduling version for publishing');
        }
    });
}

function version_schedule_remove() {
    var schedule_version_id = $(this).data('scheduled-version-id');
    $.ajax({
        url: route('coaster.admin.pages.version-schedule', {pageId: page_id}),
        type: 'POST',
        data: {
            remove: schedule_version_id
        },
        success: function (r) {
            if (r == 1) {
                update_version_table();
            } else {
                cms_alert('danger', 'Error scheduled version not removed');
            }
        },
        error: function () {
            cms_alert('danger', 'Error scheduled version not removed');
        }
    });
}

function request_publish_modal(e) {
    version_id = $(this).data('version');
    var modal = $('#requestPublishModal');
    if (version_id !== undefined) {
        var version_name = $(this).parent().parent().find('td:eq(1)').html();
        modal.find('.version').html(version_name +' (ID #'+version_id+')');
        modal.find('.version_info').show();
    } else {
        modal.find('.version_info').hide();
    }
    e.preventDefault();
    modal.modal('show');
}

function request_publish() {
    var note = $('#request_note').val();
    if (version_id === undefined) {
        $('#request_note_input').val(note);
        $('#publish_request').val(1);
        $('#editForm').submit();
    } else {
        $.ajax({
            url: route('coaster.admin.pages.request-publish', {pageId: page_id}),
            type: 'POST',
            data: {
                version_id: version_id,
                note: note
            },
            success: function (r) {
                if (r == 1) {
                    cms_alert('success', 'Publish request sent');
                    update_request_table();
                } else {
                    cms_alert('danger', 'Error sending publish request');
                }
            },
            error: function () {
                cms_alert('danger', 'Error sending publish request');
            }
        });
    }
}

function request_publish_action() {
    var request_id = $(this).data('request'), request_action = $(this).data('action'), page_name = $(this).data('name'), request_page_id = $(this).data('page');
    var request = $(this);
    var action_cell = $(this).parent();
    $.ajax({
        url: route('coaster.admin.pages.request-publish-action', {pageId: request_page_id}),
        type: 'POST',
        data: {
            request: request_id,
            request_action: request_action
        },
        success: function(r) {
            if (r == 1) {
                $('.itemTooltip').tooltip('hide');
                if (request_action == 'approved') {
                    version_id = request.data('version_id');
                    page_name = request.data('name');
                    $('.live_page_name').html(' for page '+page_name);
                    update_version();
                }
                if (page_id !== undefined) {
                    update_request_table();
                } else {
                    action_cell.append(request_action);
                    action_cell.find('.request_publish_action').remove();
                }
            }
        }
    });
}

function update_request_table(page) {
    var data = {};
    data['request_type'] = $('#viewAllRequests').data('type');
    data['request_show'] = {page:0,status:1,requested_by:1};
    if (page !== undefined) {
        data['page'] = page;
    }
    $.ajax({
        url: route('coaster.admin.pages.requests', {pageId: page_id}),
        data: data,
        type: 'POST',
        success: function(r) {
            $('.itemTooltip').tooltip('hide');
            $('#publish_requests_table').html(r);
            $('.request_publish_action').click(request_publish_action);
            $('#publish_requests_table').find('.pagination a').click(request_table_pagination);
        }
    });
}

function request_table_pagination(e) {
    e.preventDefault();
    var page = getURLParameter('page', $(this).attr('href'));
    update_request_table(page);
}

function update_version() {
    $('.live_version_id').html(version_id);
    if (version_id == latest_version) {
        $('#version-well .version-p').removeClass('hidden');
        $('#version-well .version-up').addClass('hidden');
    } else {
        $('#version-well .version-p').addClass('hidden');
        $('#version-well .version-up').removeClass('hidden');
    }
    $('#publishModal').modal('show');
    update_version_table();
}

$(document).ready(function() {
    $('#version_pagination').find('a').click(version_pagination);
    $('.version_publish').click(version_publish);

    $('.version_publish_schedule').click(version_publish_schedule_modal);
    $('.version_publish_schedule_remove').click(version_schedule_remove);
    $('#versionPublishScheduleModal').find('.schedule').click(version_publish_schedule);

    $('.version_rename').click(version_rename_modal);
    $('#renameModal').find('.btn-primary').click(version_rename);

    $('.request_publish').click(request_publish_modal);
    $('.make_request').click(request_publish);

    $('.request_publish_action').click(request_publish_action);
    $('#publish_requests_table').find('.pagination a').click(request_table_pagination);

    $('#viewAllRequests').click(function() {
        var viewAllRequests = $('#viewAllRequests');
        if (viewAllRequests.data('type') == 'awaiting') {
            viewAllRequests.data('type', '').html('View awaiting requests');
        } else {
            viewAllRequests.data('type', 'awaiting').html('View all requests');
        }
        update_request_table();
    });

});
