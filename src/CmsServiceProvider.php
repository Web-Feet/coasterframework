<?php namespace CoasterCms;

use App;
use Auth;
use CoasterCms\Helpers\Core\Page\Install;
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
        // override auth settings
        $this->app['config']['auth.guards.web.driver'] = 'coaster';
        $this->app['config']['auth.providers.users.model'] = Models\User::class;

        // add router middleware
        $kernel->pushMiddleware(UploadChecks::class);
        $router->middleware('coaster.admin', AdminAuth::class);
        $router->middleware('coaster.guest', GuestAuth::class);

        // load coaster views
        $this->loadViewsFrom(base_path(trim(config('coaster::admin.view'), '/')), 'coaster');

        // use coater guard and user provider
        Auth::extend('coaster', function ($app) {
            return new Helpers\Core\CoasterGuard(
                'coasterguard',
                new Providers\CoasterAuthUserProvider,
                $app['session.store'],
                $app['request']
            );
        });

        // set cookie jar for cookies
        Auth::setCookieJar($this->app['cookie']);

        // run routes if not in console
        if (!App::runningInConsole()) {
            include __DIR__ . '/Http/routes.php';
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
        $loader->alias('Croppa', 'CoasterCms\Helpers\Core\Croppa\CroppaFacade');
        $loader->alias('CmsBlockInput', 'CoasterCms\Helpers\Core\View\CmsBlockInput');
        $loader->alias('FormMessage', 'CoasterCms\Libraries\Builder\FormMessage');
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
        return [
            'CoasterCms\Providers\CoasterConfigProvider',
            'CoasterCms\Providers\CoasterEventsProvider',
            'Bkwld\Croppa\ServiceProvider',
            'Collective\Html\HtmlServiceProvider'
        ];
    }

}
