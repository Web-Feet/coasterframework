<div class="form-group">
    <h4 class="col-sm-12">Live Options</h4>
</div>

{!! CmsBlockInput::make('select', ['name' => 'page_info[live]', 'label' => ($page->link)?'Link Visible ?':'Live ?', 'content' => $liveSelect, 'disabled' => $disabled]) !!}
<div class="live-date-options">
    {!! CmsBlockInput::make('datetime', ['name' => 'page_info[live_start]', 'label' => 'Live From Date', 'content' => $page->live_start, 'disabled' => $disabled]) !!}
    {!! CmsBlockInput::make('datetime', ['name' => 'page_info[live_end]', 'label' => 'Live Until Date', 'content' => $page->live_end, 'disabled' => $disabled]) !!}
</div>
{!! CmsBlockInput::make('select', ['name' => 'page_info[sitemap]', 'label' => 'Sitemap', 'content' => $sitemapSelect, 'disabled' => $disabled]) !!}