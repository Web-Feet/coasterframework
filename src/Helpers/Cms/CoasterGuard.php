<?php namespace CoasterCms\Helpers\Cms;

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
        /** @var User|null $user */
        $user = $this->user();
        return ($this->check() && $user->role && $user->role->admin > 0);
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
        } else {
            /** @var User $user */
            $user = $this->user();
            $options['user_id'] = $user->id;
        }

        return (!empty($user) && $user->role->check_action($action, $options));
    }

    /**
     * @param string $controller
     * @param string $action
     * @param array $parameters
     * @return bool
     */
    public function actionRoute($controller, $action, $parameters = [])
    {
        if (!$this->admin()) {
            return false;
        }

        $actionOptions = [];
        $authRouteCheck = new AuthRoute($controller, $action, $parameters, $actionOptions);
        event($authRouteCheck);

        if ($authRouteCheck->override !== null) {
            return $authRouteCheck->override;
        } else {
            return $this->action([$controller, $action], $actionOptions);
        }
    }

}