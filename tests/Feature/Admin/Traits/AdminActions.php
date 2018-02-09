<?php namespace CoasterCms\Tests\Feature\Admin\Traits;

use CoasterCms\Models\AdminAction;
use CoasterCms\Models\AdminController;

trait AdminActions
{
    public function enableActionAccessForRole(&$role, $controller, $action)
    {
        // Allow user role to see pages index action
        $adminController = AdminController::where('controller', $controller)->first();
        $adminAction = AdminAction::where('controller_id', $adminController->id)->where('action', $action)->first();
        $role->actions()->sync([$adminAction->id]);
    }
}
