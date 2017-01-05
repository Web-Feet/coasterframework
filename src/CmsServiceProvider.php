<?php namespace CoasterCms;

use App;
use Auth;
use CoasterCms\Events\Cms\LoadAuth;
use CoasterCms\Events\Cms\LoadMiddleware;
use CoasterCms\Events\Cms\LoadRouteFile;
use CoasterCms\Events\Cms\SetViewPaths;
use CoasterCms\Helpers\Cms\Install;
use CoasterCms\Http\Middleware\AdminAuth;
use CoasterCms\Http\Middleware\GuestAuth;
use CoasterCms\Http\Middleware\PageBuilderInit;
use CoasterCms\Http\Middleware\UploadChecks;
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
        $globalMiddleware = [
            UploadChecks::class
        ];
        $routerMiddleware = [
            'coaster.admin' => AdminAuth::class,
            'coaster.guest' => GuestAuth::class,
            'coaster.pagebuilder.init' => PageBuilderInit::class
        ];
        event(new LoadMiddleware($globalMiddleware, $routerMiddleware));
        foreach ($globalMiddleware as $globalMiddlewareClass) {
            $kernel->pushMiddleware($globalMiddlewareClass);
        }
        foreach ($routerMiddleware as $routerMiddlewareName => $routerMiddlewareClass) {
            $router->middleware($routerMiddlewareName, $routerMiddlewareClass);
        }

        // use coater guard and user provider
        $authGuard = Helpers\Cms\CoasterGuard::class;
        $authUserProvider = Providers\CoasterAuthUserProvider::class;
        event(new LoadAuth($authGuard, $authUserProvider));
        if ($authGuard && $authUserProvider) {
            Auth::extend('coaster', function ($app) use ($authGuard, $authUserProvider) {
                return new $authGuard(
                    'coasterguard',
                    new $authUserProvider,
                    $app['session.store'],
                    $app['request']
                );
            });
        }

        // set cookie jar for cookies
        Auth::setCookieJar($this->app['cookie']);

        // load coaster views
        $adminViews = [
            base_path(trim(config('coaster::admin.view'), '/'))
        ];
        $frontendViews = [
            base_path(trim(config('coaster::frontend.view'), '/'))
        ];
        event(new SetViewPaths($adminViews, $frontendViews));
        $this->loadViewsFrom($adminViews, 'coaster');
        $this->loadViewsFrom($frontendViews, 'coasterCms');

        // run routes if not in console
        $routeFile = __DIR__ . '/Http/routes.php';
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
