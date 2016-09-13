<?php namespace CoasterCms\Libraries\Builder\ViewClasses;

use CoasterCms\Helpers\Cms\Page\Path;
use CoasterCms\Models\Page;
use CoasterCms\Models\PageLang;

class PageDetails
{
    /**
     * @var string
     * @deprecated
     */
    public $full_url;

    /**
     * @var string
     * @deprecated
     */
    public $full_name;

    /**
     * @var string
     */
    public $urlSegment;

    /**
     * @var string
     */
    public $fullName;

    /**
     * @var string
     */
    public $url;

    /**
     * @var string
     */
    public $name;

    /**
     * @var Page
     */
    public $page;

    /**
     * @var PageLang
     */
    public $pageLang;

    /**
     * PageDetails constructor.
     * @param int $pageId
     * @param int $groupContainerPageId
     * @param \stdClass $alt
     */
    public function __construct($pageId, $groupContainerPageId = 0, $alt = null)
    {
        if (!$alt) {
            $alt = new \stdClass;
            $alt->fullUrl = '';
            $alt->fullName = '';
        }

        $fullPaths = Path::getFullPath($pageId . ($groupContainerPageId ? ',' . $groupContainerPageId : ''));
        $page = Page::preload($pageId);
        $pageLang = PageLang::preload($pageId);
        
        $this->urlSegment = $pageLang->url;
        $this->url = $fullPaths->fullUrl ?: $alt->fullUrl;

        $this->name = $pageLang->name;
        $this->fullName = $fullPaths->fullName ?: $alt->fullName;

        $this->full_name = $this->fullName;
        $this->full_url = $this->url;

        $this->page = $page;
        $this->pageLang = $pageLang;
    }

}