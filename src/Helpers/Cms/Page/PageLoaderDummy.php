<?php namespace CoasterCms\Helpers\Cms\Page;

use CoasterCms\Models\Page;
use CoasterCms\Models\PageLang;
use CoasterCms\Models\Theme;

class PageLoaderDummy extends PageLoader
{

    /**
     * PageLoaderDummy constructor.
     * @param string $themeName
     */
    public function __construct($themeName = '')
    {
        parent::__construct();
        $this->theme = $this->theme ?: $themeName;
    }

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
        $page->setRelation('pageCurrentLang', $page_lang);
        $this->pageLevels = [$page];
    }

}