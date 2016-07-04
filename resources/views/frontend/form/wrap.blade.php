{!! Form::open($form_attrs) !!}
{!! Form::hidden('block_id', $block_id) !!}
{!! Form::hidden('page_id', $page_id) !!}
{!! $form_fields !!}
{!! Form::close() !!}