<h1>Account Settings</h1>

<br/>

{!! $account !!}

@if ($change_password)
    <a href="{{ route('coaster.admin.account.password') }}" class="btn btn-warning"><i class="fa fa-unlock-alt"></i> &nbsp; Change
        Password</a>
@endif

@if ($setAlias)
    <a href="{{ route('coaster.admin.account.name') }}" class="btn btn-warning"><i class="fa fa-users"></i> &nbsp; Set Alias</a>
@endif


@if ($auto_blog_login)
    {{ ($change_password)?'&nbsp;':'' }}
    <a href="{{ route('coaster.admin.account.blog') }}" class="btn btn-warning"><i class="fa fa-share"></i> &nbsp; Auto Blog Login
        Details</a>
@endif