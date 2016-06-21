<?php namespace CoasterCms;

use App;
use Auth;
use CoasterCms\Http\MiddleWare\AdminAuth;
use CoasterCms\Http\MiddleWare\GuestAuth;
use Illuminate\Support\ServiceProvider;
use Illuminate\Foundation\AliasLoader;
use Request;
use Route;

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
            return new Helpers\Core\CoasterGuard(
                'coasterguard',
                new Providers\CmsAuthUserProvider(),
                $app['session.store'],
                $app['request']
            );
        });

        Auth::setCookieJar($this->app['cookie']);

        if ($this->app['config']['coaster::installed']) {
            if (!App::runningInConsole()) {
                include __DIR__ . '/Http/adminRoutes.php';
                include __DIR__ . '/Http/cmsRoutes.php';
            }
        } else {
            if (!App::runningInConsole()) {
                Route::controller('install', 'CoasterCms\Http\Controllers\Frontend\InstallController');
                if (!Request::is('install') && !Request::is('install/*')) {
                    \redirect('install')->send();
                }
            } else {
                echo "Coaster Framework: CMS awaiting install, go to a web browser to complete installation\r\n";
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

        $router = $this->app['router'];
        $router->middleware('admin', AdminAuth::class);
        $router->middleware('guest', GuestAuth::class);

        // register other providers
        $this->app->register('Bkwld\Croppa\ServiceProvider');
        $this->app->register('Collective\Html\HtmlServiceProvider');

        // register aliases
        $loader = AliasLoader::getInstance();
        $loader->alias('Form', 'Collective\Html\FormFacade');
        $loader->alias('HTML', 'Collective\Html\HtmlFacade');
        $loader->alias('Croppa', 'CoasterCms\Helpers\Core\Croppa\CroppaFacade');
        $loader->alias('CmsBlockInput', 'CoasterCms\Helpers\Core\View\CmsBlockInput');
        $loader->alias('FormMessage', 'CoasterCms\Helpers\Core\View\FormMessage');
        $loader->alias('AssetBuilder', 'CoasterCms\Libraries\Builder\AssetBuilder');
        $loader->alias('PageBuilder', 'CoasterCms\Libraries\Builder\PageBuilder');
        $loader->alias('DateTimeHelper', 'CoasterCms\Helpers\Core\DateTimeHelper');
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
