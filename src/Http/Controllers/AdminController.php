<?php namespace CoasterCms\Http\Controllers;

use Auth;
use CoasterCms\Helpers\Admin\View\AdminMenu;
use Illuminate\Http\RedirectResponse;
use Illuminate\Routing\Controller;
use Request;
use URL;
use View;

class AdminController extends Controller
{
    /**
     * @var string
     */
    protected $layout;

    /**
     * @var array
     */
    protected $layoutData;

    /**
     * AdminController constructor.
     */
    public function __construct()
    {
        View::make('coaster::asset_builder.main')->render();
        $this->layout = 'coaster::template.main';
        $this->layoutData = [
            'site_name' => config('coaster::site.name'),
            'title' => ucwords(Request::segment(2)),
            'modals' => '',
            'content' => ''
        ];
    }

    /**
     * @return RedirectResponse
     */
    public function catchAll()
    {
        return redirect(URL::to(config('coaster::admin.url')));
    }

    /**
     * @param string $method
     * @param array $parameters
     * @return \Illuminate\Contracts\View\View
     */
    public function callAction($method, $parameters)
    {
        $actionResponse = call_user_func_array([$this, $method], $parameters);

        if (is_null($actionResponse)) {
            return View::make($this->layout, array_merge([
                'system_menu' => AdminMenu::getSystemMenu(),
                'sections_menu' => Auth::admin() ? AdminMenu::getSectionsMenu() : '',
            ], $this->layoutData));
        } else {
            return $actionResponse;
        }
    }

}