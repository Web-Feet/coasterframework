<?php namespace CoasterCms\Libraries\Builder\ViewClasses;

use CoasterCms\Helpers\Cms\Page\Path;
use CoasterCms\Models\MenuItem;
use CoasterCms\Models\Page;
use CoasterCms\Models\PageLang;

class MenuItemDetails
{
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
     * @var MenuItem
     */
    public $item;

    /**
     * @var int
     */
    public $parentPageId;

    /**
     * MenuItemDetails constructor.
     * @param MenuItem $item
     * @param bool $active
     * @param int $parentPageId
     * @param bool $canonicals
     */
    public function __construct(MenuItem $item, $active = false, $parentPageId = 0, $canonicals = false)
    {
        $pageId = Path::unParsePageId($item->page_id);
        $parsedPageId = Path::parsePageId($item->page_id, $parentPageId);

        $this->item = $item;
        $this->page = Page::preload($item->page_id);
        $this->parentPageId = $parentPageId;

        $this->active = $active;

        $this->name = $item->custom_name ?: PageLang::name($pageId);
        $this->url = ($this->page && $this->page->link) ? PageLang::url($item->page_id) : Path::getFullUrl($parsedPageId);
    }

}