<?php namespace CoasterCms\Http\Controllers\Backend;

use CoasterCms\Models\AdminAction;
use CoasterCms\Models\AdminController;
use CoasterCms\Models\AdminMenu;
use CoasterCms\Models\Language;
use CoasterCms\Models\PageLang;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Redirect;
use Illuminate\Support\Facades\Request;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\Facades\View;

class _Base extends Controller
{

    protected $layout = 'coaster::template.main';
    protected $layoutData = array();

    protected $allowed_actions = array();
    protected $model_plural;
    protected $model_name;
    protected $user;

    private static $controllers = array();

    public function __construct()
    {
        View::make('coaster::asset_builder.main')->render();

        $this->model_plural = ucwords(Request::segment(2));
        $this->model_name = str_singular($this->model_plural);

        $this->layoutData['site_name'] = config('coaster::site.name');
        $this->layoutData['title'] = ucwords($this->model_plural);

        $this->layoutData['modals'] = '';
        $this->layoutData['content'] = '';

        $this->layoutData['system_menu'] = ['Open Frontend' => URL::to('/') . '" target="_blank', 'Help' => config('coaster::admin.help_link') . '" target="_blank'];
        $this->layoutData['system_menu_icons'] = [];

        if (Auth::admin()) {
            $this->user = Auth::user();

            if (Language::count() > 1) {
                $page_lang = Language::find(Language::current());
                $this->layoutData['system_menu_icons']['Language: '.$page_lang->language] = 'fa fa-language';
                $this->layoutData['system_menu']['Language: '.$page_lang->language] = URL::to(config('coaster::admin.url') . '/account/language');
            }

            if (Auth::action('account')) {
                $this->layoutData['system_menu']['My Account'] = URL::to(config('coaster::admin.url') . '/account');
            }
            if (Auth::action('system')) {
                $this->layoutData['system_menu']['System Settings'] = URL::to(config('coaster::admin.url') . '/system');
            }
            $this->layoutData['system_menu']['Logout'] = URL::to(config('coaster::admin.url') . '/logout');

            // add admin menu to template
            $this->layoutData['menu'] = self::_menu();
        } else {
            $this->layoutData['system_menu']['Login'] = URL::to(config('coaster::admin.url') . '/login');
            $this->layoutData['menu'] = null;
        }

    }

    private static function _menu()
    {
        // load controllers
        $controllers = AdminController::preload_all();
        foreach ($controllers as $controller) {
            self::$controllers[$controller->id] = $controller;
        }

        // load menu items
        $menu_items = AdminMenu::orderBy('order', 'asc')->get();
        $menu = array();
        foreach ($menu_items as $menu_item) {
            if (empty($menu[$menu_item->parent])) {
                $menu[$menu_item->parent] = array();
            }
            array_push($menu[$menu_item->parent], $menu_item);
        }

        // admin menu generation
        $admin_menu = '';
        foreach ($menu[0] as $top_level_item) {
            if (Auth::action($top_level_item->action_id)) {
                $top_level_item->url = self::_menu_item_url($top_level_item->action_id);
                $sub_menu_items = '';
                if (!empty($menu[$top_level_item->id])) {
                    $items = '';
                    foreach ($menu[$top_level_item->id] as $key => &$sub_menu_item) {
                        if (Auth::action($sub_menu_item->action_id)) {
                            $sub_menu_item->url = self::_menu_item_url($sub_menu_item->action_id);
                            $items .= View::make('coaster::partials.admin_menu.subitem', array('item' => $sub_menu_item))->render();
                        }
                    }
                    if ($items) {
                        $sub_menu_items = View::make('coaster::partials.admin_menu.submenu', array('items' => $items))->render();
                    }
                }
                $admin_menu .= View::make('coaster::partials.admin_menu.item', array('sub_menu' => $sub_menu_items, 'item' => $top_level_item))->render();
            }
        }

        return $admin_menu;

    }

    private static function _menu_item_url($action_id)
    {
        if ($action_id > 0) {
            $action = AdminAction::preload($action_id);
            if (!empty($action)) {
                if ($action->action == 'index') {
                    return URL::to(config('coaster::admin.url') . '/' . self::$controllers[$action->controller_id]->controller);
                } else {
                    return URL::to(config('coaster::admin.url') . '/' . self::$controllers[$action->controller_id]->controller . '/' . $action->action);
                }
            }
        }
        return '#';
    }

    public function missingMethod($parameters = array())
    {
        $list_view = 'coaster::lists/' . str_plural(strtolower($this->model_name));
        if (View::exists($list_view)) {
            $ms = $this->model_name;
            $items = $ms::all();
            $this->layout->content = View::make($list_view)->with('items', $items)->render();
        } else {
            $class_full_name = explode('\\', get_called_class());
            $class_path = strtolower(substr(end($class_full_name), 0, -10));
            $class_path = ($class_path == 'home') ? '' : '/' . $class_path;
            return Redirect::to(
                URL::to(config('coaster::admin.url')) . $class_path
            );
        }
    }

    // setup layout for l5 (migrated from l4)
    public function setupLayout()
    {
        if (!is_null($this->layout)) {
            $this->layout = View::make($this->layout, $this->layoutData);
        }
    }

    public function callAction($method, $parameters)
    {
        $this->setupLayout();

        $required_params = (new \ReflectionMethod(get_class($this), $method))->getNumberOfRequiredParameters();
        if ($required_params > count($parameters)) {
            throw new \ErrorException('Missing arguments for ' . get_class($this) . '::' . $method . '()');
        }

        $response = call_user_func_array(array($this, $method), $parameters);

        if (is_null($response) && !is_null($this->layout)) {
            $response = $this->layout;
        }

        return $response;
    }

}
