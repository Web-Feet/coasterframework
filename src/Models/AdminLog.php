<?php namespace CoasterCms\Models;

use Illuminate\Database\Eloquent\Model as Eloquent;
use Illuminate\Support\Facades\Auth;

class AdminLog extends Eloquent
{

    protected $table = 'admin_logs';

    public function user()
    {
        return $this->belongsTo('CoasterCms\Models\User');
    }

    public function backup()
    {
        return $this->belongsTo('CoasterCms\Models\Backup', 'id', 'log_id')->where('primary_id', '<>', 0);
    }

    public static function new_log($log)
    {
        $new_log = new self;
        $new_log->user_id = Auth::user()->id;
        $new_log->log = $log;
        $new_log->save();
        return $new_log->id;
    }

}