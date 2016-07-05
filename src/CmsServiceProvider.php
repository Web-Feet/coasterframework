<?php namespace CoasterCms;

use App;
use Auth;
use CoasterCms\Events\LoadRouteFile;
use CoasterCms\Helpers\Cms\Install;
use CoasterCms\Http\MiddleWare\AdminAuth;
use CoasterCms\Http\MiddleWare\GuestAuth;
use CoasterCms\Http\MiddleWare\UploadChecks;
use Illuminate\Foundation\Http\Kernel;
use Illuminate\Routing\Router;
use Illuminate\Support\ServiceProvider;
use Illuminate\Foundation\AliasLoader;

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
     * @param Router $router
     * @param Kernel $kernel
     * @return void
     */
    public function boot(Router $router, Kernel $kernel)
    {
        // add router middleware
        $kernel->pushMiddleware(UploadChecks::class);
        $router->middleware('coaster.admin', AdminAuth::class);
        $router->middleware('coaster.guest', GuestAuth::class);

        // use coater guard and user provider
        Auth::extend('coaster', function ($app) {
            return new Helpers\Cms\CoasterGuard(
                'coasterguard',
                new Providers\CoasterAuthUserProvider,
                $app['session.store'],
                $app['request']
            );
        });

        // set cookie jar for cookies
        Auth::setCookieJar($this->app['cookie']);

        // load coaster views
        $this->loadViewsFrom(base_path(trim(config('coaster::admin.view'), '/')), 'coaster');
        $this->loadViewsFrom(base_path(trim(config('coaster::frontend.view'), '/')), 'coasterCms');

        // run routes if not in console
        if (!App::runningInConsole()) {
            $routeFile = __DIR__ . '/Http/routes.php';
        } else {
            $routeFile = '';
        }
        event(new LoadRouteFile($routeFile));
        if ($routeFile && file_exists($routeFile)) {
            include $routeFile;
        }

        // if in console and not installed, display notice
        if (App::runningInConsole() && !Install::isComplete()) {
            echo "Coaster Framework: CMS awaiting install, go to a web browser to complete installation\r\n";
        }
    }

    /**
     * Register the service provider.
     *
     * @return void
     */
    public function register()
    {
        $this->app->register('CoasterCms\Providers\CoasterEventsProvider');
        $this->app->register('CoasterCms\Providers\CoasterConfigProvider');

        // register third party providers
        $this->app->register('Bkwld\Croppa\ServiceProvider');
        $this->app->register('Collective\Html\HtmlServiceProvider');

        // register aliases
        $loader = AliasLoader::getInstance();
        $loader->alias('Form', 'Collective\Html\FormFacade');
        $loader->alias('HTML', 'Collective\Html\HtmlFacade');
        $loader->alias('Croppa', 'CoasterCms\Helpers\Cms\Croppa\CroppaFacade');
        $loader->alias('CmsBlockInput', 'CoasterCms\Helpers\Cms\View\CmsBlockInput');
        $loader->alias('FormMessage', 'CoasterCms\Libraries\Builder\FormMessage');
        $loader->alias('AssetBuilder', 'CoasterCms\Libraries\Builder\AssetBuilder');
        $loader->alias('PageBuilder', 'CoasterCms\Libraries\Builder\PageBuilder');
        $loader->alias('DateTimeHelper', 'CoasterCms\Helpers\Cms\DateTimeHelper');
    }

    /**
     * Get the services provided by the provider.
     *
     * @return array
     */
    public function provides()
    {
        return [
            'CoasterCms\Providers\CoasterConfigProvider',
            'CoasterCms\Providers\CoasterEventsProvider',
            'Bkwld\Croppa\ServiceProvider',
            'Collective\Html\HtmlServiceProvider'
        ];
    }

}
