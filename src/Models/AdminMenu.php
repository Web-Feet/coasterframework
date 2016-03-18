<?php namespace CoasterCms\Models;

class AdminMenu extends _BaseEloquent
{

    protected $table = 'admin_menu';

    public function action()
    {
        return $this->belongsTo('CoasterCms\Models\AdminAction');
    }

}