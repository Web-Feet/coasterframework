<?php namespace CoasterCms\Http\Controllers\Backend;

use CoasterCms\Helpers\View\FormMessage;
use CoasterCms\Models\User;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Input;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\View;

class AccountController extends _Base
{

    private function _change_password_checks($code)
    {
        $this->layout->title = 'Forgotten Password';
        if (empty($code)) {
            $this->layout->content = 'Invalid Code!';
        } else {
            if (!($user = User::where('tmp_code', '=', $code)->first())) {
                $this->layout->content = 'Invalid Code!';
            } else {
                $code_created = new \DateTime($user->tmp_code_created);
                $di = $code_created->diff(new \DateTime('now'));
                if ($di->days > 7) {
                    $this->layout->content = 'This code has expired!';
                } else {
                    return $user;
                }
            }
        }
        return null;
    }

    public function change_password($code = null)
    {
        $user = $this->_change_password_checks($code);
        if (!empty($user)) {
            $view_data = array(
                'user' => $user,
                'level' => 'guest',
                'form' => View::make('coaster::partials.forms.user.password', array('current_password' => false))
            );
            if (!empty($_POST)) {
                if ($user->change_password($code)) {
                    $view_data['success'] = true;
                }
            }
            $this->layout->content = View::make('coaster::pages.account.password', $view_data);
        }
    }


    public function forgotten_password()
    {
        $view_data = array();
        $rules = array(
            'email' => 'required|email',
        );
        $validation = Validator::make(Input::all(), $rules);

        if ($_POST && $validation->fails()) {
            FormMessage::set($validation->messages());
        } elseif ($_POST) {
            $email_addr = Request::input('email');
            $user = User::where('email', '=', $email_addr)->first();

            if ($user !== null) {
                if (!Auth::Action('account.password', ['user_id' => $user->id])) {
                    FormMessage::add('email', 'You can\'t change the password for this account');
                } else {
                    $code = urlencode(str_random(32) . microtime());
                    $user->tmp_code = $code;
                    $user->tmp_code_created = date('Y-m-d H:i:s');
                    $user->save();

                    Mail::send('coaster::emails.forgotten_password', array('code' => $code), function ($message) use ($email_addr) {
                        $message->from(config('coaster::site.email'));
                        $message->to($email_addr);
                        $message->subject(config('coaster::site.name') . ': Forgotten Password');
                    });
                    $failures = Mail::failures();

                    if (empty($failures)) {
                        $view_data['success'] = 'We have sent an email to you with a link to change your password.';
                    } else {
                        FormMessage::add('email', 'There was an error sending mail, please contact <a href="mailto:support@web-feet.co.uk?Subject=' . config('coaster::site.name') . ': Forgotten Password">support</a>.');
                    }
                }
            } else {
                FormMessage::add('email', 'We couldn\'t find your records.');
            }
        }
        $this->layout->title = 'Forgotten Password';
        $this->layout->content = View::make('coaster::pages.forgotten_password', $view_data);
    }

    public function post_password()
    {
        if (!$this->user->change_password()) {
            $this->get_password();
        } else {
            $data = [];
            $data['user'] = Auth::user();
            $data['level'] = 'user';
            $data['success'] = true;
            $this->layout->content = View::make('coaster::pages.account.password', $data);
        }
    }

    public function get_password()
    {
        $data = [];
        $data['user'] = Auth::user();
        $data['level'] = 'user';
        $data['form'] = View::make('coaster::partials.forms.user.password', array('current_password' => true));
        $this->layout->content = View::make('coaster::pages.account.password', $data);
    }

    private function _existing_blog_login()
    {
        $post = Request::input('blog_login');
        if (!empty($post)) {
            return $post;
        } elseif (!empty($this->user->blog_login)) {
            $blog_login = unserialize($this->user->blog_login);
            return $blog_login['login'];
        } else {
            return '';
        }
    }

    public function post_blog()
    {
        $rules = array(
            'blog_login' => 'required',
            'blog_password' => 'required'
        );
        $validation = Validator::make(Input::all(), $rules);
        $data = [];
        $data['form'] = View::make('coaster::partials.forms.user.blog', array('blog_login' => $this->_existing_blog_login()));
        if ($validation->fails()) {
            $this->layout->content = View::make('coaster::pages.account.blog', $data);
        } else {
            $this->user->blog_login = serialize(['login' => Request::input('blog_login'), 'password' => Request::input('blog_password')]);
            $this->user->save();
            $data['success'] = true;
            $this->layout->content = View::make('coaster::pages.account.blog', $data);
        }
    }

    public function postPageState()
    {
        $postData = Request::all();
        $pageStates = Auth::user()->getPageStates();

        if ($postData['expanded'] == "false" && ($key = array_search($postData['page_id'], $pageStates)) !== false) {
            unset($pageStates[$key]);
            Auth::user()->savePageStates($pageStates);
            return 1;
        } elseif ($postData['expanded'] == "true" && !in_array($postData['page_id'], $pageStates)) {
            $pageStates[] = $postData['page_id'];
            Auth::user()->savePageStates($pageStates);
            return 1;
        }

        return 0;
    }

    public function get_blog()
    {
        $form = View::make('coaster::partials.forms.user.blog', array('blog_login' => $this->_existing_blog_login()));
        $this->layout->content = View::make('coaster::pages.account.blog', array('form' => $form));
    }

    public function get_index()
    {
        $account = View::make('coaster::partials.users.info', array('user' => $this->user));
        $this->layout->content = View::make('coaster::pages.account', array('account' => $account, 'auto_blog_login' => (!empty(config('coaster::blog.url') && Auth::action('account.blog'))), 'change_password' => Auth::action('account.password')));
    }

}
