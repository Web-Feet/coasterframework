<?php namespace CoasterCms\Http\Controllers\AdminControllers;

use CoasterCms\Helpers\Cms\Page\PageCache;
use CoasterCms\Helpers\Cms\Page\Path;
use CoasterCms\Http\Controllers\AdminController as Controller;
use CoasterCms\Models\AdminLog;
use CoasterCms\Models\Menu;
use CoasterCms\Models\MenuItem;
use CoasterCms\Models\Page;
use Request;
use Response;
use View;

class MenusController extends Controller
{
    public function getIndex()
    {
        $menus = '';
        foreach (Menu::all() as $menu) {
            $menus .= $menu->getTreeView();
        }
        $this->layoutData['content'] = View::make('coaster::pages.menus', ['menus' => $menus]);
        $this->layoutData['modals'] = View::make('coaster::modals.menus.add_item', ['options' => Page::get_page_list()])->render() .
            View::make('coaster::modals.general.delete_item')->render() .
            View::make('coaster::modals.menus.sub_level_item')->render() .
            View::make('coaster::modals.menus.rename_item')->render();
    }

    public function postAdd()
    {
        $menu_id = Request::input('menu');
        $page_id = Request::input('id');
        $last_item = MenuItem::where('menu_id', '=', $menu_id)->orderBy('order', 'desc')->first();
        if (!empty($last_item)) {
            $order = $last_item->order + 1;
        } else {
            $order = 1;
        }
        $new_item = new MenuItem;
        $new_item->menu_id = $menu_id;
        $new_item->page_id = $page_id;
        $new_item->order = $order;
        $new_item->sub_levels = 0;
        $new_item->save();
        $item_name = Path::getFullName($page_id);
        AdminLog::log('Menu Item \'' . $item_name . '\' added to \'' . Menu::name($menu_id) . '\'');
        PageCache::clear();
        return $new_item->id;
    }

    public function postDelete($itemId)
    {
        if ($menu_item = MenuItem::find($itemId)) {
            $menu_item->delete();
            PageCache::clear();
            return $menu_item->getJSONLogIds();
        }
        return Response::make('Menu item with ID '.$itemId.' not found', 500);
    }

    public function postSort()
    {
        $items = Request::input('list');
        $menuItems = [];
        $pages = [];
        foreach ($items as $itemId => $parentItemId) {
            if (strpos($itemId, 'p') === 0) {
                $itemId = trim($itemId, 'p');
                if (strpos($parentItemId, 'p') === 0) {
                    $pages[$itemId] = trim($parentItemId, 'p');
                } elseif ($itemPageId = MenuItem::preload($parentItemId)->page_id) {
                    $pages[$itemId] = $itemPageId;
                }
            } else {
                $menuItems[$itemId] = null;
            }
        }
        $order = 1;
        $menuId = 0;
        foreach ($menuItems as $itemId => $parentItemId) {
            $current_item = MenuItem::preload($itemId);
            $current_item->order = $order++;
            $current_item->save();
            $menuId = $current_item->menu_id;
        }
        Page::sortPages($pages);
        AdminLog::log('Items re-ordered in menu \'' . Menu::name($menuId) . '\'');
        PageCache::clear();
        return 1;
    }

    public function postGetLevels()
    {
        $itemId = Request::input('id');
        $item = MenuItem::find($itemId);
        if (!empty($item)) {
            $menu = Menu::find($item->menu_id);
            return json_encode(['sub_levels' => $item->sub_levels, 'max_levels' => $item->getMaxLevels(), 'menu_max_levels' => $menu->max_sublevel]);
        }
        return abort(500, 'Item not found');
    }

    public function postHidePage()
    {
        $itemId = Request::input('itemId');
        $item = MenuItem::find($itemId);
        if (!empty($item)) {
            $item->setHiddenPage(Request::input('pageId'), Request::input('hide'));
            $item->save();
            PageCache::clear();
            return 1;
        }
        return abort(500, 'Item not found');
    }

    public function postSaveLevels()
    {
        $itemId = Request::input('id');
        $item = MenuItem::find($itemId);
        if (!empty($item)) {
            $menu = Menu::find($item->menu_id);
            $item->sub_levels = Request::input('sub_level') > $menu->max_sublevel ? $menu->max_sublevel : Request::input('sub_level');
            $item->save();
            // log action
            AdminLog::log('Change sub levels for menu item \'' . Path::getFullName($item->page_id) . '\' in \'' . Menu::name($item->menu_id) . '\' to ' . $item->sub_levels);
            PageCache::clear();
            return json_encode(['children' => $item->getRenderedChildItems()]);
        }
        return abort('500');
    }

    public function postRename()
    {
        $itemId = Request::input('id');
        $item = MenuItem::find($itemId);
        if (!empty($item)) {
            $item->setCustomName(Request::input('customName'), Request::input('pageId'));
            $item->save();
            // log action
            $item_name = Path::getFullName($item->page_id);
            if ($item->custom_name) {
                AdminLog::log('Renamed menu item \'' . $item_name . '\' in \'' . Menu::name($item->menu_id) . '\' to ' . $item->custom_name);
            } else {
                AdminLog::log('Removed custom name for menu item \'' . $item_name . '\' in \'' . Menu::name($item->menu_id) . '\'');
            }
            PageCache::clear();
            return 1;
        }
        return abort('500');
    }

}