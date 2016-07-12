<?php namespace CoasterCms\Http\Controllers\AdminControllers;

use CoasterCms\Http\Controllers\AdminController as Controller;
use View;

class FilemanagerController extends Controller
{

    public function getIndex()
    {
        $this->layoutData['content'] = View::make('coaster::pages.filemanager');
    }

}