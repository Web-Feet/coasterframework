<?php namespace CoasterCms\Models;

use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Request;

class PagePublishRequests extends _BaseEloquent
{

    protected $table = 'page_publish_requests';

    public function user()
    {
        return $this->belongsTo('CoasterCms\Models\User');
    }

    public function page_version()
    {
        return $this->belongsTo('CoasterCms\Models\PageVersion', 'page_version_id');
    }

    public static function can_moderate($where = array(), $paginate = 100)
    {
        $requests_q = self::with('page_version');
        foreach ($where as $column => $value) {
            $requests_q = $requests_q->where($column, '=', $value);
        }
        $requests = $requests_q->orderBy('updated_at', 'desc')->orderBy('id', 'desc')->get();

        $requests_can_approve = [];
        if (!empty($requests)) {
            foreach ($requests as $request) {
                if (Auth::action('pages.version-publish', ['page_id', $request->page_version->page_id])) {
                    $requests_can_approve[] = $request;
                }
            }
        }

        $currentPage = Request::input('page', 1);
        $requests_can_approve_s = array_slice($requests_can_approve, (($currentPage - 1) * $paginate), $paginate);
        $requests_can_approve_p = new LengthAwarePaginator($requests_can_approve_s, count($requests_can_approve), $paginate, $currentPage);
        $requests_can_approve_p->setPath(Request::getPathInfo());

        return $requests_can_approve_p;
    }

    public static function all_requests($page_id, $where = array(), $paginate = 100)
    {
        $requests_q = self::with('page_version');
        foreach ($where as $column => $value) {
            $requests_q = $requests_q->where($column, '=', $value);
        }
        $requests = $requests_q->orderBy('updated_at', 'desc')->orderBy('id', 'desc')->get();

        $filtered_requests = [];
        if (!empty($requests)) {
            foreach ($requests as $request) {
                if ($page_id == 0 || $page_id == $request->page_version->page_id) {
                    $filtered_requests[] = $request;
                }
            }
        }

        $currentPage = Request::input('page', 1);
        $filtered_requests_s = array_slice($filtered_requests, (($currentPage - 1) * $paginate), $paginate);
        $filtered_requests_p = new LengthAwarePaginator($filtered_requests_s, count($filtered_requests), $paginate, $currentPage);
        $filtered_requests_p->setPath(Request::getPathInfo());

        return $filtered_requests_p;
    }

    public function process($action)
    {
        if ($this->status == 'awaiting') {
            $page_version = PageVersion::find($this->page_version_id);
            if (!empty($page_version) && (Auth::action('pages.version-publish', ['page_id' => $page_version->page_id]) || (Auth::user()->id == $this->user_id && $action == 'cancelled'))) {
                if ($action == 'approved') {
                    $page_version->publish(true);
                }
                $this->status = $action;
                $this->mod_id = Auth::user()->id;
                $this->save();
                return 1;
            }
        }
        return 0;
    }

    public static function add($page_id, $version_id, $note = null)
    {
        $page_version = PageVersion::where('page_id', '=', $page_id)->where('version_id', '=', $version_id)->first();
        if ($page_version) {

            $page_requests = PagePublishRequests::where('status', '=', 'awaiting')->where('user_id', '=', Auth::user()->id)->get();
            if (!$page_requests->isEmpty()) {
                $page_version_ids = array();
                foreach ($page_requests as $page_request) {
                    $page_version_ids[] = $page_request->page_version_id;
                }
                $previous_requested_page_version = PageVersion::where('page_id', '=', $page_id)->whereIn('id', $page_version_ids)->first();
            }

            if (!empty($previous_requested_page_version)) {
                $page_request = PagePublishRequests::where('page_version_id', '=', $previous_requested_page_version->id)->where('user_id', '=', Auth::user()->id)->where('status', '=', 'awaiting')->first();
                if ($previous_requested_page_version->id == $page_version->id) {
                    $page_request->note = $note;
                    $page_request->save();
                    return 1;
                } else {
                    $page_request->status = 'cancelled';
                    $page_request->save();
                }
            }

            $new_request = new self;
            $new_request->page_version_id = $page_version->id;
            $new_request->status = 'awaiting';
            $new_request->user_id = Auth::user()->id;
            $new_request->note = $note;
            $new_request->save();
            return 1;
        }
        return 0;
    }

    public static function restore($obj)
    {
        $obj->save();
    }

}