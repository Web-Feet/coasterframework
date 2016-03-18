<?php namespace CoasterCms\Models;

class UserRoleAction extends _BaseEloquent
{

    protected $table = 'user_roles_actions';

    public static function restore($obj)
    {
        $obj->save();
    }

}