<?php namespace CoasterCms\Helpers\Cms;

use File;
use Route;

class Install
{

    protected static $_loadedState;

    public static function getInstallState($reload = false)
    {
        if ($reload || !isset(self::$_loadedState)) {
            static::setInstallState();
        }

        return self::$_loadedState;
    }

    public static function setInstallState($state = '')
    {
        $filePath = self::_getFilePath();
        if (!File::exists($filePath)) {
            $dir = pathinfo($filePath, PATHINFO_DIRNAME);
            if (!File::exists($dir)) {
                File::makeDirectory($dir);
            }
            $state = $state ? : 'coaster.install.permissions';
        }
        if ($state) {
            File::put($filePath, $state);
            static::$_loadedState = static::$_loadedState ? : $state;
        } else {
            static::$_loadedState = File::get($filePath);
        }
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

    protected static function _getFilePath()
    {
        return storage_path(config('coaster::site.storage_path')) . '/install.txt';
    }

}
