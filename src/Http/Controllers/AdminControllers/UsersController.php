<?php namespace CoasterCms\Http\Controllers\AdminControllers;

use Auth;
use CoasterCms\Libraries\Builder\FormMessage;
use CoasterCms\Http\Controllers\AdminController as Controller;
use CoasterCms\Models\AdminLog;
use CoasterCms\Models\User;
use CoasterCms\Models\UserRole;
use Hash;
use Mail;
use Request;
use Validator;
use View;

class UsersController extends Controller
{

    public function get_index()
    {
        $users = User::join('user_roles', 'user_roles.id', '=', 'users.role_id')->select(array('users.id', 'users.email', 'user_roles.name', 'users.active'))->get();
        $this->layoutData['modals'] = View::make('coaster::modals.general.delete_item');
        $this->layoutData['content'] = View::make('coaster::pages.users', array('users' => $users, 'can_add' => Auth::action('users.add'), 'can_delete' => Auth::action('users.delete'), 'can_edit' => Auth::action('users.edit')));
    }

    public function post_edit($user_id = 0, $action = null)
    {
        $user = User::find($user_id);
        $authUser = Auth::user();
        if (!empty($user) && $authUser->role->admin >= $user->role->admin) {
            switch ($action) {
                case 'password':
                    $data = [];
                    $data['user'] = $user;
                    $data['level'] = 'admin';
                    $data['form'] = View::make('coaster::partials.users.forms.password', array('current_password' => ($authUser->id == $user_id)));
                    $data['success'] = $user->change_password();
                    AdminLog::new_log('User \'' . $user->email . '\' updated, password changed');
                    $this->layoutData['content'] = View::make('coaster::pages.account.password', $data);
                    break;
                case 'role':
                    $user_role = UserRole::find(Request::input('role'));
                    if (!empty($user_role) && $user_role->admin <= $authUser->role->admin) {
                        $user->role_id = Request::input('role');
                        AdminLog::new_log('User \'' . $user->email . '\' updated, role change');
                        $user->save();
                        $this->layoutData['content'] = View::make('coaster::pages.users.role', array('user' => $user, 'success' => true));
                    } else {
                        $this->get_edit($user_id, $action);
                    }
                    break;
                case 'status':
                    // stop admins disabling super admins
                    if ($authUser->id != $user->id) {
                        $v = Validator::make(Request::all(), array(
                                'set' => 'integer|min:0|max:1'
                            )
                        );
                        if ($v->passes()) {
                            $user->active = Request::input('set');
                            $user->save();
                            AdminLog::new_log('User \'' . $user->email . '\' updated, status changed');
                            return 1;
                        }
                    }
                    return 0;
                    break;
            }
        } else {
            return 'Can\'t edit this user';
        }
    }

    public function get_edit($user_id = 0, $action = null)
    {
        $user = User::find($user_id);
        $authUser = Auth::user();
        if (!empty($user)) {
            switch ($action) {
                case 'password':
                    $data = [];
                    $data['user'] = $user;
                    $data['level'] = 'admin';
                    $data['form'] = View::make('coaster::partials.users.forms.password', array('current_password' => ($authUser->id == $user_id)));
                    $this->layoutData['content'] = View::make('coaster::pages.account.password', $data);
                    break;
                case 'role':
                    $all_roles = UserRole::where('admin', '<=', $authUser->role->admin)->get();
                    $roles = array();
                    foreach ($all_roles as $role) {
                        $roles[$role->id] = $role->name;
                    }
                    $this->layoutData['content'] = View::make('coaster::pages.users.role', array('user' => $user, 'roles' => $roles));
                    break;
                default:
                    $details = View::make('coaster::partials.users.info', array('user' => $user));
                    if ($authUser->role->admin >= $user->role->admin) {
                        $can_edit = true;
                    } else {
                        $can_edit = false;
                    }
                    $this->layoutData['content'] = View::make('coaster::pages.users.edit', array('email' => $user->email, 'account' => $details, 'can_edit' => $can_edit));
            }
        } else {
            $this->layoutData['content'] = 'User not found';
        }
    }

    public function get_add()
    {
        $authUser = Auth::user();
        $all_roles = UserRole::where('admin', '<=', $authUser->role->admin)->get();
        $roles = array();
        foreach ($all_roles as $role) {
            $roles[$role->id] = $role->name;
        }
        $this->layoutData['content'] = View::make('coaster::pages.users.add', array('roles' => $roles));
    }

    public function post_add()
    {
        $authUser = Auth::user();
        $v = Validator::make(Request::all(), array(
                'email' => 'required|email',
                'role' => 'required|integer'
            )
        );

        $perm_issue = true;
        $role = UserRole::find(Request::input('role'));
        if (!empty($role) && $role->admin <= $authUser->role->admin) {
            $perm_issue = false;
        }

        if ($v->passes() && !$perm_issue) {

            $password = str_random(8);
            $new_user = new User;
            $new_user->email = Request::input('email');
            $new_user->role_id = Request::input('role');
            $new_user->password = Hash::make($password);
            $new_user->save();

            AdminLog::new_log('User \'' . $new_user->email . '\' added');

            Mail::send('coaster::emails.new_account', array('email' => $new_user->email, 'password' => $password), function ($message) use ($new_user) {
                $message->from(config('coaster::site.email'));
                $message->to($new_user->email);
                $message->subject(config('coaster::site.name') . ': New Account Details');
            });

            $failures = Mail::failures();

            if (empty($failures)) {
                $email_message = 'An email has been sent to the new user with their login details.';
                $email_status = 'success';
            } else {
                $email_message = 'There was an error sending the login details to the new user.';
                $email_status = 'warning';
            }
            $this->layoutData['content'] = View::make('coaster::pages.users.add', array('success' => true, 'password' => $password, 'email_message' => $email_message, 'email_status' => $email_status));
        } else {
            FormMessage::set($v->messages());
            if ($perm_issue) {
                FormMessage::add('role', 'Don\'t have permission to create user with this role, or doesn\'t exist');
            }
            $this->get_add();
        }
    }

    public function post_delete($user_id = 0)
    {
        $user = User::find($user_id);
        // stop admins disabling super admins
        if (!empty($user) && $authUser->role->admin >= $user->role->admin && $authUser->id != $user->id) {
            if (!empty($user)) {
                return $user->delete();
            }
        }
        return 0;
    }

}