<?php namespace CoasterCms\Listeners;

use CoasterCms\Events\Cms\InitializePageBuilder;
use CoasterCms\Events\Cms\LoadedTemplate;
use CoasterCms\Events\Cms\LoadPageTemplate;
use CoasterCms\Exceptions\PageBuilderException;

class Test2
{
    /**
     * Create the event listener.
     *
     * @return void
     */
    public function __construct()
    {
        //
    }

    /**
     * Handle the event.
     *
     * @param LoadedTemplate $event
     * @return void
     */
    public function handle(LoadedTemplate $event)
    {
        dd($event);
        $event->renderedTemplate .= '1234567890';
    }
}
