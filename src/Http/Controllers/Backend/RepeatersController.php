<?php namespace CoasterCms\Http\Controllers\Backend;

use CoasterCms\Http\Controllers\AdminController as Controller;
use CoasterCms\Libraries\Blocks\Repeater;

class RepeatersController extends Controller
{

    public function post_index()
    {
        return Repeater::new_row();
    }

}