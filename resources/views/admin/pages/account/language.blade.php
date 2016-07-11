<h1>Change Language</h1>

{!! Form::open() !!}

@if ($saved)
    <p class="text-success">Current language updated.</p>
@endif

<!-- confirm password field -->
<div class="form-group {!! FormMessage::getErrorClass('language') !!}">
    {!! Form::label('language', 'Language', ['class' => 'control-label']) !!}
    {!! Form::select('language', $languages, $language, ['class' => 'form-control']) !!}
    <span class="help-block">{!! FormMessage::getErrorMessage('language') !!}</span>
</div>

<!-- submit button -->
{!! Form::submit('Change', ['class' => 'btn btn-primary']) !!}

{!! Form::close() !!}