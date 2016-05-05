<?php namespace CoasterCms\Http\Controllers\Backend;

use View;

class FilemanagerController extends _Base
{

    public function get_index()
    {
        $this->layout->content = View::make('coaster::pages.filemanager');
    }

}