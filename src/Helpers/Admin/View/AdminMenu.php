<?php namespace CoasterCms\Helpers\Admin\View;

use Auth;
use CoasterCms\Models\AdminAction;
use CoasterCms\Models\AdminController;
use CoasterCms\Models\Language;
use URL;
use View;

class AdminMenu
{

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
                    'link' => URL::to(config('coaster::admin.url') . '/account/language'),
                    'icon' => 'fa fa-language'
                ];
            }
            if (Auth::action('account')) {
                $systemMenuItems['My Account'] = [
                    'link' => URL::to(config('coaster::admin.url') . '/account'),
                    'icon' => 'fa fa-lock'
                ];
            }
            if (Auth::action('system')) {
                $systemMenuItems['System Settings'] = [
                    'link' => URL::to(config('coaster::admin.url') . '/system'),
                    'icon' => 'fa fa-cog'
                ];
            }
            $systemMenuItems['Logout'] = [
                'link' => URL::to(config('coaster::admin.url') . '/logout'),
                'icon' => 'fa fa-sign-out'
            ];

        } else {

            $systemMenuItems['Login'] = [
                'link' => URL::to(config('coaster::admin.url') . '/login'),
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
                $adminMenu .= View::make('coaster::menus.sections.item', ['sub_menu' => $subMenuItems, 'item' => $topLevelItem, 'url' => self::_itemUrl($topLevelItem->action_id)])->render();

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

                return URL::to(
                    config('coaster::admin.url') . '/' . $adminController->controller . ($adminAction->action == 'index' ? '' : '/' . $adminAction->action)
                );

            }

        }

        return '#';
    }

}