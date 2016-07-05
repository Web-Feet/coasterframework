<?php namespace CoasterCms\Libraries\Builder;

use CoasterCms\Libraries\Builder\ViewClasses\MenuItemDetails;
use CoasterCms\Models\Menu;
use CoasterCms\Models\MenuItem;
use CoasterCms\Models\Page;
use Illuminate\Support\Collection;
use View;

class MenuBuilder
{
    /**
     * @var string
     */
    protected static $_view;

    /**
     * @param string $view
     */
    public static function setView($view)
    {
        self::$_view = $view;
    }

    /**
     * @param array $menuName
     * @param array $options
     * @return string
     */
    public static function menu($menuName, $options = [])
    {
        $menu = Menu::get_menu($menuName);
        if (!empty($menu)) {
            self::_mergeOptionsAndSetView($options);
            return self::_buildMenu($menu->items()->get(), 1);
        } else {
            return '';
        }
    }

    /**
     * @param int $pageId
     * @param int $subMenuLevel
     * @param int $subLevels
     * @param array $options
     * @return string
     */
    public static function pageMenu($pageId, $subMenuLevel = 1, $subLevels = 0, $options = [])
    {
        $childPageIds = Page::child_page_ids($pageId);
        if (!empty($childPageIds)) {
            $subPages = Page::get_ordered_pages($childPageIds);
            self::_mergeOptionsAndSetView($options);
            return self::_buildMenu($subPages, $subMenuLevel, $subLevels);
        } else {
            return '';
        }
    }

    /**
     * @param array $items
     * @param int $subMenuLevel
     * @param int $subLevels
     * @param array $options
     * @return string
     */
    public static function customMenu($items, $subMenuLevel = 1, $subLevels = 0, $options = [])
    {
        self::_mergeOptionsAndSetView($options);
        return self::_buildMenu($items, $subMenuLevel, $subLevels);
    }

    /**
     * @param array $options
     */
    protected static function _mergeOptionsAndSetView(&$options)
    {
        $defaultOptions = [
            'view' => 'default'
        ];
        $options = array_merge($defaultOptions, $options);
        self::setView($options['view']);
    }

    /**
     * @param Page[]|MenuItem[] $items
     * @param int $level
     * @param int $subLevels
     * @return string
     */
    protected static function _buildMenu($items, $level = 1, $subLevels = 0)
    {
        // convert page models to menu items and remove non-live pages
        foreach ($items as $k => $item) {
            if (is_a($item, Page::class)) {
                $pageId = $item->id;
                $items[$k] = new MenuItem;
                $items[$k]->page_id = $item->id;
                $items[$k]->sub_levels = 0;
                $items[$k]->custom_name = '';
            } else {
                $pageId = $item->page_id;
            }
            $page = Page::preload($pageId);
            if (!$page || !$page->is_live()) {
                unset($items[$k]);
            }
        }

        $pageParents = [];
        $pageLevels = PageBuilder::getData('pageLevels')?:[];
        foreach ($pageLevels as $k => $parentPage) {
            if ($k > 0) {
                $pageParents[] = $parentPage->page_id;
            }
        }
        $currentPage = PageBuilder::getData('page')?:new Page;

        $total = count($items);
        $menuItems = '';
        $defaultSubLevels = $subLevels;
        if (is_a($items, Collection::class)) {
            $items = $items->all();
        }
        $items = array_values($items);
        foreach ($items as $count => $item) {
            $isFirst = ($count == 0);
            $isLast = ($count == $total - 1);
            
            $active = ($currentPage->id == $item->page_id || in_array($item->page_id, $pageParents));
            $itemData = new MenuItemDetails($item, $active);

            $subMenu = '';
            $subLevels = $item->sub_levels > 0 ? $item->sub_levels : $defaultSubLevels;
            if ($subLevels > 0) {
                if ($childPageIds = Page::child_page_ids($item->page_id)) {
                    $subPages = Page::get_ordered_pages($childPageIds);
                    $subMenu = self::_buildMenu($subPages, $level + 1, $subLevels - 1);
                }
            }

            if (!empty($subMenu)) {
                $menuItems .= self::_getRenderedView('submenu_' . $level, ['item' => $itemData, 'items' => $subMenu, 'is_first' => $isFirst, 'is_last' => $isLast, 'count' => $count + 1, 'total' => $total, 'level' => $level, 'further_levels' => $subLevels]);
            } else {
                $menuItems .= self::_getRenderedView('item', ['item' => $itemData, 'is_first' => $isFirst, 'is_last' => $isLast, 'count' => $count, 'total' => $total, 'level' => $level]);
            }
        }

        if ($level == 1) {
            return self::_getRenderedView('menu', ['items' => $menuItems]);
        } else {
            return $menuItems;
        }
    }

    /**
     * @param string $viewPath
     * @param array $data
     * @return string
     */
    protected static function _getRenderedView($viewPath, $data = [])
    {
        $viewPath = 'themes.' . PageBuilder::getData('theme') . '.menus.' . self::$_view . '.' . $viewPath;
        if (View::exists($viewPath)) {
            return View::make($viewPath, $data)->render();
        } else {
            return 'View not found (' . $viewPath . ')';
        }
    }

}