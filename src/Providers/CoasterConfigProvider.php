<?php namespace CoasterCms\Providers;

use CoasterCms\Events\Cms\LoadConfig;
use CoasterCms\Events\Cms\LoadedConfig;
use CoasterCms\Helpers\Cms\Install;
use CoasterCms\Models\Setting;
use CoasterCms\Models\User;
use Illuminate\Support\ServiceProvider;
use Schema;

class CoasterConfigProvider extends ServiceProvider
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

        $configFile = __DIR__ . '/../../config';
        $useDatabaseSettings = $db;
        
        event(new LoadConfig($configFile, $useDatabaseSettings));
        
        Setting::loadAll($configFile, 'coaster', $useDatabaseSettings);

        $overrideConfigValues = [
            'auth.guards.web.driver' => 'coaster',
            'auth.providers.users.model' => User::class,
            'croppa.src_dir' => public_path(),
            'croppa.crops_dir' => public_path() . '/cache',
            'croppa.path' => 'cache/(' . config('coaster::frontend.croppa_handle') . ')$'
        ];

        event(new LoadedConfig($overrideConfigValues));

        foreach ($overrideConfigValues as $attribute => $value) {
            app()->config->set($attribute, $value);
        }
        
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
