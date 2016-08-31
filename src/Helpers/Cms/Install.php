<?php namespace CoasterCms\Helpers\Cms;

use File;
use Route;

class Install
{

    private static $_loadedState;

    public static function getInstallState($reload = false)
    {
        if ($reload || !isset(self::$_loadedState)) {
            $installStateFile = self::_getFilePath();

            if (!File::exists($installStateFile)) {
                File::put($installStateFile, 'coaster.install.permissions');
            }

            self::$_loadedState = File::get($installStateFile);
        }

        return self::$_loadedState;
    }

    public static function setInstallState($state)
    {
        File::put(self::_getFilePath(), $state);
    }

    public static function getRedirectRoute()
    {
        $installState = self::getInstallState();
        if (!Route::getRoutes()->hasNamedRoute($installState)) {
            $installState = 'coaster.install.permissions';
        }
        return $installState;
    }

    public static function isComplete($reload = false)
    {
        return strpos(self::getInstallState($reload), 'complete') !== false;
    }

    private static function _getFilePath()
    {
        return storage_path(config('coaster::site.storage_path')) . '/install.txt';
    }

}