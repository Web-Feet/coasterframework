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
    var nameInput = $('#page_info_lang\\[name\\]'), urlInput = $('#page_info_url'), linkCheckBox = $('#page_info\\[link\\]');
    nameInput.change(function () {
        if ((!linkCheckBox.length || !linkCheckBox.is(':checked')) && (!urlInput.val() || onlyIfEmpty === undefined)) {
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
    var groupContainerSelect = groupContainer.find('select');
    var groupContainerOptions = groupContainer.find('.group-container-options');
    var oldGroup = -1;
    groupContainerSelect.change(function () {
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
    groupContainer.find('input[type=radio]').change(function() {
        if ($(this).val() == 1) {
            groupContainerSelect.find('option[value=0]').addClass('hidden');
            if (groupContainerSelect.val() == 0) {
                groupContainerSelect.val(oldGroup);
            }
            groupContainerOptions.removeClass('hidden');
            inGroup.addClass('hidden');
        } else {
            groupContainerSelect.find('option[value=0]').removeClass('hidden');
            oldGroup = groupContainerSelect.val();
            groupContainerSelect.val(0);
            groupContainerOptions.addClass('hidden');
            inGroup.removeClass('hidden');
        }
    });
    groupContainer.find('input[type=radio]:checked').trigger('change');
}