<?php

namespace CoasterCms\Models;

use Carbon\Carbon;
use Illuminate\Support\Str;
use CoasterCms\Facades\FormMessage;
use Illuminate\Auth\Authenticatable;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Request;
use CoasterCms\Notifications\NewAccount;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Facades\Validator;
use CoasterCms\Notifications\PasswordReset;
use Illuminate\Foundation\Auth\Access\Authorizable;
use Illuminate\Contracts\Auth\Authenticatable as AuthenticatableContract;
use Illuminate\Contracts\Auth\Access\Authorizable as AuthorizableContract;

class User extends Model implements AuthenticatableContract, AuthorizableContract
{
    use Authenticatable, Authorizable, Notifiable;

    protected $table = 'users';
    protected $hidden = ['password', 'remember_token'];
    protected static $_aliases;

    public function role()
    {
        return $this->belongsTo('CoasterCms\Models\UserRole');
    }

    public function change_password($tmp_code = null)
    {
        $tmp_check = false;
        if (!Auth::check()) {
            $code_created = new Carbon($this->tmp_code_created);
            $tmp_check = !empty($this->tmp_code) && $this->tmp_code == $tmp_code && $code_created->diff(new Carbon)->days <= 7;
            if (!$tmp_check) {
                FormMessage::add('tmp_code', 'The code was incorrect');
                return false;
            }
        }

        $details = Request::all();
        $rules = array('new_password' => 'required|confirmed|min:4');
        if (!($tmp_check || (Auth::check() && Auth::action('user.edit') && Auth::user()->id != $this->id))) {
            $rules['current_password'] = 'required';
        }

        $v = Validator::make($details, $rules);
        if ($v->passes()) {
            // check password
            if (!empty($rules['current_password']) && !Hash::check($details['current_password'], $this->password)) {
                FormMessage::add('current_password', 'The current password was incorrect');
                return false;
            }
            try {
                $this->updatePassword($details['new_password']);
                return true;
            } catch (\Exception $e) {
                FormMessage::add('new_password', $e->getMessage());
            }
        } else {
            FormMessage::set($v->messages());
        }
        return false;
    }

    public function getPageStates()
    {
        if (!empty($this->page_states)) {
            return unserialize($this->page_states);
        }
        return [];
    }

    public function savePageStates($pageStates)
    {
        $this->page_states = serialize($pageStates);
        $this->save();
    }

    public function getName()
    {
        return $this->name ?: $this->email;
    }

    public static function userAliases()
    {
        if (!isset(self::$_aliases)) {
            self::$_aliases = [];
            foreach (User::all() as $user) {
                self::$_aliases[$user->id] = $user->name ?: $user->email;
            }
        }
        return self::$_aliases;
    }

    public function delete()
    {
        $log_id = AdminLog::new_log('User \'' . $this->email . '\' deleted');
        Backup::new_backup($log_id, '\CoasterCms\Models\User', $this);

        // delete data
        parent::delete();

        return $log_id;
    }

    /**
     * @param string $tmpCode
     * @return $this
     * @throws \Exception
     */
    public static function findFromTmpCode($tmpCode)
    {
        if ($tmpCode && $user = User::where('tmp_code', '=', $tmpCode)->first()) {
            $code_created = new Carbon($user->tmp_code_created);
            $di = $code_created->diff(new Carbon('now'));
            if ($di->days > 7) {
                throw new \Exception('This code has expired!');
            }
            return $user;
        }
        throw new \Exception('Invalid Code!');
    }

    /**
     * @param string $routeName
     */
    public function sendPasswordResetNotification($routeName = 'coaster.admin.login.password.change')
    {
        $this->tmp_code = urlencode(Str::random(32) . microtime());
        $this->tmp_code_created = new Carbon();
        $this->save();
        $this->notify(new PasswordReset($this, $routeName));
    }

    /**
     * @param string $password
     * @param bool $ignoreChecks
     * @throws \Exception
     */
    public function updatePassword($password, $ignoreChecks = false)
    {
        // update only if users account has update password action or logged in user has admin user edit permissions
        if ($ignoreChecks || Auth::action('account.password', ['user_id' => $this->id]) || (Auth::check() && Auth::action('user.edit'))) {
            $this->password = Hash::make($password);
            $this->tmp_code = '';
            $this->save();
        } else {
            throw new \Exception('Can\'t update account password');
        }
    }

    /**
     * @param string $password
     * @param string $routeName
     */
    public function sendNewAccountNotification($password, $routeName = 'coaster.admin.login')
    {
        $this->notify(new NewAccount($this, $password, $routeName));
    }
}
