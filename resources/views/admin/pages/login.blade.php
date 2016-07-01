{!! Form::open(['url' => Request::url()]) !!}

        <!-- username field -->
<div class="form-group {!! FormMessage::getErrorClass('username') !!}">
    {!! Form::label('username', 'Username', ['class' => 'control-label']) !!}
    {!! Form::text('username', Request::input('username'), ['class' => 'form-control']) !!}
    <span class="help-block">{!! FormMessage::getErrorMessage('username') !!}</span>
</div>

<!-- password field -->
<div class="form-group {!! FormMessage::getErrorClass('password') !!}">
    {!! Form::label('password', 'Password', ['class' => 'control-label']) !!}
    {!! Form::password('password', ['class' => 'form-control']) !!}
</div>

<!-- remember field -->
<div class="form-group">
    <div class="checkbox">
        <label>
            {!! Form::checkbox('remember', 'yes', false) !!}
            Remember Me
        </label>
    </div>
</div>

{!! Form::hidden('_token', csrf_token()) !!}
{!! Form::hidden('login_path', Request::input('login_path')) !!}

        <!-- submit button -->
<p>{!! Form::submit('Login', ['class' => 'btn btn-primary']) !!}</p>

{!! Form::close() !!}

<div class="row">
    <div class="col-sm-12">
        <a href="{!! URL::to(config('coaster::admin.url').'/forgotten_password') !!}">Forgotten password?</a>
    </div>
</div>



