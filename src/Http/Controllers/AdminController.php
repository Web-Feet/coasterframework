<?php namespace CoasterCms\Http\Controllers;

use Auth;
use CoasterCms\Helpers\Admin\View\AdminMenu;
use Illuminate\Routing\Controller;
use Request;
use URL;
use View;

class AdminController extends Controller
{

    protected $layout;
    protected $layoutData;
    
    protected $user;
    
    public function __construct()
    {

        View::make('coaster::asset_builder.main')->render();
        
        $this->layout = 'coaster::template.main';
        $this->layoutData = [
            'site_name' => config('coaster::site.name'),
            'title' => ucwords(Request::segment(2)),
            'system_menu' => AdminMenu::getSystemMenu(),
            'sections_menu' => '',
            'modals' => '',
            'content' => '',
        ];

        if (Auth::admin()) {
            $this->user = Auth::user();
            $this->layoutData['sections_menu'] = AdminMenu::getSectionsMenu();
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