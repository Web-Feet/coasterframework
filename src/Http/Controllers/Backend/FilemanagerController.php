<?php namespace CoasterCms\Http\Controllers\Backend;

use CoasterCms\Http\Controllers\AdminController as Controller;
use View;

class FilemanagerController extends Controller
{

    public function get_index()
    {
        $this->layoutData['content'] = View::make('coaster::pages.filemanager');
    }

}