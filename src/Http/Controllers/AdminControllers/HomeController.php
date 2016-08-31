<?php namespace CoasterCms\Http\Controllers\AdminControllers;

use Auth;
use CoasterCms\Helpers\Cms\Install;
use CoasterCms\Helpers\Cms\View\PaginatorRender;
use CoasterCms\Http\Controllers\AdminController as Controller;
use CoasterCms\Models\AdminLog;
use CoasterCms\Models\PagePublishRequests;
use CoasterCms\Models\PageSearchLog;
use CoasterCms\Models\Setting;
use GuzzleHttp\Client;
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

        $firstTimer = false;
        if (Install::getInstallState() == 'complete-welcome') {
            Install::setInstallState('complete');
            $firstTimer = true;
        }

        $coasterBlog = 'https://www.coastercms.org/blog/';
        $coasterBlogHttpClient = new Client(['base_uri' => $coasterBlog.'wp-json/wp/v2/']);
        try {
            $latestPosts = $coasterBlogHttpClient->request('GET', 'posts', ['query' => ['per_page' => 3]]);
            $latestPosts = json_decode($latestPosts->getBody());
        } catch (\Exception $e) {
            $latestPosts = [];
        }
        $posts = collect($latestPosts);

        // Search data

        $numbSearches = PageSearchLog::count();
        if (Auth::action('search.index') && $numbSearches) {
            $search_data = PageSearchLog::orderBy('count', 'DESC')->orderBy('updated_at', 'DESC')->limit(6)->get();
            $numbSearches = $search_data->count();
            $search_view = View::make('coaster::pages.search', array('search_data' => $search_data));
        }
        else {
            $search_view = '';
        }
        $data = array('firstTimer' => $firstTimer, 'coasterPosts' => $posts, 'coasterBlog' => $coasterBlog, 'logs' => $logs, 'requests' => $requests_table, 'user_requests' => $user_requests_table, 'any_requests' => $any_requests, 'any_user_requests' => $any_user_requests);

        $data['searchLogNumber'] = $numbSearches;
        $data['searchLogs'] = $search_view;

        $data['canViewSettings'] = Auth::action('system');

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
        $this->layoutData['content'] = View::make('coaster::pages.home.logs', array('logs' => $logs, 'pagination' => PaginatorRender::admin($logs_data)));
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
