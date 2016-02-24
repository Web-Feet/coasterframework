<?php $link_field_id = str_replace(array('[', ']'), array('_', ''), $name . '[custom]'); ?>

<div class="form-group">

    <div class="row">
        {!! Form::label($name, $label, ['class' => 'control-label col-sm-2']) !!}
        <div class="col-sm-4">
            {!! Form::select($name.'[internal]', $content->options, $content->internal, ['class' => 'form-control internal-link']) !!}
        </div>
        <div class="col-sm-6">
            <div class="input-group">
                {!! Form::text($name.'[custom]', $content->external, ['id' => $link_field_id, 'class' => 'form-control custom-link']) !!}
                <span class="input-group-addon">or</span>
                <span class="input-group-btn">
                    <a href="{!! URL::to(config('coaster::admin.public').'/filemanager/dialog.php?type=2&field_id='.$link_field_id) !!}"
                       class="btn btn-default iframe-btn">Select Doc</a>
                </span>
            </div>
        </div>
    </div>

    <div class="row">
        <div class="col-sm-10 col-sm-offset-2">
            {!! Form::select($name.'[target]', $content->target_options, $content->target, ['class' => 'form-control']) !!}
        </div>
    </div>

</div>