<?php namespace CoasterCms;

use App;
use Auth;
use CoasterCms\Events\Cms\LoadAuth;
use CoasterCms\Events\Cms\LoadMiddleware;
use CoasterCms\Events\Cms\SetViewPaths;
use CoasterCms\Helpers\Cms\Install;
use CoasterCms\Http\Middleware\AdminAuth;
use CoasterCms\Http\Middleware\GuestAuth;
use CoasterCms\Http\Middleware\SecureUpload;
use CoasterCms\Http\Middleware\UploadChecks;
use CoasterCms\Libraries\Builder\FormMessage\FormMessageInstance;
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
            'coaster.secure_upload' => SecureUpload::class,
        ];
        event(new LoadMiddleware($globalMiddleware, $routerMiddleware));
        foreach ($globalMiddleware as $globalMiddlewareClass) {
            $kernel->pushMiddleware($globalMiddlewareClass);
        }
        foreach ($routerMiddleware as $routerMiddlewareName => $routerMiddlewareClass) {
            $router->middlewareGroup($routerMiddlewareName, [$routerMiddlewareClass]);
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

        // make migrations publishable
        $this->publishes([
            __DIR__.'/../database/migrations/' => database_path().'/migrations/'
        ], 'migrations');

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

        $this->app->singleton('formMessage', function () {
            return new FormMessageInstance($this->app['request'], 'default', config('coaster::frontend.form_error_class'));
        });
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
        $this->app->register('CoasterCms\Providers\CoasterConsoleProvider');
        $this->app->register('CoasterCms\Providers\CoasterPageBuilderProvider');

        // register third party providers
        $this->app->register('Bkwld\Croppa\ServiceProvider');
        $this->app->register('Collective\Html\HtmlServiceProvider');

        // register aliases
        $loader = AliasLoader::getInstance();
        $loader->alias('Form', 'Collective\Html\FormFacade');
        $loader->alias('HTML', 'Collective\Html\HtmlFacade');
        $loader->alias('Croppa', 'CoasterCms\Helpers\Cms\Croppa\CroppaFacade');
        $loader->alias('CmsBlockInput', 'CoasterCms\Helpers\Cms\View\CmsBlockInput');
        $loader->alias('FormMessage', 'CoasterCms\Libraries\Builder\FormMessageFacade');
        $loader->alias('AssetBuilder', 'CoasterCms\Libraries\Builder\AssetBuilder');
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
            'CoasterCms\Providers\CoasterConsoleProvider',
            'CoasterCms\Providers\CoasterPageBuilderProvider',
            'Bkwld\Croppa\ServiceProvider',
            'Collective\Html\HtmlServiceProvider'
        ];
    }

}
