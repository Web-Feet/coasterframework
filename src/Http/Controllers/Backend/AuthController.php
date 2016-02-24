<?php namespace CoasterCms\Http\Controllers\Backend;

use CoasterCms\Helpers\View\FormMessage;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cookie;
use Illuminate\Support\Facades\Redirect;
use Illuminate\Support\Facades\Request;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\Facades\View;

class AuthController extends _Base
{

    public function getLogin()
    {
        $this->layout->content = View::make('coaster::pages.login');
        $this->layout->title = 'Login';
    }

    public function postLogin()
    {
        $userdata = array(
            'username' => Request::input('username'),
            'password' => Request::input('password')
        );

        if ($e = Auth::attempt($userdata, (Request::input('remember') == 'yes') ? true : false)) {
            $login_path = Request::input('login_path');
            $login_path = empty($login_path) ? Cookie::get('login_path') : $login_path;
            if (empty($login_path)) {
                return Redirect::to(
                    URL::to(config('coaster::admin.url'))
                );
            } else {
                $cookie = Cookie::forget('login_path');
                return Redirect::to($login_path)->withCookie($cookie);
            }
        } else {
            FormMessage::add('username', 'Username or password incorrect');
            FormMessage::add('password', ' ');
            $this->getLogin();
        }
    }

    public function getLogout()
    {
        Auth::logout();
        return Redirect::to(
            URL::to(config('coaster::admin.url') . '/login')
        );
    }

}