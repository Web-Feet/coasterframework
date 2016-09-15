<div class="form-group {{ $field_class }}">
    {!! Form::label($name, $label, ['class' => 'control-label col-sm-2']) !!}
    <div class="col-sm-5">
        {!! Form::select($name, $selectOptions, $submitted_data?:$content, ['style' => 'width: 100%', 'class' => 'form-control chosen-select '.$class]) !!}
    </div>
    <div class="col-sm-5">
        {!! Form::text(str_replace('blockUser', 'blockUserCustom', $name), $customName, ['class' => 'form-control', 'placeholder' => 'Or Add Custom Author']) !!}
    </div>
    <span class="help-block">{{ $field_message }}</span>
</div>