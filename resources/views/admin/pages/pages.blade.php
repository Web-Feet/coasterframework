<?php AssetBuilder::setStatus('jquery-sortable', true); ?>

<div class="row">
    <div class="col-sm-6">
        <h1>Pages</h1>
    </div>
    <div class="col-sm-6 text-right">
        @if ($add_page)
            <a href="{{ Request::url().'/add/' }}" class="btn btn-warning addButton" data-page="0"><i
                        class="fa fa-plus"></i> &nbsp; Add Page</a>
        @endif
    </div>
</div>

<div class="row textbox">
    <div class="col-sm-12 pages_key">
        Key: <span class="label type_normal_dark">Normal Page</span>
        <span class="label type_link">Link / Document</span>
        <span class="label type_group">Plugin</span>
        <span class="label type_hidden">Not Live</span>
    </div>
</div>

{!! $pages !!}

@section('scripts')
    <script type='text/javascript'>

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
                maxLevels: 5,

                isTree: true,
                expandOnHover: 700,
                startCollapsed: true
            });

            $('.disclose').on('click', function () {
                $(this).toggleClass('glyphicon-plus-sign').toggleClass('glyphicon-minus-sign');
                $(this).closest('li').toggleClass('mjs-nestedSortable-collapsed').toggleClass('mjs-nestedSortable-expanded');
            });

            initialize_sort('nestedSortable');
            watch_for_delete('.delete', 'page', function (el) {
                return el.closest('li').attr('id');
            });

        });
    </script>
@stop