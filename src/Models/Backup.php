<?php namespace CoasterCms\Models;

use Eloquent;

class Backup extends Eloquent
{
    protected $table = 'backups';

    public static function new_backup($log, $model, $data)
    {
        if (!empty($data)) {
            $new_backup = new self;
            $new_backup->log_id = $log;
            if (!is_array($data) && !is_a($data, 'Illuminate\Database\Eloquent\Collection')) {
                $new_backup->primary_id = $data->id;
            } else {
                $new_backup->primary_id = 0;
            }
            $new_backup->model = $model;
            $new_backup->data = serialize($data);
            $new_backup->save();
        }
    }

    public static function restore($log_id)
    {
        $backups = self::where('log_id', '=', $log_id)->get();
        if (!empty($backups)) {
            foreach ($backups as $backup) {
                $data = unserialize($backup->data);
                if (is_array($data) || is_a($data, 'Illuminate\Database\Eloquent\Collection')) {
                    foreach ($data as $obj) {
                        self::restore_data($obj, $backup->model, $backup->primary_id);
                    }
                } else {
                    self::restore_data($data, $backup->model, $backup->primary_id);
                }
            }
        }
        return 1;
    }

    private static function restore_data($obj, $model, $key)
    {
        $if_exists = $model::find($key);
        if (!empty($if_exists)) {
            // delete existing data
            $if_exists->delete();
        }
        $obj->exists = false;
        if (method_exists($obj, 'restore')) {
            $model::restore($obj);
        } else {
            $obj->save();
        }

    }

}