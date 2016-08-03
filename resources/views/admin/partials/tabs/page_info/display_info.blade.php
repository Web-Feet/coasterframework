@if (!$page->id || $page->link == 0 || $menus)
    <h4>Display Info</h4>
@endif

@if (!$page->id || $page->link == 0)
    <div id="template_select">
        @if (!$template_select->hidden)
            {!! CmsBlockInput::make('select', ['name' => 'page_info[template]', 'label' => 'Page Template', 'content' => $template_select]) !!}
        @else
            {!! Form::hidden('page_info[template]', $template_select->selected) !!}
        @endif
    </div>
@endif

@if ($menus)
    <div class="form-group">
        {!! Form::label('page_info_other[menus]', 'Show in Menus', ['class' => 'control-label col-sm-2']) !!}
        <div class="col-sm-10">
            @foreach($menus as $menu)
                <div class="form-inline">
                    <label>
                        <?php $options = []; if (!$can_publish) :$options['disabled'] = true; endif; ?>
                        {!! Form::checkbox('page_info_other[menus]['.$menu->id.']', 1, $menu->in_menu, ['class' => 'form-control'] + $options) !!}
                        &nbsp; {!! $menu->label !!}
                    </label>
                </div>
            @endforeach
        </div>
    </div>
@endif