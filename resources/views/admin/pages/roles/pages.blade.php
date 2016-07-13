<h1>Page Permissions For {{ $role }}</h1>

<p>&nbsp;</p>
<p>These page permissions will override the default settings for this role.</p>
<p><a href="{{ route('coaster.admin.roles') }}">&raquo; Back to role management</a></p>
<p>&nbsp;</p>

{!! Form::open(['url' => Request::url(), 'id' => 'rolePageForm']) !!}

<div id="role_pages">
    {!! $pages !!}
</div>

<div id="update_page_permissions">
    {!! Form::submit('Update Permissions', ['class' => 'btn btn-primary']) !!}
</div>

{!! Form::close() !!}

@section('scripts')
    <script type='text/javascript'>
        $(document).ready(function () {
            $("input[type=checkbox]").click(function () {
                $(this).parent().parent().find('input.' + this.className).prop('checked', $(this).prop('checked'));
            });
        });
    </script>
@stop