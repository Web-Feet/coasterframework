<li id='list_{!! $page->id !!}'>
    <div class='{!! $page->type !!}'>
        <span class='disclose glyphicon glyphicon-plus-sign'></span>
        {!! $page->name !!}
        <span class="pull-right">
            @if (!empty($page->blog) && $page->permissions['blog'])
                {!! HTML::link($page->blog, '', ['class' => 'glyphicon glyphicon-share itemTooltip', 'title' => 'WordPress Admin', 'target' => '_blank']) !!}
            @endif
            @if ($page->number_of_forms > 0 && $page->permissions['forms'])
                {!! HTML::link(URL::to(config('coaster::admin.url').'/forms/list/'.$page->id), '', ['class' => 'glyphicon glyphicon-inbox itemTooltip', 'title' => 'View Form Submissions']) !!}
            @endif
            @if ($page->number_of_galleries > 0 && $page->permissions['galleries'])
                {!! HTML::link(URL::to(config('coaster::admin.url').'/gallery/list/'.$page->id), '', ['class' => 'glyphicon glyphicon-picture itemTooltip', 'title' => 'Edit Gallery']) !!}
            @endif
            @if (!empty($page->group) && $page->permissions['group'])
                {!! HTML::link(URL::to(config('coaster::admin.url').'/groups/pages/'.$page->group), '', ['class' => 'glyphicon glyphicon-list-alt itemTooltip', 'title' => 'Manage Items']) !!}
            @endif
            {!! HTML::link($page->preview_link, '', ['class' => 'glyphicon glyphicon-eye-open itemTooltip', 'title' => ($page->type=='type_hidden')?'Preview':'View Page', 'target' => '_blank']) !!}
            @if ($page->permissions['add'] == true && empty($page->link))
                {!! HTML::link(URL::current().'/add/'.$page->id, '', ['class' => 'glyphicon glyphicon-plus itemTooltip addPage', 'title' => 'Add '.(empty($page->group)?'Subpage':'Item'), 'data-page' => $page->id]) !!}
            @endif
            @if ($page->permissions['edit'] == true)
                {!! HTML::link(URL::current().'/edit/'.$page->id, '', ['class' => 'glyphicon glyphicon-pencil itemTooltip', 'title' => 'Edit Page']) !!}
            @endif
            @if ($page->permissions['delete'] == true)
                <a href="javascript:void(0)" class="delete glyphicon glyphicon-trash itemTooltip"
                   data-name="{!! $page->name !!}" title="Delete Page"></a>
            @endif
        </span>
    </div>
    {!! $page->leaf !!}
</li>