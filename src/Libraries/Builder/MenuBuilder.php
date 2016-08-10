<?php namespace CoasterCms\Libraries\Builder;

use CoasterCms\Helpers\Cms\Page\Path;
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
     * @var bool
     */
    protected static $_canonicals;

    /**
     * @param array $menuName
     * @param array $options
     * @return string
     */
    public static function menu($menuName, $options = [])
    {
        $menu = Menu::get_menu($menuName);
        if (!empty($menu)) {
            self::_setOptions($options);
            return self::_buildMenu($menu->items()->get(), 0, 1);
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
        if ($subPages = Page::getChildPages($pageId)) {
            self::_setOptions($options);
            return self::_buildMenu($subPages, $pageId, $subMenuLevel, $subLevels);
        } else {
            return '';
        }
    }

    /**
     * @param array $items
     * @param int $parentPageId
     * @param int $subMenuLevel
     * @param int $subLevels
     * @param array $options
     * @return string
     */
    public static function customMenu($items, $parentPageId = 0, $subMenuLevel = 1, $subLevels = 0, $options = [])
    {
        self::_setOptions($options);
        return self::_buildMenu($items, $parentPageId, $subMenuLevel, $subLevels);
    }

    /**
     * @param array $options
     */
    protected static function _setOptions($options)
    {
        $options = array_merge([
            'view' => 'default',
            'canonicals' => false
        ], $options);
        self::$_view = $options['view'];
        self::$_canonicals = $options['canonicals'];
    }

    /**
     * @param Page[]|MenuItem[] $items
     * @param int $level
     * @param int $subLevels
     * @return string
     */
    protected static function _buildMenu($items, $parentPageId, $level = 1, $subLevels = 0)
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
            $pageId = Path::unParsePageId($pageId);
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

            $pageId = Path::unParsePageId($item->page_id);
            
            $active = ($currentPage->id == $pageId || in_array($pageId, $pageParents));
            $itemData = new MenuItemDetails($item, $active, $parentPageId, self::$_canonicals);

            $subMenu = '';
            $subLevels = $item->sub_levels > 0 ? $item->sub_levels : $defaultSubLevels;
            if ($subLevels > 0) {
                if ($subPages = Page::category_pages($pageId)) {
                    $subMenu = self::_buildMenu($subPages, $item->page_id, $level + 1, $subLevels - 1);
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