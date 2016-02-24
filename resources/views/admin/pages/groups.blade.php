<div class="row">
    <div class="col-sm-6">
        <h1>{!! $group->name !!}</h1>
    </div>
    <div class="col-sm-6 text-right">
        @if ($can_add)
            <button class="btn btn-warning addButton" data-page="{!! $group->default_parent !!}"><i
                        class="fa fa-plus"></i> &nbsp; Add {!! $group->item_name !!}</button>
        @endif
    </div>
</div>

{!! $pages !!}

@section('scripts')
    <script type='text/javascript'>

        $(document).ready(function () {

            $('.addButton').click(function () {
                document.location.href = get_admin_url() + 'pages/add/' + $(this).attr('data-page');
            });

            watch_for_delete('.delete', 'page', function (el) {
                return el.closest('tr').attr('id');
            }, get_admin_url() + 'pages/delete');

        });
    </script>
@stop