<?php namespace CoasterCms\Models;

use Illuminate\Database\Eloquent\Model as Eloquent;

class AdminMenu extends Eloquent
{

    protected $table = 'admin_menu';

    public function action()
    {
        return $this->belongsTo('CoasterCms\Models\AdminAction');
    }

}