<?php namespace CoasterCms\Providers;

use CoasterCms\Contracts\PageBuilder as PageBuilderContract;
use CoasterCms\Facades\PageBuilder as PageBuilderFacade;
use CoasterCms\Helpers\Cms\Page\PageLoader;
use CoasterCms\Libraries\Builder\PageBuilder\DefaultInstance;
use CoasterCms\Libraries\Builder\PageBuilder;
use Illuminate\Foundation\AliasLoader;
use Illuminate\Support\ServiceProvider;
use Illuminate\View\Factory;

class CoasterPageBuilderProvider extends ServiceProvider
{

    /**
     * Bootstrap the application events.
     *
     * @return void
     */
    public function boot()
    {
        $this->app->singleton('pageBuilder', function ($app) {
            $pb = new PageBuilder(DefaultInstance::class, [new PageLoader]);
            /** @var Factory $view */
            $view = $app['view'];
            $view->share('pb', $pb);
            return $pb;
        });
    }

    /**
     * Register the service provider.
     *
     * @return void
     */
    public function register()
    {
        // register class aliases
        $loader = AliasLoader::getInstance();
        $loader->alias('PageBuilder', PageBuilderFacade::class);

        // register container aliases
        $this->app->alias('pageBuilder', PageBuilder::class);
        $this->app->alias('pageBuilder', PageBuilderContract::class);
    }

}
