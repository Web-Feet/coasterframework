<li id='list_{!! $page->id !!}'>
    <div class='{!! $li_info->type !!}'>
        <span class='disclose glyphicon glyphicon-plus-sign'></span>
        {!! $page_lang->name !!} {{ $li_info->group ? ' &nbsp; (Group: ' . $li_info->group->name . ')' : '' }}
        <span class="pull-right">
            @if (!empty($li_info->blog) && $permissions['blog'])
                {!! HTML::link($li_info->blog, '', ['class' => 'glyphicon glyphicon-share itemTooltip', 'title' => 'WordPress Admin', 'target' => '_blank']) !!}
            @endif
            @if ($li_info->number_of_forms > 0 && $permissions['forms'])
                <a href="{{ route('coaster.admin.forms.list', ['pageId' => $page->id]) }}" class="glyphicon glyphicon-inbox itemTooltip" title="View Form Submissions"></a>
            @endif
            @if ($li_info->number_of_galleries > 0 && $permissions['galleries'])
                <a href="{{ route('coaster.admin.gallery.list', ['pageId' => $page->id]) }}" class="glyphicon glyphicon-picture itemTooltip" title="Edit Gallery"></a>
            @endif
            @if ($page->group_container && $permissions['group'])
                <a href="{{ route('coaster.admin.groups.pages', ['groupId' => $page->group_container]) }}" class="glyphicon glyphicon-list-alt itemTooltip" title="Manage Items"></a>
            @endif
            {!! HTML::link($li_info->preview_link, '', ['class' => 'glyphicon glyphicon-eye-open itemTooltip', 'title' => ($li_info->type=='type_hidden')?'Preview':'View Page', 'target' => '_blank']) !!}
            @if ($permissions['add'] == true && empty($page->link))
                <a href="{{ route('coaster.admin.pages.add', ['pageId' => $page->group_container?0:$page->id, 'groupId' => $page->group_container?:null]) }}" class="glyphicon glyphicon-plus itemTooltip addPage" title="{{ 'Add '.($page->group_container?'Group Page':'Sub Page') }}"></a>
            @endif
            @if ($permissions['edit'] == true)
                <a href="{{ route('coaster.admin.pages.edit', ['pageId' => $page->id]) }}" class="glyphicon glyphicon-pencil itemTooltip" title="Edit Page"></a>
            @endif
            @if ($permissions['delete'] == true)
                <a href="javascript:void(0)" class="delete glyphicon glyphicon-trash itemTooltip"
                   data-name="{!! $page_lang->name !!}" title="Delete Page"></a>
            @endif
        </span>
    </div>
    {!! $li_info->leaf !!}
</li>