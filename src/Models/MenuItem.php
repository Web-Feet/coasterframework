<?php namespace CoasterCms\Models;

use Auth;
use CoasterCms\Helpers\Cms\Page\Path;
use CoasterCms\Libraries\Traits\DataPreLoad;
use CoasterCms\Libraries\Traits\Logger;
use Eloquent;
use View;

class MenuItem extends Eloquent
{
    use Logger, DataPreLoad;

    protected $table = 'menu_items';

    public function page()
    {
        return $this->belongsTo('CoasterCms\Models\Page');
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

    public function getRenderedChildItems($parentPageId = null, $subLevels = null, $depth = 1)
    {
        $parentPageId = $parentPageId ?: $this->page_id;
        $subLevels = is_null($subLevels) ? $this->sub_levels : $subLevels;
        if ($subLevels > 0) {
            $renderedPages = '';
            if ($childPages = Page::getChildPages($parentPageId)) {
                $permissions = [
                    'rename' => Auth::action('menus.rename')
                ];
                foreach ($childPages as $childPage) {
                    $leaf = $this->getRenderedChildItems($childPage->id, $subLevels - 1, $depth + 1);
                    $name = PageLang::getName($childPage->id);
                    $renderedPages .= View::make('coaster::partials.menus.page_li', ['page' => $childPage, 'item' => $this, 'name' => $name, 'leaf' => $leaf, 'permissions' => $permissions])->render();
                }
                return View::make('coaster::partials.menus.page_ol', ['renderedPages' => $renderedPages, 'item' => $this, 'depth' => $depth])->render();
            }
        }
        return '';
    }

    public function getCustomName($pageId = 0)
    {
        if ($pageId) {
            $pageNames = @unserialize($this->custom_page_names);
            if (is_array($pageNames) && array_key_exists($pageId, $pageNames)) {
                return $pageNames[$pageId];
            }
            return '';
        } else {
            return $this->custom_name;
        }
    }

    public function setCustomName($customName, $pageId = 0)
    {
        if ($pageId) {
            $pageNames = @unserialize($this->custom_page_names);
            $pageNames = is_array($pageNames) ? $pageNames : [];
            if ($customName) {
                $pageNames[$pageId] = $customName;
            } elseif (array_key_exists($pageId, $pageNames)) {
                unset($pageNames[$pageId]);
            }
            if ($pageNames) {
                $this->custom_page_names = serialize($pageNames);
            }
        } else {
            $this->custom_name = $customName;
        }
    }

    public static function boot()
    {
        parent::boot();
        static::deleted(function (MenuItem $menuItem) {
            $log = 'Menu Item \'' . Path::getFullName($menuItem->page_id) . '\' deleted from \'' . Menu::name($menuItem->menu_id) . '\'';
            $menuItem->addLogWithBackup($log, $menuItem);
        });
    }

}