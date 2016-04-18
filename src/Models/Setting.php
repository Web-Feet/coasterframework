<?php namespace CoasterCms\Models;

use Illuminate\Database\Eloquent\Model as Eloquent;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\File;

Class Setting extends Eloquent
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
                if ($setting->value != '' || strpos($setting->name, 'key') !== 0) {
                    Config::set($namespace . '::' . $setting->name, $setting->value);
                }
            }
        }

    }

}