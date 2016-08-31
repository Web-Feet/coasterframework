<h1>Change User Role: {!! $user->email !!}</h1>

<br/>

@if (isset($success))

    <p class="text-success">Role for {!! $user->email !!} has been successfully updated!</p>
    <p><a href="{{ route('coaster.admin.users.edit', ['userId' => $user->id]) }}">&raquo; Return to user details page</a></p>

    @else

    {!! Form::open(['url' => Request::url()]) !!}

            <!-- user role field -->
    <div class="form-group">
        {!! Form::label('role', 'User Role', ['class' => 'control-label']) !!}
        {!! Form::select('role', $roles, null, ['class' => 'form-control']) !!}
        <span class="help-block"></span>
    </div>

    <!-- submit button -->
    <button type="submit" class="btn btn-primary">Update Role</button>

    {!! Form::close() !!}

@endif