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

    $('#fileupload').fileupload({
        // Uncomment the following to send cross-domain cookies:
        //xhrFields: {withCredentials: true},
        url: window.location.href.replace('/edit/', '/update/'),
        sequentialUploads: false
    });

    var fixHelper = function (e, ui) {
        ui.children().each(function () {
            $(this).width($(this).width());
        });
        return ui;
    };

    $("#gallery tbody").sortable({
        handle: 'td:first',
        helper: fixHelper,
        items: 'tr',
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