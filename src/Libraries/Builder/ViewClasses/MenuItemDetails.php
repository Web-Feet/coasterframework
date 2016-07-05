<?php namespace CoasterCms\Libraries\Builder\ViewClasses;

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
     * MenuItemDetails constructor.
     * @param MenuItem $item
     * @param bool $active
     */
    public function __construct(MenuItem $item, $active = false)
    {
        $this->item = $item;
        $this->page = Page::preload($item->page_id);

        $this->active = $active;

        $this->name = $item->custom_name ?: PageLang::name($item->page_id);
        $this->url = $this->page->link ? PageLang::url($item->page_id) : PageLang::full_url($item->page_id);
    }

}