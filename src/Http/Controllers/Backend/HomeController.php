<?php namespace CoasterCms\Http\Controllers\Backend;

use CoasterCms\Helpers\View\PaginatorRender;
use CoasterCms\Models\AdminLog;
use CoasterCms\Models\PagePublishRequests;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\View;

class HomeController extends _Base
{

    public function index()
    {
        $requests = PagePublishRequests::can_moderate(['status' => 'awaiting'], 10);
        if ($requests->isEmpty()) {
            $requests = 'No awaiting requests';
        }
        $requests_table = View::make('coaster::partials.tabs.publish_requests.table', array('show' => ['page' => true, 'status' => false, 'requested_by' => true], 'requests' => $requests))->render();

        $any_requests = config('coaster::admin.publishing') && !PagePublishRequests::can_moderate([], 1)->isEmpty();

        $user_requests = PagePublishRequests::all_requests(0, ['user_id' => Auth::user()->id, 'status' => 'awaiting'], 10);
        if ($user_requests->isEmpty()) {
            $user_requests = 'No awaiting requests';
        }
        $user_requests_table = View::make('coaster::partials.tabs.publish_requests.table', array('show' => ['page' => true, 'status' => true, 'requested_by' => false], 'requests' => $user_requests))->render();

        $any_user_requests = config('coaster::admin.publishing') && !PagePublishRequests::all_requests(0, ['user_id' => Auth::user()->id], 1)->isEmpty();

        $logs_data = AdminLog::with(['user', 'backup'])->orderBy('id', 'desc')->paginate(10);

        $logs = View::make('coaster::partials.logs.table', array('logs' => $logs_data));

        $welcome_message = '';
        if (Storage::get('install.txt') == 'complete_welcome') {
            Storage::put('install.txt', 'complete');
            $welcome_message = '<h2>Welcome to Coaster CMS, click on the pages link to start editing content</h2><p>&nbsp;</p>';
        }

        $this->layout->title = 'Home';
        $this->layout->content = View::make('coaster::pages.dashboard', array('welcome_message' => $welcome_message, 'logs' => $logs, 'requests' => $requests_table, 'user_requests' => $user_requests_table, 'any_requests' => $any_requests, 'any_user_requests' => $any_user_requests));
    }

    public function getLogs()
    {
        $logs_data = AdminLog::with('user')->with('backup')->orderBy('id', 'desc')->paginate(50);

        $logs = View::make('coaster::partials.logs.table', array('logs' => $logs_data));

        $this->layout->title = 'Logs';
        $this->layout->content = View::make('coaster::pages.home.logs', array('logs' => $logs, 'pagination' => PaginatorRender::run($logs_data, config('coaster::admin.bootstrap_version'))));
    }

    public function getRequests()
    {
        $requests = PagePublishRequests::can_moderate([], 25);
        if ($requests->isEmpty()) {
            $requests = 'There have been no requests';
        }
        $requests_table = View::make('coaster::partials.tabs.publish_requests.table', array('show' => ['page' => true, 'status' => true, 'requested_by' => true], 'requests' => $requests))->render();

        $this->layout->title = 'Publish Requests';
        $this->layout->content = View::make('coaster::pages.home.requests', array('title' => $this->layout->title, 'requests' => $requests_table, 'pagination' => !is_string($requests) ? PaginatorRender::run($requests, 2) : ''));
    }

    public function getYourRequests()
    {
        $user_requests = PagePublishRequests::all_requests(0, ['user_id' => Auth::user()->id], 25);

        if ($user_requests->isEmpty()) {
            $user_requests = 'You have made no requests';
        }
        $user_requests_table = View::make('coaster::partials.tabs.publish_requests.table', array('show' => ['page' => true, 'status' => true, 'requested_by' => false], 'requests' => $user_requests))->render();

        $this->layout->title = 'Your Publish Requests';
        $this->layout->content = View::make('coaster::pages.home.requests', array('title' => $this->layout->title, 'requests' => $user_requests_table, 'pagination' => !is_string($user_requests) ? PaginatorRender::run($user_requests, 2) : ''));

    }

}