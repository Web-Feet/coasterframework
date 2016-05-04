<?php namespace CoasterCms\Models;

use Illuminate\Database\Eloquent\Model as Eloquent;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Cache;

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

    /**
     * Latest tag (version)
     *
     * @param type var Description
     * @return {11:return type}
     */
    public static function latestTag()
    {
      if (!Cache::has('coaster::site.version')) {
          try {
              $gitHub = new \GuzzleHttp\Client(
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

}
