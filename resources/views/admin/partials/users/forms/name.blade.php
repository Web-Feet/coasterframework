{!! Form::open() !!}

<div class="form-group {!! FormMessage::getErrorClass('name') !!}">
    {!! Form::label('name', 'Account Alias', ['class' => 'control-label']) !!}
    {!! Form::text('name', $user->name, ['class' => 'form-control']) !!}
    <span class="help-block">{!! FormMessage::getErrorMessage('name') !!}</span>
</div>

{!! Form::submit('Update', ['class' => 'btn btn-primary']) !!}

{!! Form::close() !!}