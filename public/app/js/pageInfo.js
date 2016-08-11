function updateListenLiveOptions() {
    var liveOptionInput = $('#page_info\\[live\\]');
    liveOptionInput.change(function() {
        if (liveOptionInput.val() != 2) {
            $('.live-date-options').addClass('hidden');
        } else {
            $('.live-date-options').removeClass('hidden');
        }
    }).trigger('change');
}

function updateListenPageUrl(onlyIfEmpty) {
    var nameInput = $('#page_info_lang\\[name\\]'), urlInput = $('#page_info_url');
    nameInput.change(function () {
        if (!urlInput.val() || onlyIfEmpty === undefined) {
            urlInput.val(parsePageUrl(nameInput.val()));
        }
    });
}

function parsePageUrl(url) {
    return url.toLowerCase()
        .replace(/\s+/g, '-')
        .replace(/[^\w-]/g, '-')
        .replace(/-{2,}/g, '-')
        .replace(/^-+/g, '')
        .replace(/-+$/g, '');
}

function updateListenGroupFields() {
    var groupContainer = $('#groupContainer'), inGroup = $('#inGroup');
    var inGroups = inGroup.find('input[type=checkbox]');
    groupContainer.find('select').change(function () {
        if ($(this).val() != 0) {
            inGroup.addClass('hidden');
        } else {
            inGroup.removeClass('hidden');
        }
    }).trigger('change');
    inGroups.change(function() {
        var anyChecked = false;
        inGroups.each(function () {
            anyChecked = anyChecked || $(this).is(':checked');
        });
        if (anyChecked) {
            groupContainer.addClass('hidden');
        } else {
            groupContainer.removeClass('hidden');
        }
    }).trigger('change');
}