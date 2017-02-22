<li id="list_p{{ $page->id }}" data-id="{{ $item->id }}" data-page-id="{{ $page->id }}" {!! $leaf ? 'class="mjs-nestedSortable-branch mjs-nestedSortable-collapsed"' : '' !!}>
    <div>
        <span class='disclose glyphicon glyphicon-plus-sign'></span>
        {!! $name !!} &nbsp; <span class="custom-name">{{ ($customName = $item->getCustomName($page->id)) ? '(Custom Name: ' . $customName . ')' : '' }}</span>
        <span class='pull-right'>
            @if ($permissions['rename'] == true)
                <i class="rename glyphicon glyphicon-pencil itemTooltip" data-name="{!! $name !!}" title="Rename Menu Item"></i>&nbsp;
            @endif
        </span>
    </div>
    {!! $leaf !!}
</li>