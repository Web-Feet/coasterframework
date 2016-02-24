<div class="form-group">
    {!! Form::label($name, $label, ['class' => 'control-label col-sm-2']) !!}
    <div class="col-sm-10">
        <div class="row">
            <div class="col-sm-3"> No of Pictures: <b>{!! $content->pictures !!}</b></div>
            <div class="col-sm-9"> {!! HTML::link(URL::to(config('coaster::admin.url').'/gallery/edit/'.$page_id.'/'.$block_id), 'Edit Gallery', ['class' => 'btn btn-default']) !!}</div>
        </div>
    </div>
</div>