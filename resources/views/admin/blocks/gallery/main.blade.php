<div class="form-group">
    {!! Form::label($name, $label, ['class' => 'control-label col-sm-2']) !!}
    <div class="col-sm-10">
        <div class="row">
            <div class="col-sm-3"> No of Pictures: <b>{!! $content->pictures !!}</b></div>
            <div class="col-sm-9"> <a href="{{ route('coaster.admin.gallery.edit', ['pageId' => $page_id, 'blockId' => $block_id]) }}" class="btn btn-default">Edit Gallery</a></div>
        </div>
    </div>
</div>