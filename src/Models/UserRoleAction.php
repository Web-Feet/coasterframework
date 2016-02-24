<?php namespace CoasterCms\Models;

use Illuminate\Database\Eloquent\Model as Eloquent;

class UserRoleAction extends Eloquent
{

    protected $table = 'user_roles_actions';

    public static function restore($obj)
    {
        $obj->save();
    }

}