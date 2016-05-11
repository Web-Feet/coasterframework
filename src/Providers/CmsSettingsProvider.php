<?php namespace CoasterCms\Providers;

use CoasterCms\Models\Setting;
use File;
use Illuminate\Support\ServiceProvider;
use Schema;

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
        try {
            if (Schema::hasTable('settings')) {
                $db = true;
            }
        } catch (\PDOException $e) {
        }

        Setting::loadAll(__DIR__ . '/../../config', 'coaster', $db);
        // override croppa settings
        $this->app['config']['croppa.src_dir'] = public_path();
        $this->app['config']['croppa.crops_dir'] = public_path() . '/cache';
        $this->app['config']['croppa.path'] = 'cache/(' . config('coaster::frontend.croppa_handle') . ')$';

        $storagePath = storage_path(config('coaster::site.storage_path')) . '/';
        if (File::exists($storagePath.'install.txt') && strpos(File::get($storagePath.'install.txt'), 'complete') !== false) {
            $this->app['config']['coaster::installed'] = 1;
            if (!$db) {
                die('Database error, settings table could not be found');
            }
        } else {
            $this->app['config']['coaster::installed'] = 0;
        }

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
