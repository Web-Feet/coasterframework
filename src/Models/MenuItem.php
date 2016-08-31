<?php namespace CoasterCms\Models;

use Eloquent;

class MenuItem extends Eloquent
{

    protected $table = 'menu_items';

    public function page()
    {
        return $this->belongsTo('CoasterCms\Models\Page');
    }

    public static function restore($obj)
    {
        $obj->save();
    }

    public static function get_page_menus($page_id)
    {
        $menus = array();
        $items = self::where('page_id', '=', $page_id)->get();
        foreach ($items as $item) {
            $menus[$item->menu_id] = $item->menu_id;
        }
        return $menus;
    }

    public static function set_page_menus($page_id, $menus)
    {
        $current = self::get_page_menus($page_id);
        if (!empty($menus)) {
            foreach ($menus as $menu_id => $checked_val) {
                if (!in_array($menu_id, $current)) {
                    $last_item_in_menu = self::where('menu_id', '=', $menu_id)->orderBy('order', 'desc')->first();
                    if (!empty($last_item_in_menu)) {
                        $order = $last_item_in_menu->order + 1;
                    } else {
                        $order = 1;
                    }
                    $new_item = new self;
                    $new_item->page_id = $page_id;
                    $new_item->menu_id = $menu_id;
                    $new_item->order = $order;
                    $new_item->custom_name = '';
                    $new_item->save();
                } else {
                    unset($current[$menu_id]);
                }
            }
        }
        if (!empty($current)) {
            self::where('page_id', '=', $page_id)->whereIn('menu_id', $current)->delete();
        }
    }

}