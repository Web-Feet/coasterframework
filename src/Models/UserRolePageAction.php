<?php namespace CoasterCms\Models;

use Illuminate\Database\Eloquent\Model as Eloquent;

class UserRolePageAction extends Eloquent
{

    protected $table = 'user_roles_page_actions';

    public static function restore($obj)
    {
        $obj->save();
    }

}