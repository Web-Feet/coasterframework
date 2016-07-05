<?php namespace CoasterCms\Helpers\Cms;

use Auth;
use CoasterCms\Models\PageGroup;
use CoasterCms\Models\User;
use Illuminate\Auth\SessionGuard;
use Request;

class CoasterGuard extends SessionGuard
{

    public function check()
    {
        return parent::check();
    }

    public function admin()
    {
        if ($this->check() && $this->user()->role->admin > 0) {
            return true;
        } else {
            return false;
        }
    }

    public function action($action, $options = [])
    {
        if (!empty($options['user_id'])) {
            $user = User::find($options['user_id']);
        } else {
            $user = $this->user();
        }

        if (empty($user)) {
            return false;
        } else {
            $options['user_id'] = $user->id;
            return $user->role->check_action($action, $options);
        }
    }

    public function actionRoute(array $route, $parameters = [])
    {
        if (!$this->user() || $this->user()->role->admin < 1) {
            return false;
        }

        $options = [];
        switch ($route[0]) {
            case 'forms':
            case 'gallery':
            case 'pages':
                $options['page_id'] = isset($parameters['one']) ? $parameters['one'] : 0;
                $page_info = Request::input('page_info');
                if ($route[1] == 'add') { // use parent page id when adding page
                    $options['page_id'] = !empty($page_info['parent']) ? $page_info['parent'] : $options['page_id'];
                }
                break;
            case 'groups':
                $group_id = isset($parameters['one']) ? $parameters['one'] : 0;
                $page_group = PageGroup::find($group_id);
                $options['page_id'] = !empty($page_group) ? $page_group->default_parent : 0;
        }

        if (!empty($route[1]) && $route[0] == 'pages' && $route[1] == 'sort') {
            // let page sort function deal with permissions
            return true;
        }

        return Auth::action($route, $options);
    }

}