<?php namespace CoasterCms\Models;

use Auth;
use Eloquent;
use View;

class Menu extends Eloquent
{

    protected $table = 'menus';

    protected $_pageNames;

    public static $menus = [];

    public static function get_menu($menu_name)
    {
        if (empty(self::$menus)) {
            $menus = self::all();
            foreach ($menus as $menu) {
                self::$menus[$menu->id] = $menu;
                self::$menus[$menu->name] = $menu;
            }
        }
        if (!empty(self::$menus[$menu_name])) {
            return self::$menus[$menu_name];
        }
        return null;
    }

    public function items()
    {
        return $this->hasMany('CoasterCms\Models\MenuItem')->orderBy('order', 'asc');
    }

    public static function name($menu_id)
    {
        $menu = self::find($menu_id);
        return !empty($menu) ? $menu->label : null;
    }

    public function getTreeView()
    {
        $renderedMenuItems = '';
        $this->_pageNames = Page::get_page_list();
        $menuItems = $this->items()->get();
        $permissions = [
            'can_add_item' => Auth::action('menus.add'),
            'delete' => Auth::action('menus.delete'),
            'subpage' => Auth::action('menus.save_levels'),
            'rename' => Auth::action('menus.rename')
        ];
        foreach ($menuItems as $menuItem) {
            if (isset($this->_pageNames[$menuItem->page_id])) {
                $leaf = $menuItem->getRenderedChildItems();
                $fullName = $this->_pageNames[$menuItem->page_id];
                $renderedMenuItems .= View::make('coaster::partials.menus.li', ['item' => $menuItem, 'menu' => $this, 'name' => $fullName, 'leaf' => $leaf, 'permissions' => $permissions])->render();
            }
        }
        return View::make('coaster::partials.menus.ol', ['renderedItems' => $renderedMenuItems, 'menu' => $this, 'permissions' => $permissions])->render();
    }

}