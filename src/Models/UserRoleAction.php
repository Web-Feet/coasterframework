<?php namespace CoasterCms\Models;

use Eloquent;

class UserRoleAction extends Eloquent
{

    protected $table = 'user_roles_actions';

    public static function restore($obj)
    {
        $obj->save();
    }

}