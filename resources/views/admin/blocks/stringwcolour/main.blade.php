<div class="form-group {!! FormMessage::getErrorClass($name) !!}">
    {!! Form::label($name, $label, ['class' => 'control-label col-sm-2']) !!}
    <div class="col-sm-6">
        {!! Form::text($name . '[text]', $content->text, ['class' => 'form-control ' . $class]) !!}
        <span class="help-block">{!! FormMessage::getErrorMessage($name) !!}</span>
    </div>
    <div class="col-sm-4">
        {!! Form::select($name . '[colour]', $selectOptions, $content->colour, ['class' => 'form-control ' . $selectClass]) !!}
    </div>
</div>