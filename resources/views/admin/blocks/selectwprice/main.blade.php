<?php $class = !empty($content->class) ? $class . ' ' . $content->class : $class; ?>

<div class="form-group {!! FormMessage::getErrorClass($name) !!}">
    {!! Form::label($name, $label, ['class' => 'control-label col-sm-2']) !!}
    <div class="col-sm-6">
        {!! Form::select($name, $content->options, $content->selected, ['style' => 'width: 100%', 'class' => 'form-control chosen-select '.$class]) !!}
        <span class="help-block">{!! FormMessage::getErrorMessage($name) !!}</span>
    </div>
    <div class="col-sm-4">
        {!! Form::text(str_replace('blocksp', 'blocksp_price', $name), $content->price, ['class' => 'form-control', 'placeholder' => '&pound;']) !!}
    </div>
</div>