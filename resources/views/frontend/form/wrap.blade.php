{!! Form::open($formAttributes) !!}
{!! Form::hidden('block_id', $blockId) !!}
{!! Form::hidden('page_id', PageBuilder::pageId($useReal)) !!}
@if ($honeyPot)
{!! Form::text('coaster_check', '', ['class' => 'hidden']) !!}
@endif
{!! $formView !!}
{!! Form::close() !!}