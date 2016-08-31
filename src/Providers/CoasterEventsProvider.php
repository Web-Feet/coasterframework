<?php namespace CoasterCms\Providers;

use Illuminate\Foundation\Support\Providers\EventServiceProvider;

class CoasterEventsProvider extends EventServiceProvider
{
    /**
     * The event listener mappings.
     *
     * @var array
     */
    protected $listen = [
        'CoasterCms\Events\Admin\AuthRoute' => [
            'CoasterCms\Listeners\Admin\AuthRouteCheck',
        ],
    ];
    
}
