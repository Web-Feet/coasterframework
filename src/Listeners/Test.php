<?php namespace CoasterCms\Listeners;

use CoasterCms\Events\Cms\InitializePageBuilder;
use CoasterCms\Events\Cms\LoadPageTemplate;
use CoasterCms\Exceptions\PageBuilderException;

class Test
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
     * @param InitializePageBuilder $event
     * @return void
     */
    public function handle(LoadPageTemplate $event)
    {
        $event->template = 'themes.'.\PageBuilder::getData('theme').'.templates.home';
    }
}
