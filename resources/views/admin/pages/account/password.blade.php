<h1>Change Password</h1>

@if (isset($success) && $success)

    @if ($level == 'admin')
        <p class="text-success">Password for {!! $user->email !!} has been successfully updated!</p>
        <p>{!! HTML::link(URL::to(config('coaster::admin.url').'/users/edit/').$user->id, '&raquo; Return to user details page') !!}</p>
    @elseif ($level == 'user')
        <p class="text-success">Your password has been successfully updated!</p>
        <p>{!! HTML::link(URL::to(config('coaster::admin.url').'/account'), '&raquo; Return to account settings') !!}</p>
    @else
        <p class="text-success">Your password has been successfully updated!</p>
        <p><a href="{!! URL::to(config('coaster::admin.url').'/login') !!}">&raquo; You can now login here</a></p>
    @endif

@else

    <div class="form-group">
        <div class="input-group">
            <span class="input-group-addon"><i class="glyphicon glyphicon-envelope"></i></span>
            <input class="form-control" value="{!! $user->email !!}" id="inputIcon" type="text" title="email" disabled/>
        </div>
    </div>

    {!! $form !!}

@endif

