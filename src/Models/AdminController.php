<?php namespace CoasterCms\Models;

use Eloquent;

class AdminController extends Eloquent
{
    /**
     * @var string
     */
    protected $table = 'admin_controllers';

    /**
     * @var array
     */
    private static $_controllers;

    /**
     * @param $controller_id
     * @return array
     */
    public static function preload($controller_id)
    {
        self::_preload();
        return self::$_controllers[$controller_id];
    }

    /**
     * @return array
     */
    public static function preload_all()
    {
        self::_preload();
        return self::$_controllers;
    }

    /**
     * Load controllers from db once
     */
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