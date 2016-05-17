<?php namespace CoasterCms\Models;

use Cache;
use Config;
use DB;
use Eloquent;
use File;
use \GuzzleHttp\Client;

Class Setting extends Eloquent
{
    protected $table = 'settings';
    public static $settings = array();

    private static $_blogConn;

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

    /**
     * Latest tag (version)
     * @return string
     */
    public static function latestTag()
    {
      if (!Cache::has('coaster::site.version')) {
          try {
              $gitHub = new Client(
                  [
                      'base_uri' => 'https://api.github.com/repos/'
                  ]
              );
              $latestRelease = json_decode($gitHub->request('GET', 'Web-Feet/coasterframework/releases/latest')->getBody());
              Cache::put('coaster::site.version', $latestRelease->tag_name, 30);
          } catch (\Exception $e) {
              return 'not-found';
          }
      }
      return Cache::get('coaster::site.version');
    }

    public static function blogConnection()
    {
        if (!isset(self::$_blogConn)) {
            if (config('coaster::blog.connection')) {
                self::$_blogConn = new \PDO(config('coaster::blog.connection'), config('coaster::blog.username'), config('coaster::blog.password'));
            } else {
                self::$_blogConn = DB::connection()->getPdo();
            }
        }
        return self::$_blogConn;
    }

}
