<?php namespace CoasterCms\Http\Controllers\Backend;

use CoasterCms\Libraries\Blocks\Repeater;

class RepeatersController extends _Base
{

    public function post_index()
    {
        return Repeater::new_row();
    }

}