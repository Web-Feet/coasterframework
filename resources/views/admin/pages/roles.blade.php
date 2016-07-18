<div class="row textbox">
    <div class="col-lg-8 col-md-6 col-sm-6">
        <h1>Roles</h1>
        <div class="form-inline">
            <div class="form-group">
                {!! Form::label('role_select', 'View Role: ', ['class' => 'control-label']) !!} &nbsp;
                {!! Form::select('role_select', $roles, null, ['id' => 'role', 'class' => 'form-control long-select']) !!}
                &nbsp; <i id="loading_icon" class="fa fa-cog fa-spin"></i> &nbsp;
                <span id="loading_text">Loading ...</span>
                <span id="saving_text" style="display: none;">Saving ...</span>
            </div>
        </div>
    </div>
    <div class="col-lg-2 col-md-3 col-sm-3 text-right">
        <button type="button" class="btn btn-danger addButton" id="deleteRole"><i class="fa fa-trash"></i> &nbsp; Delete
            Role
        </button>
    </div>
    <div class="col-lg-2 col-md-3 col-sm-3 text-right">
        <button type="button" class="btn btn-warning addButton" id="addRole"><i class="fa fa-plus"></i> &nbsp; Add Role
        </button>
    </div>
</div>

<div id="actions">
    {!! $actions !!}
</div>

@section('scripts')
    <script type='text/javascript'>

        var delete_item, save_timer, load_timer;

        function update_checkboxes(role_id) {
            var user_role = parseInt({{ Auth::user()->role->id }});
            clearTimeout(load_timer);
            $('#loading_icon').show();
            $('#loading_text').show();
            $('#page_permissions').attr('href', route('coaster.admin.roles.pages', {roleId: $('#role').val()}));
            $.ajax({
                url: '{{ route('coaster.admin.roles.actions') }}/' + role_id,
                type: 'POST',
                dataType: 'json',
                success: function (data) {
                    $("#actions input[type=checkbox]").prop('checked', false);
                    $.each(data, function (k, v) {
                        $("#actions input[name=" + k + "]").prop('checked', true);
                    });
                    if (role_id == user_role) {
                        $("#actions input.controller-roles").attr('disabled', 'disabled');
                    } else {
                        $("#actions input.controller-roles").removeAttr("disabled");
                    }
                    load_timer = setTimeout(function () {
                        $('#loading_icon').hide();
                        $('#loading_text').hide();
                    }, 500);
                }
            });
        }

        $(document).ready(function () {
            update_checkboxes($('#role').val());
            $('#role').change(function () {
                update_checkboxes($(this).val());
            });
            $('#addRole').click(function () {
                $('#addRoleModal').modal('show');
            });
            $("#actions input[type=checkbox]").click(function () {
                clearTimeout(save_timer);
                $('#loading_icon').show();
                $('#saving_text').show();
                $.ajax({
                    url: '{{ route('coaster.admin.roles.edit') }}',
                    type: 'POST',
                    data: {action: $(this).attr('name'), role: $('#role').val(), value: $(this).prop('checked')},
                    success: function (r) {
                        if (r === '1') {
                            save_timer = setTimeout(function () {
                                $('#loading_icon').hide();
                                $('#saving_text').hide();
                            }, 500);
                        }
                    }
                });
            });
            $('#addRoleModal .add').click(function () {
                if ($('#role_name').val() == "") {
                    $('#role_name').parent().parent().addClass('has-error');
                }
                else {
                    $('#addRoleModal').modal('hide');
                    $.ajax({
                        url: '{{ route('coaster.admin.roles.add') }}',
                        type: 'POST',
                        dataType: 'json',
                        data: {name: $('#role_name').val(), copy: $('#role_copy').val()},
                        success: function (data) {
                            var last = 0;
                            $.each(data, function (k, v) {
                                $('#role').append('<option value="' + k + '">' + v + '</option>');
                                $('#role_copy').append('<option value="' + k + '">' + v + '</option>');
                                $('#new_role').append('<option value="' + k + '">' + v + '</option>');
                                last = k;
                            });
                            $('#role').val(last);
                            update_checkboxes(last);
                        }
                    });
                    $('#role_name').parent().parent().removeClass('has-error');
                    $('#role_name').val('');
                    $('#role_copy').val(0);
                }
            });

            $('#deleteRole').click(function () {
                delete_item = $('#role').val();
                $('#deleteModal .roleName').html($('#role option:selected').text());
                $('#new_role').parent().parent().removeClass('has-error');
                $('#new_role_help').html('');
                $('#deleteModal').modal('show');
            });
            $('#deleteModal .delete').click(function () {
                if ($('#new_role').val() == delete_item) {
                    $('#new_role').parent().parent().addClass('has-error');
                    $('#new_role_help').html('must select a different role');
                }
                else {
                    $('#deleteModal').modal('hide');
                    $.ajax({
                        url: '{{ route('coaster.admin.roles.delete') }}',
                        type: 'POST',
                        data: {role: delete_item, new_role: $('#new_role').val()},
                        success: function (r) {
                            $('#role option[value=' + delete_item + ']').remove();
                            $('#new_role option[value=' + delete_item + ']').remove();
                            $('#role_copy option[value=' + delete_item + ']').remove();
                            update_checkboxes($('#role').val());
                        }
                    });
                }
            });
        });

    </script>
@stop

