<?php namespace CoasterCms\Providers;

use App;
use CoasterCms\Helpers\Core\Install;
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

        if (Install::isComplete()) {
            if (!$db) {
                abort(503, 'Database error, settings table could not be found');
            }
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
