<div class="form-group {{ $field_class }}">
    {!! Form::label($name, $label, ['class' => 'control-label col-sm-2']) !!}
    <div class="col-sm-10">
        {!! Form::select($name, $content->pages_array, $submitted_data?:$content->selected, ['class' => 'form-control '.$class]) !!}
        <span class="help-block">{{ $field_message }}</span>
    </div>
</div>