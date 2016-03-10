<div class="form-group {!! FormMessage::get_class($name) !!}">
    {!! Form::label($name, $label, ['class' => 'control-label col-sm-2']) !!}
    <div class="col-sm-6">
        {!! Form::text($name, $content->text, ['class' => 'form-control ' . $class]) !!}
        <span class="help-block">{!! FormMessage::get_message($name) !!}</span>
    </div>
    <div class="col-sm-4">
        {!! Form::text(str_replace('blockp', 'blockp_price', $name), $content->price, ['class' => 'form-control', 'placeholder' => '&pound;']) !!}
    </div>
</div>