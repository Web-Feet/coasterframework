{!! CmsBlockInput::make('string', ['name' => 'page_info_lang[name]', 'label' => 'Page Name', 'content' => $page_lang->name, 'disabled' => $disabled_lang]) !!}

<div class="form-group {!! FormMessage::get_class('page_info_lang[url]') !!}">
    {!! Form::label('page_info_lang[url]', 'Page Url:', ['class' => 'control-label col-sm-2']) !!}
    <div class="col-sm-10">
        <div id="url-group" class="input-group">
            @if (!$page->id || $page->link == 0)
                <span class="input-group-addon" id="url-prefix">{{ $urlArray[$urlPrefixPage] }}</span>
            @endif
            <?php $options = []; if ($disabled_lang): $options = ['disabled' => true]; endif; ?>
            {!! Form::text('page_info_lang[url]', urldecode($page_lang->url), ['class' => 'form-control', 'id' => 'page_info_url'] + $options) !!}
            @if (!$page->id || $page->link == 1)
                <span class="input-group-addon link_show">or</span>
                <span class="input-group-btn link_show">
                    <a href="{!! URL::to(config('coaster::admin.public').'/filemanager/dialog.php?type=2&field_id=page_info_url') !!}"
                       class="btn btn-default iframe-btn">Select Doc</a>
                </span>
            @endif
        </div>
        <span class="help-block">{!! FormMessage::get_message('page_info_lang[url]') !!}</span>
    </div>
</div>

@if (!$page->id || $page->link == 0)
    <div id="template_select">
        @if (!$template_select->hidden)
            {!! CmsBlockInput::make('select', ['name' => 'page_info[template]', 'label' => 'Page Template', 'content' => $template_select]) !!}
        @else
            {!! Form::hidden('page_info[template]', $template_select->selected) !!}
        @endif
    </div>
@endif

<script type="text/javascript">
    var urlArray = {!! json_encode($urlArray) !!};
</script>