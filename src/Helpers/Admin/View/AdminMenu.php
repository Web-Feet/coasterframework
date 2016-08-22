<?php namespace CoasterCms\Helpers\Admin\View;

use Auth;
use CoasterCms\Models\AdminAction;
use CoasterCms\Models\AdminController;
use CoasterCms\Models\Language;
use Request;
use URL;
use View;

class AdminMenu
{

    protected static $_setActive;

    public static function getSystemMenu()
    {
        
        $systemMenuItems = [
            'Open Frontend' => [
                'link' => URL::to('/') . '" target="_blank',
                'icon' => 'fa fa-tv'
            ],
            'Help' => [
                'link' => config('coaster::admin.help_link') . '" target="_blank',
                'icon' => 'fa fa-life-ring'
            ]
        ];


        if (Auth::admin()) {

            if (Language::count() > 1) {
                $page_lang = Language::find(Language::current());
                $systemMenuItems['Language:' . $page_lang->language] = [
                    'link' => route('coaster.admin.account.language'),
                    'icon' => 'fa fa-language'
                ];
            }
            if (Auth::action('account')) {
                $systemMenuItems['My Account'] = [
                    'link' => route('coaster.admin.account'),
                    'icon' => 'fa fa-lock'
                ];
            }
            if (Auth::action('system')) {
                $systemMenuItems['System Settings'] = [
                    'link' =>  route('coaster.admin.system'),
                    'icon' => 'fa fa-cog'
                ];
            }
            $systemMenuItems['Logout'] = [
                'link' => route('coaster.admin.logout'),
                'icon' => 'fa fa-sign-out'
            ];

        } else {

            $systemMenuItems['Login'] = [
                'link' => route('coaster.admin.login'),
                'icon' => 'fa fa-lock'
            ];

        }

        $systemMenu = '';
        foreach ($systemMenuItems as $systemMenuItem => $details) {
            $details = ['item' => $systemMenuItem] + $details;
            $systemMenu .= View::make('coaster::menus.system.item', $details)->render();
        }

        return $systemMenu;

    }

    public static function getSectionsMenu()
    {
        // load menu items
        $menuItems = \CoasterCms\Models\AdminMenu::orderBy('order', 'asc')->get();
        $menu = [];
        foreach ($menuItems as $menuItem) {
            if (!isset($menu[$menuItem->parent])) {
                $menu[$menuItem->parent] = [];
            }
            array_push($menu[$menuItem->parent], $menuItem);
        }

        // admin menu generation
        $adminMenu = '';
        foreach ($menu[0] as $topLevelItem) {
            if (Auth::action($topLevelItem->action_id)) {

                self::$_setActive = false;

                // check if top level item has sub menu
                $subMenuItems = '';
                if (!empty($menu[$topLevelItem->id])) {
                    $items = '';
                    foreach ($menu[$topLevelItem->id] as $key => $subMenuItem) {
                        if (Auth::action($subMenuItem->action_id)) {
                            $items .= View::make('coaster::menus.sections.subitem', ['item' => $subMenuItem, 'url' => self::_itemUrl($subMenuItem->action_id)])->render();
                        }
                    }
                    if ($items) {
                        $subMenuItems = View::make('coaster::menus.sections.submenu', ['items' => $items])->render();
                    }
                }

                // get top level item view
                $url = self::_itemUrl($topLevelItem->action_id);
                $adminMenu .= View::make('coaster::menus.sections.item', ['sub_menu' => $subMenuItems, 'item' => $topLevelItem, 'active' => self::$_setActive, 'url' => $url])->render();

            }
        }
        
        return $adminMenu;
    }

    /**
     * @param $actionId
     * @return string
     */
    private static function _itemUrl($actionId)
    {
        if ($actionId > 0) {

            /** @var AdminAction $adminAction */
            $adminAction = AdminAction::preload($actionId);

            if (!empty($adminAction)) {

                /** @var AdminController $adminController */
                $adminController = AdminController::preload($adminAction->controller_id);

                $routeName = 'coaster.admin.' . $adminController->controller . ($adminAction->action == 'index' ? '' : '.' . $adminAction->action);

                if (strpos(Request::route()->getName(), $routeName) === 0) {
                    //self::$_setActive = true;
                }

                return route($routeName);

            }

        }

        return '#';
    }

}