<li id="list_{!! $item->id !!}" data-id="{!! $item->id !!}">
    <div>
        <span class='disclose glyphicon glyphicon-plus-sign'></span>
        {!! $name !!} &nbsp; <span class="custom-name">{{ ($customName = $item->getCustomName()) ? '(Custom Name: ' . $customName . ')' : '' }}</span>
        <span class='pull-right'>
            @if ($permissions['subpage'] == true)
                <span class='sl_numb'>{!! $item->sub_levels !!}</span> Sublevels  &nbsp;
                <i class="sub-levels fa fa-sort-amount-desc itemTooltip" data-name="{!! $name!!}"
                   data-max-sublevel="{!! $menu->max_sublevel !!}" title="Edit Subpage Level"></i> &nbsp;
            @else
                <span class='sl_numb'>{!! $item->sub_levels !!}</span> Sublevels &nbsp;
            @endif
            @if ($permissions['rename'] == true)
                <i class="rename glyphicon glyphicon-pencil itemTooltip" data-name="{!! $name !!}"
                   title="Rename Menu Item"></i>&nbsp;
            @endif
            @if ($permissions['delete'] == true)
                <i class="delete glyphicon glyphicon-trash itemTooltip" data-name="{!! $name !!}"
                   title="Delete Menu Item"></i>&nbsp;
            @endif
        </span>
    </div>
    {!! $leaf !!}
</li>