<?php AssetBuilder::setStatus('jquery-sortable', true); ?>

<div class="row">
    <div class="col-sm-6">
        <h1>Pages</h1>
    </div>
    <div class="col-sm-6 text-right">
        @if ($add_page)
            <a href="{{ route('coaster.admin.pages.add') }}" class="btn btn-warning addButton" data-page="0"><i
                        class="fa fa-plus"></i> &nbsp; Add Page</a>
        @endif
    </div>
</div>

<div class="row textbox">
    <div class="col-sm-12 pages_key">
        Key: <span class="label type_normal_dark">Normal Page</span>
        <span class="label type_link">Link / Document</span>
        @if ($groups_exist)
        <span class="label type_group">Group Page</span>
        @endif
        <span class="label type_hidden">Not Live</span>
    </div>
</div>

{!! $pages !!}

@section('scripts')
    <script type='text/javascript'>

        var expanded = {!! json_encode($page_states) !!};

        var rootPages = {!! json_encode($rootPageIds) !!};

        $(document).ready(function () {

            @if ($max)
            $('.addPage').click(function () {
                event.preventDefault();
                cms_alert('error', 'Max Pages Reached', '');
            });
            @endif

            $('#sortablePages').nestedSortable({
                forcePlaceholderSize: true,
                handle: 'div',
                helper: 'clone',
                items: 'li',
                opacity: .6,
                placeholder: 'placeholder',
                revert: 250,
                tabSize: 25,
                tolerance: 'pointer',
                toleranceElement: '> div',
                maxLevels: 10,

                isTree: true,
                expandOnHover: 700,
                startCollapsed: true,

                isAllowed: function canMovePage(placeholder, placeholderParent, currentItem) {
                    return !(placeholderParent && (rootPages.indexOf(placeholderParent.attr('id')) != -1 || rootPages.indexOf(currentItem.attr('id')) != -1));
                }
            });

            $('.disclose').on('click', function (e) {
                $(this).toggleClass('glyphicon-plus-sign').toggleClass('glyphicon-minus-sign');
                var li = $(this).closest('li');
                li.toggleClass('mjs-nestedSortable-collapsed').toggleClass('mjs-nestedSortable-expanded');
                if (!e.isTrigger) {
                    $.ajax({
                        url: '{{ route('coaster.admin.account.page-state') }}',
                        type: 'POST',
                        data: {
                            page_id: li.attr('id').replace('list_', ''),
                            expanded: li.hasClass('mjs-nestedSortable-expanded')
                        }
                    });
                }
            });

            $.each(expanded, function(index, value) {
                $('#list_'+value+ '.mjs-nestedSortable-branch > .ui-sortable-handle > .disclose').trigger('click');
            });

            initialize_sort('nestedSortable');
            watch_for_delete('.delete', 'page', function (el) {
                return el.closest('li').attr('id');
            });

        });
    </script>
@stop