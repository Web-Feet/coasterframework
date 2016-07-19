<?php namespace CoasterCms\Helpers\Cms;

use Auth;
use CoasterCms\Events\Admin\AuthRoute;
use CoasterCms\Models\User;
use Illuminate\Auth\SessionGuard;

class CoasterGuard extends SessionGuard
{

    /**
     * @return bool
     */
    public function admin()
    {
        return ($this->check() && $this->user()->role->admin > 0);
    }

    /**
     * @param array $action
     * @param array $options
     * @return bool
     */
    public function action($action, $options = [])
    {
        if (!empty($options['user_id'])) {
            $user = User::find($options['user_id']);
        } elseif ($user = $this->user()) {
            $options['user_id'] = $user->id;
        } else {
            return false;
        }

        return $user->role->check_action($action, $options);
    }

    /**
     * @param string $controller
     * @param string $action
     * @param array $parameters
     * @return bool
     */
    public function actionRoute($controller, $action, $parameters = [])
    {
        if (!$this->user() || $this->user()->role->admin < 1) {
            return false;
        }

        $returnOptions = [];
        $authRouteCheck = new AuthRoute($controller, $action, $parameters, $returnOptions);
        event($authRouteCheck);

        return ($authRouteCheck->ignore || Auth::action([$controller, $action], $returnOptions));
    }

}