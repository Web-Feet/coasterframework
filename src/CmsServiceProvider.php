<?php namespace CoasterCms;

use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Request;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\ServiceProvider;

class CmsServiceProvider extends ServiceProvider
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
        $this->loadViewsFrom(base_path(trim(config('coaster::admin.view'), '/')), 'coaster');

        $this->app['config']['auth.guards.web.driver'] = 'coaster';
        $this->app['config']['auth.providers.users.model'] = Models\User::class;

        Auth::extend('coaster', function ($app, $name, array $config) {
            return new Helpers\CoasterGuard(
                'coasterguard',
                new Providers\CmsAuthUserProvider(),
                $app['session.store'],
                $app['request']
            );
        });

        Auth::setCookieJar($this->app['cookie']);

        if ($this->app['config']['coaster::installed']) {
            if (!App::runningInConsole()) {
                $router = $this->app['router'];
                $router->middleware('admin', \CoasterCms\Http\MiddleWare\AdminAuth::class);
                $router->middleware('guest', \CoasterCms\Http\MiddleWare\GuestAuth::class);
                try {
                    include app_path() . '/Http/routes.php';
                } catch (\Exception $e) {

                }
                include __DIR__ . '/Http/routes.php';
            }
        } else {
            if (!App::runningInConsole()) {
                Route::controller('install', 'CoasterCms\Http\Controllers\Frontend\InstallController');
                if (!Request::is('install') && !Request::is('install/*')) {
                    return redirect('install')->send();
                }
            } else {
                echo "Coaster Framework: CMS not installed, go to a web browser to complete installation\r\n";
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
        // load cms settings first
        $this->app->register('CoasterCms\Providers\CmsSettingsProvider');

        // register other providers
        $this->app->register('Bkwld\Croppa\ServiceProvider');
        $this->app->register('Collective\Html\HtmlServiceProvider');

        // register aliases
        $loader = \Illuminate\Foundation\AliasLoader::getInstance();
        $loader->alias('Form', 'Collective\Html\FormFacade');
        $loader->alias('HTML', 'Collective\Html\HtmlFacade');
        $loader->alias('Croppa', 'CoasterCms\Helpers\CroppaFacade');
        $loader->alias('CmsBlockInput', 'CoasterCms\Helpers\View\CmsBlockInput');
        $loader->alias('FormMessage', 'CoasterCms\Helpers\View\FormMessage');
        $loader->alias('AssetBuilder', 'CoasterCms\Libraries\Builder\AssetBuilder');
        $loader->alias('PageBuilder', 'CoasterCms\Libraries\Builder\PageBuilder');
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
