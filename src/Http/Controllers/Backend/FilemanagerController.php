<?php namespace CoasterCms\Http\Controllers\Backend;

use Illuminate\Support\Facades\View;

class FilemanagerController extends _Base
{

    public function get_index()
    {
        $this->layout->content = View::make('coaster::pages.filemanager');
    }

}