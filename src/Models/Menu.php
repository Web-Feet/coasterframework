<?php namespace CoasterCms\Models;

use Illuminate\Database\Eloquent\Model as Eloquent;

class Menu extends Eloquent
{

    protected $table = 'menus';
    public static $menus = array();

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

}