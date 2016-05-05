<?php namespace CoasterCms\Http\Controllers\Backend;

use CoasterCms\Models\PageSearchLog;
use View;

class PagesearchlogController extends _Base
{

    public function get_index()
    {
        $searchdata = PageSearchLog::orderBy('count', 'DESC')->orderBy('updated_at', 'DESC')->get();
        $this->layout->content = View::make('coaster::pages.pagesearchlog', array('searchdata' => $searchdata));
    }


}
