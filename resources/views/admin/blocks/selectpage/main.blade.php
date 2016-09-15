<div class="form-group {{ $field_class }}">
    {!! Form::label($name, $label, ['class' => 'control-label col-sm-2']) !!}
    <div class="col-sm-10">
        {!! Form::hidden(CmsBlockInput::appendName($name, '_exists'), '') !!}
        {!! Form::select($name, $selectOptions, $submitted_data?:$content, ['style' => 'width: 100%', 'class' => 'form-control chosen-select '.$class]) !!}
        <span class="help-block">{{ $field_message }}</span>
    </div>
</div>