<?php namespace CoasterCms\Models;

use Illuminate\Database\Eloquent\Model as Eloquent;

class AdminAction extends Eloquent
{
    protected $table = 'admin_actions';

    private static $_actions;

    public static function preload($action_id)
    {
        self::_preload();
        return self::$_actions[$action_id];
    }

    public static function inherited()
    {
        self::_preload();
        $actions = [];
        foreach (self::$_actions as $action) {
            if ($action->inherit) {
                $actions[] = $action;
            }
        }
        return $actions;
    }

    public static function edit_based()
    {
        self::_preload();
        $actions = [];
        foreach (self::$_actions as $action) {
            if ($action->edit_based) {
                $actions[] = $action;
            }
        }
        return $actions;
    }

    private static function _preload()
    {
        if (!isset(self::$_actions)) {
            self::$_actions = [];
            if ($actions = self::all()) {
                foreach ($actions as $action) {
                    self::$_actions[$action->id] = $action;
                }
            }
        }
    }

}