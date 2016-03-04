<?php namespace CoasterCms\Libraries\Builder;

use CoasterCms\Models\Page;
use CoasterCms\Models\PageLang;
use Illuminate\Support\Facades\View;

class MenuBuilder
{
    private static $_view;

    public static function set_view($view)
    {
        if (View::exists('themes.' . PageBuilder::$theme . '.menus.' . $view . '.menu') && View::exists('themes.' . PageBuilder::$theme . '.menus.' . $view . '.item')) {
            self::$_view = $view;
            return true;
        } else {
            return false;
        }
    }

    public static function build_menu($items, $level = 1, $sub_levels = 0)
    {
        $menu_items = '';
        $item_data = new \stdClass;
        $i = 1;
        $is_last = false;
        $is_first = true;
        $total = count($items);
        $page_parents = array();
        if (!empty(PageBuilder::$page_levels)) {
            foreach (PageBuilder::$page_levels as $k => $parent_page) {
                if ($k > 0) $page_parents[] = $parent_page->page_id;
            }
        }
        foreach ($items as $item) {
            if (get_class($item) == 'CoasterCms\Models\Page') {
                $page_id = $item->id;
            } else {
                if ($item->sub_levels >= 0) {
                    $sub_levels = (int) $item->sub_levels;
                }
                $page_id = $item->page_id;
            }
            $string = explode(',', $page_id);
            $page = Page::preload($string[0]);
            if (!empty($page) && $page->is_live()) {
                $custom_name = trim($item->custom_name);
                if (!empty($custom_name)) {
                    $item_data->name = $custom_name;
                } else {
                    $item_data->name = PageLang::name($page_id);
                }
                if ($page->link == 1) {
                    $item_data->url = PageLang::url($page_id);
                } else {
                    $item_data->url = PageLang::full_url($page_id);
                }
                if ((!empty(PageBuilder::$page_info) && PageBuilder::$page_info->page_id == $page_id) || in_array($page_id, $page_parents)) {
                    $item_data->active = true;
                } else {
                    $item_data->active = false;
                }
                $sub_menu = '';
                if ($sub_levels > 0) {
                    $child_page_ids = Page::child_page_ids($page->id);
                    if (!empty($child_page_ids)) {
                        $sub_pages = Page::get_ordered_pages($child_page_ids);
                        $sub_menu = self::build_menu($sub_pages, $level + 1, $sub_levels - 1);
                    }
                }
                if ($i == $total) {
                    $is_last = true;
                }
                if (!empty($sub_menu) && View::exists('themes.' . PageBuilder::$theme . '.menus.' . self::$_view . '.submenu_' . $level)) {
                    $menu_items .= View::make('themes.' . PageBuilder::$theme . '.menus.' . self::$_view . '.submenu_' . $level, array('item' => $item_data, 'items' => $sub_menu, 'is_first' => $is_first, 'is_last' => $is_last, 'count' => $i, 'total' => $total, 'level' => $level, 'further_levels' => $sub_levels));
                } else {
                    $menu_items .= View::make('themes.' . PageBuilder::$theme . '.menus.' . self::$_view . '.item', array('item' => $item_data, 'is_first' => $is_first, 'is_last' => $is_last, 'count' => $i, 'total' => $total, 'level' => $level));
                }
            }
            $i++;
            $is_first = false;
        }
        if ($level == 1)
            return View::make('themes.' . PageBuilder::$theme . '.menus.' . self::$_view . '.menu', array('items' => $menu_items));
        else
            return $menu_items;
    }

}