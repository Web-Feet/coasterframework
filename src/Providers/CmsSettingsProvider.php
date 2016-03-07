<?php namespace CoasterCms\Providers;

use CoasterCms\Models\Setting;
use Illuminate\Support\Facades\Request;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\ServiceProvider;

class CmsSettingsProvider extends ServiceProvider
{

    /**
     * Indicates if loading of the provider is deferred.
     *
     * @var bool
     */
    protected $defer = false;

    /**
     * Bootstrap the application events.
     *
     * @return void
     */
    public function boot()
    {
        $db = false;

        if (Storage::exists('install.txt') && strpos(Storage::get('install.txt'), 'complete') !== false) {
            $this->app['config']['coaster::installed'] = 1;
            try {
                if (Schema::hasTable('settings')) {
                    $db = true;
                }
            } catch (\PDOException $e) {
            }
            if (!$db) {
                die('Database error, settings table could not be found');
            }
        } else {
            $this->app['config']['coaster::installed'] = 0;
            if (!Storage::exists('install.txt')) {
                Storage::put('install.txt', 'set-env');
            }
        }

        $this->app['config']['coaster::site.secure'] = Request::isSecure();

        Setting::loadAll(__DIR__ . '/../../config', 'coaster', $db);
        // override croppa settings
        $this->app['config']['croppa.src_dir'] = public_path();
        $this->app['config']['croppa.crops_dir'] = public_path() . '/cache';
        $this->app['config']['croppa.path'] = 'cache/(' . config('coaster::frontend.croppa_handle') . ')$';
    }

    /**
     * Register the service provider.
     *
     * @return void
     */
    public function register()
    {

    }

    /**
     * Get the services provided by the provider.
     *
     * @return array
     */
    public function provides()
    {
        return array();
    }

}
