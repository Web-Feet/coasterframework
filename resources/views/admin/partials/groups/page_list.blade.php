<div class="table-responsive">
    <table class="table table-striped">

        <thead>
        <tr>
            <th>{!! $item_name !!}</th>
            @foreach ($blocks as $block)
                <th>
                    {!! $block->label !!}
                </th>
            @endforeach
            <th>Actions</th>
        </tr>
        </thead>

        <tbody>
        @foreach ($pages as $page)
            <tr id='list_{!! $page->id !!}' data-name='{!! $page->name !!}'>
                <td>
                    {!! $page->name !!}
                </td>
                @foreach ($page->col as $col)
                    <td>
                        {!! $col !!}
                    </td>
                @endforeach
                <td>
                    @if ($can_edit[$page->id])
                        <a href="{{ route('coaster.admin.pages.edit', ['pageId' => $page->id]) }}" class="glyphicon glyphicon-pencil itemTooltip" title="{{ 'Edit '.$item_name }}"></a>
                    @endif
                    @if ($can_delete[$page->id])
                        <i class="delete glyphicon glyphicon-trash itemTooltip" data-name="{!! $page->name !!}"
                           title="Delete {{ $item_name }}"></i>
                    @endif
                </td>
            </tr>
        @endforeach
        </tbody>

    </table>
</div>