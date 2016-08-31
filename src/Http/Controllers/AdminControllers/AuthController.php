<?php namespace CoasterCms\Http\Controllers\AdminControllers;

use Auth;
use CoasterCms\Libraries\Builder\FormMessage;
use CoasterCms\Http\Controllers\AdminController as Controller;
use Cookie;
use Request;
use View;

class AuthController extends Controller
{

    public function login()
    {
        if (Request::input()) {
            $userData = [
                'username' => Request::input('username'),
                'password' => Request::input('password')
            ];
            $rememberMe = Request::input('remember') == 'yes';

            if ($e = Auth::attempt($userData, $rememberMe)) {
                $login_path = Request::input('login_path') ?: Cookie::get('login_path');
                if (empty($login_path)) {
                    return \redirect()->route('coaster.admin.home');
                } else {
                    $cookie = Cookie::forget('login_path');
                    return \redirect($login_path)->withCookie($cookie);
                }
            } else {
                FormMessage::add('username', 'Username or password incorrect');
                FormMessage::add('password', ' ');
            }
        }

        $this->layoutData['content'] = View::make('coaster::pages.login');
        $this->layoutData['title'] = 'Login';
        return null;
    }

    public function logout()
    {
        Auth::logout();
        return \redirect()->route('coaster.admin.login');
    }

}