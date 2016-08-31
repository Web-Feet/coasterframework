<h1>Add New User</h1>

<br/>

@if (isset($success))

    <p class="text-success">
        The account '{!! Request::input('email') !!}' has been successfully created!<br/>
        Account Password: {!! $password !!}
    </p>
    @if (Request::input('send_email') == 1)
        <p class="text-{!! $email_status !!}">{!! $email_message !!}</p>
    @endif

    <p>
        <a href="{{ route('coaster.admin.users.add') }}">Add Another User</a><br />
        <a href="{{ route('coaster.admin.users') }}">Back To User List</a>
    </p>

    @else

    {!! Form::open() !!}

            <!-- user email field -->
    <div class="form-group {!! FormMessage::getErrorClass('email') !!}">
        {!! Form::label('email', 'User Email', ['class' => 'control-label']) !!}
        {!! Form::text('email', Request::input('email'), ['class' => 'form-control']) !!}
        <span class="help-block">{!! FormMessage::getErrorMessage('email') !!}</span>
    </div>

    <!-- user role field -->
    <div class="form-group {!! FormMessage::getErrorClass('role') !!}">
        {!! Form::label('role', 'User Role', ['class' => 'control-label']) !!}
        {!! Form::select('role', $roles, Request::input('role'), ['class' => 'form-control']) !!}
        <span class="help-block">{!! FormMessage::getErrorMessage('role') !!}</span>
    </div>

    <!-- send email -->
    <div class="form-group">
        {!! Form::label('send_email', 'Send Email', ['class' => 'control-label']) !!}
        {!! Form::checkbox('send_email', 1, true, ['class' => 'form-control']) !!}
    </div>

    <!-- submit button -->
    <button type="submit" class="btn btn-primary addButton"><i class="fa fa-plus"></i> &nbsp; Add User</button>

    {!! Form::close() !!}

@endif