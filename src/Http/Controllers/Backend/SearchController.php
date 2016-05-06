<?php namespace CoasterCms\Http\Controllers\Backend;

use CoasterCms\Models\PageSearchLog;
use View;

class SearchController extends _Base
{

    public function get_index()
    {
        $search_data = PageSearchLog::orderBy('count', 'DESC')->orderBy('updated_at', 'DESC')->get();
        $this->layout->content = View::make('coaster::pages.search', array('search_data' => $search_data));
    }


}
