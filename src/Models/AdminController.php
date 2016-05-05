<?php namespace CoasterCms\Models;

use Eloquent;

class AdminController extends Eloquent
{
    protected $table = 'admin_controllers';

    private static $_controllers;

    public static function preload($controller_id)
    {
        self::_preload();
        return self::$_controllers[$controller_id];
    }

    public static function preload_all()
    {
        self::_preload();
        return self::$_controllers;
    }

    private static function _preload()
    {
        if (!isset(self::$_controllers)) {
            self::$_controllers = [];
            if ($controllers = self::all()) {
                foreach ($controllers as $controller) {
                    self::$_controllers[$controller->id] = $controller;
                }
            }
        }
    }

}