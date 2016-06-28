<?php namespace CoasterCms\Helpers\Core\Page;

use CoasterCms\Models\Language;
use CoasterCms\Models\Page;
use CoasterCms\Models\PageLang;
use CoasterCms\Models\PageVersion;
use CoasterCms\Models\PageVersionSchedule;
use Illuminate\Database\Eloquent\Builder;
use Request;

class PageLoaderDummy extends PageLoader
{

    /**
     * 
     */
    protected function _load()
    {
        $page = new Page;
        $page->id = 0;
        $page_lang = new PageLang;
        $page_lang->name = '';
        $page_lang->url = '';
        $page_lang->live_version = 0;
        $page->page_lang = [$page_lang];
        $this->pageLevels = [$page];
    }

}