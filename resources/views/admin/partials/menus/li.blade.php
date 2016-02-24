<li id='list_{!! $item->id !!}' xmlns="http://www.w3.org/1999/html">
    <div>
        {!! $item->name !!} <span class="custom-name">{!! $item->custom_name !!}</span>
        <span class='pull-right'>
            @if ($item->permissions['subpage'] == true)
                <span class='sl_numb'>{!! $item->sub_levels !!}</span> Sublevels  &nbsp;
                <i class="editsub glyphicon glyphicon-signal itemTooltip" data-name="{!! $item->name !!}"
                   data-max-sublevel="{!! $item->max_sublevel !!}" title="Edit Subpage Level"></i> &nbsp;
            @else
                <span class='sl_numb'>{!! $item->sub_levels !!}</span> Sublevels &nbsp;
            @endif
            @if ($item->permissions['rename'] == true)
                <i class="rename glyphicon glyphicon-pencil itemTooltip" data-name="{!! $item->name !!}"
                   title="Rename Menu Item"></i>&nbsp;
            @endif
            @if ($item->permissions['delete'] == true)
                <i class="delete glyphicon glyphicon-trash itemTooltip" data-name="{!! $item->name !!}"
                   title="Delete Menu Item"></i>&nbsp;
            @endif
        </span>
    </div>
</li>