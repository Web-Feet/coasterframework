<div class="row">
    <div class="col-sm-6">
        <h1>{!! $group->name !!}</h1>
    </div>
    <div class="col-sm-6 text-right">
        @if ($can_add)
            <button class="btn btn-warning addButton" data-group="{!! $group->id !!}">
                <i class="fa fa-plus"></i> &nbsp; Add {!! $group->item_name !!}</button>
        @endif
    </div>
</div>

{!! $pages !!}

@section('scripts')
    <script type='text/javascript'>

        $(document).ready(function () {

            $('.addButton').click(function () {
                document.location.href = route('coaster.admin.pages.add', {pageId: 0, groupId: $(this).attr('data-group')});
            });

            watch_for_delete('.delete', 'page', function (el) {
                return el.closest('tr').attr('id');
            }, route('coaster.admin.pages.delete', {pageId : ''}));

        });
    </script>
@stop