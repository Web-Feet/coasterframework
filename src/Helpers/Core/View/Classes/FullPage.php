<?php namespace CoasterCms\Helpers\Core\View\Classes;

use CoasterCms\Models\Page;
use CoasterCms\Models\PageLang;

class FullPage
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
     * @var bool
     */
    public $active;

    /**
     * @var Page
     */
    public $page;

    /**
     * @var PageLang
     */
    public $pageLang;

    /**
     * FullPage constructor.
     * @param Page $page
     * @param PageLang $pageLang
     * @param string $groupContainerPath
     */
    public function __construct(Page $page, PageLang $pageLang, $groupContainerPath = '')
    {
        $fullPaths = PageLang::get_full_path($page->id . $groupContainerPath);
        
        $this->urlSegment = $pageLang->url;
        $this->url = $fullPaths->full_url;

        $this->name = $pageLang->name;
        $this->fullName = $fullPaths->full_name;

        $this->full_name = $this->fullName;
        $this->full_url = $this->url;

        $this->page = $page;
        $this->pageLang = $pageLang;
    }

}