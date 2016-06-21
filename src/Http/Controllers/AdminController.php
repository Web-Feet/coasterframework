<?php namespace CoasterCms\Http\Controllers;

use Auth;
use CoasterCms\Helpers\Admin\View\AdminMenu;
use Illuminate\Routing\Controller;
use Request;
use URL;
use View;

class AdminController extends Controller
{

    protected $modelName;
    
    protected $layout;
    protected $layoutData;
    
    protected $user;
    
    public function __construct()
    {
        $modelPlural = ucwords(Request::segment(2));
        $this->modelName = str_singular($modelPlural);
        
        View::make('coaster::asset_builder.main')->render();
        
        $this->layout = 'coaster::template.main';
        $this->layoutData = [
            'site_name' => config('coaster::site.name'),
            'title' => $modelPlural,
            'system_menu' => AdminMenu::getSystemMenu(),
            'menu' => '',
            'modals' => '',
            'content' => '',
        ];

        if (Auth::admin()) {
            $this->user = Auth::user();
            $this->layoutData['menu'] = AdminMenu::getSectionsMenu();
        }

    }
    
    public function catchAll()
    {
        return redirect(URL::to(config('coaster::admin.url')));
    }

    public function callAction($method, $parameters)
    {
        $actionResponse = call_user_func_array([$this, $method], $parameters);

        if (is_null($actionResponse)) {
            return View::make($this->layout, $this->layoutData);
        } else {
            return $actionResponse;
        }
    }

}