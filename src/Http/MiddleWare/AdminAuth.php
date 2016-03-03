<?php namespace CoasterCms\Http\MiddleWare;

use Closure;
use Illuminate\Support\Facades\Auth;

class AdminAuth
{

    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request $request
     * @param  \Closure $next
     * @return mixed
     */
    public function handle($request, Closure $next)
    {
        $controller = $request->segment(2);
        $action = $request->segment(3);
        $parameters = $request->route()->parameters();

        if (Auth::actionRoute(array($controller, $action), $parameters)) {
            return $next($request);
        } elseif (Auth::admin()) {
            return abort(403, 'Action not permitted');
        } else {
            return redirect()->action('\CoasterCms\Http\Controllers\Backend\AuthController@getLogin')
                ->withCookie(cookie('login_path', $request->getRequestUri()));
        }

    }

}

