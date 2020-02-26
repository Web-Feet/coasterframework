<?php

namespace CoasterCms\Models;

use Illuminate\Support\Facades\Auth;
use Eloquent;

class AdminLog extends Eloquent
{
    /**
     * @var string
     */
    protected $table = 'admin_logs';

    /**
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function user()
    {
        return $this->belongsTo('CoasterCms\Models\User');
    }

    /**
     * @return mixed
     */
    public function backup()
    {
        return $this->belongsTo('CoasterCms\Models\Backup', 'id', 'log_id')->where('primary_id', '<>', 0);
    }

    /**
     * @deprecated
     * @param string $log
     * @return int
     */
    public static function new_log($log)
    {
        $newLog = new self;
        $newLog->user_id = Auth::user()->id;
        $newLog->log = $log;
        $newLog->save();
        return $newLog->id;
    }

    /**
     * @param string $log
     * @return int
     */
    public static function log($log)
    {
        return static::new_log($log);
    }

    /**
     * @param string $log
     * @param mixed $object
     * @return int
     */
    public static function logWithBackup($log, $object)
    {
        // default text if log string empty
        $log = $log ?: 'Backed up \'' . substr(strrchr(get_class($object), '\\'), 1) . '\'  ID ' . $object->id;

        // record and get log ID
        $logId = static::log($log);

        // backup object
        Backup::new_backup($logId, get_class($object), $object);

        return $logId;
    }
}
