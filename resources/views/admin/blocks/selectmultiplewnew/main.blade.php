<div class="form-group {{ $field_class }}">
    {!! Form::label($name, $label, ['class' => 'control-label col-sm-2']) !!}
    <div class="col-sm-5">
        {!! Form::hidden(CmsBlockInput::appendName($name, '_exists'), '') !!}
        {!! Form::select($name.'[]', $selectOptions, $submitted_data?:$content, ['style' => 'width: 100%', 'class' => 'chosen-select '.$class, 'multiple' => 'multiple']) !!}
    </div>
    <div class="col-sm-5">
        {!! Form::text(str_replace('blockSmN', 'blockSmNCustom', $name), '', ['class' => 'form-control', 'placeholder' => 'Add new options (comma separated)']) !!}
    </div>
    <span class="help-block">{{ $field_message }}</span>
</div>