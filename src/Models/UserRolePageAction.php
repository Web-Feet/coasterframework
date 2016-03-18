<?php namespace CoasterCms\Models;

class UserRolePageAction extends _BaseEloquent
{

    protected $table = 'user_roles_page_actions';

    public static function restore($obj)
    {
        $obj->save();
    }

}