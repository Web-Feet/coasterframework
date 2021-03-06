<?php namespace CoasterCms;

use Auth;
use CoasterCms\Events\Cms\LoadAuth;
use CoasterCms\Events\Cms\LoadMiddleware;
use CoasterCms\Events\Cms\SetViewPaths;
use CoasterCms\Http\Middleware\AdminAuth;
use CoasterCms\Http\Middleware\GuestAuth;
use CoasterCms\Http\Middleware\SecureUpload;
use CoasterCms\Http\Middleware\UploadChecks;
use CoasterCms\Libraries\Builder\FormMessage;
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
            'coaster.cms' => [],
            'coaster.admin' => [AdminAuth::class],
            'coaster.guest' => [GuestAuth::class],
            'coaster.secure_upload' => [SecureUpload::class],
        ];
        event(new LoadMiddleware($globalMiddleware, $routerMiddleware));
        foreach ($globalMiddleware as $globalMiddlewareClass) {
            $kernel->pushMiddleware($globalMiddlewareClass);
        }
        foreach ($routerMiddleware as $routerMiddlewareName => $routerMiddlewareClass) {
            $router->middlewareGroup($routerMiddlewareName, $routerMiddlewareClass);
        }

        // use coater guard and user provider
        $authGuard = Helpers\Cms\CoasterGuard::class;
        $authUserProvider = Providers\CoasterAuthUserProvider::class;
        event(new LoadAuth($authGuard, $authUserProvider));
        if ($authGuard && $authUserProvider) {
            Auth::extend('coaster', function ($app) use ($authGuard, $authUserProvider) {
                $guard = new $authGuard(
                    'coasterguard',
                    new $authUserProvider($app['hash'], config('auth.providers.users.model')),
                    $app['session.store'],
                    $app['request']
                );

                // set cookie jar for cookies
                if (method_exists($guard, 'setCookieJar')) {
                    $guard->setCookieJar($this->app['cookie']);
                }
                if (method_exists($guard, 'setDispatcher')) {
                    $guard->setDispatcher($this->app['events']);
                }
                if (method_exists($guard, 'setRequest')) {
                    $guard->setRequest($this->app->refresh('request', $guard, 'setRequest'));
                }

                return $guard;
            });
        }

        // load coaster views
        $adminViews = [
            rtrim(config('coaster::admin.view'), '/')
        ];
        $frontendViews = [
            rtrim(config('coaster::frontend.view'), '/')
        ];
        event(new SetViewPaths($adminViews, $frontendViews));
        $this->loadViewsFrom($adminViews, 'coaster');
        $this->loadViewsFrom($frontendViews, 'coasterCms');

        $this->app->singleton('formMessage', function () {
            return new FormMessage($this->app['session'], 'default', config('coaster::frontend.form_error_class'));
        });
    }

    /**
     * Register the service provider.
     *
     * @return void
     */
    public function register()
    {
        if (!defined('COASTER_ROOT')) {
            define('COASTER_ROOT', dirname(__DIR__));
        }

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
        $loader->alias('FormMessage', 'CoasterCms\Facades\FormMessage');
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
