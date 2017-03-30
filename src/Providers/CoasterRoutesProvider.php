<?php namespace CoasterCms\Providers;

use CoasterCms\Helpers\Cms\Install;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\ServiceProvider;

class CoasterRoutesProvider extends ServiceProvider
{

    /**
     * Bootstrap the application events.
     *
     * @return void
     */
    public function boot()
    {
        $this->mapApiRoutes();
        $this->mapWebRoutes();
    }

    /**
     * Define the "web" routes for the application.
     *
     * These routes all receive session state, CSRF protection, etc.
     *
     * @return void
     */
    protected function mapWebRoutes()
    {
        $namespace = 'CoasterCms\Http\Controllers';
        $routeName = 'coaster.';
        $routesDir = realpath(__DIR__ . '/../routes/web');
        $adminRouteName = $routeName . 'admin.';
        $adminUrl = config('coaster::admin.url') . '/';

        if (!Install::isComplete()) {
            Route::middleware(['web'])
                ->as($routeName . 'install.')
                ->namespace($namespace)
                ->group($routesDir . '/install.php');
        }

        Route::middleware(['web', 'coaster.admin'])
            ->prefix($adminUrl)
            ->as($adminRouteName)
            ->namespace($namespace . '\AdminControllers')
            ->group($routesDir . '/admin-auth.php');

        Route::middleware(['web', 'coaster.guest'])
            ->prefix($adminUrl)
            ->as($adminRouteName)
            ->namespace($namespace . '\AdminControllers')
            ->group($routesDir . '/admin-guest.php');

        Route::middleware('web')
            ->prefix($adminUrl)
            ->as(rtrim($adminRouteName, '.'))
            ->namespace($namespace)
            ->group($routesDir . '/admin.php');

        Route::middleware(['web', 'auth'])
            ->namespace($namespace)
            ->group($routesDir . '/cms-auth.php');

        Route::middleware(['web', 'coaster.pagebuilder.init'])
            ->namespace($namespace)
            ->group($routesDir . '/cms.php');
    }

    /**
     * Define the "api" routes for the application.
     *
     * These routes are typically stateless.
     *
     * @return void
     */
    protected function mapApiRoutes()
    {
    }

}
