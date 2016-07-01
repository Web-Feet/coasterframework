<?php namespace CoasterCms\Libraries\Builder;

use CoasterCms\Models\Menu;
use CoasterCms\Models\MenuItem;
use CoasterCms\Models\Page;
use CoasterCms\Models\PageLang;
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
            $defaultOptions = [
                'view' => 'default'
            ];
            $options = array_merge($defaultOptions, $options);
            self::setView($options['view']);
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
            $defaultOptions = [
                'view' => 'default'
            ];
            $options = array_merge($defaultOptions, $options);
            self::setView($options['view']);
            return self::_buildMenu($subPages, $subMenuLevel, $subLevels);
        } else {
            return '';
        }
    }

    /**
     * @param array $items
     * @param int $subMenuLevel
     * @param int $subLevels
     * @return string
     */
    public static function customMenu($items, $subMenuLevel = 1, $subLevels = 0)
    {
        return self::_buildMenu($items, $subMenuLevel, $subLevels);
    }

    /**
     * @param array $items
     * @param int $level
     * @param int $subLevels
     * @return string
     */
    protected static function _buildMenu($items, $level = 1, $subLevels = 0)
    {
        // remove pages that aren't live and convert page models to menu items
        foreach ($items as $k => $item) {
            if (is_a($item, Page::class)) {
                $items[$k] = new MenuItem;
                $items[$k]->page_id = $item->id;
                $items[$k]->sub_levels = 0;
                $items[$k]->custom_name = '';
                $items[$k]->page = $item;
            } else {
                $string = explode(',', $items[$k]->page_id);
                $items[$k]->page = Page::preload($string[0]);
            }
            if (empty($items[$k]->page) || !$items[$k]->page->is_live()) {
                unset($items[$k]);
            }
        }

        $pageParents = [];
        $pageLevels = PageBuilder::getData('pageLevels')?:[];
        foreach ($pageLevels as $k => $parentPage) {
            if ($k > 0) $pageParents[] = $parentPage->page_id;
        }
        $currentPage = PageBuilder::getData('page')?:new Page;

        $total = count($items);
        $menuItems = '';
        $itemData = new \stdClass;
        $defaultSubLevels = $subLevels;
        if (is_a($items, Collection::class)) {
            $items = $items->all();
        }
        $items = array_values($items);
        foreach ($items as $count => $item) {
            $isFirst = ($count == 0);
            $isLast = ($count == $total - 1);

            $custom_name = trim($item->custom_name);
            if (!empty($custom_name)) {
                $itemData->name = $custom_name;
            } else {
                $itemData->name = PageLang::name($item->page_id);
            }
            if ($item->page->link == 1) {
                $itemData->url = PageLang::url($item->page_id);
            } else {
                $itemData->url = PageLang::full_url($item->page_id);
            }
            if ($currentPage->id == $item->page_id || in_array($item->page_id, $pageParents)) {
                $itemData->active = true;
            } else {
                $itemData->active = false;
            }

            $subMenu = '';
            $subLevels = $item->sub_levels > 0 ? $item->sub_levels : $defaultSubLevels;
            if ($subLevels > 0) {
                $childPageIds = Page::child_page_ids($item->page->id);
                if ($childPageIds) {
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