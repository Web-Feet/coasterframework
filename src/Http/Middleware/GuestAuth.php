<?php namespace CoasterCms\Http\Middleware;

use Auth;
use Closure;

class GuestAuth
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
        if (Auth::admin()) {
            if ($login_path = $request->get('login_path') ?: $request->cookie('login_path')) {
                return \redirect($login_path)
                    ->withCookie(cookie('login_path', null, -2628000));
            } else {
                return \redirect()->route('coaster.admin');
            }
        } else {
            return $next($request);
        }
    }

}
