<h1>Forgotten Password</h1>


@if (isset($success))

    <p class="success">{!! $success !!}</p>

    @else

    {!! Form::open(['url' => Request::url()]) !!}

    <!-- email field -->
    <div class="form-group {!! FormMessage::getErrorClass('email') !!}">
        {!! Form::label('email', 'Email Address', ['class' => 'control-label']) !!}
        {!! Form::text('email', Request::input('email'), ['class' => 'form-control']) !!}
        <span class="help-block">{!! FormMessage::getErrorMessage('email') !!}</span>
    </div>

    <!-- submit button -->
    {!! Form::submit('Send me an email', ['class' => 'btn btn-primary', 'onclick' => 'return validate()']) !!}

    {!! Form::close() !!}

@endif


