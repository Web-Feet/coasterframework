<?php

namespace CoasterCms\Libraries\Builder;

use CoasterCms\Facades\PageBuilder;
use CoasterCms\Helpers\Cms\Page\Path;
use CoasterCms\Libraries\Builder\ViewClasses\MenuItemDetails;
use CoasterCms\Models\Menu;
use CoasterCms\Models\MenuItem;
use CoasterCms\Models\Page;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\View;

class MenuBuilder
{

    /**
     * @var MenuTree
     */
    protected $_tree;

    /**
     * @var int
     */
    public $rootPageId;

    /**
     * @var MenuItem[]
     */
    public $rootItems;

    /**
     * @var int
     */
    public $subLevels;

    /**
     * @var int
     */
    public $startLevel;

    /**
     * @var array
     */
    public $options;

    /**
     * @var array
     */
    public $activePageId;

    /**
     * @var
     */
    public $activeParentIds;

    /**
     * @param string $menuName
     * @param array $options
     * @return string
     */
    public static function menu($menuName, $options = [])
    {
        if ($menu = Menu::preload($menuName)) {
            return (new static($menu->items()->get(), 0, 0, 1, ['menu' => $menu] + $options))->render();
        } else {
            return '';
        }
    }

    /**
     * @param int $parentPageId
     * @param int $startLevel
     * @param int $subLevels
     * @param array $options
     * @return string
     */
    public static function pageMenu($parentPageId, $startLevel = 1, $subLevels = 0, $options = [])
    {
        if ($subPages = Page::getChildPages($parentPageId)) {
            return (new static($subPages, $parentPageId, $startLevel, $subLevels, $options))->render();
        } else {
            return '';
        }
    }

    /**
     * @param Page[]|MenuItem[]|Collection $items
     * @param int $parentPageId
     * @param int $startLevel
     * @param int $subLevels
     * @param array $options
     * @return string
     */
    public static function customMenu($items, $parentPageId = 0, $startLevel = 1, $subLevels = 0, $options = [])
    {
        return (new static($items, $parentPageId, $startLevel, $subLevels, $options))->render();
    }

    /**
     * MenuBuilder constructor.
     * @param Page[]|MenuItem[]|Collection $menuItems
     * @param int $rootPageId
     * @param int $subLevels
     * @param int $startLevel
     * @param array $options
     */
    public function __construct($menuItems, $rootPageId = 0, $subLevels = 0, $startLevel = 1, $options = [])
    {
        if (is_a($menuItems, Collection::class)) {
            $menuItems = $menuItems->all();
        }

        $this->rootPageId = $rootPageId;
        $this->rootItems = $this->_convertPagesToItems($menuItems);
        $this->subLevels = $subLevels;
        $this->startLevel = $startLevel;
        $this->_tree = new MenuTree();

        $this->options = array_merge([
            'view' => 'default',
            'canonicals' => config('coaster::frontend.canonicals')
        ], $options);
    }

    /**
     * @return string
     */
    public function render()
    {
        $menuItems = $this->_buildMenuItems($this->rootItems, $this->rootPageId, $this->startLevel, $this->subLevels);
        return $this->_getView('menu', ['items' => $this->_getSubItemsView($menuItems), 'tree' => $this->_tree], true);
    }

    /**
     * @param Page[]|MenuItem[] $items
     * @param MenuItem|null $baseItem
     * @return MenuItem[]
     */
    protected function _convertPagesToItems($items, $baseItem = null)
    {
        $baseItem = $baseItem ?: new MenuItem;
        foreach ($items as $k => $item) {
            if (is_a($item, Page::class)) {
                $spoofMenuItem = clone $baseItem;
                $spoofMenuItem->page_id = $item->id;
                $spoofMenuItem->sub_levels = null;
                $spoofMenuItem->custom_name = $spoofMenuItem->getCustomName($spoofMenuItem->page_id);
                $items[$k] = $spoofMenuItem;
            }
        }
        return $items;
    }

    /**
     * @param MenuItem[] $items
     * @param int $parentPageId
     * @param int $level
     * @param int $subLevels
     * @return array
     */
    protected function _buildMenuItems($items, $parentPageId, $level = 1, $subLevels = 0)
    {
        // remove deleted pages and hidden ones from array
        $items = $this->_returnExistingLiveItems($items);

        $total = count($items);
        $menuItems = [];

        foreach ($items as $count => $item) {
            $isFirst = ($count == 0);
            $isLast = ($count == $total - 1);

            $pageId = Path::unParsePageId($item->page_id);
            $active = $this->_isActivePage($pageId); // or active parent page
            $itemData = new MenuItemDetails($item, $active, $parentPageId, $this->options['canonicals']);

            $this->_tree->add($itemData);

            $subItemsToRender = [];
            if ($subLevelsToRender = is_null($item->sub_levels) ? $subLevels : $item->sub_levels) {
                if ($subPages = Page::category_pages($pageId)) {
                    $subPages = $this->_convertPagesToItems($subPages, $item);
                    $this->_tree->downLevel($pageId);
                    $subItemsToRender = $this->_buildMenuItems($subPages, $pageId, $level + 1, $subLevelsToRender - 1);
                    $this->_tree->upLevel();
                }
            }

            $view = $subItemsToRender ? 'submenu_' . $level : 'item';
            $menuItems[] = $this->_getView($view, [
                'item' => $itemData,
                'items' => $this->_getSubItemsView($subItemsToRender),
                'is_first' => $isFirst,
                'is_last' => $isLast,
                'count' => $count + 1,
                'total' => $total,
                'level' => $level,
                'further_levels' => $subLevelsToRender,
                'tree' => $this->_tree->newInstance($item->page_id)
            ]);
        }

        return $menuItems;
    }

    /**
     * @param string $viewPath
     * @param array $data
     * @param bool $render
     * @return \Illuminate\Contracts\View\View|string
     */
    protected function _getView($viewPath, $data = [], $render = false)
    {
        $viewPath = 'themes.' . PageBuilder::getData('theme') . '.menus.' . $this->options['view'] . '.' . $viewPath;
        if (View::exists($viewPath)) {
            $view = View::make($viewPath, array_merge($this->options, $data));
            return $render ? $view->render() : $view;
        } else {
            return 'View not found (' . $viewPath . ')';
        }
    }

    /**
     * @param $subItemsToRender
     * @return \Illuminate\Contracts\View\View|string
     */
    protected function _getSubItemsView($subItemsToRender)
    {
        return $subItemsToRender ? View::make('coasterCms::menu.items', ['itemsData' => $subItemsToRender]) : '';
    }

    /**
     * @param MenuItem[] $items
     * @return MenuItem[]
     */
    protected function _returnExistingLiveItems($items)
    {
        foreach ($items as $k => $item) {
            $pageId = Path::unParsePageId($item->page_id);
            if ($item->isHiddenPage($pageId)) {
                unset($items[$k]);
                continue;
            }
            $page = Page::preload($pageId);
            if (!$page->exists || !$page->is_live()) {
                unset($items[$k]);
            }
        }
        return array_values($items);
    }

    /**
     * @param $pageId
     * @return bool
     */
    protected function _isActivePage($pageId)
    {
        $this->_loadActivePages();
        return ($pageId == $this->activePageId || in_array($pageId, $this->activeParentIds));
    }

    /**
     * @param bool $force
     */
    protected function _loadActivePages($force = false)
    {
        if ($force || !isset($this->activePageId)) {
            $this->activeParentIds = [];
            $pageLevels = PageBuilder::getData('pageLevels') ?: [];
            foreach ($pageLevels as $k => $parentPage) {
                if ($k > 0) { // ignore home page
                    $this->activeParentIds[] = $parentPage->page_id;
                }
            }
            $page = PageBuilder::getData('page') ?: new Page;
            $this->activePageId = (int) $page->id;
        }
    }
}
