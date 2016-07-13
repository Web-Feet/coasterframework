<li id='list_{!! $page->id !!}'>
    <div class='{!! $page->type !!}'>
        <span class='disclose glyphicon glyphicon-plus-sign'></span>
        {!! $page->name !!}
        <span class="pull-right">
            @if (!empty($page->blog) && $page->permissions['blog'])
                {!! HTML::link($page->blog, '', ['class' => 'glyphicon glyphicon-share itemTooltip', 'title' => 'WordPress Admin', 'target' => '_blank']) !!}
            @endif
            @if ($page->number_of_forms > 0 && $page->permissions['forms'])
                <a href="{{ route('coaster.admin.forms.list', ['pageId' => $page->id]) }}" class="glyphicon glyphicon-inbox itemTooltip" title="View Form Submissions"></a>
            @endif
            @if ($page->number_of_galleries > 0 && $page->permissions['galleries'])
                <a href="{{ route('coaster.admin.gallery.list', ['pageId' => $page->id]) }}" class="glyphicon glyphicon-picture itemTooltip" title="Edit Gallery"></a>
            @endif
            @if (!empty($page->group) && $page->permissions['group'])
                <a href="{{ route('coaster.admin.groups.pages', ['groupId' => $page->group]) }}" class="glyphicon glyphicon-list-alt itemTooltip" title="Manage Items"></a>
            @endif
            {!! HTML::link($page->preview_link, '', ['class' => 'glyphicon glyphicon-eye-open itemTooltip', 'title' => ($page->type=='type_hidden')?'Preview':'View Page', 'target' => '_blank']) !!}
            @if ($page->permissions['add'] == true && empty($page->link))
                <a href="{{ route('coaster.admin.pages.add', ['pageId' => $page->id]) }}" class="glyphicon glyphicon-plus itemTooltip addPage" title="{{ 'Add '.(empty($page->group)?'Subpage':'Item') }}"></a>
            @endif
            @if ($page->permissions['edit'] == true)
                <a href="{{ route('coaster.admin.pages.edit', ['pageId' => $page->id]) }}" class="glyphicon glyphicon-pencil itemTooltip" title="Edit Page"></a>
            @endif
            @if ($page->permissions['delete'] == true)
                <a href="javascript:void(0)" class="delete glyphicon glyphicon-trash itemTooltip"
                   data-name="{!! $page->name !!}" title="Delete Page"></a>
            @endif
        </span>
    </div>
    {!! $page->leaf !!}
</li>