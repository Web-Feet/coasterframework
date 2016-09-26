<div class="form-group">
    {!! Form::label($name, $label, ['class' => 'control-label col-sm-2']) !!}
    <div class="col-sm-10">
        <div class="row">
            <div class="col-sm-3"> No of Pictures: <b>{!! count($content) !!}</b></div>
            <div class="col-sm-9"> <a href="{{ route('coaster.admin.gallery.edit', ['pageId' => $_block->getPageId(), 'blockId' => $_block->id]) }}" class="btn btn-default">Edit Gallery</a></div>
        </div>
    </div>
</div>