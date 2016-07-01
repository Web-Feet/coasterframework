<?php namespace CoasterCms\Providers;

use Illuminate\Contracts\Events\Dispatcher;
use Illuminate\Foundation\Support\Providers\EventServiceProvider;

class CoasterEventsProvider extends EventServiceProvider
{
    /**
     * The event listener mappings.
     *
     * @var array
     */
    protected $listen = [
    ];

    /**
     * @param Dispatcher  $events
     * @return void
     */
    public function boot(Dispatcher $events)
    {
        parent::boot($events);
    }
}
