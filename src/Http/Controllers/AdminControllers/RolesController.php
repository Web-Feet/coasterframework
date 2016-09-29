<?php namespace CoasterCms\Http\Controllers\AdminControllers;

use Auth;
use CoasterCms\Http\Controllers\AdminController as Controller;
use CoasterCms\Models\AdminAction;
use CoasterCms\Models\AdminController;
use CoasterCms\Models\Page;
use CoasterCms\Models\PageGroup;
use CoasterCms\Models\PageLang;
use CoasterCms\Models\User;
use CoasterCms\Models\UserRole;
use Request;
use Response;
use Validator;
use View;

class RolesController extends Controller
{

    private $_child_pages;
    private $_role_permissions;

    public function getIndex()
    {
        $actions = array();
        foreach (AdminAction::where('inherit', '=', 0)->where('edit_based', '=', 0)->get() as $action) {
            if (!isset($actions[$action->controller_id])) {
                $actions[$action->controller_id] = array();
            }
            if ($action->action == 'version-publish' && $action->controller_id == 2) {
                $publishing_action_id = $action->id;
            }
            $actions[$action->controller_id][$action->id] = $action;
        }

        // alter actions shown based on site settings
        if (!config('coaster::admin.publishing') && isset($publishing_action_id)) {
            unset($actions[2][$publishing_action_id]);
        }
        if (config('coaster::admin.advanced_permissions')) {
            $action = new \stdClass;
            $action->id = -1;
            $action->name = 'Advanced page based permissions';
            $actions[2][] = $action;
        }

        $roles = array();
        foreach (UserRole::where('admin', '=', 1)->get() as $role) {
            $roles[$role->id] = $role->name;
        }

        $sections = array();
        $group = array(
            1 => 'Content Management',
            2 => 'Settings',
            3 => 'User Admin'
        );
        foreach ($group as $id => $name) {
            $sections[$id] = '';
        }
        foreach (AdminController::orderBy('role_order', 'asc')->get() as $controller) {
            $options = '';
            if (!isset($actions[$controller->id])) {
                continue;
            }
            foreach ($actions[$controller->id] as $action) {
                $class = ' controller-' . $controller->controller;
                $options .= View::make('coaster::partials.roles.option', array('name' => $action->name, 'id' => $action->id, 'val' => false, 'class' => $class))->render();
            }
            $sections[$controller->role_section] .= View::make('coaster::partials.roles.section', array('section' => $controller->role_name, 'options' => $options))->render();

        }
        $content = '';
        foreach ($group as $id => $name) {
            $content .= View::make('coaster::partials.roles.group', array('group' => $name, 'sections' => $sections[$id]))->render();
        }

        $this->layoutData['content'] = View::make('coaster::pages.roles', array('actions' => $content, 'roles' => $roles));
        $copy_roles = $roles;
        $copy_roles[0] = '-- None --';
        ksort($copy_roles);
        $this->layoutData['modals'] = View::make('coaster::modals.roles.add', array('roles' => $copy_roles))->render() . View::make('coaster::modals.roles.delete', array('roles' => $roles))->render();
    }

    public function postActions($role_id = 0)
    {
        $allowed_actions = array();
        if ($role_id != 0) {
            $role = UserRole::find($role_id);
            if (!empty($role)) {
                foreach ($role->actions as $action) {
                    $allowed_actions[$action->id] = true;
                }
            }
        }
        return json_encode($allowed_actions);
    }

    public function postAdd()
    {
        $v = Validator::make(Request::all(), array(
            'name' => 'required',
            'copy' => 'required|integer'
        ));
        if ($v->passes()) {
            $role = new UserRole;
            $role->name = Request::input('name');
            $role->admin = 1;
            $role->save();
            $role_info = array();
            $copy = UserRole::find(Request::input('copy'));
            if (!empty($copy)) {
                $copy_actions = array();
                $controller = AdminController::where('controller', '=', 'roles')->first();
                foreach ($copy->actions as $action) {
                    // don't copy role permissions
                    if ($controller->id != $action->controller_id) {
                        array_push($copy_actions, $action->id);
                    }
                }
                $role->actions()->sync($copy_actions);
                foreach ($copy->page_actions as $page_action) {
                    $role->page_actions()->attach($page_action->id,['action_id' => $page_action->pivot->action_id, 'access' => $page_action->pivot->access]);
                }
            }
            $role_info[$role->id] = $role->name;
            return json_encode($role_info);
        }
        return 0;
    }

    public function postEdit()
    {
        $v = Validator::make(Request::all(), array(
            'role' => 'required|integer',
            'action' => 'required|integer',
            'value' => 'required',
        ));
        if ($v->passes()) {
            $role = UserRole::find(Request::input('role'));
            if (!empty($role)) {
                $action = AdminAction::find(Request::input('action'));
                $controller = AdminController::find($action->controller_id);
                if (!empty($controller) && ($controller->controller != 'roles' || $role->id != Auth::user()->role->id)) {
                    $role->actions()->detach(Request::input('action'));
                    if (Request::input('value') == 'true') {
                        $role->actions()->attach(Request::input('action'));
                    }
                    return 1;
                }
            }
        }
        return 0;
    }

    public function postDelete()
    {
        $v = Validator::make(Request::all(), array(
            'new_role' => 'required|integer'
        ));
        $error = 'Invalid new role ID: '. Request::input('new_role');
        if ($v->passes()) {
            $error = 'Role does not exist with ID: '.Request::input('role');
            if ($role = UserRole::find(Request::input('role'))) {
                $error = 'New role ID must be different from the role being deleted';
                if (Request::input('new_role') != $role->id) {
                    User::where('role_id', '=', Request::input('role'))->update(['role_id' => Request::input('new_role')]);
                    return json_encode($role->delete());
                }
            }
        }
        return Response::make($error, 500);
    }

    public function getPages($role_id)
    {
        $this->_role_permissions = UserRole::find($role_id);

        if (!empty($this->_role_permissions)) {

            $pages = Page::orderBy('order', 'asc')->get();
            $this->_child_pages = array();

            foreach ($pages as $page) {
                if (!isset($this->_child_pages[$page->parent])) {
                    $this->_child_pages[$page->parent] = array();
                }
                array_push($this->_child_pages[$page->parent], $page);
            }

            $this->layoutData['content'] = View::make('coaster::pages.roles.pages', array('pages' => $this->_print_pages(0), 'role' => $this->_role_permissions->name));
        }
    }

    public function postPages($role_id)
    {
        if (config('coaster::admin.advanced_permissions')) {

            $page_actions = AdminAction::where('controller_id', '=', 2)->where('inherit', '=', 0)->where('edit_based', '=', 0)->get();
            $actionIds = [];
            foreach ($page_actions as $action) {
                $actionIds[$action->action] = $action->id;
            }
            if (!config('coaster::admin.publishing')) {
                unset($actionIds['version-publish']);
            }

            $pages_permissions = Request::input('page');
            $this->_role_permissions = UserRole::find($role_id);

            // defaults
            $defaults = [];
            foreach ($actionIds as $action => $id) {
                $defaults[$id] = false;
            }
            foreach ($this->_role_permissions->actions as $action) {
                if (array_key_exists($action->id, $defaults)) {
                    $defaults[$action->id] = 1;
                }
            }

            // existing
            $existing = [];
            foreach ($this->_role_permissions->page_actions as $page_permission) {
                if (!isset($existing[$page_permission->pivot->page_id])) {
                    $existing[$page_permission->pivot->page_id] = [];
                }
                $existing[$page_permission->pivot->page_id][$page_permission->pivot->action_id] = $page_permission->pivot->access;
            }

            // save updates
            $pages = Page::where('parent', '>=', '0')->get();
            foreach ($pages as $page) {
                foreach ($actionIds as $action => $action_id) {

                    // get value entered
                    if (isset($pages_permissions[$page->id][$action])) {
                        $value = 'allow';
                    } else {
                        $value = 'deny';
                    }

                    // check if update is required
                    if (isset($existing[$page->id][$action_id])) {
                        if ($defaults[$action_id] && $value == 'allow' || !$defaults[$action_id] && $value == 'deny') {
                            // remove existing
                            $this->_role_permissions->page_actions()->newPivotStatementForId($page->id)->whereActionId($action_id)->delete();
                            if ($page->group_container > 0) {
                                $group = PageGroup::find($page->group_container);
                                foreach ($group->pages as $group_page) {
                                    $this->_role_permissions->page_actions()->newPivotStatementForId($group_page->id)->whereActionId($action_id)->delete();
                                }
                            }

                        } elseif ($existing[$page->id][$action_id] != $value) {
                            // update existing
                            $this->_role_permissions->page_actions()->newPivotStatementForId($page->id)->whereActionId($action_id)->update(['access' => $value]);
                            if ($page->group_container > 0) {
                                $group = PageGroup::find($page->group_container);
                                foreach ($group->pages as $group_page) {
                                    $this->_role_permissions->page_actions()->newPivotStatementForId($group_page->id)->whereActionId($action_id)->update(['access' => $value]);
                                }
                            }
                        }
                    } elseif (!$defaults[$action_id] && $value == 'allow' || $defaults[$action_id] && $value == 'deny') {
                        // add new page action
                        $this->_role_permissions->page_actions()->attach($page->id, ['action_id' => $action_id, 'access' => $value]);
                        if ($page->group_container > 0) {
                            $group = PageGroup::find($page->group_container);
                            foreach ($group->pages as $group_page) {
                                $this->_role_permissions->page_actions()->attach($group_page->id, ['action_id' => $action_id, 'access' => $value]);
                            }
                        }
                    }

                }
            }

            $this->addAlert('success', 'Page Permissions Updated');
        }

        $this->getPages($role_id);
    }

    private function _print_pages($parent)
    {
        $pages_li = '';
        foreach ($this->_child_pages[$parent] as $child_page) {

            $page_lang = PageLang::preload($child_page->id);
            $sub_pages = "";

            if ($child_page->group_container > 0) {

            } elseif (!empty($this->_child_pages[$child_page->id])) {
                $sub_pages = $this->_print_pages($child_page->id);
            }

            $page_actions = AdminAction::where('controller_id', '=', 2)->where('inherit', '=', 0)->where('edit_based', '=', 0)->get();
            $edit_actions = [];
            foreach ($page_actions as $action) {
                if ($action->action == 'index') {
                    $edit_actions['pages'] = false;
                } else {
                    $edit_actions['pages.' . $action->action] = false;
                }
            }

            if (!config('coaster::admin.publishing')) {
                unset($edit_actions['pages.version-publish']);
            }

            $actions = $this->_role_permissions->processed_actions(['page_id' => $child_page->id]);
            $actions = array_merge($edit_actions, array_intersect_key($actions, $edit_actions));

            $page_actions = [];
            foreach ($actions as $action => $value) {
                if ($action == 'pages') {
                    $page_actions['index'] = $value;
                } else {
                    $page_actions[str_replace('pages.', '', $action)] = $value;
                }
            }

            $pages_li .= View::make('coaster::partials.roles.pages.li', array('page_lang' => $page_lang, 'sub_pages' => $sub_pages, 'actions' => $page_actions))->render();

        }
        return View::make('coaster::partials.roles.pages.ul', array('pages_li' => $pages_li));
    }

}