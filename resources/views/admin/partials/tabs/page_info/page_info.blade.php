{!! CmsBlockInput::make('string', ['name' => 'page_info_lang[name]', 'label' => 'Page Name', 'content' => $page_details->name, 'disabled' => $page_details->disabled]) !!}

<div class="form-group {!! FormMessage::get_class('page_info_lang[url]') !!}">
    {!! Form::label('page_info_lang[url]', 'Page Url:', ['class' => 'control-label col-sm-2']) !!}
    <div class="col-sm-10">
        <div id="url-group" class="input-group">
            @if (empty($page_details->link))
                <span class="input-group-addon" id="url-prefix">{{ $urlArray[$page_details->parent] }}</span>
            @endif
            <?php $options = []; if ($page_details->disabled): $options = ['disabled' => true]; endif; ?>
            {!! Form::text('page_info_lang[url]', urldecode($page_details->url), ['class' => 'form-control', 'id' => 'page_info_url'] + $options) !!}
            @if ((!isset($page_details->link) || $page_details->link == 1))
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

@if ((!isset($page_details->link) || $page_details->link == 0))
    <div id="template_select">
        @if (!$page_details->page_template->hidden)
            {!! CmsBlockInput::make('select', ['name' => 'page_info[template]', 'label' => 'Page Template', 'content' => $page_details->page_template]) !!}
        @else
            {!! Form::hidden('page_info[template]', $page_details->page_template->selected) !!}
        @endif
    </div>
@endif

<script type="text/javascript">
    var urlArray = {!!  json_encode($urlArray) !!};
</script>