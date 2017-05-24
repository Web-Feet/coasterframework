<?php namespace CoasterCms\Http\Middleware;

use Auth;
use Closure;

class SecureUpload
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
        $response = $next($request); // get response first as if it's a 404 don't redirect

        if (!Auth::check() && $response->getStatusCode() == 200) {
            return \redirect()->route('coaster.admin.login')->withCookie(cookie('login_path', $request->getRequestUri()));
        }

        return $response;
    }

}
