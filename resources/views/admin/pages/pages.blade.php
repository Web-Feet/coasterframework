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
    <div class="col-sm-8 pages_key">
        Key: <span class="label type_normal_dark">Normal Page</span>
        <span class="label type_link">Link / Document</span>
        @if ($groups_exist)
        <span class="label type_group">Group Page</span>
        @endif
        <span class="label type_hidden">Not Live</span>
    </div>
    <div class="col-sm-12 col-md-4">
      {!! Form::open() !!}
        {!! Form::hidden('search_entity', CoasterCms\Models\Page::class) !!}
        {!! Form::text('q', '', array('placeholder' => 'Search pages...', 'class' => 'form-control search-box')) !!}
      {!! Form::close() !!}
    </div>
</div>

<div id="sort-wrap">
{!! $pages !!}
</div>

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

            var initList = function()
            {
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
                        url:  route('coaster.admin.account.page-state'),
                        type: 'POST',
                        data: {
                            page_id: li.attr('id').replace('list_', ''),
                            expanded: li.hasClass('mjs-nestedSortable-expanded')
                        }
                    });
                }
            });

            initialize_sort('nestedSortable');
            watch_for_delete('.delete', 'page', function (el) {
                return el.closest('li').attr('id');
            });

          };
          initList();
          $.each(expanded, function(index, value) {
              $('#list_'+value+ '.mjs-nestedSortable-branch > .ui-sortable-handle > .disclose').trigger('click');
          });
          var search = {
            searchEl: null,
            searchQ: '',
            searchFrm: null,
            ajaxCall: null,
            originalState: null,
            sortableTableListId: '',
            init: function(el, sortableTableListId)
            {
              search.sortableTableListId = sortableTableListId;
              search.originalState = $('#' + search.sortableTableListId).clone();
              search.searchEl = el;
              search.searchFrm = search.searchEl.parents('form:eq(0)');
              search.bindEvents();
              search.searchQ = getURLParameter('q');
            },
            bindEvents: function()
            {
              search.searchEl.bind('keyup', function(e)
              {
                search.searchQ = search.searchEl.val();
                if (search.searchQ.length > 1)
                {
                  search.doSearch();
                }
                else if(search.searchQ.length == 0){
                  $('#sort-wrap').html(search.originalState);
                  initList();
                }
              });
            },
            doSearch: function()
            {
              if (search.ajaxCall !== null) {
                search.ajaxCall.abort();
              }
              search.ajaxCall = $.ajax({
                type: 'POST',
                url: route('coaster.admin.adminsearch') + '?rnfd'+ Math.random(),
                data: search.searchFrm.serialize(),
                success: function(r)
                {
                  $('#sort-wrap').html(r);
                  watch_for_delete('.delete', 'page', function (el) {
                      return el.closest('li').attr('id');
                  });
                  search.ajaxCall = null;
                }
              });
            }
          };
          search.init($('.search-box'), 'sortablePages');
        });
    </script>
@stop
