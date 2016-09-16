<div class="form-group {!! FormMessage::getErrorClass($name) !!}">
    {!! Form::label($name, $label, ['class' => 'control-label col-sm-2']) !!}
    <div class="col-sm-6">
        {!! Form::select($name . '[select]', $selectOptions, $content->select, ['style' => 'width: 100%', 'class' => 'form-control chosen-select '.$class]) !!}
        <span class="help-block">{!! FormMessage::getErrorMessage($name) !!}</span>
    </div>
    <div class="col-sm-4">
        {!! Form::text($name . '[price]', $content->price, ['class' => 'form-control', 'placeholder' => '&pound;']) !!}
    </div>
</div>