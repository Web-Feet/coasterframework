<?php namespace CoasterCms\Helpers;

use Illuminate\Support\Facades\Storage;

class InstallCheck
{
    private static $_site;

    public static function getSite()
    {
        return isset(self::$_site)?self::$_site:false;
    }

    public static function setSite($site)
    {
        self::$_site = $site;
    }

    public static function isComplete($site = null)
    {
        $site = $site?:self::$_site;

        return strpos('complete', self::getStatus($site)) !== false;
    }

    public static function getStatus($site = null)
    {
        $site = $site?:self::$_site;

        $file = $site?'install_'.$site:'install';
        $file .= '.txt';

        if (!Storage::exists($file)) {
            return false;
        } else {
            return Storage::get($file);
        }
    }

    public static function setStatus($status, $site = null)
    {
        $site = $site?:self::$_site;

        $file = $site?'install_'.$site:'install';
        $file .= '.txt';

        Storage::put($file, $status);
    }

}