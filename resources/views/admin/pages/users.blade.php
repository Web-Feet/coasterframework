<div class="row textbox">
    <div class="col-sm-6">
        <h1>User List</h1>
    </div>
    <div class="col-sm-6 text-right">
        @if ($can_add == true)
            <a href="{{ route('coaster.admin.users.add') }}" class="btn btn-warning addButton"><i class="fa fa-plus"></i> &nbsp;
                Add User</a>
        @endif
    </div>
</div>

<div class="table-responsive">
    <table class="table table-bordered table-striped">
        <thead>
        <tr>
            <th>User</th>
            <th>Role</th>
            @if ($can_edit || $can_delete)
                <th>Actions</th>
            @endif
        </tr>
        </thead>
        <tbody>
        @foreach ($users as $user)
            <tr id="user_{!! $user->id !!}">
                <td>{!! $user->email !!}</td>
                <td>{!! $user->name !!}</td>
                @if ($can_edit || $can_delete)
                    <td data-uid="{!! $user->id !!}">
                        @if ($can_edit)
                            <?php $enable = ($user->active == 0) ? null : ' hide'; ?>
                            <?php $disable = ($user->active == 0) ? ' hide' : null; ?>
                            <i class="glyphicon glyphicon-stop itemTooltip{!! $enable !!}" title="Enable User"></i>
                            <i class="glyphicon glyphicon-play itemTooltip{!! $disable !!}" title="Disable User"></i>
                            <a class="glyphicon glyphicon-pencil itemTooltip" href="{{ route('coaster.admin.users.edit', ['userId' => $user->id]) }}" title="Edit User"></a>
                        @endif
                        @if ($can_delete)
                            <i class="glyphicon glyphicon-remove itemTooltip" title="Remove User"
                               data-name="{!! $user->email !!}"></i>
                        @endif
                    </td>
                @endif
            </tr>
        @endforeach
        </tbody>
    </table>
</div>

@section('scripts')
    <script type="text/javascript">

        function disable_user(user_id, active) {
            if (user_id == {{ Auth::user()->id }}) {
                alert('Can\'t disable your own account');
            }
            else {
                $.ajax({
                    url: route('coaster.admin.users.edit', {userId: user_id, action: 'status'}),
                    type: 'POST',
                    data: {set: active},
                    success: function (r) {
                        if (r == 1) {
                            if (active == 0) {
                                $("#user_" + user_id + " .glyphicon-play").addClass('hide');
                                $("#user_" + user_id + " .glyphicon-stop").removeClass('hide');
                            }
                            else {
                                $("#user_" + user_id + " .glyphicon-stop").addClass('hide');
                                $("#user_" + user_id + " .glyphicon-play").removeClass('hide');
                            }
                        }
                        else {
                            cms_alert('danger', 'Error Processing Request', 'Can\'t disable this user.');
                        }
                    }
                });
            }
        }

        $(document).ready(function () {
            $('.glyphicon-play').click(function () {
                disable_user($(this).parent().attr('data-uid'), 0);
            });
            $('.glyphicon-stop').click(function () {
                disable_user($(this).parent().attr('data-uid'), 1);
            });

            watch_for_delete('.glyphicon-remove', 'user', function (el) {
                var user_id = el.parent().attr('data-uid');
                if (user_id == {!! Auth::user()->id !!}) {
                    alert('Can\'t delete your own account');
                    return false;
                } else {
                    return 'user_' + user_id;
                }
            });
        });

    </script>
@stop