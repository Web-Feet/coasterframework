<h1>User Details: {!! $email !!}</h1>

<br/>

{!! $account !!}

@if ($can_edit)
    <a href="{{ URL::Current().'/password' }}" class="btn btn-warning"><i class="fa fa-lock"></i> &nbsp; Change Password</a> &nbsp;
    <a href="{{ URL::Current().'/role' }}" class="btn btn-warning"><i class="fa fa-bullhorn"></i> &nbsp; Change Role</a>
@endif