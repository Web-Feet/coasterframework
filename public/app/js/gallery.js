function updateCaption(image_btn) {

    var file = $(image_btn).attr('data-file');
    var caption = $($(image_btn).parent().parent().children('input')[0]).val();

    $(image_btn).addClass('btn-warning disabled').html('Updating ...');

    $.ajax({
        url: window.location.href.replace('edit', 'caption'),
        type: 'POST',
        data: {file_data: file, caption: caption},
        success: function (r) {
            $(image_btn).removeClass('btn-warning').html('Saved');
            setTimeout(function () {
                $(image_btn).removeClass('disabled');
                $(image_btn).html('Update');
            }, 2000);
        }
    });

}

$(document).ready(function () {

    // Init
    $('#fileupload').fileupload({
        // Uncomment the following to send cross-domain cookies:
        //xhrFields: {withCredentials: true},
        url: window.location.href.replace('/edit/', '/update/'),
        sequentialUploads: true // required as uploaded are version individually
    });

    // Load existing files:
    $('#fileupload').addClass('fileupload-processing');
    $.ajax({
        // Uncomment the following to send cross-domain cookies:
        //xhrFields: {withCredentials: true},
        url: $('#fileupload').fileupload('option', 'url'),
        dataType: 'json',
        context: $('#fileupload')[0]
    }).always(function () {
        $(this).removeClass('fileupload-processing');
    }).done(function (result) {
        $(this).fileupload('option', 'done')
            .call(this, $.Event('done'), {result: result});
    });

    // Sortable on gallery
    $("#gallery tbody").sortable({
        handle: 'td:first',
        items: 'tr',
        helper: function (e, ui) {
            ui.children().each(function () {
                $(this).width($(this).width());
            });
            return ui;
        },
        update: function () {
            var sort_arr = $(this).sortable("toArray", {attribute: 'data-file'});
            $.ajax({
                url: window.location.href.replace('edit', 'sort'),
                type: 'POST',
                data: {arr: sort_arr}
            });
        }
    });

});