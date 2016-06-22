<?php namespace CoasterCms\Http\Controllers\AdminControllers;

use Auth;
use CoasterCms\Helpers\Core\Install;
use CoasterCms\Helpers\Core\View\PaginatorRender;
use CoasterCms\Http\Controllers\AdminController as Controller;
use CoasterCms\Models\AdminLog;
use CoasterCms\Models\PagePublishRequests;
use CoasterCms\Models\PageSearchLog;
use CoasterCms\Models\Setting;
use File;
use View;

class HomeController extends Controller
{

    public function getIndex()
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
        if (Install::getInstallState() == 'complete-welcome') {
            Install::setInstallState('complete');
            $welcome_message = '<h2>Welcome to Coaster CMS, click on the pages link to start editing content</h2><p>&nbsp;</p>';
        }

        // Search data

        $any_searches = PageSearchLog::hasSearchData();
        if (Auth::action('search.index') && $any_searches)
        {
            $search_data = PageSearchLog::orderBy('count', 'DESC')->orderBy('updated_at', 'DESC')->limit(5)->get();
            $search_view = View::make('coaster::pages.search', array('search_data' => $search_data));
        }
        else
        {
            $any_searches = false;
            $search_view = '';
        }
        $data = array('welcome_message' => $welcome_message, 'logs' => $logs, 'requests' => $requests_table, 'user_requests' => $user_requests_table, 'any_requests' => $any_requests, 'any_user_requests' => $any_user_requests);

        $data['any_searches'] = $any_searches;
        $data['search_logs'] = $search_view;

        $upgrade = new \stdClass;
        $upgrade->from = config('coaster::site.version');
        $upgrade->to = Setting::latestTag();
        $upgrade->required = version_compare(config('coaster::site.version'), $upgrade->to, '<');
        $upgrade->allowed = Auth::action('system.upgrade');

        $data['upgrade'] = $upgrade;

        $this->layoutData['title'] = 'Admin home';
        $this->layoutData['content'] = View::make('coaster::pages.dashboard', $data);
    }

    public function getLogs()
    {
        $logs_data = AdminLog::with('user')->with('backup')->orderBy('id', 'desc')->paginate(50);

        $logs = View::make('coaster::partials.logs.table', array('logs' => $logs_data));

        $this->layoutData['title'] = 'Logs';
        $this->layoutData['content'] = View::make('coaster::pages.home.logs', array('logs' => $logs, 'pagination' => PaginatorRender::run($logs_data, config('coaster::admin.bootstrap_version'))));
    }

    public function getRequests()
    {
        $requests = PagePublishRequests::can_moderate([], 25);
        if ($requests->isEmpty()) {
            $requests = 'There have been no requests';
        }
        $requests_table = View::make('coaster::partials.tabs.publish_requests.table', array('show' => ['page' => true, 'status' => true, 'requested_by' => true], 'requests' => $requests))->render();

        $this->layoutData['title'] = 'Publish Requests';
        $this->layoutData['content'] = View::make('coaster::pages.home.requests', array('title' => $this->layoutData['title'], 'requests' => $requests_table, 'pagination' => !is_string($requests) ? PaginatorRender::run($requests, 2) : ''));
    }

    public function getYourRequests()
    {
        $user_requests = PagePublishRequests::all_requests(0, ['user_id' => Auth::user()->id], 25);

        if ($user_requests->isEmpty()) {
            $user_requests = 'You have made no requests';
        }
        $user_requests_table = View::make('coaster::partials.tabs.publish_requests.table', array('show' => ['page' => true, 'status' => true, 'requested_by' => false], 'requests' => $user_requests))->render();

        $this->layoutData['title'] = 'Your Publish Requests';
        $this->layoutData['content'] = View::make('coaster::pages.home.requests', array('title' => $this->layoutData['title'], 'requests' => $user_requests_table, 'pagination' => !is_string($user_requests) ? PaginatorRender::run($user_requests, 2) : ''));

    }

}
