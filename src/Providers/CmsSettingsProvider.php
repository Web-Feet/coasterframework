<?php namespace CoasterCms\Providers;

use CoasterCms\Helpers\InstallCheck;
use CoasterCms\Models\Setting;
use Illuminate\Support\Facades\Request;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\URL;
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
        for ($i=1; $i<100 ;$i++) {
            $url = getenv('MULTI_SITE_'.$i.'_URL');
            if (!$url) break;
            if (strpos(URL::to('/'), trim(getenv('MULTI_SITE_'.$i.'_URL'), '/')) === 0) {
                if ($site = getenv('MULTI_SITE_'.$i.'_DB_PREFIX')) {
                    config(['database.connections.' . config('database.default') . '.prefix' => getenv('MULTI_SITE_' . $i . '_DB_PREFIX')]);
                    InstallCheck::setSite($site);
                }

            }
        }

        if(!empty($_COOKIE['db_prefix'])) {
            config(['database.connections.' . config('database.default') . '.prefix' => $_COOKIE['db_prefix']]);
        }

        $db = false;

        if (InstallCheck::isComplete()) {
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
            if (!InstallCheck::getStatus()) {
                InstallCheck::setStatus('set-env');
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
