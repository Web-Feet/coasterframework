<div class="form-group {{ $field_class }}">
    {!! Form::label($name, $label, ['class' => 'control-label col-sm-2']) !!}
    <div class="col-sm-5">
        {!! Form::hidden($name . '[exists]', 1) !!}
        {!! Form::select($name . '[select][]', $selectOptions, $submitted_data?$submitted_data['select']:$content, ['style' => 'width: 100%', 'class' => 'chosen-select '.$class, 'multiple' => 'multiple']) !!}
    </div>
    <div class="col-sm-5">
        {!! Form::text($name . '[custom]', '', ['class' => 'form-control', 'placeholder' => 'Add new options (comma separated)']) !!}
    </div>
    <span class="help-block">{{ $field_message }}</span>
</div>
