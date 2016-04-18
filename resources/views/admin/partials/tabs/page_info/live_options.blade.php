<h4>Live Options</h4>
{!! CmsBlockInput::make('select', ['name' => 'page_info[live]', 'label' => 'Live ?', 'content' => $page_details->live, 'disabled' => $page_details->disabled]) !!}
<div class="live-date-options">
    {!! CmsBlockInput::make('datetime', ['name' => 'page_info[live_start]', 'label' => 'Live From Date', 'content' => $page_details->live_start, 'disabled' => $page_details->disabled]) !!}
    {!! CmsBlockInput::make('datetime', ['name' => 'page_info[live_end]', 'label' => 'Live Until Date', 'content' => $page_details->live_end, 'disabled' => $page_details->disabled]) !!}
</div>
{!! CmsBlockInput::make('select', ['name' => 'page_info[sitemap]', 'label' => 'Sitemap', 'content' => $page_details->sitemap]) !!}