<div class="row">
    <div class="col-sm-6">
        <h1>{!! $group->name !!}</h1>
    </div>
    <div class="col-sm-6 text-right">
        @if ($can_edit)
            <a href="{{ route('coaster.admin.groups.edit', ['groupId' => $group->id]) }}" class="btn btn-warning addButton">
                <i class="fa fa-pencil"></i> &nbsp; Edit Group Settings</a> &nbsp;
        @endif
        @if ($can_add)
            <a href="{{ route('coaster.admin.pages.add', ['pageId' => 0, 'groupId' => $group->id]) }}" class="btn btn-warning addButton">
                <i class="fa fa-plus"></i> &nbsp; Add {!! $group->item_name !!}</a>
        @endif
    </div>
</div>

{!! $pages !!}

@section('scripts')
    <script type='text/javascript'>

        $(document).ready(function () {

            watch_for_delete('.delete', 'page', function (el) {
                return el.closest('tr').attr('id');
            }, route('coaster.admin.pages.delete', {pageId : ''}));

        });
    </script>
@stop