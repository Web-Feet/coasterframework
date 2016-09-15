<h4>Live Options</h4>

{!! CmsBlockInput::make('select', ['name' => 'page_info[live]', 'label' => ($page->link)?'Link Visible ?':'Live ?', 'content' => $page->live, 'selectOptions' => $liveOptions, 'disabled' => $disabled]) !!}
<div class="live-date-options">
    {!! CmsBlockInput::make('datetime', ['name' => 'page_info[live_start]', 'label' => 'Live From Date', 'content' => \CoasterCms\Helpers\Cms\DateTimeHelper::mysqlToJQuery($page->live_start), 'disabled' => $disabled]) !!}
    {!! CmsBlockInput::make('datetime', ['name' => 'page_info[live_end]', 'label' => 'Live Until Date', 'content' => \CoasterCms\Helpers\Cms\DateTimeHelper::mysqlToJQuery($page->live_end), 'disabled' => $disabled]) !!}
</div>
{!! CmsBlockInput::make('select', ['name' => 'page_info[sitemap]', 'label' => 'Sitemap', 'content' => $page->sitemap, 'selectOptions' => $sitemapOptions, 'disabled' => $disabled]) !!}