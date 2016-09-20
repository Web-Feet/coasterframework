<?php namespace CoasterCms\Http\Controllers\AdminControllers;

use CoasterCms\Http\Controllers\AdminController as Controller;
use CoasterCms\Libraries\Blocks\Repeater;

class RepeatersController extends Controller
{

    public function postIndex()
    {
        return Repeater::newRow();
    }

}