<?php namespace CoasterCms\Providers;

use Illuminate\Support\ServiceProvider;
use CoasterCms\Console\Commands as Commands;

class CoasterConsoleProvider extends ServiceProvider
{

    /**
     * Indicates if loading of the provider is deferred.
     *
     * @var bool
     */
    protected $defer = false;

    /**
     * @var array
     */
    protected $_commands = [
        Commands\Migrate::class,
        Commands\UpdateAssets::class
    ];

    /**
     * Register the service provider.
     *
     * @return void
     */
    public function register()
    {
        $this->commands($this->_commands);
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
