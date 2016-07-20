<h1>Redirects</h1>

<div class="row textbox">
    <div class="col-sm-12">
        <p>The force option will force the redirect to run even it a page exists at the 'Redirect From' path.</p>
        <p>You can use % at the end of the 'Redirect From' field, ie. "test/%" will catch any pathnames starting with
            "test/".</p>
    </div>
</div>

{!! Form::open(['id' => 'editForm', 'enctype' => 'multipart/form-data']) !!}

<div class="table-responsive">
    <table id="redirects" class="table table-bordered table-striped">
        <thead>
        <tr>
            <th><a href="{!! route('coaster.admin.redirects').'?order=redirect' !!}">Redirect From</a></th>
            <th><a href="{!! route('coaster.admin.redirects').'?order=to' !!}">Redirect To</a></th>
            <th><a href="{!! route('coaster.admin.redirects').'?order=forced' !!}">Forced</a></th>
            @if ($can_edit)
                <th>Remove</th>
            @endif
        </tr>
        </thead>
        <tbody>
        @foreach ($redirects as $redirect)
            <tr id="redirect_{!! $redirect->id !!}">
                <td>
                    @if ($can_edit)
                        {!! Form::text('redirect['.$redirect->id.'][from]', $redirect->redirect, ['class' => 'form-control']) !!}
                    @else
                        {!! $redirect->redirect !!}
                    @endif
                </td>
                <td>
                    @if ($can_edit)
                        {!! Form::text('redirect['.$redirect->id.'][to]', $redirect->to, ['class' => 'form-control']) !!}
                    @else
                        {!! $redirect->to !!}
                    @endif
                </td>
                <td>
                    @if ($can_edit)
                        {!! Form::checkbox('redirect['.$redirect->id.'][force]', 1, $redirect->force, ['class' => 'form-control']) !!}
                    @else
                        {!! ($redirect->force==1)?'Yes':'No' !!}
                    @endif
                </td>
                @if ($can_edit)
                    <td>
                        <i class="glyphicon glyphicon-remove itemTooltip" title="Remove Redirect"
                           onclick="delete_redirect('{!! $redirect->id !!}')"></i>
                    </td>
                @endif
            </tr>
        @endforeach
        </tbody>
    </table>
</div>

@if ($can_edit)
    <div class="row textbox">
        <div class="col-sm-12">
            <button type="button" class="btn add_another" onclick="add_redirect()"><i class="fa fa-plus"></i>
                &nbsp; Add Another
            </button>
            <button class="btn btn-primary" type="submit"><i class="fa fa-floppy-o"></i> &nbsp; Save Redirects</button>
        </div>
    </div>
@endif

{!! Form::close() !!}


@section('scripts')
    <script type='text/javascript'>

        function delete_redirect(id) {
            var check_new = id.toString().substr(0, 3);
            if (check_new != 'new') {
                $.ajax({
                    url: route('coaster.admin.redirects.edit'),
                    type: 'POST',
                    data: {delete_id: id},
                    success: function () {
                        $("#redirect_" + id).remove();
                    }
                });
            }
            else {
                $("#redirect_" + id).remove();
            }
        }

        var new_redirect = 1;

        @if ($can_edit)
        function add_redirect() {
            var id = 'new' + new_redirect;
            new_redirect++;
            $("#redirects > tbody").append('<tr id="redirect_' + id + '">' +
                    '<td><input class="form-control" name="redirect[' + id + '][from]" type="text"></td>' +
                    '<td><input class="form-control" name="redirect[' + id + '][to]" type="text"></td>' +
                    '<td><input name="redirect[' + id + '][force]" class="form-control" type="checkbox" value="1"></td>' +
                    '<td><i class="glyphicon glyphicon-remove itemTooltip" title="Remove Redirect" onclick="delete_redirect(\'' + id + '\')"></i></td>' +
                    '</tr>'
            );
            $('.itemTooltip').tooltip({placement: 'bottom', container: 'body'});
        }
        @endif

    </script>
@stop

