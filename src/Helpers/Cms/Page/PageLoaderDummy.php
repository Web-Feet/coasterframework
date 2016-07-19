<?php namespace CoasterCms\Helpers\Cms\Page;

use CoasterCms\Models\Page;
use CoasterCms\Models\PageLang;

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