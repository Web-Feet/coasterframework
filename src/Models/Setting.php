<?php namespace CoasterCms\Models;

use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\File;

Class Setting extends _BaseEloquent
{
    protected $table = 'settings';
    public static $settings = array();

    public static function loadAll($configFolder, $namespace, $db = true)
    {
        $files = File::files($configFolder);

        foreach ($files as $file) {
            $config = File::getRequire($file);
            Config::set($namespace . '::' . pathinfo($file, PATHINFO_FILENAME), $config);
        }

        if ($db) {
            $settings = self::all();
            foreach ($settings as $setting) {
                Config::set($namespace . '::' . $setting->name, $setting->value);
            }
        }

    }

}