<?php namespace CoasterCms\Events\Cms;

class LoadMiddleware
{
    /**
     * @var array
     */
    public $globalMiddleware;

    /**
     * @var array
     */
    public $routerMiddleware;

    /**
     * LoadMiddleware constructor.
     * @param array $globalMiddleware
     * @param array $routerMiddleware
     */
    public function __construct(&$globalMiddleware, &$routerMiddleware)
    {
        $this->globalMiddleware = &$globalMiddleware;
        $this->frontendViews = &$routerMiddleware;
    }
    
    /**
     * Get the channels the event should be broadcast on.
     *
     * @return array
     */
    public function broadcastOn()
    {
        return [];
    }

}
