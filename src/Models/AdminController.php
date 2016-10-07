<?php namespace CoasterCms\Models;

use CoasterCms\Libraries\Traits\DataPreLoad;
use Eloquent;

class AdminController extends Eloquent
{
    use DataPreLoad;

    /**
     * @var string
     */
    protected $table = 'admin_controllers';

}