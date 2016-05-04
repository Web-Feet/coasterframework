<?php namespace CoasterCms\Http\Controllers\Backend;

use CoasterCms\Helpers\View\FormMessage;
use CoasterCms\Models\PageSearchLog;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\View;

class PagesearchlogController extends _Base
{

    public function get_index()
    {
        $searchdata = PageSearchLog::orderBy('count', 'DESC')->orderBy('updated_at', 'DESC')->get();
        $this->layout->content = View::make('coaster::pages.pagesearchlog', array('searchdata' => $searchdata));
    }


}
