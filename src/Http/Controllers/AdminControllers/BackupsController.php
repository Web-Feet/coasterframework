<?php namespace CoasterCms\Http\Controllers\AdminControllers;

use Auth;
use CoasterCms\Http\Controllers\AdminController as Controller;
use CoasterCms\Models\AdminLog;
use CoasterCms\Models\Backup;
use Request;

class BackupsController extends Controller
{

    public function postUndo()
    {
        $log_ids = Request::input('log_ids');
        $restored = 0;
        if (!empty($log_ids)) {
            if (is_string($log_ids)) {
                $log_ids = [$log_ids];
            }
            foreach ($log_ids as $log_id) {
                $log = AdminLog::find($log_id);
                if (!empty($log) && ((time() - strtotime($log->created_at)) < config('coaster::admin.undo_time') && $log->user_id == Auth::user()->id) || Auth::action('backups.restore')) {
                    if (Backup::restore($log_id) == 1) {
                        AdminLog::new_log('Restored log ID #' . $log_id);
                        $restored = 1;
                    } else {
                        $restored = 0;
                    }
                }
            }
        }
        return $restored;
    }

}