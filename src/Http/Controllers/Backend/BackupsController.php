<?php namespace CoasterCms\Http\Controllers\Backend;

use CoasterCms\Models\AdminLog;
use CoasterCms\Models\Backup;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Request;

class BackupsController extends _Base
{

    public function post_undo()
    {
        $log_ids = Request::input('log_ids');
        $restored = 0;
        if (!empty($log_ids)) {
            if (is_string($log_ids)) {
                $log_ids = [$log_ids];
            }
            foreach ($log_ids as $log_id) {
                $log = AdminLog::find($log_id);
                if (!empty($log) && ((time() - strtotime($log->created_at)) < config('coaster::admin.undo_time') && $log->user_id == $this->user->id) || Auth::action('backups.restore')) {
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