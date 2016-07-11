<?php namespace CoasterCms\Http\Middleware;

use Auth;
use Closure;

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

        if (Auth::check()) {
            if (Auth::actionRoute(array($controller, $action), $parameters)) {
                return $next($request);
            } elseif (Auth::admin()) {
                return abort(403, 'Action not permitted');
            }
        }

        return \redirect()->route('coaster.admin.login')->withCookie(cookie('login_path', $request->getRequestUri()));
    }

}

