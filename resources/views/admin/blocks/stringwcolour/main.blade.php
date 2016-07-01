<div class="form-group {!! FormMessage::getErrorClass($name) !!}">
    {!! Form::label($name, $label, ['class' => 'control-label col-sm-2']) !!}
    <div class="col-sm-6">
        {!! Form::text($name, $content->text, ['class' => 'form-control ' . $class]) !!}
        <span class="help-block">{!! FormMessage::getErrorMessage($name) !!}</span>
    </div>
    <div class="col-sm-4">
        {!! Form::select(str_replace('blockc', 'blockc_colour', $name), $content->options, $content->colour, ['class' => 'form-control ' . $content->class]) !!}
    </div>
</div>