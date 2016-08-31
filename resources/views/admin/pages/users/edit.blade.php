<h1>User Details: {!! $user->email !!}</h1>

<br/>

{!! $account !!}

@if ($can_edit)
    <a href="{{ route('coaster.admin.users.edit', ['userId' => $user->id, 'action' => 'password']) }}" class="btn btn-warning"><i class="fa fa-lock"></i> &nbsp; Change Password</a> &nbsp;
    <a href="{{ route('coaster.admin.users.edit', ['userId' => $user->id, 'action' => 'name']) }}" class="btn btn-warning"><i class="fa fa-users"></i> &nbsp; Change Alias</a> &nbsp;
    <a href="{{ route('coaster.admin.users.edit', ['userId' => $user->id, 'action' => 'role']) }}" class="btn btn-warning"><i class="fa fa-bullhorn"></i> &nbsp; Change Role</a>
@endif