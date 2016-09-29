<?php namespace CoasterCms\Models;

use Eloquent;

class UserRole extends Eloquent
{

    protected $table = 'user_roles';

    private $_allowed_actions;
    private $_allowed_page_actions;

    private $_processed_actions;


    public function users()
    {
        return $this->hasMany('CoasterCms\Models\User');
    }

    public function actions()
    {
        return $this->belongsToMany('CoasterCms\Models\AdminAction', 'user_roles_actions', 'role_id', 'action_id');
    }

    public function page_actions()
    {
        return $this->belongsToMany('CoasterCms\Models\Page', 'user_roles_page_actions', 'role_id', 'page_id')->withPivot('action_id', 'access');
    }

    public function check_action($action, $options = [])
    {
        if ($this->admin > 1) {
            return true;
        }

        if (is_array($action)) {
            $action = trim(implode('.', $action), '.');
        }
        if (!is_numeric($action)) {
            // fix format update
            $action = str_replace("_", "-", $action);
        }
        if ($action == '') {
            $action = 'home';
        }

        $role_actions = $this->processed_actions($options);
        if (!empty($role_actions[$action])) {
            return $role_actions[$action];
        } else {
            return false;
        }
    }

    public function processed_actions($options = [])
    {
        if (empty($this->_processed_actions)) {
            $this->_processed_actions = [];
        }

        $page_id = !empty($options['page_id']) ? $options['page_id'] : 0;

        if (!isset($this->_processed_actions[$page_id])) {

            // always have access to
            $this->_processed_actions[$page_id] = array(
                'logout' => true,
            );

            // standard actions
            foreach ($this->_allowed_actions() as $action_id) {
                $this->_edit_allowed_action($action_id, true, $page_id);
            }

            // override with per page actions
            if ($page_id) {
                foreach ($this->_allowed_page_actions($page_id) as $action_id => $access) {
                    if ($access == 'allow') {
                        $this->_edit_allowed_action($action_id, true, $page_id);
                    } else {
                        $this->_edit_allowed_action($action_id, false, $page_id);
                    }
                }
            }

            // add inherited permissions
            foreach (AdminAction::inherited() as $action) {
                if ($action->inherit == -1 || ($action->inherit && !empty($this->_processed_actions[$page_id][$action->inherit]) && $this->_processed_actions[$page_id][$action->inherit])) {
                    $this->_edit_allowed_action($action->id, true, $page_id);
                }
            }

            // add permissions based on per page or site wide content edit permissions
            foreach (AdminAction::edit_based() as $action) {
                if (($page_id && !empty($this->_processed_actions[$page_id]['pages.edit']) && $this->_processed_actions[$page_id]['pages.edit'])
                    || (!$page_id && !empty($this->_processed_actions[$page_id]['blocks']) && $this->_processed_actions[$page_id]['blocks'])
                ) {
                    $this->_edit_allowed_action($action->id, true, $page_id);
                }
            }

        }

        return $this->_processed_actions[$page_id];
    }

    private function _allowed_actions()
    {
        if (!isset($this->_allowed_actions)) {

            $this->_allowed_actions = [];

            foreach ($this->actions as $action) {
                $this->_allowed_actions[] = $action->id;
            }

        }

        return $this->_allowed_actions;
    }

    private function _allowed_page_actions($page_id)
    {
        if (!config('coaster::admin.advanced_permissions')) {
            return [];
        }

        if (!isset($this->_allowed_page_actions)) {

            $this->_allowed_page_actions = [];

            foreach ($this->page_actions as $page_actions) {
                if (empty($this->_allowed_page_actions[$page_actions->pivot->page_id])) {
                    $this->_allowed_page_actions[$page_actions->pivot->page_id] = [];
                }
                $this->_allowed_page_actions[$page_actions->pivot->page_id][$page_actions->pivot->action_id] = $page_actions->pivot->access;
            }

        }

        return !empty($this->_allowed_page_actions[$page_id]) ? $this->_allowed_page_actions[$page_id] : [];
    }

    private function _edit_allowed_action($action_id, $set = true, $page_id)
    {
        $action = AdminAction::preload($action_id);
        $controller = AdminController::preload($action->controller_id);
        $action_name = $controller->controller . '.' . $action->action;

        // add action id and controller_action as keys so both can be checked
        $this->_processed_actions[$page_id][$action->id] = $set;
        $this->_processed_actions[$page_id][$action_name] = $set;
        if ($action->action == 'index') {
            $this->_processed_actions[$page_id][$controller->controller] = $set;
        }
    }

    public function delete()
    {
        $log_id = AdminLog::new_log('User role \'' . $this->name . '\' deleted');
        Backup::new_backup($log_id, '\CoasterCms\Models\UserRole', $this);

        $actions = UserRoleAction::where('role_id', '=', $this->id);
        $page_actions = UserRolePageAction::where('role_id', '=', $this->id);

        Backup::new_backup($log_id, '\CoasterCms\Models\UserRoleAction', $actions->get());
        Backup::new_backup($log_id, '\CoasterCms\Models\UserRolePageAction', $page_actions->get());

        // delete data
        parent::delete();
        $actions->delete();
        $page_actions->delete();

        return $log_id;
    }

}