<?php namespace CoasterCms\Http\Controllers\AdminControllers;

use Auth;
use CoasterCms\Libraries\Builder\FormMessage;
use CoasterCms\Http\Controllers\AdminController as Controller;
use Cookie;
use Redirect;
use Request;
use URL;
use View;

class AuthController extends Controller
{

    public function getLogin()
    {
        $this->layoutData['content'] = View::make('coaster::pages.login');
        $this->layoutData['title'] = 'Login';
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