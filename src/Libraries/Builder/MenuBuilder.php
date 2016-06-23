<?php namespace CoasterCms\Libraries\Builder;

use CoasterCms\Models\MenuItem;
use CoasterCms\Models\Page;
use CoasterCms\Models\PageLang;
use View;

class MenuBuilder
{
    /**
     * @var string
     */
    private static $_view;

    /**
     * @param string $view
     */
    public static function setView($view)
    {
        self::$_view = $view;
    }

    /**
     * @param string $viewPath
     * @param array $data
     * @return string
     */
    protected static function _getRenderedView($viewPath, $data = [])
    {
        $viewPath = 'themes.' . PageBuilder::$theme . '.menus.' . self::$_view . '.' . $viewPath;
        if (View::exists($viewPath)) {
            return View::make($viewPath, $data)->render();
        } else {
            return 'View not found (' . $viewPath . ')';
        }
    }

    public static function buildMenu($items, $level = 1, $sub_levels = 0)
    {
        // remove pages that aren't live and convert page models to menu items
        foreach ($items as $k => $item) {
            if (get_class($item) == 'CoasterCms\Models\Page') {
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

        $page_parents = array();
        $pageLevels = PageBuilder::$pageLevels?:[];
        foreach ($pageLevels as $k => $parent_page) {
            if ($k > 0) $page_parents[] = $parent_page->page_id;
        }
        $currentPage = PageBuilder::$page?:new Page;

        $total = count($items);
        $menu_items = '';
        $item_data = new \stdClass;
        $default_sub_levels = $sub_levels;
        $items = array_values($items->all());
        foreach ($items as $count => $item) {
            $is_first = ($count == 1);
            $is_last = ($count == $total);

            $custom_name = trim($item->custom_name);
            if (!empty($custom_name)) {
                $item_data->name = $custom_name;
            } else {
                $item_data->name = PageLang::name($item->page_id);
            }
            if ($item->page->link == 1) {
                $item_data->url = PageLang::url($item->page_id);
            } else {
                $item_data->url = PageLang::full_url($item->page_id);
            }
            if ($currentPage->id == $item->page_id || in_array($item->page_id, $page_parents)) {
                $item_data->active = true;
            } else {
                $item_data->active = false;
            }

            $sub_menu = '';
            if ($item->sub_levels > 0) {
                $sub_levels = $item->sub_levels;
            } else {
                $sub_levels = $default_sub_levels;
            }
            if ($sub_levels > 0) {
                $child_page_ids = Page::child_page_ids($item->page->id);
                if (!empty($child_page_ids)) {
                    $sub_pages = Page::get_ordered_pages($child_page_ids);
                    $sub_menu = self::buildMenu($sub_pages, $level + 1, $sub_levels - 1);
                }
            }

            if (!empty($sub_menu)) {
                $menu_items .= self::_getRenderedView('submenu_' . $level, array('item' => $item_data, 'items' => $sub_menu, 'is_first' => $is_first, 'is_last' => $is_last, 'count' => $count, 'total' => $total, 'level' => $level, 'further_levels' => $sub_levels));
            } else {
                $menu_items .= self::_getRenderedView('item', array('item' => $item_data, 'is_first' => $is_first, 'is_last' => $is_last, 'count' => $count, 'total' => $total, 'level' => $level));
            }
        }

        if ($level == 1) {
            return self::_getRenderedView('menu', array('items' => $menu_items));
        } else {
            return $menu_items;
        }
    }

}