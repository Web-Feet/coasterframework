<?php $class = !empty($content->class) ? $class . ' ' . $content->class : $class; ?>

<div class="form-group {{ $field_class }}">
    {!! Form::label($name, $label, ['class' => 'control-label col-sm-2']) !!}
    <div class="col-sm-10">
        {!! Form::hidden(CmsBlockInput::appendName($name, '_exists'), '') !!}
        {!! Form::select($name.'[]', $content->options, $submitted_data?:$content->selected, ['style' => 'width: 100%', 'class' => 'chosen-select '.$class, 'multiple' => 'multiple']) !!}
        <span class="help-block">{{ $field_message }}</span>
    </div>
</div>